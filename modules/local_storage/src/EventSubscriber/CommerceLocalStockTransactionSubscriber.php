<?php

namespace Drupal\commerce_stock_local\EventSubscriber;

use Drupal\commerce_stock_local\Event\LocalStockTransactionEvent;
use Drupal\commerce_stock_local\Event\LocalStockTransactionEvents;
use Drupal\Core\Cache\CacheTagsInvalidatorInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Test class to test the commerce_stock transaction events.
 */
class CommerceLocalStockTransactionSubscriber implements EventSubscriberInterface {

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * The cache tags invalidator.
   *
   * @var \Drupal\Core\Cache\CacheTagsInvalidatorInterface
   */
  protected $cacheTagsInvalidator;

  /**
   * Constructs a CommerceStockTransactionSubscriber.
   *
   * @param \Drupal\Core\Cache\CacheTagsInvalidatorInterface $cache_tags_invalidator
   *   The cache tags invalidator.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   */
  public function __construct(
    CacheTagsInvalidatorInterface $cache_tags_invalidator,
    EntityTypeManagerInterface $entity_type_manager
  ) {
    $this->cacheTagsInvalidator = $cache_tags_invalidator;
    $this->entityTypeManager = $entity_type_manager;
  }

  /**
   * @inheritDoc
   */
  public static function getSubscribedEvents() {
    return [
      LocalStockTransactionEvents::LOCAL_STOCK_TRANSACTION_INSERT => 'onTransactionInsert',
    ];
  }

  /**
   * Invalidate the cache for the purchased entity.
   *
   * @param \Drupal\commerce_stock_local\Event\LocalStockTransactionEvent $event
   *   The event.
   */
  public function onTransactionInsert(LocalStockTransactionEvent $event) {
    $purchasableEntity = $this->entityTypeManager->getStorage($event['entity_type_id'])->load($event['entity_id']);
    $this->cacheTagsInvalidator->invalidateTags($purchasableEntity->getCacheTagsToInvalidate());
  }

}
