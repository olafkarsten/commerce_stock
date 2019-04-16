<?php

namespace Drupal\commerce_stock;

use Drupal\commerce\PurchasableEntityInterface;

/**
 * Defines a common interface for generic stock out transactions.
 */
interface StockOutInterface {

  /**
   * Removing stock.
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
   * @param float|null $unit_cost
   *   The unit cost.
   * @param string|null $currency_code
   *   The currency code.
   * @param string|null $transaction_note
   *   The transaction note or NULL.
   * @param int|null $order_id
   *   The order id the transaction belongs to or NULL if the transaction
   *   was not triggered by an order.
   * @param array $metadata
   *   Holds all the optional values those are:
   *     - related_tid: the related transaction id. (int)
   *     - unit_cost: the unit cost (float)
   *     - currency: the currency of the unit cost (string)
   *     - data: array of arbitrary data.
   */
  public function removeStock(PurchasableEntityInterface $entity, $location_id, $zone, $quantity, $user_id, $transaction_note = NULL, $order_id = NULL, array $metadata = []);

}
