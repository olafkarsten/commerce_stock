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
    $form['transaction_details_form']['order']['#title'] = $this->t('The order, this return belongs to.');
    return $form;
  }

  public function validateForm(array $form, FormStateInterface $form_state) {
    // TODO: Validate the stock return quantity against the order, if we have one.
    parent::validateForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array $form, FormStateInterface $form_state) {
    $data = parent::extractTransactionData($form, $form_state);
    $order_id = $form_state->getValue(['transaction_details_form', 'order']);
    $transaction_note = empty($data['transaction_note']) ?: $this->getTransactionDefaultLogMessage();
    $metadata = array_merge(['message' => $transaction_note], $data['metadata']);
    try {
      $this->createTransaction(
        $data['source']['location'],
        $data['source']['zone'],
        $data['quantity'],
        $this->getPluginId(),
        $data['user_id'],
        $order_id,
        $metadata
      );
    } catch (\Exception $e){

    }

  }

}
