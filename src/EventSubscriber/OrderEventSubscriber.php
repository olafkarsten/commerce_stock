<?php

namespace Drupal\commerce_stock\EventSubscriber;

use Drupal\commerce\Context;
use Drupal\commerce\PurchasableEntityInterface;
use Drupal\commerce_order\Event\OrderEvent;
use Drupal\commerce_order\Event\OrderEvents;
use Drupal\commerce_order\Event\OrderItemEvent;
use Drupal\commerce_stock\ContextCreatorTrait;
use Drupal\commerce_stock\Plugin\StockEventsInterface;
use Drupal\commerce_stock\StockLocationInterface;
use Drupal\commerce_stock\StockServiceManagerInterface;
use Drupal\commerce_stock\StockTransactionsInterface;
use Drupal\Core\Entity\Entity;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\state_machine\Event\WorkflowTransitionEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Performs stock transactions on order and order item events.
 */
class OrderEventSubscriber implements EventSubscriberInterface {

  use ContextCreatorTrait;
  use StringTranslationTrait;

  /**
   * The stock service manager.
   *
   * @var \Drupal\commerce_stock\StockServiceManagerInterface
   */
  protected $stockServiceManager;

  /**
   * Constructs a new OrderReceiptSubscriber object.
   *
   * @param \Drupal\commerce_stock\StockServiceManagerInterface $stock_service_manager
   *   The stock service manager.
   */
  public function __construct(
    StockServiceManagerInterface $stock_service_manager
  ) {
    $this->stockServiceManager = $stock_service_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents() {
    $events = [
      // State change events fired on workflow transitions from state_machine.
      'commerce_order.place.post_transition' => ['onOrderPlace', -100],
      'commerce_order.cancel.post_transition' => ['onOrderCancel', -100],
      // Order storage events dispatched during entity operations in
      // CommerceContentEntityStorage.
      // ORDER_UPDATE handles new order items since ORDER_ITEM_INSERT doesn't.
      OrderEvents::ORDER_UPDATE => ['onOrderUpdate', -100],
      OrderEvents::ORDER_PREDELETE => ['onOrderDelete', -100],
      OrderEvents::ORDER_ITEM_UPDATE => ['onOrderItemUpdate', -100],
      OrderEvents::ORDER_ITEM_DELETE => ['onOrderItemDelete', -100],
    ];
    return $events;
  }

  /**
   * Creates a stock transaction when an order is placed.
   *
   * @param \Drupal\state_machine\Event\WorkflowTransitionEvent $event
   *   The order workflow event.
   */
  public function onOrderPlace(WorkflowTransitionEvent $event) {
    $order = $event->getEntity();
    foreach ($order->getItems() as $item) {
      $entity = $item->getPurchasedEntity();
      if (!$entity) {
        continue;
      }
      $service = $this->stockServiceManager->getService($entity);
      $checker = $service->getStockChecker();
      if ($checker->getIsStockManaged($entity)) {
        // If always in stock then no need to create a transaction.
        if ($checker->getIsAlwaysInStock($entity)) {
          return;
        }
        $quantity = -1 * $item->getQuantity();
        $context = self::createContextFromOrder($order);
        $location = $service->getConfiguration()
          ->getTransactionLocation($context, $entity, $quantity);
        $transaction_type = StockTransactionsInterface::STOCK_SALE;
        $metadata = [
          'data' => ['message' => 'order placed'],
        ];

        $this->runTransactionEvent(StockEventsInterface::ORDER_PLACE_EVENT, $context,
          $entity, $quantity, $location, $transaction_type, $order->id(), $metadata);
      }
    }
  }

  /**
   * Run the stock event.
   *
   * @param int $orderEvent
   *   The order event ID as defined by the OrderEvents class.
   * @param \Drupal\commerce\Context $context
   *   The context containing the customer & store.
   * @param \Drupal\commerce\PurchasableEntityInterface $entity
   *   The purchasable entity.
   * @param int $quantity
   *   The quantity.
   * @param \Drupal\commerce_stock\StockLocationInterface $location
   *   The stock location.
   * @param int $transaction_type_id
   *   The transaction type ID.
   * @param string|int|null $order_id
   *   The commerce order identifier or NULL.
   * @param array $metadata
   *   Holds all the optional values those are:
   *     - related_tid: related transaction.
   *     - unit_cost: unit cost.
   *     - currency: the currency of the unit cost.
   *     - data: Serialized data array holding a message.
   *
   * @return int
   *   Return the ID of the transaction or FALSE if no transaction created.
   */
  private function runTransactionEvent(
    $orderEvent,
    Context $context,
    PurchasableEntityInterface $entity,
    $quantity,
    StockLocationInterface $location,
    $transaction_type_id,
    $order_id = NULL,
    array $metadata = []
  ) {

    $type = \Drupal::service('plugin.manager.stock_events');
    $plugin = $type->createInstance('core_stock_events');
    return $plugin->stockEvent($context, $entity, $orderEvent, $quantity, $location,
      $transaction_type_id, $order_id, $metadata);
  }

  /**
   * Acts on the order update event to create transactions for new items.
   *
   * The reason this isn't handled by OrderEvents::ORDER_ITEM_INSERT is because
   * that event never has the correct values.
   *
   * @param \Drupal\commerce_order\Event\OrderEvent $event
   *   The order event.
   */
  public function onOrderUpdate(OrderEvent $event) {
    $order = $event->getOrder();
    $original_order = self::getOriginalEntity($order);

    foreach ($order->getItems() as $item) {
      if (!$original_order->hasItem($item)) {
        if ($order && !in_array($order->getState()->value, [
          'draft',
          'canceled',
        ])) {
          if (!$item->hasPurchasedEntity()) {
            continue;
          }
          $entity = $item->getPurchasedEntity();
          $service = $this->stockServiceManager->getService($entity);
          $checker = $service->getStockChecker();
          // If always in stock then no need to create a transaction.
          if ($checker->getIsAlwaysInStock($entity)) {
            return;
          }
          $context = self::createContextFromOrder($order);
          $location = $service->getConfiguration()
            ->getTransactionLocation($context, $entity, $item->getQuantity());
          $transaction_type = StockTransactionsInterface::STOCK_SALE;
          $quantity = -1 * $item->getQuantity();
          $metadata = [
            'data' => ['message' => 'order item added'],
          ];

          $this->runTransactionEvent(StockEventsInterface::ORDER_UPDATE_EVENT, $context,
            $entity, $quantity, $location, $transaction_type, $order->id(), $metadata);
        }
      }
    }
  }

  /**
   * Performs a stock transaction for an order Cancel event.
   *
   * @param \Drupal\state_machine\Event\WorkflowTransitionEvent $event
   *   The order workflow event.
   */
  public function onOrderCancel(WorkflowTransitionEvent $event) {
    $order = $event->getEntity();
    $original_order = self::getOriginalEntity($order);

    if ($original_order && $original_order->getState()->value === 'draft') {
      return;
    }
    foreach ($order->getItems() as $item) {
      $entity = $item->getPurchasedEntity();
      if (!$entity) {
        continue;
      }
      $service = $this->stockServiceManager->getService($entity);
      $checker = $service->getStockChecker();
      if ($checker->getIsStockManaged($entity)) {
        // If always in stock then no need to create a transaction.
        if ($checker->getIsAlwaysInStock($entity)) {
          return;
        }
        $quantity = $item->getQuantity();
        $context = self::createContextFromOrder($order);
        $location = $service->getConfiguration()
          ->getTransactionLocation($context, $entity, $quantity);
        $transaction_type = StockTransactionsInterface::STOCK_RETURN;
        $metadata = [
          'data' => ['message' => 'order canceled'],
        ];

        $this->runTransactionEvent(StockEventsInterface::ORDER_CANCEL_EVENT, $context,
          $entity, $quantity, $location, $transaction_type, $order->id(), $metadata);
      }
    }
  }

  /**
   * Performs a stock transaction on an order delete event.
   *
   * This happens on PREDELETE since the items are not available after DELETE.
   *
   * @param \Drupal\commerce_order\Event\OrderEvent $event
   *   The order event.
   */
  public function onOrderDelete(OrderEvent $event) {
    $order = $event->getOrder();
    if (in_array($order->getState()->value, ['draft', 'canceled'])) {
      return;
    }
    $items = $order->getItems();
    foreach ($items as $item) {
      $entity = $item->getPurchasedEntity();
      if (!$entity) {
        continue;
      }
      $service = $this->stockServiceManager->getService($entity);
      $checker = $service->getStockChecker();
      if ($checker->getIsStockManaged($entity)) {
        // If always in stock then no need to create a transaction.
        if ($checker->getIsAlwaysInStock($entity)) {
          return;
        }
        $quantity = $item->getQuantity();
        $context = self::createContextFromOrder($order);
        $location = $service->getConfiguration()
          ->getTransactionLocation($context, $entity, $quantity);
        $transaction_type = StockTransactionsInterface::STOCK_RETURN;
        $metadata = [
          'data' => ['message' => 'order deleted'],
        ];
        $this->runTransactionEvent(StockEventsInterface::ORDER_DELET_EVENT, $context,
          $entity, $quantity, $location, $transaction_type, $order->id(), $metadata);
      }
    }
  }

  /**
   * Performs a stock transaction on an order item update.
   *
   * @param \Drupal\commerce_order\Event\OrderItemEvent $event
   *   The order item event.
   */
  public function onOrderItemUpdate(OrderItemEvent $event) {
    $item = $event->getOrderItem();
    $order = $item->getOrder();

    if ($order && !in_array($order->getState()->value, ['draft', 'canceled'])) {
      $original = self::getOriginalEntity($item);
      $diff = $original->getQuantity() - $item->getQuantity();
      if ($diff) {
        $entity = $item->getPurchasedEntity();
        if (!$entity) {
          return;
        }
        $service = $this->stockServiceManager->getService($entity);
        $checker = $service->getStockChecker();
        if ($checker->getIsStockManaged($entity)) {
          // If always in stock then no need to create a transaction.
          if ($checker->getIsAlwaysInStock($entity)) {
            return;
          }
          $transaction_type = ($diff < 0) ? StockTransactionsInterface::STOCK_SALE : StockTransactionsInterface::STOCK_RETURN;
          $context = self::createContextFromOrder($order);
          $location = $service->getConfiguration()
            ->getTransactionLocation($context, $entity, $diff);
          $metadata = [
            'data' => ['message' => 'order item quantity updated'],
          ];

          $this->runTransactionEvent(StockEventsInterface::ORDER_ITEM_UPDATE_EVENT, $context,
            $entity, $diff, $location, $transaction_type, $order->id(), $metadata);
        }
      }
    }
  }

  /**
   * Performs a stock transaction when an order item is deleted.
   *
   * @param \Drupal\commerce_order\Event\OrderItemEvent $event
   *   The order item event.
   */
  public function onOrderItemDelete(OrderItemEvent $event) {
    $item = $event->getOrderItem();
    $order = $item->getOrder();
    if ($order && !in_array($order->getState()->value, ['draft', 'canceled'])) {
      $entity = $item->getPurchasedEntity();
      if (!$entity) {
        return;
      }
      /** @var \Drupal\commerce_stock\StockServiceInterface $service */
      $service = $this->stockServiceManager->getService($entity);
      $checker = $service->getStockChecker();
      if ($checker->getIsStockManaged($entity)) {
        // If always in stock then no need to create a transaction.
        if ($checker->getIsAlwaysInStock($entity)) {
          return;
        }
        $context = self::createContextFromOrder($order);
        self::createContextFromOrder($order);
        $location = $service->getConfiguration()
          ->getTransactionLocation($context, $entity, $item->getQuantity());
        $transaction_type = StockTransactionsInterface::STOCK_RETURN;
        $metadata = [
          'data' => ['message' => 'order item deleted'],
        ];

        $this->runTransactionEvent(StockEventsInterface::ORDER_ITEM_DELETE_EVENT, $context,
          $entity, $item->getQuantity(), $location, $transaction_type, $order->id(), $metadata);
      }
    }
  }

  /**
   * Returns the entity from an updated entity object. In certain
   * cases the $entity->original property is empty for updated entities. In such
   * a situation we try to load the unchanged entity from storage.
   *
   * @param \Drupal\Core\Entity\Entity $entity
   *   The changed/updated entity object.
   *
   * @return null|\Drupal\Core\Entity\Entity
   *   The original unchanged entity object or NULL.
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   */
  public static function getOriginalEntity(Entity $entity) {
    // $entity->original only exists during save. See
    // \Drupal\Core\Entity\EntityStorageBase::save().
    // If we don't have $entity->original we try to load it.
    $original_entity = NULL;
    $original_entity = $entity->original;

    // @ToDo Consider how this may change due to: ToDo https://www.drupal.org/project/drupal/issues/2839195
    if (!$original_entity) {
      $id = $entity->getOriginalId() !== NULL ? $entity->getOriginalId() : $entity->id();
      $original_entity = \Drupal::entityTypeManager()
        ->getStorage($entity->getEntityTypeId())
        ->loadUnchanged($id);
    }
    return $original_entity;
  }

}
