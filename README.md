# Installation Guide

## Prerequisites
- Docker is required
- Ports 8000, 8001, 3306, 3307, 8083, 8080 must be free

## Steps
- Go to branch `main`
- Run `docker compose up -d` to start the services
- Run `docker exec -it laravel-app bash` to enter the container
- Run `php artisan migrate` to migrate the database
- Run `php artisan migrate --env=testing` to migrate replica database
- Run `php artisan db:seed` to seed the database
- Application can be viewed in `http://localhost:8000/docs/api`
- Go to branch `pre-release`
- Run `php artisan migrate` to migrate the database
- Run `php artisan migrate --env=testing` to migrate replica database
- In another terminal, run `docker exec -it laravel-sync-data bash` to enter the container
- Run `php artisan app:sync-role-command` 
- In `laravel-app` terminal, run `php artisan app:migrate-role`
- Go to branch `main-new`

## Important
- to stop the container run `docker compose down -v` command
