<?php

namespace Drupal\commerce_stock\Plugin\StockTransactionTypes;

use Drupal\Core\Form\FormStateInterface;

/**
 * Generic Stock In Transaction.
 *
 * @StockTransactionTypes(
 *   id = "stock_receive",
 *   label = @Translation("Stock receive"),
 *   description = @Translation("Transaction type to add stock most often due to a delivery."),
 *   log_message = @Translation("Stock received with no further details."),
 * )
 */
class StockReceive extends StockIn {

  /**
   * @inheritdoc
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $form = parent::buildForm($form, $form_state);
    $form['transaction_details_form']['#description'] = $this->getDescription();
    return $form;
  }

  /**
   * @inheritdoc
   */
  public function submitForm(array $form, FormStateInterface $form_state) {
    $data = parent::extractTransactionData($form, $form_state);
    $transaction_note = empty($data['transaction_note']) ?: $this->getTransactionDefaultLogMessage();
    $metadata = array_merge(['message' => $transaction_note], $data['metadata']);

    $this->createTransaction(
      $data['source']['location'],
      $data['source']['zone'],
      $data['quantity'],
      $this->getPluginId(),
      $data['user_id'],
      $data['order_id'],
      $metadata
    );
  }

}
