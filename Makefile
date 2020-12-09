
DOCKER_REGISTRY=local
CONTAINER_NAME=testing

.PHONY: help
help:
	@echo "IDC Migration PHP module supported make targets are:"
	@echo "  build-container: builds the Docker image used for tests"
	@echo "  composer-install: installs the dependencies in composer.lock"
	@echo "  check-platform-reqs: insures the PHP version and installed extensions are runtime compatible"
	@echo "  test: executes unit tests"
	@echo "  clean: removes build state from '.make/' and removes the Docker image used for tests"

.PHONY: build-container
build-container: .make/build-container

.make/build-container:
	docker build -t ${DOCKER_REGISTRY}/${CONTAINER_NAME} .
	@touch .make/build-container

.PHONY: composer-install
composer-install: .make/build-container .make/composer-install

.make/composer-install:
	docker run --rm -v $$PWD:/app ${DOCKER_REGISTRY}/${CONTAINER_NAME} install
	@touch .make/composer-install

.PHONY: check-platform-reqs
check-platform-reqs: .make/build-container .make/composer-install .make/check-platform-reqs

.make/check-platform-reqs:
	docker run --rm -v $$PWD:/app ${DOCKER_REGISTRY}/${CONTAINER_NAME} check-platform-reqs
	@touch .make/check-platform-reqs

.PHONY: test
test: .make/build-container .make/composer-install .make/check-platform-reqs
	docker run --rm -v $$PWD:/app ${DOCKER_REGISTRY}/${CONTAINER_NAME} vendor/bin/phpunit tests

.PHONY: clean
clean:
	-docker rmi ${DOCKER_REGISTRY}/${CONTAINER_NAME}
	-rm -f .make/*