# Installation Guide

## Prerequisites
- Docker is required
- Ports 8000, 8001, 3306, 3307, 8083, 8080 must be free

## Context
Consider we have an app that creates user. Initially we users could have only one role so `role` column was present in `users` table. Later we decided that users can have multiple roles so we created a new table `roles` and moved the role column to the new table. Now we have two tables `users` and `roles`.

## Problem
We already have millions of users, and script to migrate of `role` data from `user` table to `roles` table takes fair bit of time. If some user logs in during this process and changes his/her role, that data might be lost.

## Solution
`Debezium` listens to the database bin log, and produces every mutation change event to Kafka topic. `sync-data` subscribes to these changes through `php artisan app:sync-role-command` and updates the `roles` table accordingly so that migration data during the migration process is not lost.

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
