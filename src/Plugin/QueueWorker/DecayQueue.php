<?php

namespace Drupal\entity_activity_tracker\Plugin\QueueWorker;

use Drupal\Core\Queue\QueueWorkerBase;
use Drupal\entity_activity_tracker\Event\ActivityDecayEvent;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\hook_event_dispatcher\Event\Cron\CronEvent;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

/**
 * Triggers decay event or processes plugins deppending on Event.
 *
 * @QueueWorker(
 *   id = "decay_queue",
 *   title = @Translation("Decay queue"),
 *   cron = {"time" = 10}
 * )
 */
class DecayQueue extends QueueWorkerBase implements ContainerFactoryPluginInterface {

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * A logger instance.
   *
   * @var \Psr\Log\LoggerInterface
   */
  protected $logger;

  /**
   * The event dispatcher.
   *
   * @var \Symfony\Component\EventDispatcher\EventDispatcherInterface
   */
  protected $eventDispatcher;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    $instance = new static($configuration, $plugin_id, $plugin_definition);
    $instance->entityTypeManager = $container->get('entity_type.manager');
    $instance->logger = $container->get('logger.factory')->get('entity_activity_tracker');
    $instance->eventDispatcher = $container->get('event_dispatcher');
    return $instance;
  }

  /**
   * {@inheritdoc}
   */
  public function processItem($event) {
    switch ($event) {
      case $event instanceof ActivityDecayEvent:
        // If here we get the ActivityDecayEvent we process plugins.
        $enabled_plugins = $event->getTracker()->getProcessorPlugins()->getEnabled();
        foreach ($enabled_plugins as $plugin_id => $processor_plugin) {
          $processor_plugin->processActivity($event);

          $message = $plugin_id . ' plugin processed';
          $this->logger->info($message);
        }

        break;

      case $event instanceof CronEvent:
        // Get all trackers.
        $trackers = $this->getTrackers();

        // Here we dispatch a Decay Event for each tracker.
        foreach ($trackers as $tracker) {
          $event = new ActivityDecayEvent($tracker);
          $this->eventDispatcher->dispatch(ActivityDecayEvent::DECAY, $event);
        }

        $this->logger->info("Activity Decay Dispatched");
        break;
    }
  }

  /**
   * This gets all EntityActivityTrackers config entities.
   *
   * @return \Drupal\entity_activity_tracker\Entity\EntityActivityTrackerInterface[]
   *   An array of Trackers indexed by their ID.
   */
  protected function getTrackers() {
    return $this->entityTypeManager->getStorage('entity_activity_tracker')->loadMultiple();
  }

}
