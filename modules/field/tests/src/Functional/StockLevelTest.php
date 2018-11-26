<?php
/**
 * This file is part of the commerce_contrib package.
 *
 * @author Olaf Karsten <olaf.karsten@beckerundkarsten.de>
 */

namespace Drupal\Tests\commerce_stock_field\Functional;

use Drupal\commerce\Context;
use Drupal\commerce_stock\StockTransactionsInterface;
use Drupal\Tests\commerce_stock\Functional\StockBrowserTestBase;
use Drupal\Tests\commerce_stock\Kernel\StockLevelFieldCreationTrait;

class StockLevelTest extends StockBrowserTestBase {

  use StockLevelFieldCreationTrait;

  /**
   * @var string
   */
  protected $fieldName;

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
    'commerce_stock_field',
    'commerce_stock_local',
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
  protected function setup() {
    parent::setUp();

    $this->drupalLogin($this->adminUser);

    $config = \Drupal::configFactory()
      ->getEditable('commerce_stock.service_manager');
    $config->set('default_service_id', 'local_stock');
    $config->save();

    $entity_type = "commerce_product_variation";
    $bundle = 'default';
    $this->fieldName = 'stock_level_test';

    $widget_settings = [
      'step' => 1,
      'transaction_note' => FALSE,
    ];
    $this->createStockLevelField($entity_type, $bundle, 'commerce_stock_level_simple_transaction', [], [], $widget_settings);

    $this->variation = $this->createEntity('commerce_product_variation', [
      'type' => 'default',
      'sku' => strtolower($this->randomMachineName()),
      'price' => [
        'number' => 999,
        'currency_code' => 'USD',
      ],
    ]);
    $this->product = $this->createEntity('commerce_product', [
      'type' => 'default',
      'title' => $this->randomMachineName(),
      'stores' => [$this->store],
      'variations' => [$this->variation],
    ]);

    self::assertTrue($this->variation->hasField($this->fieldName));

    $stockServiceConfiguration = $this->stockServiceManager->getService($this->variation)
      ->getConfiguration();
    $context = new Context($this->adminUser, $this->store);
    $this->locations = $stockServiceConfiguration->getAvailabilityLocations($context, $this->variation);
    $this->stockServiceManager->createTransaction($this->variation, $this->locations[1]->getId(), '', 10, 10.10, 'USD', StockTransactionsInterface::STOCK_IN, []);

  }

  public function testEditProductVariationForm() {

    //$this->drupalGet('product/' . $this->product->id());
    //$this->assertSession()->pageTextContains('StockLevelTest');
    //$this->assertSession()->elementContains('css', '.field--name-field-stock-level-test p', '10');

    $uri = $this->variation->toUrl('edit-form');
    $this->saveHtmlOutput();
    $test1 = $this->variations[0]->isActive();
    $test2 = $this->variations[1]->isActive();
    $test3 = $this->variations[2]->isActive();
    $uri = $this->variations[0]->toUrl('edit-form');
    $this->drupalGet($this->variations[0]->toUrl('edit-form'));
    $this->saveHtmlOutput();
    $this->assertSession()->statusCodeEquals(200);
    $this->assertSession()->fieldExists('sku[0][value]');
    $this->assertSession()->buttonExists('Save');

    // Ensure the stock part of the form is healty.
    $this->assertSession()
      ->fieldExists('commerce_stock_always_in_stock[value]');
    $this->assertSession()
      ->checkboxNotChecked('commerce_stock_always_in_stock[value]');

    $elements = $this->xpath('//input[starts-with(@name,"' . $this->fieldName . '")]');

    $this->assertSession()->pageTextContains('Always in stock?');
    $this->assertSession()->fieldExists($this->fieldName . '[0][adjustment]');

  }

  /**
   * Test the default formatter appears on product add to cart forms.
   *
   * @throws \Behat\Mink\Exception\ElementHtmlException
   * @throws \Behat\Mink\Exception\ResponseTextException
   */
  public function atestDefaultFormatter() {

    $this->drupalGet('product/' . $this->product->id());
    $this->assertSession()->pageTextContains('StockLevelTest');
    $this->assertSession()->elementContains('css', '.field--name-field-stock-level-test p', '10');
  }

}
