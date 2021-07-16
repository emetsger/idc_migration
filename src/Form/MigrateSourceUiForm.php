<?php

namespace Drupal\idc_migration\Form;

use Drupal\Core\Form\FormStateInterface;

use Drupal\idc_migration\MigrateBatchExecutable;
use Drupal\migrate\Plugin\MigrationInterface;
use Drupal\migrate_source_ui\Form\MigrateSourceUiForm as MigrateSourceUiFormBase;
use Drupal\migrate_source_ui\StubMigrationMessage;

/**
 * Slightly extended migration execution form.
 */
class MigrateSourceUiForm extends MigrateSourceUiFormBase {

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $migration_id = $form_state->getValue('migrations');
    /** @var \Drupal\migrate\Plugin\Migration $migration */
    $migration = $this->pluginManagerMigration->createInstance($migration_id);

    // Reset status.
    $status = $migration->getStatus();
    if ($status !== MigrationInterface::STATUS_IDLE) {
      $migration->setStatus(MigrationInterface::STATUS_IDLE);
      $this->messenger()->addWarning($this->t('Migration @id reset to Idle', ['@id' => $migration_id]));
    }

    $options = [];

    // Prepare the migration with the path injected.
    $definition = $this->pluginManagerMigration->getDefinition($migration_id);
    // Override the file path.
    $definition['source']['path'] = $form_state->getValue('file_path');
    /** @var \Drupal\migrate\Plugin\MigrationInterface $migration */
    $migration = $this->pluginManagerMigration->createStubMigration($definition);

    // Force updates or not.
    if ($form_state->getValue('update_existing_records')) {
      $migration->getIdMap()->prepareUpdate();
    }

    $executable = new MigrateBatchExecutable($migration, new StubMigrationMessage(), $options);
    batch_set($executable->prepareBatch());
  }

}
