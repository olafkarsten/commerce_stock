<?php

namespace Drupal\commerce_stock\Plugin\Commerce\StockService;

use Drupal\commerce\Context;
use Drupal\commerce\PurchasableEntityInterface;
use Drupal\Component\Plugin\ConfigurablePluginInterface;
use Drupal\Component\Plugin\DerivativeInspectionInterface;
use Drupal\Core\Plugin\PluginFormInterface;
use Drupal\Core\Plugin\PluginWithFormsInterface;

/**
 * Defines the base interface for stock services.
 */
interface StockServiceInterface extends PluginWithFormsInterface, ConfigurablePluginInterface, PluginFormInterface, DerivativeInspectionInterface {

  /**
   * Gets the stock service label.
   *
   * The label is admin-facing and usually includes the name of the used API.
   * For example: "Local storage (Drupal/SQL)".
   *
   * @return mixed
   *   The stock service name.
   */
  public function getLabel();

  /**
   * Gets the stock service display label.
   *
   * The display label is customer-facing and more generic.
   * For example: "Commerce stock local storage".
   *
   * @return string
   *   The stock service display label.
   */
  public function getDisplayLabel();

  /**
   * Gets the stock checker.
   *
   * @return \Drupal\commerce_stock\StockCheckInterface
   *   The stock checker.
   */
  public function getStockChecker();

  /**
   * Gets the stock updater.
   *
   * @return \Drupal\commerce_stock\StockUpdateInterface
   *   The stock updater.
   */
  public function getStockUpdater();

  /**
   * Get the location for automatic stock allocation.
   *
   * This is normally a designated location to act as the main warehouse.
   * This can also be a location worked out in realtime using the provided
   * context (order & customer), entity and the quantity requested.
   *
   * @param \Drupal\commerce\PurchasableEntityInterface $entity
   *   The purchasable entity.
   * @param int $quantity
   *   The quantity.
   *
   * @param \Drupal\commerce\Context $context
   *   The context containing the customer & store.
   *
   * @return \Drupal\commerce_stock\StockLocationInterface|null
   *   The stock location, or null if none is available.
   */
  public function getTransactionLocation(
    PurchasableEntityInterface $entity,
    $quantity,
    Context $context
  );

  /**
   * Get locations holding stock.
   *
   * The locations should be filtered for the provided context and purchasable
   * entity.
   *
   * @param \Drupal\commerce\PurchasableEntityInterface $entity
   *   The purchasable entity.
   *
   * @param \Drupal\commerce\Context $context
   *   The context containing the customer & store.
   *
   * @return \Drupal\commerce_stock\StockLocationInterface[]
   *   List of relevant locations.
   */
  public function getAvailabilityLocations(
    PurchasableEntityInterface $entity,
    Context $context
  );

}
