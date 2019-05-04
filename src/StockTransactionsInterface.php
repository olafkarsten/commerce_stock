<?php

namespace Drupal\commerce_stock;

use Drupal\commerce\PurchasableEntityInterface;
use Drupal\commerce\Context;

/**
 * Defines a common interface for creating stock transactions.
 */
interface StockTransactionsInterface {

  const STOCK_IN = 1;
  const STOCK_OUT = 2;
  const STOCK_SALE = 4;
  const STOCK_RETURN = 5;
  const NEW_STOCK = 6;
  const MOVEMENT_FROM = 7;
  const MOVEMENT_TO = 8;

}
