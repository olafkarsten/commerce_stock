<?php

namespace Drupal\Tests\commerce_stock_field\Functional;

use Drupal\Tests\commerce_stock\Kernel\StockLevelFieldCreationTrait;
use Drupal\Tests\commerce_stock\Kernel\StockTransactionQueryTrait;
use Behat\Mink\Exception\ExpectationException;

/**
 * Provides tests for the stock level widget.
 *
 * @group commerce_stock
 */
class StockLevelWidgetsTest extends StockLevelFieldTestBase {

  use StockTransactionQueryTrait;
  use StockLevelFieldCreationTrait;

  /**
   * Tests the commerce_stock_level_simple widget.
   *
   * @throws \Drupal\Core\Entity\EntityMalformedException
   */
  public function testSimpleTransactionStockLevelWidget() {

    $entity_type = "commerce_product_variation";
    $bundle = 'default';
    $this->drupalGet($this->variation->toUrl('edit-form'));
    $this->assertSession()->statusCodeEquals(200);

    // Ensure the stock part of the form is healty.
    $this->assertSession()
      ->fieldExists('commerce_stock_always_in_stock[value]');
    $this->assertSession()
      ->checkboxNotChecked('commerce_stock_always_in_stock[value]');

    // Check the defaults.
    $this->assertSession()->fieldExists($this->fieldName . '[0][adjustment]');
    $this->assertSession()->fieldExists($this->fieldName . '[0][stock_transaction_note]');
    $this->assertSession()->fieldDisabled($this->fieldName . '[0][stock_transaction_note]');

    $widget_id = "commerce_stock_level_simple_transaction";
    $default_note = $this->randomString(200);
    $widget_settings = [
      'custom_transaction_note' => FALSE,
      'default_transaction_note' => $default_note ,
      'step' => '1',
    ];
    $this->configureFormDisplay($widget_id, $widget_settings, $entity_type, $bundle);
    $this->drupalGet($this->variation->toUrl('edit-form'));
    $this->assertSession()->statusCodeEquals(200);
    $this->assertSession()->fieldExists($this->fieldName . '[0][adjustment]');
    $this->assertSession()->fieldExists($this->fieldName . '[0][stock_transaction_note]');
    $this->assertSession()->fieldDisabled($this->fieldName . '[0][stock_transaction_note]');
    $this->assertSession()->fieldValueEquals($this->fieldName . '[0][stock_transaction_note]', $default_note);

    $widget_settings = [
      'custom_transaction_note' => TRUE,
      'default_transaction_note' => $default_note ,
      'step' => '1',
    ];
    $this->configureFormDisplay($widget_id, $widget_settings, $entity_type, $bundle);
    $this->drupalGet($this->variation->toUrl('edit-form'));
    $this->assertSession()->statusCodeEquals(200);
    $this->assertSession()->fieldExists($this->fieldName . '[0][adjustment]');
    $this->assertSession()->fieldExists($this->fieldName . '[0][stock_transaction_note]');
    self::setExpectedException(ExpectationException::class);
    $this->assertSession()->fieldDisabled($this->fieldName . '[0][stock_transaction_note]');
    $this->assertSession()->fieldValueEquals($this->fieldName . '[0][stock_transaction_note]', $default_note);
  }

}
