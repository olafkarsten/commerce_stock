<?php

namespace Drupal\commerce_stock_field\Plugin\Field\FieldType;

use Drupal\commerce_stock\StockTransactionsInterface;
use Drupal\Core\Field\FieldException;
use Drupal\Core\Field\FieldItemBase;
use Drupal\Core\Field\FieldStorageDefinitionInterface;
use Drupal\Core\TypedData\DataDefinition;
use Drupal\Core\TypedData\DataDefinitionInterface;
use Drupal\Core\TypedData\TypedDataInterface;

/**
 * Plugin implementation of the 'commerce_stock_field' field type.
 *
 * @FieldType(
 *   id = "commerce_stock_level",
 *   label = @Translation("Stock level"),
 *   module = "commerce_stock_field",
 *   description = @Translation("Stock level"),
 *   default_widget = "commerce_stock_level_simple_transaction",
 *   default_formatter = "commerce_stock_level_simple",
 *   cardinality = 1,
 * )
 */
class StockLevel extends FieldItemBase {

  /**
   * {@inheritdoc}
   */
  public static function schema(
    FieldStorageDefinitionInterface $field_definition
  ) {
    // We don't need storage but as computed fields are not properly implemented
    // We will use a dummy column that should be ignored.
    // @see https://www.drupal.org/node/2392845.
    return [
      'columns' => [
        'value' => [
          'type' => 'numeric',
          'size' => 'normal',
          'precision' => 19,
          'scale' => 4,
          'not null' => FALSE,
        ],
      ],
    ];
  }

  /**
   * {@inheritdoc}
   */
  public static function propertyDefinitions(
    FieldStorageDefinitionInterface $field_definition
  ) {
    // @todo What's the difference/utility between both fields?
    $properties['value'] = DataDefinition::create('float')
      ->setLabel(t('Available stock'));
    $properties['available_stock'] = DataDefinition::create('float')
      ->setLabel(t('Available stock'))
      ->setComputed(TRUE)
      ->setReadOnly(TRUE)
      ->setClass('Drupal\commerce_stock_field\StockLevelProcessor')
      ->setSetting('stock level', 'summary');

    return $properties;
  }

  /**
   * {@inheritdoc}
   */
  public function isEmpty() {
    $value = $this->get('value')->getValue();
    return $value === NULL;
  }

  /**
   * This updates the stock based on parameters set by the stock widget.
   *
   * For computed fields we didn't find a chance to trigger the transaction,
   * other than in ::setValue(). ::postSave() is not called for computed fields.
   */
  public function setValue($values, $notify = TRUE) {
    // To prevent multiple stock transactions, we need to track the processing.
    static $processed = [];

    // Supports absolute values being passed in directly, i.e.
    // programmatically.
    if (!is_array($values)) {
      $value = filter_var($values, FILTER_VALIDATE_FLOAT);
      if ($value) {
        $values = ['adjustment' => $value];
      }
      else {
        return;
      }
    }

    if (!empty($this->getEntity())) {

      $entity = $this->getEntity();
      if (empty($entity->id())) {
        return;
      }

      // Entity allready processed.
      if (isset($processed[$entity->getEntityTypeId() . $entity->id()])) {
        return;
      }

      $processed[$entity->getEntityTypeId() . $entity->id()] = TRUE;

      $transaction_qty = 0;
      $stockServiceManager = \Drupal::service('commerce_stock.service_manager');

      // Supports values being passed in directly, i.e.
      // programmatically.
      if (!is_array($values)) {
        $values = ['adjustment' => $values];
      }

      if ($values['absolute_stock_level']) {
        // Prevent deleting any stock in case of no adjustment value.
        if(empty($values['adjustment']) && $values['adjustment'] !== "0") {
          return;
        }
        $new_level = $values['adjustment'];
        $level = $stockServiceManager->getStockLevel($entity);
        $transaction_qty = $new_level - $level;
      }
      else {
        $transaction_qty = $values['adjustment'];
      }

      /**
      if (isset($values['stock'])) {
        if (empty($values['stock']['entry_system'])) {
          $transaction_qty = (int) $values['stock']['value'];
        }
        // Or supports a field widget entry system.
        else {
          switch ($values['stock']['entry_system']) {
            case 'simple':
              $new_level = $values['stock']['value'];
              $level = $stockServiceManager->getStockLevel($entity);
              $transaction_qty = $new_level - $level;
              break;

            case 'basic':
              $transaction_qty = (int) $values['stock']['adjustment'];
              break;
          }
        }
      }
       */

      // Some basic validation.
      $transaction_qty = filter_var((float) ($transaction_qty), FILTER_VALIDATE_FLOAT);

      if ($transaction_qty) {
        $transaction_type = ($transaction_qty > 0) ? StockTransactionsInterface::STOCK_IN : StockTransactionsInterface::STOCK_OUT;
        // @todo Add zone and location to form.
        /** @var \Drupal\commerce_stock\StockLocationInterface $location */
        $location = $stockServiceManager->getTransactionLocation($stockServiceManager->getContext($entity), $entity, $transaction_qty);
        if (empty($location)) {
          // If we have no location, something isn't properly configured.
          throw new \RuntimeException('The StockServiceManager didn\'t return a location');
        }
        $zone = isset($values['zone']) ?: '';
        $unit_cost = NULL;
        if(isset($values['unit_cost']['amount'])){
           $unit_cost = filter_var((float) ($values['unit_cost']['amount']), FILTER_VALIDATE_FLOAT);
           $unit_cost ?: NULL;
        };
        $currency_code = isset($values['unit_cost']['currency_code']) ?: NULL;
        $transaction_note = !empty($values['stock_transaction_note']) ? $values['stock_transaction_note'] : 'Transaction issued by stock level field.';
        $metadata = ['data' => ['message' => $transaction_note]];
        $stockServiceManager->createTransaction($entity, $location->getId(), $zone, $transaction_qty, (float) $unit_cost, $currency_code, $transaction_type, $metadata);
      }
    }
  }
}
