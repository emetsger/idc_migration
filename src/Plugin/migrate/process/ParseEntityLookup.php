<?php


namespace Drupal\idc_migration\Plugin\migrate\process;


use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\migrate\MigrateExecutableInterface;
use Drupal\migrate\Plugin\MigrationInterface;
use Drupal\migrate\ProcessPluginBase;
use Drupal\migrate\Row;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Configures and invokes the `entity_lookup` plugin using arguments that are parsed from a source field.  This plugin
 * may be used wherever an `entity_lookup` plugin may be used, provided that the values being processed by this plugin
 * adhere to the format documented below.<br><br>
 *
 * This plugin will parse a string with the following form, assuming the `delimiter` configuration parameter is equal to
 * the colon character:<br><br>
 *   `<entity_type>:<bundle_key>:<bundle_name>:<value_key>:<value>`<br><br>
 * For example, `taxonomy_term:vid:subject:name:History`.  The form of the string provides an unambiguous reference that
 * can be used to configure and invoke the `entity_lookup` plugin.<br><br>
 *
 * When a default value is provided by the `defaults` configuration key, it may be elided from the string being
 * processed.  For example, given the defaults:
 *
 * ```
 *     defaults:
 *      entity_type: taxonomy_term
 *      bundle_key: vid
 *      value_key: name
 * ```
 *
 * The above example could be written as: `::subject::History`.<br><br>
 *
 * If a value is elided from the string and there is no default provided by the plugin configuration, then the value
 * will not be passed to the `entity_lookup` plugin.  This may be the case when an entity does not have a bundle (e.g.
 * the `User` entity type does not have bundles).<br><br>
 *
 * By default the delimiter used by this plugin is the colon character.  A different delimiter may be configured using
 * the `delimiter` configuration key documented below.  Delimiters may be longer than a single character, but they
 * _must_ be latin-1 characters.  If a delimiter appears as a value in the string being processed (i.e. it is not meant
 * to be parsed as a delimiter), then it must be "percent-encoded".  For example, if the value being processed by this
 * plugin is: `node:nid:islandora_object:title:The Story of My Life: An Autobiography`, the colon between "Life" and
 * "An Autobiography" must be percent encoded as: `node:nid:islandora_object:title:The Story of My Life%3A An Autobiography`.
 * If encoding the colon is not desirable, a different delimiter may be configured.<br><br>
 *
 * Available configuration keys:
 *
 * * `defaults`: an associative array of default values used to parameterize the `entity_lookup` plugin when elided from the source string.  Possible values include any keys supported by the `entity_lookup` plugin.
 * * `delimiter`: the character string used to delimit the fields of the source string; it must be from the basic latin character set.
 *
 * Example:
 * ```
 * process:
 *   plugin: parse_entity_lookup
 *     source: subject
 *     delimiter: ':'
 *     defaults:
 *      entity_type: taxonomy_term
 *      bundle_key: vid
 *      bundle_name: subject
 *      value_key: name
 * ```
 *
 * @see \Drupal\migrate\Plugin\MigrateProcessInterface
 * @MigrateProcessPlugin(
 *   id = "parse_entity_lookup",
 *   handle_multiples = TRUE
 * )
 */
class ParseEntityLookup extends ProcessPluginBase implements ContainerFactoryPluginInterface {

    /**
     * The default delimiter used to separate the fields in the $value being processed by the transform(...) method.<br><br>
     *
     * Note: the delimiter must be from the basic latin character set.
     */
    private const default_delimiter = ':';

    /**
     * Maps RFC 3986 reserved characters to their percent-encoded equivalent
     */
    private const reserved_char_map = [
        '-' => '%2d',
        '_' => '%5f',
        '.' => '%2e',
        '~' => '%7e',
    ];

    /**
     * DI key for the MigratePluginManager instance
     */
    static $migrate_plugin_manager = 'plugin.manager.migrate.process';

    /**
     * The migration.
     *
     * @var \Drupal\migrate\Plugin\MigrationInterface
     */
    protected $migration;

    /**
     * The migration plugin manager, used to retrieve the 'entity_lookup' plugin definition.
     *
     * @var \Drupal\migrate\Plugin\MigratePluginManager
     */
    protected $migration_plugin_manager;

