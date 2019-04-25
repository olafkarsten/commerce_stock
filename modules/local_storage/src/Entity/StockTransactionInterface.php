<?php

namespace Drupal\commerce_stock_local\Entity;

use Drupal\commerce_order\Entity\OrderInterface;
use Drupal\commerce_price\Price;
use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\user\EntityOwnerInterface;

/**
 * Provides an interface for defining Stock transaction entities.
 *
 * @ingroup commerce_stock_local
 */
interface StockTransactionInterface extends ContentEntityInterface, EntityOwnerInterface {

  /**
   * Gets the purchasable entity.
   *
   * @return \Drupal\commerce\PurchasableEntityInterface|null
   *   The purchasable entity, or NULL.
   */
  public function getPurchasableEntity();

  /**
   * Gets the purchasable entity ID.
   *
   * @return int
   *   The purchasable entity ID.
   */
  public function getPurchasableEntityId();

  /**
   * Sets the transaction location
   *
   * @param \Drupal\commerce_stock_local\Entity\LocalStockLocationInterface $location
   *
   * @return $this
   */
  public function setLocation(LocalStockLocationInterface $location);

  /**
   * Gets the transaction location.
   *
   * @return \Drupal\commerce_stock_local\Entity\LocalStockLocationInterface
   *    The location.
   */
  public function getLocation();

  /**
   * Set the transaction Zone/Bin.
   *
   * @param string $zone
   *   The transaction zone.
   *
   * @return $this
   */
  public function setZone($zone);

  /**
   * Get the transaction zone
   *
   * @return string|null
   *    The transaction zone or NULL.
   */
  public function getZone();

  /**
   * Set the related order.
   *
   * @param \Drupal\commerce_order\Entity\OrderInterface $order
   *
   * @return $this
   */
  public function setOrder(OrderInterface $order);

  /**
   * Get the related order.
   *
   * @return \Drupal\commerce_order\Entity\OrderInterface|NULL
   *   The related order or NULL.
   */
  public function getOrder();

  /**
   * Set a related transaction.
   *
   * @param \Drupal\commerce_stock_local\Entity\StockTransaction $transaction
   *
   * @return $this
   */
  public function setRelatedTransaction(StockTransaction $transaction);

  /**
   * Get a related transaction.
   *
   * @return \Drupal\commerce_stock_local\Entity\StockTransaction|NULL
   *   The stock transaction or NULL.
   */
  public function getRelatedTransaction();

  /**
   * Sets the transaction quantity.
   *
   * @param string $quantity
   *   The transaction quantity.
   *
   * @return $this
   */
  public function setQuantity($quantity);

  /**
   * Gets the transaction quantity.
   *
   * @return string
   *   The transaction quantity
   */
  public function getQuantity();

  /**
   * Gets the transaction unit cost.
   *
   * @return \Drupal\commerce_price\Price|null
   *   The transaction unit cost, or NULL.
   */
  public function getTransactionUnitCost();

  /**
   * Sets the transaction unit cost.
   *
   * @param \Drupal\commerce_price\Price $unit_cost
   *   The transaction unit cost.
   *
   * @return $this
   */
  public function setTransactionUnitCost(Price $unit_cost);

  /**
   * Gets an transaction data value with the given key.
   *
   * @param string $key
   *   The key.
   *
   * @return mixed
   *   The value.
   */
  public function getData($key);

  /**
   * Sets an transaction data value with the given key.
   *
   * @param string $key
   *   The key.
   * @param mixed $value
   *   The value.
   *
   * @return $this
   */
  public function setData($key, $value);

  /**
   * Sets an transaction log message.
   *
   * @param string $message
   *   The message.
   *
   * @return $this
   */
  public function setTransactionLogMessage($message);

  /**
   * Gets the transaction log message.
   *
   * @return string
   *   The message.
   */
  public function getTransactionLogMessage();

  /**
   * Gets the Stock transaction creation timestamp.
   *
   * @return int
   *   Creation timestamp of the Stock transaction.
   */
  public function getCreatedTime();

  /**
   * Sets the Stock transaction creation timestamp.
   *
   * @param int $timestamp
   *   The Stock transaction creation timestamp.
   *
   * @return \Drupal\commerce_stock_local\Entity\StockTransactionInterface
   *   The called Stock transaction entity.
   */
  public function setCreatedTime($timestamp);

}
