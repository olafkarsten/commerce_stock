<?php

namespace Drupal\commerce_stock;

use Drupal\commerce\Context;
use Drupal\commerce\PurchasableEntityInterface;
use Drupal\commerce_order\Entity\OrderInterface;

/**
 * The stock service manager interface.
 */
interface StockServiceManagerInterface {

  /**
   * Get a service relevant for the entity.
   *
   * @param \Drupal\commerce\PurchasableEntityInterface $entity
   *   The purchasable entity.
   * @param \Drupal\commerce\Context $context
   *   The context.
   * @param int|NULL $quantity
   *   The quantity or NULL
   * @param \Drupal\commerce_order\Entity\OrderInterface
   *   The commerce order to which the purchasable entity belongs, if applicable or NULL.
   *
   * @return \Drupal\commerce_stock\StockServiceInterface
   *   The appropriate stock service for the given purchasable entity.
   */
  public function getService(
    PurchasableEntityInterface $entity,
    Context $context,
    $quantity = NULL,
    OrderInterface $order = NULL
  );

}
