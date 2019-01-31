<?php

namespace Drupal\commerce_stock\Plugin\StockTransactionTypes;

use Drupal\Core\Form\FormStateInterface;

/**
 * Generic Stock In Transaction.
 *
 * @StockTransactionTypes(
 *   id = "stock_in",
 *   label = @Translation("Basic stock in"),
 *   description = @Translation("Generic transaction type to add stock for an item."),
 *   log_message = @Translation("Stock added with no further details."),
 * )
 */
class StockIn extends TransactionTypeFormBase {

  /**
   * @inheritdoc
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $form = parent::buildForm($form, $form_state);

    $locationOptions = $this->getLocationOptions($form['locations']);

    $form['transaction_details_form']['#description'] = $this->getDescription();

    $form['transaction_details_form']['source'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('Location'),
      '#weight' => 20,
    ];

    $form['transaction_details_form']['source']['location'] = [
      '#type' => 'select',
      '#title' => $this->t('From: Location'),
      '#description' => $this->t('Source location for the stock transfer.'),
      '#options' => $locationOptions,
      '#access' => count($locationOptions) > 1,
    ];

    $form['transaction_details_form']['source']['zone'] = [
      '#type' => 'textfield',
      '#title' => $this->t('From: Zone/Bins'),
      '#description' => $this->t('The location zone (bins) to take the stock from.'),
      '#size' => 60,
      '#maxlength' => 50,
    ];

    $form['transaction_details_form']['transaction_qty']['#min'] = $form['transaction_details_form']['transaction_qty']['step'];
    $form['transaction_details_form']['transaction_note']['#default_value'] = $this->getTransactionDefaultLogMessage();
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

}
