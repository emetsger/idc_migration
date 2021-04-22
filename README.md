# IDC Migration Module
A [composer][1]-based project providing custom migration plugins for Drupal.

Provided plugins include:
* `deepen`: transforms arrays suitable for input to the `sub_process` migrate module
* `pairtree`: calculates a checksum for a bytestream, and returns it in a structured form useful for content-based addressing
* `parse_entity_lookup`: parameterizes instances of the `entity_lookup` plugin by parsing values from the source

The `Makefile` provides common targets used to setup the development environment and execute tests.  Docker provides `composer`, PHPUnit, and any other novel dependencies.

## Usage
Runtime dependencies are maintained in `composer.json`, and should track the versions of dependencies used by the [IDC `drupal` container][2].  For example, if the version of Drupal or PHP is bumped in the `drupal` container, it should be updated in this project as well.

### As a dependency
To depend on this module, add the following to `composer.json`:
```json
"repositories": [
    {
        "type": "vcs",
        "url": "https://github.com/jhu-idc/idc_migration"
    }
]
```
And then execute `composer require jhu_idc/idc_migration ^1.0`

### For development
To develop with this module, check out this repository and run `make composer-install`.  Dependencies will be downloaded and installed underneath the `vendor/` subdirectory per the `composer.lock` file.  Once dependencies are installed, IDEs should be able to provide support for development tasks.  

`make help` will provide a list of supported targets and their description.

## Running tests
Tests are executed by bind mounting this code inside a Docker container and executing `phpunit` within the container.  This absolves the host of any obligation to install PHP, composer, or PHPUnit.

To run tests: `make test`

If you receive an inexplicable "class not found" error when running a test, you may need to regenerate the autoload information that is produced by composer.  To do that, run a `make clean test`; that will create a new image and run `composer install` which will insure the autoload information is up-to-date.

## Requires
- Docker
- PHP 7.3
- Drupal 8.9+


[1]: https://getcomposer.org/
[2]: https://github.com/jhu-idc/idc-isle-dc/blob/development/codebase/composer.json