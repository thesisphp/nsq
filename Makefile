up:
	docker compose up -d
down:
	docker compose down --remove-orphans
php:
	docker compose exec php bash
