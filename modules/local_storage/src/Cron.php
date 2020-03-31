<?php

namespace Drupal\commerce_stock_local;

use Drupal\commerce\PurchasableEntityInterface;
use Drupal\commerce_stock\CronInterface;
use Drupal\Component\Datetime\TimeInterface;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Database\Connection;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Queue\QueueFactory;
use Drupal\workflows\StateInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * The local stock default cron implementation.
 */
class Cron implements CronInterface {

  /**
   * The Drupal state storage service.
   *
   * @var \Drupal\Core\State\StateInterface
   */
  protected $state;

  /**
   * The database connection.
   *
   * @var \Drupal\Core\Database\Connection
   */
  protected $database;

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * The commerce_stock_local_stock_level_updater queue.
   *
   * @var \Drupal\Core\Queue\QueueInterface
   */
  protected $queue;

  /**
   * The configuration factory.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $configFactory;

  /**
   * The time service.
   *
   * @return \Drupal\Component\Datetime\TimeInterface
   *   The time service.
   */
  protected $time;

  /**
   * Constructs the local stock checker.
   *
   * @param \Drupal\Core\State\StateInterface $state
   *   The state service.
   * @param \Drupal\Core\Database\Connection $database
   *   The database connection.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity manager.
   * @param \Drupal\Core\Queue\QueueFactory $queue_factory
   *   The queue factory.
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The configuration factory.
   * @param \Drupal\Component\Datetime\TimeInterface $time
   *   The time service.
   */
  public function __construct(
    StateInterface $state,
    Connection $database,
    EntityTypeManagerInterface $entity_type_manager,
    QueueFactory $queue_factory,
    ConfigFactoryInterface $config_factory,
    TimeInterface $time
  ) {
    $this->state = $state;
    $this->database = $database;
    $this->entityTypeManager = $entity_type_manager;
    $this->queue = $queue_factory->get('commerce_stock_local_stock_level_updater', TRUE);
    $this->configFactory = $config_factory;
    $this->time = $time;
  }

  /**
   * @inheritDoc
   */
  public function run() {
    $next = $this->state->get('commerce_stock_local.update_level_next') ?: 0;
    $request_time = $this->time->getRequestTime();
    if ($request_time >= $next) {
      $this->update_stock_level_queue();
      $interval = $this->configFactory->get('commerce_stock_local.cron')->get('update_interval');
      $this->state->set('commerce_stock_local.update_level_next', $request_time + $interval);
    }
  }

  /**
   * Updates the stock level update queue.
   *
   * Adds purchasable entities from the latest unprocessed stock transactions
   * to the queue worker responsible for totaling location stock levels.
   */
  protected function update_stock_level_queue(){

    // Get the batch size.
    $llq_batchsize = $this->state->get('commerce_stock_local.llq_batchsize');
    $batchsize = !empty($llq_batchsize) ? $llq_batchsize : 50;

    // Prepare the list of purchasable entity types and bundles.
    $entity_types = $this->entityTypeManager->getDefinitions();
    $purchasable_entity_types = array_filter($entity_types, function ($entity_type) {
      return $entity_type->isSubclassOf('\Drupal\commerce\PurchasableEntityInterface');
    });

    /** @var \Drupal\commerce\PurchasableEntityInterface $entity_type */
    foreach ($purchasable_entity_types as $entity_type) {

      $entity_type_id = $entity_type->id();
      // Get the last processed product id.
      $key_base = $key = 'commerce_stock_local.' . $entity_type_id . '.';
      $key = $key_base . 'location_level_last_id';
      $location_level_last_id = !empty($this->state->get($key)) ? $this->state->get($key) : 0;

      // Check if the queue is empty and not initialized to 0.
      if (($this->queue->numberOfItems() == 0) && ($location_level_last_id != 0)) {
        // Set the queue reset state.
        $this->state->set('commerce_stock_local.llq_reset', TRUE);
        $llq_reset = TRUE;
      } else {
        // Get the queue reset state.
        $llq_reset = $this->state->get('commerce_stock_local.llq_reset');
        $llq_reset = !empty($llq_reset) ? $llq_reset : FALSE;
      }

      $storage = $this->entityTypeManager->getStorage($entity_type->id());
      $id_field = $entity_type->getKey('id');
      $result = $storage->getQuery()
        ->condition($id_field, $location_level_last_id, '>')
        ->sort($id_field, 'ASC')
        ->range(0, $batchsize)
        ->execute();
      foreach ($result as $pid) {
        $entity = $storage->load($pid);
        // If the entity doesn't exist anymore, we bailout here.
        if(!$entity){
          continue;
        }
        $data = [
          'entity_id' => $entity->id(),
          'entity_type' => $entity->getEntityTypeId(),
        ];
        $this->queue->createItem($data);
      }
    }
  }

