<?php

namespace Drupal\Tests\commerce_stock_local\Kernel;

use Drupal\commerce\Context;
use Drupal\commerce_product\Entity\Product;
use Drupal\commerce_product\Entity\ProductVariation;
use Drupal\commerce_stock\StockTransactionsInterface;
use Drupal\commerce_stock_local\Entity\StockLocation;
use Drupal\commerce_stock_local\LocalStockChecker;
use Drupal\Tests\commerce_stock\Kernel\CommerceStockKernelTestBase;

/**
 * Test the LocalStockChecker.
 *
 * @coversDefaultClass \Drupal\commerce_stock_local\LocalStockChecker
 *
 * @group commerce_stock
 */
class LocalStockPerformanceTest extends CommerceStockKernelTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = [
    'commerce_product',
  ];

  /**
   * The stock service manager.
   *
   * @var \Drupal\commerce_stock\StockServiceManagerInterface
   */
  protected $stockServiceManager;

  /**
   * A sample user.
   *
   * @var \Drupal\user\UserInterface
   */
  protected $user;

  /**
   * The commerce context.
   *
   * @var \Drupal\commerce\Context
   */
  protected $context;

  /**
   * The variations.
   *
   * @var array \Drupal\commerce_product\Entity\ProductVariationInterface[]
   */
  protected $variations;

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();
    $this->variations = [];

    $this->installEntitySchema('commerce_stock_location_type');
    $this->installEntitySchema('commerce_stock_location');
    $this->installEntitySchema('commerce_product');
    $this->installEntitySchema('commerce_product_variation');
    $this->installEntitySchema('commerce_stock_transaction');
    $this->installEntitySchema('commerce_stock_transaction_types');
    $this->installConfig([
      'commerce_product',
      'commerce_stock',
      'commerce_stock_local',
    ]);
    $this->installSchema(
      'commerce_stock_local',
      [
        'commerce_stock_transaction',
        'commerce_stock_transaction_type',
        'commerce_stock_location_level',
      ]);

    $location = StockLocation::create([
      'type' => 'default',
      'name' => $this->randomString(),
      'status' => 1,
    ]);
    $location->save();

    $configFactory = $this->container->get('config.factory');
    $config = $configFactory->getEditable('commerce_stock.service_manager');
    $config->set('default_service_id', 'local_stock');
    $config->save();
    $this->stockServiceManager = \Drupal::service('commerce_stock.service_manager');

    $user = $this->createUser();
    $this->user = $this->reloadEntity($user);

    for($i = 0; $i < 10000; $i++ ) {
      $variation = ProductVariation::create([
        'type' => 'default',
        'sku' => 'TEST_' . strtolower($this->randomMachineName()),
        'title' => $this->randomString(),
        'status' => 1,
        'price' => [
          'number' => '12.00',
          'currency_code' => 'USD',
        ],
      ]);
      $variation->save();
      if($i <= 1000) {
        if($i % 2 === 0){
          
          $this->stockServiceManager->createTransaction($variation, $location->id(), 'TESTZONE', mt_rand(1,100), 0, 'EUR', StockTransactionsInterface::STOCK_IN);
        } else {
          $this->stockServiceManager->createTransaction($variation, $location->id(), 'TESTZONE', mt_rand(1,100), 0, 'EUR', StockTransactionsInterface::STOCK_OUT);
        }
      }
      if($i > 1000 && $i <= 2000) {
        for($j = 0; $j < 1000; $j++) {
          if ($j % 2 === 0) {
            $this->stockServiceManager->createTransaction($variation, $location->id(), 'TESTZONE', mt_rand(1,100), 0, 'EUR', StockTransactionsInterface::STOCK_IN);
          }
          else {
            $this->stockServiceManager->createTransaction($variation, $location->id(), 'TESTZONE', mt_rand(1,100), 0, 'EUR', StockTransactionsInterface::STOCK_OUT);
          }
        }
      }
      if($i > 2000 && $i <= 3000) {
        for($j = 0; $j < 25; $j++) {
          if ($i % 2 === 0) {
            $this->stockServiceManager->createTransaction($variation, $location->id(), 'TESTZONE', mt_rand(1, 100), 0, 'EUR', StockTransactionsInterface::STOCK_IN);
          }
          else {
            $this->stockServiceManager->createTransaction($variation, $location->id(), 'TESTZONE', mt_rand(1, 100), 0, 'EUR', StockTransactionsInterface::STOCK_OUT);
          }
        }
      }
      if($i > 3000 && $i <= 4000) {
        for($j = 0; $j < 75; $j++) {
          if ($i % 2 === 0) {
            $this->stockServiceManager->createTransaction($variation, $location->id(), 'TESTZONE', mt_rand(1, 100), 0, 'EUR', StockTransactionsInterface::STOCK_IN);
          }
          else {
            $this->stockServiceManager->createTransaction($variation, $location->id(), 'TESTZONE', mt_rand(1, 100), 0, 'EUR', StockTransactionsInterface::STOCK_OUT);
          }
        }
      }
      if($i > 4000 && $i <= 5000) {
        for($j = 0; $j < 50; $j++) {
          if ($i % 2 === 0) {
            $this->stockServiceManager->createTransaction($variation, $location->id(), 'TESTZONE', mt_rand(1, 100), 0, 'EUR', StockTransactionsInterface::STOCK_IN);
          }
          else {
            $this->stockServiceManager->createTransaction($variation, $location->id(), 'TESTZONE', mt_rand(1, 100), 0, 'EUR', StockTransactionsInterface::STOCK_OUT);
          }
        }
      }
      if($i > 5000) {
        for($j = 0; $j < 120; $j++) {
          if ($i % 2 === 0) {
            $this->stockServiceManager->createTransaction($variation, $location->id(), 'TESTZONE', mt_rand(1, 100), 0, 'EUR', StockTransactionsInterface::STOCK_IN);
          }
          else {
            $this->stockServiceManager->createTransaction($variation, $location->id(), 'TESTZONE', mt_rand(1, 100), 0, 'EUR', StockTransactionsInterface::STOCK_OUT);
          }
        }
      }
      $this->variations[] = $variation;
      $i++;
    }

    $this->user = $this->createUser(['mail' => $this->randomString() . '@example.com']);
    $this->context = new Context($this->user, $this->store);

  }

  /**
   * Performance test stock level.
   */
  public function testGettingTheStocklevel() {
    $start = microtime(true);
    foreach($this->variations as $variation){
      $locations = $this->stockServiceManager->getService($variation)->getConfiguration()->getAvailabilityLocations($this->context, $variation);
      $checker = $this->stockServiceManager->getService($variation)->getStockChecker();
      $checker->getTotalStockLevel($variation, $locations);
    }
    $time_elapsed_secs = microtime(true) - $start;
    var_dump($time_elapsed_secs);
  }

}
