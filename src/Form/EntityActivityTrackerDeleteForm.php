<?php

namespace Drupal\entity_activity_tracker\Form;

use Drupal\Core\Entity\EntityConfirmFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Drupal\entity_activity_tracker\Event\TrackerDeleteEvent;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Messenger\MessengerInterface;
use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\entity_activity_tracker\Event\ActivityEventInterface;

/**
 * Builds the form to delete Entity activity tracker entities.
 */
class EntityActivityTrackerDeleteForm extends EntityConfirmFormBase {

  /**
   * The event dispatcher.
   *
   * @var \Symfony\Component\EventDispatcher\EventDispatcherInterface
   */
  protected $eventDispatcher;

  /**
   * The cache backend to use.
   *
   * @var \Drupal\Core\Cache\CacheBackendInterface
   */
  protected $cacheBackend;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    $instance = new static();
    $instance->eventDispatcher = $container->get('event_dispatcher');
    $instance->messenger = $container->get('messenger');
    $instance->cacheBackend = $container->get('cache.default');
    return $instance;
  }

  /**
   * {@inheritdoc}
   */
  public function getQuestion() {
    return $this->t('Are you sure you want to delete %name?', ['%name' => $this->entity->label()]);
  }

  /**
   * {@inheritdoc}
   */
  public function getCancelUrl() {
    return new Url('entity.entity_activity_tracker.collection');
  }

  /**
   * {@inheritdoc}
   */
  public function getConfirmText() {
    return $this->t('Delete');
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $this->entity->delete();

    $event = new TrackerDeleteEvent($this->entity);
    $this->eventDispatcher->dispatch(ActivityEventInterface::TRACKER_DELETE, $event);

    // Invalidate caches to make views aware of new tracker.
    $this->cacheBackend->invalidateAll();

    $this->messenger->addMessage(
      $this->t('content @type: deleted @label.',
        [
          '@type' => $this->entity->bundle(),
          '@label' => $this->entity->label(),
        ]
        )
    );

    $form_state->setRedirectUrl($this->getCancelUrl());
  }

}
