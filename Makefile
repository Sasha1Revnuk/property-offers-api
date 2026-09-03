# Define variables
APP_CONTAINER=php

# One-shot Docker bootstrap: env, build, up, migrate --seed, swagger
setup:
	@test -f .env || cp .env.example .env
	sudo mkdir -p "storage/logs/nginx"
	sudo chmod -R 777 storage/logs/nginx
	docker compose build
	docker compose up -d
	@echo "Waiting for MySQL..."
	@i=1; \
	while [ $$i -le 30 ]; do \
		if docker compose exec -T $(APP_CONTAINER) php artisan db:show --no-interaction >/dev/null 2>&1; then \
			echo "MySQL is ready."; \
			break; \
		fi; \
		if [ $$i -eq 30 ]; then \
			echo "MySQL did not become ready in time."; \
			exit 1; \
		fi; \
		sleep 2; \
		i=$$((i + 1)); \
	done
	docker compose exec -T -u fpm_user $(APP_CONTAINER) composer install
	@grep -qE '^APP_KEY=.+' .env || docker compose exec -T -u fpm_user $(APP_CONTAINER) php artisan key:generate --no-interaction
	docker compose exec -T -u fpm_user $(APP_CONTAINER) bash -c "sudo chmod -R 777 storage/framework storage/logs || chmod -R 777 storage/framework storage/logs"
	docker compose exec -T -u fpm_user $(APP_CONTAINER) php artisan storage:link --no-interaction || true
	docker compose exec -T -u fpm_user $(APP_CONTAINER) php artisan migrate --seed --no-interaction
	docker compose exec -T -u fpm_user $(APP_CONTAINER) php artisan l5-swagger:generate --no-interaction
	@echo "Setup complete. API: http://127.0.0.1:5000  Docs: http://127.0.0.1:5000/api/documentation"

build:
	docker compose build

up:
	docker compose up -d

down:
	docker compose down

php:
	docker compose exec -u fpm_user -t -i $(APP_CONTAINER) bash

stan:
	docker compose exec -u fpm_user $(APP_CONTAINER) bash -c "composer stan"

swagger:
	docker compose exec -u fpm_user $(APP_CONTAINER) bash -c "php artisan l5-swagger:generate --no-interaction"