    public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition, MigrationInterface $migration = NULL) {
        $instance = new static(
            $configuration,
            $plugin_id,
            $plugin_definition
        );

        $instance->migration = $migration;

        $instance->migration_plugin_manager = $container->get(self::$migrate_plugin_manager);

        return $instance;
    }

    public function transform($value, MigrateExecutableInterface $migrate_executable, Row $row, $destination_property) {
        $delimiter = $this->configuration['delimiter'] ?: self::default_delimiter;

        // split the fields in the value into an array using the defined delimiter
        $split_values = explode($delimiter, $value);

        // unescape the fields in case they contain the separator, and trim any whitespace
        foreach ($split_values as &$unescaped_value) {
            $unescaped_value = trim($this->decode($delimiter, $unescaped_value));
        }

        // transform the values from the spreadsheet to an `entity_lookup` config and supply the default values.
        $entity_lookup_configuration = $this->applyDefaults(
            $this->toEntityLookupConfig($split_values), $this->configuration['defaults']);

        // create an instance of the entity_lookup plugin using the configuration supplied by the migration
        $entity_lookup_plugindef = $this->migration_plugin_manager->getDefinitions()['entity_lookup'];
        $entity_lookup_plugin = $this->migration_plugin_manager->createInstance($entity_lookup_plugindef['id'],
            $entity_lookup_configuration, $this->migration);

        // invoke the entity_lookup plugin with the last portion of the original value, and return
        return $entity_lookup_plugin->transform($split_values[3], $migrate_executable, $row, $destination_property);
    }

    /**
     * Transforms the source values obtained from the migration into an array representing the entity_lookup configuration.<br><br>
     *
     * Note that caller is responsible for applying the defaults provided by this (`parse_entity_plugin`) plugin.
     *
     * @param array $split_values source values from the migration as an array keyed by integers
     * @return array an array suitable for use as an `entity_lookup` configuration (i.e. a map with string keys)
     */
    function toEntityLookupConfig($split_values): array {
        // Values from the spreadsheet will be indexed with integers.  Map them to configuration keys.

        $entity_lookup_configuration_from_values = [
            'entity_type' => $split_values[0],
            'bundle' => $split_values[1],
            'value_key' => $split_values[2],
        ];

        // If a key is the zero-length string or null, redact it from the returned array.  Some entity types
        // (e.g. Users) do not use all the configuration keys (e.g. bundles), and to have them present in the
        // configuration is a fatal error.

        foreach ($entity_lookup_configuration_from_values as $k => $v) {
            if ($v === NULL || '' === trim($v)) {
                unset($entity_lookup_configuration_from_values[$k]);
            }
        }

        return $entity_lookup_configuration_from_values;
    }

    /**
     * Decodes the provided string.  If $delimiter is present in its percent-encoded form in $string, this method will
     * decode the percent-encoded form into $delimiter, and return the decoded string.
     *
     * @param string $delimiter the delimiter used to separate the fields in the $value being processed by the transform(...) method.  Note the delimiter must be from the basic latin character set.
     * @param string $string a string that may contain the percent-encoded form of $delimiter
     * @return string the decoded string
     */
    function decode(string $delimiter, string $string): string {
        $encoded_delimiter = '';
        foreach (str_split($delimiter) as $char) {
            if (array_key_exists($char, self::reserved_char_map)) {
                $encoded_delimiter .= self::reserved_char_map[$char];
                continue;
            }
            $encoded_delimiter .= rawurlencode($char);
        }

        $decoded_string = str_ireplace($encoded_delimiter, $delimiter, $string);

        return $decoded_string;
    }

    /**
     * Merges two arrays keyed by strings.  The first array is parsed from the spreadsheet, the second array is provided
     * by this plugin's configuration.  Values from the spreadsheet will override the plugin configuration; if a value
     * is elided from the spreadsheet, it will be provided by the plugin configuration.
     *
     * @param array $entity_lookup_config an array containing the 'entity_lookup' configuration parsed from the source values in the spreadsheet
     * @param array $defaults an array of default values merged with $entity_lookup_config
     * @return array the merged array
     */
    function applyDefaults($entity_lookup_config, $defaults): array {
        return array_merge($defaults, $entity_lookup_config);
    }


}