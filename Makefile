include .env

DOCKER_REGISTRY:=ghcr.io/jhu-sheridan-libraries/idc-isle-dc
IMAGE_NAME:=idc_migration-testing
GIT_TAG:=$(shell git describe --tags --always)

.PHONY: help
help:
	@echo "IDC Migration PHP module supported make targets are:"
	@echo "  build-image: builds the Docker image used for tests, and updates the TEST_IMAGE_TAG in .env"
	@echo "  push-image: pushes the Docker image to GHCR"
	@echo "  composer-install: installs the dependencies in composer.lock"
	@echo "  composer-update: updates the dependencies in composer.lock per composer.json version requirements"
	@echo "  check-platform-reqs: insures the PHP version and installed extensions are runtime compatible"
	@echo "  test: executes unit tests"
	@echo "  clean: removes build state from '.make/', the Docker image used for tests, the 'vendor' directory, composer.lock, and reverts .env"
	@echo "  echo-image-tag: displays the current value for TEST_IMAGE_TAG from .env"
	@echo "  echo-git-tag: displays the calculated value for GIT_TAG, based on 'git describe'"

.PHONY: build-image
build-image: .make/build-image

.make/build-image:
	docker build -t ${DOCKER_REGISTRY}/${IMAGE_NAME}:${GIT_TAG} .
	@touch .make/build-image
	@sed -e 's/^TEST_IMAGE_TAG=.*/TEST_IMAGE_TAG=${GIT_TAG}/' < .env > /tmp/idc_migration.env
	@mv /tmp/idc_migration.env ./.env
	@echo "Built and tagged ${DOCKER_REGISTRY}/${IMAGE_NAME}:${GIT_TAG}"

.PHONY: push-image
push-image: .make/push-image

.make/push-image: .make/build-image
	docker push ${DOCKER_REGISTRY}/${IMAGE_NAME}:${TEST_IMAGE_TAG}
	@touch .make/push-image

.PHONY: composer-update
composer-update: .make/build-image .make/composer-update

.make/composer-update:
	docker run --rm -v $$PWD:/app ${DOCKER_REGISTRY}/${IMAGE_NAME}:${TEST_IMAGE_TAG} update
	@touch .make/composer-update

.PHONY: composer-install
composer-update: .make/build-image .make/composer-install

.make/composer-install:
	docker run --rm -v $$PWD:/app ${DOCKER_REGISTRY}/${IMAGE_NAME}:${TEST_IMAGE_TAG} install
	@touch .make/composer-install

.PHONY: check-platform-reqs
check-platform-reqs: .make/build-image .make/composer-update .make/check-platform-reqs

.make/check-platform-reqs:
	docker run --rm -v $$PWD:/app ${DOCKER_REGISTRY}/${IMAGE_NAME}:${TEST_IMAGE_TAG} check-platform-reqs
	@touch .make/check-platform-reqs

.PHONY: test
test: .make/build-image .make/composer-install .make/check-platform-reqs
	docker run --rm -v $$PWD:/app ${DOCKER_REGISTRY}/${IMAGE_NAME}:${TEST_IMAGE_TAG} vendor/bin/phpunit tests

.PHONY: clean
clean:
	@echo "Removing image ${DOCKER_REGISTRY}/${IMAGE_NAME}:${TEST_IMAGE_TAG}"
	-@docker rmi ${DOCKER_REGISTRY}/${IMAGE_NAME}:${TEST_IMAGE_TAG}
	@echo "Removing make state from ./.make"
	-@rm -f .make/*
	@echo "Removing vendored source"
	-@rm -rf vendor/
	@echo "Removing composer.lock"
	-@rm -f composer.lock
	@echo "Reverting .env"
	-@git checkout -- .env

.PHONY: echo-git-tag
echo-git-tag:
	@echo ${GIT_TAG}

.PHONY: echo-image-tag
echo-image-tag:
	@echo ${TEST_IMAGE_TAG}