<?php

declare(strict_types=1);

namespace Typhoon\Nsq\Internal\Lookup;

use Amp\Cancellation;
use Amp\Http\Client\ApplicationInterceptor;
use Amp\Http\Client\DelegateHttpClient;
use Amp\Http\Client\Request;
use Amp\Http\Client\Response;
use function Amp\delay;

/**
 * @internal
 * @psalm-internal Typhoon\Nsq
 */
final class RetryWithBackoff implements ApplicationInterceptor
{
    /**
     * @param non-negative-int $attempts
     */
    public function __construct(
        private readonly int $attempts,
        private readonly float $sleep = 0.2,
        private readonly float $maxSleep = 1,
        private readonly float $jitter = 0.1,
    ) {}

    public function request(Request $request, Cancellation $cancellation, DelegateHttpClient $httpClient): Response
    {
        $attempt = 0;

        do {
            try {
                return $httpClient->request($request, $cancellation);
            } catch (\Throwable $e) {
                if ($this->attempts === 0) {
                    throw $e;
                }

                delay(min(($attempt * $this->jitter) + $this->sleep, $this->maxSleep), reference: false);
            }
        } while (++$attempt <= $this->attempts);

        throw $e;
    }
}
