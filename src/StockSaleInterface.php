<?php

namespace Drupal\commerce_stock;

use Drupal\commerce\PurchasableEntityInterface;

/**
 * Defines a common interface for creating stock sale transactions.
 */
interface StockSaleInterface {

  /**
   * Sell stock.
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
   * @param int $order_id
   *   The order ID.
   * @param int $user_id
   *   The user ID.
   * @param string $message
   *   The message.
   */
  public function sellStock(PurchasableEntityInterface $entity, $location_id, $zone, $quantity, $unit_cost, $currency_code, $order_id, $user_id, $message = NULL);

}
