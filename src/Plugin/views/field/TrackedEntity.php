<?php

namespace Drupal\entity_activity_tracker\Plugin\views\field;

use Drupal\views\Plugin\views\field\FieldPluginBase;
use Drupal\views\ResultRow;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\entity_activity_tracker\ActivityRecordStorageInterface;

/**
 * A handler to provide a field that is completely custom by the administrator.
 *
 * @ingroup views_field_handlers
 *
 * @ViewsField("tracked_entity")
 */
class TrackedEntity extends FieldPluginBase implements ContainerFactoryPluginInterface {

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * The Activity Record Storage Service.
   *
   * @var \Drupal\entity_activity_tracker\ActivityRecordStorageInterface
   */
  protected $activityRecordStorage;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    $instance = new static($configuration,$plugin_id,$plugin_definition);
    $instance->entityTypeManager = $container->get('entity_type.manager');
    $instance->activityRecordStorage = $container->get('entity_activity_tracker.activity_record_storage');
    return $instance;
  }

  /**
   * {@inheritdoc}
   */
  public function usesGroupBy() {
    return FALSE;
  }

  /**
   * {@inheritdoc}
   */
  public function query() {
    $this->ensureMyTable();

    // Make this field query activity_id col.
    // This is needed to know wich record we are dealing with.
    $this->realField = 'activity_id';

    $params = $this->options['group_type'] != 'group' ? ['function' => $this->options['group_type']] : [];
    $this->field_alias = $this->query->addField($this->tableAlias, $this->realField, NULL, $params);
  }

  /**
   * {@inheritdoc}
   */
  protected function defineOptions() {
    $options = parent::defineOptions();

    $options['hide_alter_empty'] = ['default' => FALSE];
    return $options;
  }

  /**
   * {@inheritdoc}
   */
  public function render(ResultRow $values) {
    $activity_record = $this->activityRecordStorage->getActivityRecord($values->activity_id);

    $entity_strorage = $this->entityTypeManager->getStorage($activity_record->getEntityType());
    $tracked_entity = $entity_strorage->load($activity_record->getEntityId());

    return $tracked_entity->toLink()->toString();
  }

}
