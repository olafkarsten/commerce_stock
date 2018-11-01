<?php

namespace Drupal\commerce_stock_field\Plugin\Field\FieldWidget;

use Drupal\commerce\PurchasableEntityInterface;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Link;

/**
 * Plugin implementation of the 'commerce_stock_level' widget.
 *
 * @FieldWidget(
 *   id = "commerce_stock_level_simple_transaction",
 *   module = "commerce_stock_field",
 *   label = @Translation("Simple transaction stock level widget"),
 *   description = @Translation("Do simple stock transactions (add, remove) on
 *   the edit form."),
 *   field_types = {
 *     "commerce_stock_level"
 *   }
 * )
 */
class SimpleTransactionStockLevelWidget extends StockLevelWidgetBase {

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
  public function settingsForm(array $form, FormStateInterface $form_state) {
    $element = [];
    $element['transaction_note'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Provide note'),
      '#default_value' => $this->getSetting('transaction_note'),
      '#description' => $this->t('Provide an input box for a transaction note.'),
      '#states' => [
        'invisible' => [
          'select[name="fields[field_stock_level][settings_edit_form][settings][entry_system]"]' => ['value' => 'transactions'],
        ],
      ],
    ];
    return $element;
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
    $element = parent::formElement($items, $delta, $element, $form, $form_state);
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
