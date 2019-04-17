<?php

namespace Drupal\commerce_stock_ui\Plugin\StockTransactionTypeForm;

use Drupal\Core\Form\FormStateInterface;

/**
 * Stock Move Transaction.
 *
 * @StockTransactionTypeForm(
 *   id = "stock_move",
 *   label = @Translation("Stock move"),
 *   description = @Translation("Use this one to move stock between different locations and/or zones."),
 * )
 */
class StockMove extends TransactionsTypeFormBase {

  /**
   * @inheritdoc
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $form = parent::buildForm($form, $form_state);

    $locationOptions = $this->getLocationOptions($form['locations']['#value']);

    $form['transaction_details_form']['source'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('Source'),
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
    $form['transaction_details_form']['target'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('Target'),
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
   * @inheritdoc
   */
  public function submitForm(array $form, FormStateInterface $form_state) {
    // TODO: Implement submitForm() method.
  }

}
