<?php

namespace Drupal\commerce_stock\Plugin\StockTransactionTypes;

use Drupal\Core\Form\FormStateInterface;

/**
 * Generic Stock In Transaction.
 *
 * @StockTransactionTypes(
 *   id = "stock_sale",
 *   label = @Translation("Stock sale"),
 *   description = @Translation("Transaction type usally used for customer orders."),
 *   log_message = @Translation("Stock transaction based on an order."),
 * )
 */
class StockSale extends StockOut {

  /**
   * @inheritdoc
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $form = parent::buildForm($form, $form_state);
    $form['transaction_details_form']['#description'] = $this->getDescription();
    return $form;
  }

}
