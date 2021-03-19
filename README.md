# IDC Migration Module
A [composer][1]-based project providing custom migration plugins for Drupal. 

The supplied Dockerfile may be used to run tests and execute `composer` commands.  The `Makefile` provides common targets used to setup the development environment and execute tests.

Runtime dependencies are maintained in `composer.json`, and should track the versions of dependencies used by the [IDC `drupal` container][2].  For example, if the version of Drupal or PHP is bumped in the `drupal` container, it should be updated in this project as well.  Since migrations are a part of Drupal core, there are no other dependencies on additional migration modules like Migrate Plus.

## Usage
To depend on this module, add the following to `composer.json`:
```json
"repositories": [
    {
        "type": "vcs",
        "url": "https://github.com/jhu-idc/idc_migration"
    }
]
```

And then execute ``composer require jhu_idc/idc_migration ^1.0``

To develop against this module, check out this repository and run `make composer-install`.  Dependencies will be downloaded and installed underneath the `vendor/` subdirectory per the `composer.lock` file.  Once dependencies are installed, IDEs should be able to provide support for development tasks.  

`make help` will provide a list of supported targets and their description.

## Running tests
Tests are executed by bind mounting this code inside a Docker container and executing `phpunit` within the container.  This absolves the host of any obligation to install PHP, composer, or PHPUnit.

To run tests: ``make test``

## Requires
- Docker
- PHP 7.3
- Drupal 8.8+


[1]: https://getcomposer.org/
[2]: https://github.com/jhu-idc/idc-isle-dc/blob/development/codebase/composer.json