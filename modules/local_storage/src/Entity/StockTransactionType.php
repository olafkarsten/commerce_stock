<?php

namespace Drupal\commerce_stock_local\Entity;

use Drupal\Core\Config\Entity\ConfigEntityBundleBase;

/**
 * Defines the Stock transaction type entity.
 *
 * @ConfigEntityType(
 *   id = "commerce_stock_transaction_type",
 *   label = @Translation("Stock transaction type"),
 *   handlers = {
 *     "view_builder" = "Drupal\Core\Entity\EntityViewBuilder",
 *     "list_builder" = "Drupal\commerce_stock_local\StockTransactionTypeListBuilder",
 *     "form" = {
 *       "add" = "Drupal\commerce_stock_local\Form\StockTransactionTypeForm",
 *       "edit" = "Drupal\commerce_stock_local\Form\StockTransactionTypeForm",
 *       "delete" = "Drupal\commerce_stock_local\Form\StockTransactionTypeDeleteForm"
 *     },
 *     "route_provider" = {
 *      "default" = "Drupal\Core\Entity\Routing\AdminHtmlRouteProvider",
 *      "delete-multiple" = "Drupal\entity\Routing\DeleteMultipleRouteProvider",
 *     },
 *   },
 *   config_prefix = "commerce_stock_transaction_type",
 *   admin_permission = "administer commerce stock transaction entities",
 *   bundle_of = "commerce_stock_transaction",
 *   entity_keys = {
 *     "id" = "id",
 *     "label" = "label",
 *     "uuid" = "uuid"
 *   },
 *   links = {
 *     "canonical" = "/admin/commerce/config/commerce_stock_transaction_type/{commerce_stock_transaction_type}",
 *     "add-form" = "/admin/commerce/config/commerce_stock_transaction_type/add",
 *     "edit-form" = "/admin/commerce/config/commerce_stock_transaction_type/{commerce_stock_transaction_type}/edit",
 *     "delete-form" = "/admin/commerce/config/commerce_stock_transaction_type/{commerce_stock_transaction_type}/delete",
 *     "collection" = "/admin/commerce/config/commerce_stock_transaction_type"
 *   }
 * )
 */
class StockTransactionType extends ConfigEntityBundleBase implements StockTransactionTypeInterface {

  /**
   * The Stock transaction type ID.
   *
   * @var string
   */
  protected $id;

  /**
   * The Stock transaction type label.
   *
   * @var string
   */
  protected $label;

  /**
   * The purchasable entity type ID.
   *
   * @var string
   */
  protected $purchasableEntityType;

  /**
   * {@inheritdoc}
   */
  public function getPurchasableEntityTypeId() {
    return $this->purchasableEntityType;
  }

  /**
   * {@inheritdoc}
   */
  public function setPurchasableEntityTypeId($purchasable_entity_type_id) {
    $this->purchasableEntityType = $purchasable_entity_type_id;
    return $this;
  }

}
