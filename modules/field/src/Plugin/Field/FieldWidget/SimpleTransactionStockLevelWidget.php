<?php

namespace Drupal\commerce_stock_field\Plugin\Field\FieldWidget;

use Drupal\commerce\PurchasableEntityInterface;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Link;

/**
 * Plugin implementation of the 'commerce_stock_level' widget.
 *
 * @FieldWidget(
 *   id = "commerce_stock_level_simple_transaction",
 *   module = "commerce_stock_field",
 *   label = @Translation("Simple stock transaction widget"),
 *   description = @Translation("Do simple stock transactions (add, remove) on
 *   the edit form."),
 *   field_types = {
 *     "commerce_stock_level"
 *   }
 * )
 */
class SimpleTransactionStockLevelWidget extends StockLevelWidgetBase {
}
