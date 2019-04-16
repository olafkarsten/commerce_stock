<?php

namespace Drupal\Tests\commerce_stock_ui\FunctionalJavascript;

use Drupal\commerce\Context;
use Drupal\commerce_stock\Plugin\StockTransactionTypes\StockTransactionTypeInterface;
use Drupal\commerce_stock\StockTransactionsInterface;
use Drupal\Tests\commerce_stock_ui\Functional\StockUIBrowserTestBase;

/**
 * Test the admin complex transaction form.
 * @runTestsInSeparateProcesses
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
   * @var \Drupal\commerce_stock\Plugin\StockTransactionTypesManagerInterface;
   */
  protected $transactionTypesManager;

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

    $container = \Drupal::getContainer();
    $this->transactionTypesManager = $container->get('plugin.manager.stock_transaction_type_form');

    $this->variation = $this->variations[2];
    /** @var \Drupal\commerce_stock\StockServiceInterface $stockService */
    $stockService = $this->stockServiceManager->getService($this->variation);
    $stockConfig = $stockService->getConfiguration();
    /** @var \Drupal\commerce_stock\StockCheckInterface $stockChecker */
    $stockChecker = $stockService->getStockChecker();
    /** @var \Drupal\commerce_stock\StockUpdateInterface $stockUpdater */
    $this->stockUpdater = $stockService->getStockUpdater();

    $this->context = new Context($this->adminUser, $this->store);
    $locations = $stockConfig->getAvailabilityLocations($this->context, $this->variation);

    $this->stockUpdater->createTransaction($this->variation, $locations[1]->getId(), '', 10, 10.10, 'USD', StockTransactionsInterface::STOCK_IN, []);

    self::assertTrue($stockChecker->getTotalStockLevel($this->variation, $locations) == 10);
  }

  /**
   * Test the transaction form
   */
  public function testTransactionForm() {
    $this->drupalGet('admin/commerce/config/stock/transactions');
    $this->assertSession()->buttonExists('Select variation');
    $this->assertSession()->fieldExists('product_variation');
    $value = $this->variation->label() . ' ('.$this->variation->id() .')';
    $this->getSession()->getPage()->fillField('product_variation', $value);
    $this->assertSession()->buttonNotExists('Submit');
    $this->submitForm([], 'Select variation');
    // Wait for AJAX request to finish.
    $this->waitForAjaxToFinish();

    $this->assertSession()->buttonExists('Select variation');
    $this->assertSession()->fieldExists('product_variation');
    $this->saveHtmlOutput();
    $this->assertSession()->fieldExists('transaction_details_form[quantity]');
    $this->assertSession()->optionExists('transaction_type_selection', 'stock_in');
    // Check if the transaction types select component is healthy.
    $this->transactionTypesManager->getDefinitions();
    /** @var \Drupal\commerce_stock\Plugin\StockTransactionTypes\StockTransactionTypeInterface $transactionType */
    foreach($this->transactionTypesManager->getDefinitions() as $transactionType){
      $this->assertSession()->optionExists('transaction_type_selection', $transactionType['id']);
    }
    $this->assertSession()->buttonExists('Submit');
  }

}
