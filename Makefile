# Define variables
APP_CONTAINER=php

init:
	sudo mkdir -p "storage/logs/nginx"
	sudo chmod -R 777 storage/logs/nginx
	docker compose build
	docker compose up -d
	docker compose exec -u fpm_user -t -i $(APP_CONTAINER) bash -c "composer install"
	docker compose exec -u fpm_user -t -i $(APP_CONTAINER) bash -c "php artisan key:generate"
	docker compose exec -u fpm_user -t -i $(APP_CONTAINER) bash -c "sudo chmod -R 777 storage/framework"
	docker compose exec -u fpm_user -t -i $(APP_CONTAINER) bash -c "sudo chmod -R 777 storage/logs"
	docker compose exec -u fpm_user -t -i $(APP_CONTAINER) bash -c "php artisan storage:link"

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


