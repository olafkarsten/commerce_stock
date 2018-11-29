<?php

namespace Drupal\commerce_stock_field\Plugin\Field\FieldWidget;

use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Form\FormStateInterface;

/**
 * Plugin implementation of the 'absolute_commerce_stock_level' widget.
 *
 * @FieldWidget(
 *   id = "commerce_stock_level_absolute",
 *   module = "commerce_stock_field",
 *   label = @Translation("Absolute stock level"),
 *   description = @Translation("Sets the absolute stock level. You will loose
 *   all the glamour of transaction based stock handling. We recommend using
 *   the simple stock transaction widget instead. Learn more in the
 *   documentation."), field_types = {
 *     "commerce_stock_level"
 *   }
 * )
 */
class AbsoluteStockLevelWidget extends StockLevelWidgetBase {

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
    $field = $items->first();
    $level = $field->available_stock;
    $element['stock_level'] = array_merge(
      $element['adjustment'],
      [
        '#title' => $this->t('Absolute stock level settings'),
        '#description' => $this->t('Sets the stock level. Current stock level: @stock_level. Note: Under the hood we create a transaction. Setting the absolute stock level may end in unexpected results. Learn more about transactional inventory management in the docs.', ['@stock_level' => $level]),
        '#min' => 0,
        // We don't use zero as default, because its a valid value and would reset
        // the stock level to 0.
        '#default_value' => NULL,

      ]);
    unset($element['adjustment']);
    return $element;
  }

  /**
   * {@inheritdoc}
   */
  public function massageFormValues(array $values, array $form, FormStateInterface $form_state) {
    if (empty($values[0]['stock_level']) && $values[0]['stock_level'] !== "0") {
      $values[0]['adjustment'] = NULL;
      return $values;
    }
    $new_level = $values[0]['stock_level'];
    $current_level = $this->stockServiceManager->getStockLevel($values[0]['stocked_entity']);
    $values[0]['adjustment'] = $new_level - $current_level;
    return $values;
  }

  /**
   * @inheritdoc
   */
  protected function getHelpText() {
    return $this->t("Set the absolute stock level. We don't recommend using this widget. Read the docs to learn why.");
  }

}
