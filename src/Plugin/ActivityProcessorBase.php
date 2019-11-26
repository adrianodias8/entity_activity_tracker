<?php

namespace Drupal\entity_activity_tracker\Plugin;

use Drupal\Component\Plugin\PluginBase;
use Drupal\Core\DependencyInjection\DependencySerializationTrait;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\entity_activity_tracker\ActivityRecordStorageInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\entity_activity_tracker\Entity\EntityActivityTrackerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\EventDispatcher\Event;

/**
 * Base class for Activity processor plugins.
 */
abstract class ActivityProcessorBase extends PluginBase implements ActivityProcessorInterface {

  use StringTranslationTrait;
  use DependencySerializationTrait;

  /**
   * The activity record storage service.
   *
   * @var \Drupal\entity_activity_tracker\ActivityRecordStorageInterface
   */
  protected $activityRecordStorage;

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    $instance = new static($configuration, $plugin_id, $plugin_definition);
    $instance->activityRecordStorage = $container->get('entity_activity_tracker.activity_record_storage');
    $instance->entityTypeManager = $container->get('entity_type.manager');
    $instance->setConfiguration($configuration);
    return $instance;
  }

  /**
   * {@inheritdoc}
   */
  public function getSummary() {
    $replacements = [
      '@plugin_name' => $this->pluginDefinition['label']->render(),
      '@plugin_summary' => $this->pluginDefinition['summary']->render(),
      '@activity' => $this->configuration[$this->getConfigField()],
    ];
    return $this->t('<b>@plugin_name:</b> <br> @plugin_summary: @activity <br>', $replacements);
  }

  /**
   * {@inheritdoc}
   */
  public function processActivity(Event $event) {
    // code...
  }

  /**
   * Let plugins decide if can process.
   */
  public function canProcess(Event $event) {
    // By default we will tell to ActivityProcessorQueue to allways process.
    return ActivityProcessorInterface::PROCESS;
  }

  /**
   * Get existing entities of tracker that was just created.
   *
   * @param \Drupal\entity_activity_tracker\EntityActivityTrackerInterface $tracker
   *   The tracker config entity.
   *
   * @return \Drupal\Core\Entity\ContentEntityInterface[]
   *   Existing entities to be tracked.
   */
  protected function getExistingEntities(EntityActivityTrackerInterface $tracker) {
    $storage = $this->entityTypeManager->getStorage($tracker->getTargetEntityType());
    $bundle_key = $storage->getEntityType()->getKey('bundle');
    return $this->entityTypeManager->getStorage($tracker->getTargetEntityType())->loadByProperties([$bundle_key => $tracker->getTargetEntityBundle()]);
  }

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    return [];
  }

  /**
   * {@inheritdoc}
   */
  public function getConfiguration() {
    return [
      'id' => $this->getPluginId(),
    ] + $this->configuration;
  }

  /**
   * {@inheritdoc}
   */
  public function setConfiguration(array $configuration) {
    $this->configuration = $configuration + $this->defaultConfiguration();
    return $this;
  }

}
