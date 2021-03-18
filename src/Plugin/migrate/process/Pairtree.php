<?php


namespace Drupal\idc_migration\Plugin\migrate\process;


use Drupal\migrate\MigrateException;
use Drupal\migrate\MigrateExecutableInterface;
use Drupal\migrate\ProcessPluginBase;
use Drupal\migrate\Row;

/**
 * Transforms a checksum encoded as a base 16 string into a pair tree path.  The returned path is relative, and is meant
 * to be used by prefixing it with a Drupal filesystem uri, e.g. 'public://' or 'temporary://'.
 *
 * For example, if the input to this plugin is the value "c9a060c39365820edc5d1a51f221d49e96a8a730", then the return
 * (using the default values for configuration keys) would be "c9/a0/60/c39365820edc5d1a51f221d49e96a8a730".
 *
 * Available configuration keys:
 * - truncate: if true, the pairs encoded in the path portion of the pair tree are not included in the file name. Defaults to true.
 * - pairlen: the lengths of the pairs encoded in path portion.  Defaults to 2.
 * - depth: the number of pairs encoded in the path portion.  Defaults to 3.
 *
 * Example:
 * @code
 * process:
 *   path:
 *     plugin: pairtree
 *     source: '@sha1checksum_of_a_file'
 *     pairlen: 2
 *     truncate: true
 *     depth: 3
 * @endcode
 *
 * @see \Drupal\migrate\Plugin\MigrateProcessInterface
 * @MigrateProcessPlugin(
 *   id = "pairtree",
 *   handle_multiples = FALSE
 * )
 */
class Pairtree extends ProcessPluginBase {

    public function transform($value, MigrateExecutableInterface $migrate_executable, Row $row, $destination_property) {
        $pair_len = 2;
        $depth = 3;
        $truncate_id = true;

        if (is_int($this->configuration['pairlen'])) {
            if (intval($this->configuration['pairlen']) < 1) {
                throw new MigrateException('pairlen must be a positive integer');
            } else {
                $pair_len = intval($this->configuration['pairlen']);
            }
        }

        if (is_int($this->configuration['depth'])) {
            if (intval($this->configuration['depth']) < 1) {
                throw new MigrateException('depth must be a positive integer');
            } else {
                $depth = intval($this->configuration['depth']);
            }
        }

        if (key_exists("truncate", $this->configuration)) {
            if ($this->configuration['truncate']) {
                $truncate_id = true;
            } else {
                $truncate_id = false;
            }
        }

        if ($pair_len * $depth > strlen($value)) {
            throw new MigrateException('number of pairs is too long (pair_len * depth > strlen(value)');
        }

        $pair_prefix = substr($value, 0, $pair_len * $depth);
        $pairs = str_split($pair_prefix, $pair_len);
        $result = sprintf("%s/", join("/", $pairs));

        if ($truncate_id) {
            $result = sprintf("%s%s", $result, substr($value, $pair_len * $depth));
        } else {
            $result = sprintf("%s%s", $result, $value);
        }

        return $result;
    }
}