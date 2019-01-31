<?php

namespace Drupal\commerce_stock;

use Drupal\commerce\PurchasableEntityInterface;

/**
 * Defines a common interface for creating stock transactions.
 */
interface StockMoveInterface {

  /**
   * Move stock.
   *
   * @param \Drupal\commerce\PurchasableEntityInterface $entity
   *   The purchasable entity (most likely a product variation entity).
   * @param int $from_location_id
   *   The source location ID.
   * @param int $to_location_id
   *   The target location ID.
   * @param string $from_zone
   *   The source zone.
   * @param string $to_zone
   *   The target zone.
   * @param float $quantity
   *   The quantity.
   * @param float $unit_cost
   *   The unit cost.
   * @param string $currency_code
   *   The currency code.
   * @param int $user_id
   *   The user ID.
   * @param string $message
   *   The message.
   */
  public function moveStock(PurchasableEntityInterface $entity, $from_location_id, $to_location_id, $from_zone, $to_zone, $quantity, $unit_cost, $currency_code, $user_id, $message = NULL);

}
