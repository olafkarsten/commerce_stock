<?php

namespace Drupal\commerce_stock;

use Drupal\commerce\PurchasableEntityInterface;

/**
 * Defines a common interface for creating stock return transactions.
 */
interface StockReturnInterface {

  /**
   * Stock returns.
   *
   * @param \Drupal\commerce\PurchasableEntityInterface $entity
   *   The purchasable entity (most likely a product variation entity).
   * @param int $location_id
   *   The location ID.
   * @param string $zone
   *   The zone.
   * @param float $quantity
   *   The quantity.
   * @param int $user_id
   *   The user ID.
   * @param int $order_id
   *   The order ID or NULL.
   * @param float|null $unit_cost
   *   The unit cost or NULL.
   * @param string|null $currency_code
   *   The currency code or NULL.
   * @param string|null $transaction_note
   *   The transaction note or NULL.
   * @param array $data
   *   Any arbitrary transaction related data.
   */
  public function returnStock(
    PurchasableEntityInterface $entity,
    $location_id,
    $zone,
    $quantity,
    $user_id,
    $order_id = NULL,
    $unit_cost = NULL,
    $currency_code = NULL,
    $transaction_note = NULL,
    array $data = []
  );

}
