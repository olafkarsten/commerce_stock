<?php
/**
 * This file is part of the commerce_contrib package.
 *
 * @author Olaf Karsten <olaf.karsten@beckerundkarsten.de>
 */

namespace Drupal\Tests\commerce_stock_field\Functional;

use Drupal\field\Entity\FieldConfig;
use Drupal\field\Entity\FieldStorageConfig;
use Drupal\Tests\commerce_product\Functional\ProductBrowserTestBase;

class StockLevelTest extends ProductBrowserTestBase {

  /**
   * @var string
   */
  protected $fieldName;

  /**
   * The test product.
   *
   * @var \Drupal\commerce_product\Entity\ProductInterface
   */
  protected $product;

  /**
   * The test product variation.
   *
   * @var \Drupal\commerce_product\Entity\ProductVariationInterface
   */
  protected $variation;

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = [
    'commerce_stock',
    'commerce_stock_field',
  ];

  /**
   * {@inheritdoc}
   */
  protected function getAdministratorPermissions() {
    return array_merge([
    ], parent::getAdministratorPermissions());
  }

  /**
   * Setting up the test.
   */
  protected function setup(){
    parent::setUp();

    $entity_type = "commerce_product_variation";
    $bundle = 'default';
    $entity_manager = \Drupal::entityManager();
    $entity_manager->clearCachedDefinitions();
    $this->fieldName = 'field_stock_level_test';

    /** @var \Drupal\field\Entity\FieldStorageConfig $field_storage */
    FieldStorageConfig::create([
      'field_name' => $this->fieldName,
      'type' => 'commerce_stock_level',
      'entity_type' => $entity_type,
    ])->save();

    FieldConfig::create([
      'entity_type' => $entity_type,
      'field_name' => $this->fieldName,
      'bundle' => $bundle,
      'label' => 'StockLevel',
    ])->save();

    entity_get_form_display('commerce_product_variation', 'default', 'default')
      ->setComponent('field_stock_level_test', [
        'type' => 'commerce_stock_level_simple_transaction',
      ])
      ->save();

    $entity_manager->clearCachedDefinitions();
    $definitions = $entity_manager->getFieldStorageDefinitions('commerce_product_variation', 'default');
    $this->assertTrue(!empty($definitions[$this->fieldName]));

    $this->product = $this->createEntity('commerce_product', [
      'type' => 'default',
    ]);
    $this->variation = $this->createEntity('commerce_product_variation', [
      'type' => 'default',
      'product_id' => $this->product->id(),
      'sku' => strtolower($this->randomMachineName()),
    ]);
  }

  public function testEditProductVariationForm() {
    $this->drupalGet($this->variation->toUrl('edit-form'));

    $this->saveHtmlOutput();

    $this->assertSession()->statusCodeEquals(200);
    $this->assertSession()->fieldExists('sku[0][value]');

    $this->assertSession()->buttonExists('Save');

    // Ensure the stock part of the form is healty.
    $this->assertSession()
      ->fieldExists('commerce_stock_always_in_stock[value]');
    $this->assertSession()
      ->checkboxNotChecked('commerce_stock_always_in_stock[value]');

    $this->assertSession()->pageTextContains('Always in stock?');
    $this->assertSession()->fieldExists($this->fieldName .'[0][adjustment]');

  }

}
