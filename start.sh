#! /bin/bash

cp .env.example .env
touch database/database.sqlite
composer install --ignore-platform-reqs
docker compose up --build --remove-orphans -d
docker compose run app composer install
docker compose run app cp -n .env.example .env
docker compose run app php artisan migrate:fresh --seed
docker compose exec -d app php artisan queue:work --sleep=3 --tries=3

