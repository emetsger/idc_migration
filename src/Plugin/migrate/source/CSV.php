<?php

namespace Drupal\idc_migration\Plugin\migrate\source;

use Drupal\migrate_source_csv\Plugin\migrate\source\CSV as CSVBase;

/**
 * Extension of the CSV class which allows it to be serializable.
 *
 * @MigrateSource(
 *   id = "idc_csv",
 *   source_module = "idc_migration"
 * )
 */
class CSV extends CSVBase {

  /**
   * {@inheritdoc}
   *
   * @throws \Drupal\migrate\MigrateException
   * @throws \League\Csv\Exception
   */
  public function initializeIterator() {
    $header = $this->getReader()->getHeader();
    if ($this->configuration['fields']) {
      // If there is no header record, we need to flip description and name so
      // the name becomes the header record.
      $header = array_flip($this->fields());
    }
    // XXX: Need to wrap this in an ArrayIterator as Generators are not
    // serializable.
    return new \ArrayIterator(iterator_to_array($this->getGenerator($this->getReader()->getRecords($header))));
  }

}
