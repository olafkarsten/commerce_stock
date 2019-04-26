<?php

namespace Drupal\commerce_stock_local\Form;

use Drupal\commerce\PurchasableEntityInterface;
use Drupal\Core\Entity\EntityForm;
use Drupal\Core\Entity\EntityTypeInterface;
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

    // Prepare the list of purchasable entity types.
    $entity_types = $this->entityTypeManager->getDefinitions();
    $purchasable_entity_types = array_filter($entity_types, function (EntityTypeInterface $entity_type) {
      return $entity_type->entityClassImplements(PurchasableEntityInterface::class);
    });
    $purchasable_entity_types = array_map(function (EntityTypeInterface $entity_type) {
      return $entity_type->getLabel();
    }, $purchasable_entity_types);

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

    $form['purchasableEntityType'] = [
      '#type' => 'select',
      '#title' => $this->t('Purchasable entity type'),
      '#default_value' => $commerce_stock_transaction_type->getPurchasableEntityTypeId(),
      '#options' => $purchasable_entity_types,
      '#empty_value' => '',
      '#disabled' => !$commerce_stock_transaction_type->isNew(),
    ];

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
