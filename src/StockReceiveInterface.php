<?php

namespace Drupal\commerce_stock;

use Drupal\commerce\PurchasableEntityInterface;

/**
 * Defines a common interface for creating stock receive transactions.
 */
interface StockReceiveInterface {

  /**
   * Receive stock.
   *
   * @param \Drupal\commerce\PurchasableEntityInterface $entity
   *   The purchasable entity (most likely a product variation entity).
   * @param int $location_id
   *   The location ID.
   * @param string $zone
   *   The zone.
   * @param float $quantity
   *   The quantity.
   * @param float $unit_cost
   *   The unit cost.
   * @param string $currency_code
   *   The currency code.
   * @param int $user_id
   *   The user ID.
   * @param string|null $transaction_note
   *   The transaction note or NULL.
   */
  public function receiveStock(PurchasableEntityInterface $entity, $location_id, $zone, $quantity, $unit_cost, $currency_code, $user_id, $transaction_note = NULL);

}
