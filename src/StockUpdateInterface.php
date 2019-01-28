<?php

namespace Drupal\commerce_stock;

use Drupal\commerce\PurchasableEntityInterface;

/**
 * Defines a common interface for writing stock.
 */
interface StockUpdateInterface {

  /**
   * Create a stock transaction.
   *
   * @param \Drupal\commerce\PurchasableEntityInterface $entity
   *   The purchasable entity.
   * @param int $location_id
   *   The location ID.
   * @param string $zone
   *   The zone.
   * @param float $quantity
   *   The quantity.
   * @param int $transaction_type_id
   *   The transaction type ID.
   * @param int $user_id
   *   Id of the user that created the transaction. In case of an order
   *   created through the website, this is the same user id as the $order->uid.
   * @param int|null $order_id
   *   The order id the transaction belongs to or NULL if the transaction
   *   was not triggered by an order.
   * @param array $metadata
   *   Holds all the optional values those are:
   *     - related_tid, the related transaction id. (int)
   *     - unit_cost, the unit cost (float)
   *     - currency: the currency of the unit cost (string)
   *     - data: Serialized data array.
   *
   * @return int
   *   Return the ID of the transaction.
   */
  public function createTransaction(PurchasableEntityInterface $entity, $location_id, $zone, $quantity, $transaction_type_id, $user_id, $order_id = null, array $metadata);

}
