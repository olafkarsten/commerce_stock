<?php

namespace Drupal\commerce_stock\Plugin\StockTransactionTypes;

use Drupal\Core\Form\FormStateInterface;

/**
 * Stock Return Transaction.
 *
 * @StockTransactionTypes(
 *   id = "stock_return",
 *   label = @Translation("Stock return"),
 *   description = @Translation("Transaction type to be used for returning stock."),
 *   log_message = @Translation("Stock returned with no further details."),
 * )
 */
class StockReturn extends StockIn {

  /**
   * @inheritdoc
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $form = parent::buildForm($form, $form_state);
    $form['transaction_details_form']['#description'] = $this->getDescription();

    $form['transaction_details_form']['order'] = [
      '#type' => 'entity_autocomplete',
      '#title' => $this->t('Optional order number'),
      '#target_type' => 'commerce_order',
      '#selection_handler' => 'default',
      '#weight' => 40,
    ];

    return $form;
  }

}
