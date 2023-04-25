#!/usr/bin/make

SHELL = /bin/bash

USER_ID := $(shell id -u)
GROUP_ID := $(shell id -g)

export USER_ID
export GROUP_ID

prepare_app:
	docker exec --user jobdeal jobdeal .docker/test/setup.sh

build:
	USER_ID=${USER_ID} GROUP_ID=${GROUP_ID} docker-compose -f docker-compose.test.yml  up --build --force-recreate -d

# Recreates the database and pushes all seeds
fresh_db:
	docker exec --user jobdeal jobdeal php artisan --env=testing migrate:fresh --seed

build_test: build prepare_app

test_all: build_test test

test:
	docker exec --user jobdeal jobdeal vendor/bin/phpunit

test_specific:
	docker exec --user jobdeal jobdeal vendor/bin/phpunit $(TEST)
