<?php

namespace Drupal\commerce_stock_local;

use Drupal\commerce\PurchasableEntityInterface;
use Drupal\Core\Entity\Sql\SqlEntityStorageInterface;

/**
 * Defines the interface for local stock location storage.
 */
interface StockLocationStorageInterface extends SqlEntityStorageInterface {

  /**
   * Loads the enabled locations for the given Purchasable Entity.
   *
   * Enabled variations are active stock locations that have
   * been filtered through the FILTER_STOCK_LOCATIONS event.
   *
   * @param \Drupal\commerce\PurchasableEntityInterface $entity
   *   The purchasable entity.
   *
   * @return \Drupal\commerce_stock_local\Entity\StockLocationInterface[]
   *   The enabled stock locations.
   */
  public function loadEnabled(PurchasableEntityInterface $entity);

}
