include .env

DOCKER_REGISTRY:=ghcr.io/jhu-sheridan-libraries/idc-isle-dc
IMAGE_NAME:=idc_migration-testing
GIT_TAG:=$(shell git describe --tags --always)

.PHONY: help
help:
	@echo "IDC Migration PHP module supported make targets are:"
	@echo "  build-image: builds the Docker image used for tests, and updates the TEST_IMAGE_TAG in .env"
	@echo "  push-image: pushes the Docker image created by the 'build-image' target to GHCR"
	@echo "  pull-image: pulls the Docker image tagged in .env"
	@echo "  composer-install: installs the dependencies in composer.lock"
	@echo "  composer-update: updates the dependencies in composer.lock per composer.json version requirements"
	@echo "  check-platform-reqs: insures the PHP version and installed extensions are runtime compatible"
	@echo "  test: executes unit tests in a docker container"
	@echo "  clean: removes build state from '.make/', the 'vendor' directory, composer.lock, and reverts .env"
	@echo "  echo-image-tag: displays the current value for TEST_IMAGE_TAG from .env"
	@echo "  echo-git-tag: displays the calculated value for GIT_TAG, based on 'git describe'"
	@echo "  update-lock-hash: updates the hash of composer.lock"

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
	source .env && docker push ${DOCKER_REGISTRY}/${IMAGE_NAME}:$${TEST_IMAGE_TAG}
	@touch .make/push-image

.PHONY: pull-image
pull-image: .make/pull-image

.make/pull-image:
	source .env && docker pull ${DOCKER_REGISTRY}/${IMAGE_NAME}:$${TEST_IMAGE_TAG}
	@touch .make/pull-image

.PHONY: composer-update
composer-update: .make/pull-image .make/composer-update

.make/composer-update:
	source .env && docker run --rm -v $$PWD:/app ${DOCKER_REGISTRY}/${IMAGE_NAME}:$${TEST_IMAGE_TAG} update
	@touch .make/composer-update

.PHONY: composer-install
composer-install: .make/pull-image .make/composer-install

.make/composer-install:
	source .env && docker run --rm -v $$PWD:/app ${DOCKER_REGISTRY}/${IMAGE_NAME}:$${TEST_IMAGE_TAG} install
	@touch .make/composer-install

.PHONY: check-platform-reqs
check-platform-reqs: .make/pull-image .make/composer-update .make/check-platform-reqs

.make/check-platform-reqs:
	source .env && docker run --rm -v $$PWD:/app ${DOCKER_REGISTRY}/${IMAGE_NAME}:$${TEST_IMAGE_TAG} check-platform-reqs
	@touch .make/check-platform-reqs

.PHONY: test
test: .make/pull-image .make/composer-install .make/check-platform-reqs
	source .env && docker run --rm -v $$PWD:/app ${DOCKER_REGISTRY}/${IMAGE_NAME}:$${TEST_IMAGE_TAG} vendor/bin/phpunit tests

.PHONY: clean
clean:
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
	@source .env && echo $${TEST_IMAGE_TAG}

.PHONY: update-lock-hash
update-lock-hash:
	-source .env && @docker run --rm -v $$PWD:/app ${DOCKER_REGISTRY}/${IMAGE_NAME}:$${TEST_IMAGE_TAG} update --lock
