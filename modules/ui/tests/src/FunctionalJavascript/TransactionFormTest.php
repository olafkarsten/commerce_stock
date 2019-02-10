<?php

namespace Drupal\Tests\commerce_stock_ui\FunctionalJavascript;

use Drupal\commerce\Context;
use Drupal\commerce_stock\Plugin\StockTransactionTypes\StockTransactionTypesInterface;
use Drupal\Tests\commerce_stock_ui\Functional\StockUIBrowserTestBase;

/**
 * Tests out of stock functionality.
 *
 * @group commerce_stock
 */
class TransactionFormTest extends StockUIBrowserTestBase {

  /**
   * The product variation we use in this test.
   *
   * @var \Drupal\commerce_product\Entity\ProductVariationInterface
   */
  protected $variation;

  /**
   * The stock updater service.
   *
   * @var \Drupal\commerce_stock\StockUpdateInterface
   */
  protected $stockUpdater;

  /**
   * The context object.
   *
   * @var \Drupal\commerce\Context
   */
  protected $context;

  /**
   * {@inheritdoc}
   */
  public function setUp() {
    parent::setUp();
    $this->drupalLogin($this->adminUser);
    $config = \Drupal::configFactory()
      ->getEditable('commerce_stock.service_manager');
    $config->set('default_service_id', 'local_stock');
    $config->save();

    $this->variation = $this->variations[2];
    /** @var \Drupal\commerce_stock\StockServiceInterface $stockService */
    $stockService = $this->stockServiceManager->getService($this->variation);
    $stockConfig = $stockService->getConfiguration();
    /** @var \Drupal\commerce_stock\StockCheckInterface $stockUpdater */
    $stockChecker = $stockService->getStockChecker();
    /** @var \Drupal\commerce_stock\StockCheckInterface */
    $this->stockUpdater = $stockService->getStockUpdater();
    $this->context = new Context($this->adminUser, $this->store);
    $locations = $stockConfig->getAvailabilityLocations($this->context, $this->variation);
    $this->stockUpdater->createTransaction($this->variation, $locations[1]->getId(), '', 10, 10.10, 'USD', StockTransactionTypesInterface::STOCK_IN, []);
    self::assertTrue($stockChecker->getTotalStockLevel($this->variation, $locations) == 10);
  }

  /**
   * Test the transaction form
   */
  public function testTransactionForm() {
    $this->drupalGet('admin/commerce/config/stock/transactions');
    $this->assertSession()->buttonExists('Select variation');
    $this->assertSession()->fieldExists('product_variation');
    $edit = [
      'product_variation' => 2,
    ];
    $this->submitForm($edit, 'Select variation');
    $this->assertSession()->
    // Wait for AJAX request to finish.
    $this->waitForAjaxToFinish();

    $this->assertSession()->buttonExists('Select variation');
    $this->assertSession()->fieldExists('product_variation');
    $this->saveHtmlOutput();
    $this->assertSession()->fieldExists('transaction_qty');
  }

}
