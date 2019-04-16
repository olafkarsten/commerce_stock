<?php

namespace Drupal\commerce_stock\Plugin\StockTransactionTypes;

use Drupal\Core\Form\FormStateInterface;

/**
 * Generic Stock In Transaction.
 *
 * @StockTransactionTypes(
 *   id = "stock_out",
 *   label = @Translation("Basic stock out"),
 *   description = @Translation("A generic transaction type to remove stock for an item."),
 *   log_message = @Translation("Stock removed with no further details."),
 * )
 */
class StockOut extends TransactionTypeBase {

  /**
   * @inheritdoc
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $form = parent::buildForm($form, $form_state);

    $locationOptions = $this->getLocationOptions($form['locations']['#value']);

    $form['transaction_details_form']['#description'] = $this->getDescription();

    $form['transaction_details_form']['target'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('Location'),
      '#weight' => 30,
    ];
    $form['transaction_details_form']['target']['location'] = [
      '#type' => 'select',
      '#title' => $this->t('To: Location'),
      '#description' => $this->t('Target location for the stock transfer.'),
      '#options' => $locationOptions,
      '#access' => count($locationOptions) > 1,
    ];
    $form['transaction_details_form']['target']['zone'] = [
      '#type' => 'textfield',
      '#title' => $this->t('To: Zone/Bins'),
      '#description' => $this->t('The location zone (bins) to move the stock to.'),
      '#size' => 60,
      '#maxlength' => 50,
    ];

    $form['transaction_details_form']['transaction_qty']['#min'] = -1 * $form['transaction_details_form']['transaction_qty']['step'];
    return $form;
  }

  /**
   * @inheritdoc
   */
  public function getTransactionDefaultLogMessage() {
    $message = parent::getTransactionDefaultLogMessage();
    if ($message) {
      return $message;
    }
    return $this->pluginDefinition['log_message'] ? $this->pluginDefinition['log_message']->render() : NULL;
  }

  /**
   * {@inheritdoc}
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
