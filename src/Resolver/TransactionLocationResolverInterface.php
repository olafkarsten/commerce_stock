<?php

namespace Drupal\commerce_stock\Resolver;

use Drupal\commerce\PurchasableEntityInterface;
use Drupal\commerce\Context;

/**
 * Defines the interface for transaction location resolvers.
 */
interface TransactionLocationResolverInterface {

  /**
   * Resolves the transaction location for a purchasable entity.
   *
   * @param \Drupal\commerce\PurchasableEntityInterface $entity
   *   The purchasable entity.
   * @param int $quantity
   *   The quantity.
   * @param \Drupal\commerce\Context $context
   *   The context.
   *
   * @return \Drupal\commerce_stock\StockLocationInterface|null
   *   The location that should be used for a stock transaction, if resolved.
   *   Otherwise NULL, indicating that the next resolver in the chain should be called.
   */
  public function resolve(
    PurchasableEntityInterface $entity,
    $quantity,
    Context $context
  );

}
