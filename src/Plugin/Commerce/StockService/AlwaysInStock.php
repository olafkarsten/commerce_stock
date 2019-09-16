<?php

namespace Drupal\commerce_stock\Plugin\Commerce\StockService;

use Drupal\commerce\Context;
use Drupal\commerce\PurchasableEntityInterface;
use Drupal\commerce_stock\StockCheckInterface;
use Drupal\commerce_stock\StockUpdateInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;

/**
 * Provides an 'Always in stock' service.
 *
 * @CommerceStockService(
 *   id = 'always_in_stock',
 *   label = 'Always in stock',
 *   display_label = 'Always in stock',
 * )
 */
class AlwaysInStock extends StockServiceBase implements StockCheckInterface, StockUpdateInterface {

  /**
   * Constructs a new AlwaysInStock object.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   */
  public function __construct(
    array $configuration,
    $plugin_id,
    $plugin_definition,
    EntityTypeManagerInterface $entity_type_manager
  ) {
    parent::__construct($configuration, $plugin_id, $plugin_definition, $entity_type_manager);
  }

  /**
   * {@inheritdoc}
   */
  public function getLabel(){
    return 'Always in stock';
  }

  /**
   * {@inheritdoc}
   */
  public function getDisplayLabel(){
    return 'Always in stock';
  }

  /**
   * {@inheritdoc}
   */
  public function getStockChecker() {
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getStockUpdater() {
    return $this;
  }

  public function createTransaction(
    PurchasableEntityInterface $entity,
    $location_id,
    $zone,
    $quantity,
    $transaction_type_id,
    $user_id,
    $order_id = NULL,
    $related_tid = NULL,
    $unit_cost = NULL,
    $currency_code = NULL,
    array $data = []
  ) {
    return NULL;
  }

  /**
   * {@inheritdoc}
   */
  public function getTotalStockLevel(
    PurchasableEntityInterface $entity,
    array $locations
  ) {
    return PHP_INT_MAX;
  }

  /**
   * {@inheritdoc}
   */
  public function getIsInStock(
    PurchasableEntityInterface $entity,
    array $locations
  ) {
    return TRUE;
  }

  /**
   * {@inheritdoc}
   */
  public function getIsAlwaysInStock(PurchasableEntityInterface $entity) {
    return TRUE;
  }

  /**
   * {@inheritdoc}
   */
  public function getIsStockManaged(PurchasableEntityInterface $entity) {
    return TRUE;
  }

  /**
   * {@inheritdoc}
   */
  public function getLocationList($return_active_only = TRUE) {
    return [];
  }

  /**
   * {@inheritdoc}
   */
  public function getAvailabilityLocations(
    PurchasableEntityInterface $entity,
    Context $context
  ) {
    return [];
  }

  /**
   * {@inheritdoc}
   */
  public function getTransactionLocation(
    PurchasableEntityInterface $entity,
    $quantity,
    Context $context
  ) {
    return NULL;
  }
}
