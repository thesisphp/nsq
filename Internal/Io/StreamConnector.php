<?php

declare(strict_types=1);

namespace Typhoon\Nsq\Internal\Io;

use Amp\Cancellation;
use Amp\Socket;
use Typhoon\Nsq\Config;
use Typhoon\Nsq\Exception\AuthenticationRequired;
use Typhoon\Nsq\Exception\UnexpectedFrame;
use Typhoon\Nsq\Internal\Io\Stream\BufferedStream;
use Typhoon\Nsq\Internal\Io\Stream\DeflateStream;
use Typhoon\Nsq\Internal\Io\Stream\SocketStream;
use Typhoon\Nsq\Internal\Protocol;

/**
 * @internal
 * @psalm-internal Typhoon\Nsq
 */
final class StreamConnector
{
    public function __construct(
        private readonly Config $config,
    ) {}

    /**
     * @return array{Stream, Protocol\Writer, Protocol\Reader}
     */
    public function connect(?Cancellation $cancellation = null): array
    {
        $context = (new Socket\ConnectContext())
            ->withConnectTimeout($this->config->connectionTimeout);

        if ($this->config->tcpNodelay) {
            $context = $context->withTcpNoDelay();
        }

        $socket = Socket\connect($this->config->host, $context);

        $stream = new SocketStream($socket);
        [$writer, $reader] = [new Protocol\Writer($stream), new Protocol\Reader($stream)];

        $writer->write(Protocol\Command::magic());
        $writer->write(Protocol\Command::identify((string) new Protocol\Negotiate(
            clientId: $this->config->clientId,
            hostname: $this->config->hostname,
            tlsv1: $this->config->tls,
            deflate: $this->config->deflate,
            deflateLevel: $this->config->deflateLevel,
            snappy: $this->config->snappy,
            userAgent: $this->config->userAgent,
        )));

        $frame = $reader->readOk($cancellation);
        if (!$frame instanceof Protocol\Response) {
            throw UnexpectedFrame::forFrame($frame);
        }

        $serverConfig = Protocol\ServerConfig::fromJSON($frame->body);

        if ($serverConfig->tlsv1) {
            $socket->setupTls($cancellation);

            if (!($frame = $reader->readOk($cancellation)) instanceof Protocol\Ok) {
                throw UnexpectedFrame::forFrame($frame);
            }
        }

        if ($serverConfig->deflate) {
            $stream = new BufferedStream(
                new DeflateStream($stream, $serverConfig->deflateLevel),
            );

            [$writer, $reader] = [$writer->upgrade($stream), $reader->upgrade($stream)];

            if (!($frame = $reader->readOk($cancellation)) instanceof Protocol\Ok) {
                throw UnexpectedFrame::forFrame($frame);
            }
        }

        if ($serverConfig->authRequired) {
            if ($this->config->authenticationSecret === null) {
                throw new AuthenticationRequired();
            }

            $writer->write(Protocol\Command::auth($this->config->authenticationSecret));

            if (!($frame = $reader->readOk()) instanceof Protocol\Response) {
                throw UnexpectedFrame::forFrame($frame);
            }
        }

        return [$stream, $writer, $reader];
    }
}
