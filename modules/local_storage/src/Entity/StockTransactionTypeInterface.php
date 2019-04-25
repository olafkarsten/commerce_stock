<?php

namespace Drupal\commerce_stock_local\Entity;

use Drupal\Core\Config\Entity\ConfigEntityInterface;

/**
 * Provides an interface for defining stock transaction type entities.
 */
interface StockTransactionTypeInterface extends ConfigEntityInterface {

  /**
   * Gets the stock transaction type's purchasable entity type ID.
   *
   * E.g, if transaction types of this type are used to create
   * transactions for product variations, the purchasable entity type ID
   * will be 'commerce_product_variation'.
   *
   * @return string
   *   The purchasable entity type ID.
   */
  public function getPurchasableEntityTypeId();

  /**
   * Sets the stock transaction type's purchasable entity type ID.
   *
   * @param string $purchasable_entity_type_id
   *   The purchasable entity type.
   *
   * @return $this
   */
  public function setPurchasableEntityTypeId($purchasable_entity_type_id);
}
