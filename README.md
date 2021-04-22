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

## Versioning
Version numbers follow the `<major>.<minor>.<patch>` formula.  Each field is an integer.

Releases that are drop-in replacements should increment the `<patch>` field.  This includes adding a new plugin or functionality to the module.

Releases that require changes to the configuration or use of plugins in the module by other projects should increment the `<minor>` field, and reset `<patch>` to zero.

Releases that represent a major architectural shift or rewrite should increment `<major>` and reset `<minor>` and `<patch>` to zero.

### Versioning FAQ
Q. I've added a new plugin to the current version, `1.0.2`.  What should the next version be?
A. `1.0.3`, since adding a new plugin doesn't change existing behavior.  The new release may be "dropped in" without any changes users of the plugin.

Q. I've changed the default values of the `parse_entity_lookup` in version `1.1.2`.  What should the next version be?
A. Users of version `1.1.2` depend on the current defaults, and their configurations may need to be updated if the default values change.  Unless you can be assured the new defaults won't adversely affect current users (for example, you've _added_ a new default value to support an enhancement to the plugin), the new version should be `1.2.0`.

Q. I've upgraded the requirements of version `2.1.0` to require Drupal 9 in the next release.  What should the new version be?
A. The new version is not a drop-in replacement for users who remain on the Drupal 8 platform.  If `2.1.0` is going to be your last release supporting Drupal 8, you could consider releasing `2.2.0`.  However, that may paint you into a corner if you need to maintain Drupal 8 and 9 compatibility (e.g. a security patch against your most recent Drupal 8 release, `2.1.0`).  The safest thing to do is release `3.0.0`, reflecting the requirement that clients upgrade to Drupal 9, and leaving room for releasing a Drupal 8-compatible `2.2.0` in the future.

Q. A security issue requires me to make a _minor_ (i.e. not drop in) release of `1.1.3`, because the fix is not a drop-in.  According to the policy that means the release version would be `1.2.0`.  However, I've already released `1.2.0`; the `1.2.x` branch supports Drupal 9.  What do I do?
A. Options are: release `1.1.4` (increment `<patch>` instead of `<minor>`) and alert users that it is not "drop in".  Or, modify the version structure from `<major>.<minor>.<patch>` to `<major>.<minor>.<patch>-<tag>` where `<tag>` could be `sec` for "security" (other values or uses of `<tag>` are "beta", "rc", etc.) and release `1.1.4-sec`.  The tag (and release notes) should indicate to users that a breaking change is included.  There are other variations on this theme.  If your toolchain supporting release processes allows tags in the form `1.1.4-sec`, that's probably the way to go.  The underlying issue, however, is not resolved.  You've broken your promise that `<patch>` changes are drop-in replacements: anyone on `1.1.3` or older cannot upgrade to later versions without accommodating a breaking change, even if it is in the name of security.  Alternately, start a new release tree based on your next available `<minor>` number, say `1.3.0`.  Release the security fix for `1.1.3` as version `1.3.0`.  The version jump is "ugly", but semantically correct.

## Release Process
1. Ensure the main branch (be it `main`, `development`, `master`, etc) is ready for release (all commits are in, CI is passing, etc.).
1. Determine the new version number.
1. Create a tag for the release.  Since this is a composer-based project, prefix the letter 'v' to the version number as your tag.  If your release version is `1.2.0` then your tag would be `v1.2.0`
1. Push the tag to GitHub
1. Write release notes  



[1]: https://getcomposer.org/
[2]: https://github.com/jhu-idc/idc-isle-dc/blob/development/codebase/composer.json