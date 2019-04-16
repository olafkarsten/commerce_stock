<?php

namespace Drupal\commerce_stock_ui;


use Drupal\commerce_stock\Plugin\StockTransactionTypes;
/**
 * Test transaction type
 *
 * @StockTransactionTypeForm(
 *   id = "test_transaction_type_form",
 *   label = @Translation("TEST TRANSACTION TYPE"),
 *   description = @Translation("Transaction type form sally used for testing."),
 *   log_message = @Translation("Stock test transaction type default log message."),
 * )
 */
class TestTransactionType extends TransactionTypeBase {

  /**
   * @inheritdoc
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $form = parent::buildForm($form, $form_state);
    $form['transaction_details_form']['#description'] = $this->getDescription();
    return $form;
  }

}
