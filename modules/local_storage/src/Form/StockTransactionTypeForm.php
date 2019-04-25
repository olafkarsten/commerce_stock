<?php

namespace Drupal\commerce_stock_local\Form;

use Drupal\Core\Entity\EntityForm;
use Drupal\Core\Form\FormStateInterface;

/**
 * Class StockTransactionTypeForm.
 */
class StockTransactionTypeForm extends EntityForm {

  /**
   * {@inheritdoc}
   */
  public function form(array $form, FormStateInterface $form_state) {
    $form = parent::form($form, $form_state);

    $commerce_stock_transaction_type = $this->entity;
    $form['label'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Label'),
      '#maxlength' => 255,
      '#default_value' => $commerce_stock_transaction_type->label(),
      '#description' => $this->t("Label for the Stock transaction type."),
      '#required' => TRUE,
    ];

    $form['id'] = [
      '#type' => 'machine_name',
      '#default_value' => $commerce_stock_transaction_type->id(),
      '#machine_name' => [
        'exists' => '\Drupal\commerce_stock_local\Entity\StockTransactionType::load',
      ],
      '#disabled' => !$commerce_stock_transaction_type->isNew(),
    ];

    /* You will need additional form elements for your custom properties. */

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function save(array $form, FormStateInterface $form_state) {
    $commerce_stock_transaction_type = $this->entity;
    $status = $commerce_stock_transaction_type->save();

    switch ($status) {
      case SAVED_NEW:
        drupal_set_message($this->t('Created the %label Stock transaction type.', [
          '%label' => $commerce_stock_transaction_type->label(),
        ]));
        break;

      default:
        drupal_set_message($this->t('Saved the %label Stock transaction type.', [
          '%label' => $commerce_stock_transaction_type->label(),
        ]));
    }
    $form_state->setRedirectUrl($commerce_stock_transaction_type->toUrl('collection'));
  }

}
