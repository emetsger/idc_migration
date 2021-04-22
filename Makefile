DOCKER_REGISTRY=ghcr.io/jhu-sheridan-libraries/idc-isle-dc
CONTAINER_NAME=idc_migration-testing
GIT_TAG:=$(shell git describe --tags --always)

.PHONY: help
help:
	@echo "IDC Migration PHP module supported make targets are:"
	@echo "  build-image: builds the Docker image used for tests"
	@echo "  push-image: pushes the Docker image to GHCR"
	@echo "  composer-install: installs the dependencies in composer.lock"
	@echo "  composer-update: updates the dependencies in composer.lock per composer.json version requirements"
	@echo "  check-platform-reqs: insures the PHP version and installed extensions are runtime compatible"
	@echo "  test: executes unit tests"
	@echo "  clean: removes build state from '.make/', the Docker image used for tests, the 'vendor' directory, and composer.lock"

.PHONY: build-image
build-image: .make/build-image

.make/build-image:
	docker build -t ${DOCKER_REGISTRY}/${CONTAINER_NAME}:${GIT_TAG} .
	@touch .make/build-image

.PHONY: push-image
push-image: .make/push-image

.make/push-image: .make/build-image
	docker push ${DOCKER_REGISTRY}/${CONTAINER_NAME}:${GIT_TAG}
	@touch .make/push-image

.PHONY: composer-update
composer-update: .make/build-image .make/composer-update

.make/composer-update:
	docker run --rm -v $$PWD:/app ${DOCKER_REGISTRY}/${CONTAINER_NAME}:${GIT_TAG} update
	@touch .make/composer-update

.PHONY: composer-install
composer-update: .make/build-image .make/composer-install

.make/composer-install:
	docker run --rm -v $$PWD:/app ${DOCKER_REGISTRY}/${CONTAINER_NAME}:${GIT_TAG} install
	@touch .make/composer-install

.PHONY: check-platform-reqs
check-platform-reqs: .make/build-image .make/composer-update .make/check-platform-reqs

.make/check-platform-reqs:
	docker run --rm -v $$PWD:/app ${DOCKER_REGISTRY}/${CONTAINER_NAME}:${GIT_TAG} check-platform-reqs
	@touch .make/check-platform-reqs

.PHONY: test
test: .make/build-image .make/composer-install .make/check-platform-reqs
	docker run --rm -v $$PWD:/app ${DOCKER_REGISTRY}/${CONTAINER_NAME}:${GIT_TAG} vendor/bin/phpunit tests

.PHONY: clean
clean:
	-docker rmi ${DOCKER_REGISTRY}/${CONTAINER_NAME}:${GIT_TAG}
	-rm -f .make/*
	-rm -rf vendor/
	-rm -f composer.lock