  /**
   * Gets stock level for a given location and purchasable entity.
   *
   * @param int $location_id
   *   Location id.
   * @param \Drupal\commerce\PurchasableEntityInterface $entity
   *   Purchasable entity.
   *
   * @return array
   *   An array of 'qty' and 'last_transaction_id' values.
   */
  public function getLocationStockLevel(
    $location_id,
    PurchasableEntityInterface $entity
  ) {
    $result = $this->database->select('commerce_stock_location_level', 'll')
      ->fields('ll')
      ->condition('location_id', $location_id)
      ->condition('entity_id', $entity->id())
      ->condition('entity_type', $entity->getEntityTypeId())
      ->execute()
      ->fetch();

    return [
      'qty' => $result ? $result->qty : 0,
      'last_transaction' => $result ? $result->last_transaction_id : 0,
    ];
  }

  /**
   * Gets the last transaction id for a given location and purchasable entity.
   *
   * @param int $location_id
   *   Location id.
   * @param \Drupal\commerce\PurchasableEntityInterface $entity
   *   The purchasable entity.
   *
   * @return int
   *   The last location stock transaction id.
   */
  public function getLocationStockTransactionLatest(
    $location_id,
    PurchasableEntityInterface $entity
  ) {
    $query = $this->database->select('commerce_stock_transaction')
      ->condition('location_id', $location_id)
      ->condition('entity_id', $entity->id())
      ->condition('entity_type', $entity->getEntityTypeId());
    $query->addExpression('MAX(id)', 'max_id');

    $result = $query
      ->execute()
      ->fetch();

    return $result && $result->max_id ? $result->max_id : 0;
  }

  /**
   * Gets the sum of all stock transactions between a range of transactions.
   *
   * @param int $location_id
   *   The location id.
   * @param \Drupal\commerce\PurchasableEntityInterface $entity
   *   The purchasable entity.
   * @param int $min
   *   The minimum transaction number.
   * @param int $max
   *   The maximum transaction number.
   *
   * @return int
   *   The sum of stock transactions for a given location and purchasable
   *   entity.
   */
  public function getLocationStockTransactionSum(
    $location_id,
    PurchasableEntityInterface $entity,
    $min,
    $max
  ) {
    $query = $this->database->select('commerce_stock_transaction', 'txn')
      ->fields('txn', ['location_id'])
      ->condition('location_id', $location_id)
      ->condition('entity_id', $entity->id())
      ->condition('entity_type', $entity->getEntityTypeId())
      ->condition('id', $min, '>');
    if ($max) {
      $query->condition('id', $max, '<=');
    }
    $query->addExpression('SUM(qty)', 'qty');
    $query->groupBy('location_id');
    $result = $query->execute()
      ->fetch();

    return $result ? $result->qty : 0;
  }

  /**
   * {@inheritdoc}
   */
  public function getTotalStockLevel(
    PurchasableEntityInterface $entity,
    array $locations
  ) {
    $location_info = $this->getLocationsStockLevels($entity, $locations);
    $total = 0;
    foreach ($location_info as $location) {
      $total += $location['qty'] + $location['transactions_qty'];
    }

    return $total;
  }

  /**
   * Gets the stock levels for a set of locations.
   *
   * @param \Drupal\commerce\PurchasableEntityInterface $entity
   *   The purchasable entity.
   * @param \Drupal\commerce_stock\StockLocationInterface[] $locations
   *   The stock locations.
   *
   * @return array
   *   Stock level information indexed by location id with these values:
   *     - 'qty': The quantity.
   *     - 'last_transaction': The id of the last transaction.
   */
  public function getLocationsStockLevels(
    PurchasableEntityInterface $entity,
    array $locations
  ) {
    $location_levels = [];
    /** @var \Drupal\commerce_stock\StockLocationInterface $location */
    foreach ($locations as $location) {
      $location_id = $location->getId();
      $location_level = $this->getLocationStockLevel($location_id, $entity);

      $latest_txn = $this->getLocationStockTransactionLatest($location_id, $entity);
      $transactions_qty = $this->getLocationStockTransactionSum($location_id, $entity, $location_level['last_transaction'], $latest_txn);

      $location_levels[$location_id] = [
        'qty' => $location_level['qty'],
        'transactions_qty' => $transactions_qty,
      ];
    }

    return $location_levels;
  }

  /**
   * {@inheritdoc}
   */
  public function getIsInStock(
    PurchasableEntityInterface $entity,
    array $locations
  ) {
    return ($this->getTotalStockLevel($entity, $locations) > 0);
  }

  /**
   * {@inheritdoc}
   */
  public function getIsAlwaysInStock(PurchasableEntityInterface $entity) {
    return $entity->get('commerce_stock_always_in_stock') && $entity->get('commerce_stock_always_in_stock')->value == TRUE;
  }

  /**
   * Gets the locations. This is an old implementation of the now changed
   * interface.
   *
   * @deprecated in commerce_stock:8.x-1.0 and is
   * removed from commerce_stock:8.x-2.0. Use the StockLocationStorage service instead.
   */
  public function getLocationList($return_active_only = TRUE) {

    $locations = $this->locationStorage->loadMultiple();

    if ($return_active_only) {
      $active = [];
      /** @var StockLocationInterface $location */
      foreach ($locations as $location) {
        if ($location->isActive()) {
          $active[] = $location;
        }
      }
      return $active;
    }

    return $locations;
  }

}
