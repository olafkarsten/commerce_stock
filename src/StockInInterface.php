<?php

namespace Drupal\commerce_stock;

use Drupal\commerce\PurchasableEntityInterface;

/**
 * Defines a common interface for a generic transaction that adds stock.
 */
interface StockInInterface {

  /**
   * Adding stock.
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
   * @param string|null $transaction_note
   *   The transaction note or NULL.
   * @param float $unit_cost
   *   The unit cost.
   * @param string $currency_code
   *   The currency code.
   * @param string|int|null $related_tid
   *   Transaction id of a related transaction e.g. in case of transfering
   *   stock from one bin to another.
   * @param array $data
   *   Amy arbitrary transaction related data.
   */
  public function addStock(
    PurchasableEntityInterface $entity,
    $location_id,
    $zone,
    $quantity,
    $transaction_type_id,
    $user_id,
    $order_id = NULL,
    $transaction_note = NULL,
    $unit_cost = NULL,
    $currency_code = NULL,
    $related_tid = NULL,
    array $data = []
  );

}
