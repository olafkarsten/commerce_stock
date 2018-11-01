<?php

namespace Drupal\commerce_stock_field\Plugin\Field\FieldWidget;

use Drupal\commerce\PurchasableEntityInterface;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Form\FormStateInterface;

/**
 * Plugin implementation of the 'absolute_commerce_stock_level' widget.
 *
 * @FieldWidget(
 *   id = "commerce_stock_level_absolute",
 *   module = "commerce_stock_field",
 *   label = @Translation("Absolute stock level widget"),
 *   description = @Translation("Sets the absolute stock level. You will loose
 *   all the glamour of transaction based stock handling. We recommend using
 *   the simple stock transaction widget. Learn more in the documentation."),
 *   field_types = {
 *     "commerce_stock_level"
 *   }
 * )
 */
class AbsoluteStockLevelWidget extends StockLevelWidgetBase {

  /**
   * Submits the form.
   *
   * @param $form
   *   The form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The form state.
   */
  public function submitForm($form, FormStateInterface $form_state) {
    parent::submitForm($form, $form_state);
    // @ToDo figure out the correct value for level to put in the message here.
    $this->messenger->addMessage(t('The stock level was set to %level.', ['%level' => 'TODO']));
  }

  /**
   * {@inheritdoc}
   */
  public function settingsSummary() {
    $summary = [];
    $summary[] = $this->t('Transaction note: @transaction_note', ['@transaction_note' => $this->getSetting('transaction_note') ? 'Yes' : 'No']);
    return $summary;
  }

  /**
   * {@inheritdoc}
   */
  public function formElement(
    FieldItemListInterface $items,
    $delta,
    array $element,
    array &$form,
    FormStateInterface $form_state
  ) {
    $field = $items->first();
    $entity = $items->getEntity();

    // @ToDo Use ::isApplicable instead.
    if (!($entity instanceof PurchasableEntityInterface)) {
      // No stock if this is not a purchasable entity.
      return [];
    }
    if ($entity->isNew()) {
      // We can not work with entities before they are fully created.
      return [];
    }

    // If not a valid context.
    if (!$this->stockServiceManager->isValidContext($entity)) {
      // Return an empty form.
      return [];
    }

    // Get the available stock level.
    $level = $field->available_stock;

    $element = [];
    if (empty($entity->id())) {
      // We don't have a product ID as yet.
      $element['label'] = [
        '#type' => 'html_tag',
        '#tag' => 'strong',
        '#value' => $this->t('In order to set the stock level you need to save the product first!'),
      ];
    }
    else {
      $element['stocked_entity'] = [
        '#type' => 'value',
        '#value' => $entity,
      ];
      $element['value'] = [
        '#title' => $this->t('Set the stock level'),
        '#description' => $this->t(''),
        '#type' => 'textfield',
        '#default_value' => $level,
        '#size' => 10,
        '#maxlength' => 12,
      ];

      // @ToDo Allow for setting a default value.
      if ($this->getSetting('transaction_note')) {
        $element['stock_transaction_note'] = [
          '#title' => $this->t('Transaction note'),
          '#description' => $this->t('Add a note to this transaction.'),
          '#type' => 'textfield',
          '#default_value' => '',
          '#size' => 20,
        ];
      }
    }

    return $element;
  }

  /**
   * Simple stock form - Used to update the stock level.
   *
   * @todo: This is not go live ready code,
   */
  public function validateSimple($element, FormStateInterface $form_state) {
    if (!is_numeric($element['#value'])) {
      $form_state->setError($element, $this->t('Stock must be a number.'));
      return;
    }
    // @todo Needs to mark element as needing updating? Updated qty??
  }

  /**
   * Validates a basic stock field widget form.
   */
  public function validateBasic($element, FormStateInterface $form_state) {
    // @to do.
    return TRUE;
  }

  /**
   * Submits the form.
   */
  public function submitAll(array &$form, FormStateInterface $form_state) {
    drupal_set_message($this->t('updated STOCK!!'));
  }

}
