<?php

namespace Drupal\Tests\commerce_stock_local\Kernel\Entity;

use Drupal\commerce_stock_local\Entity\StockLocation;
use Drupal\commerce_stock_local\Entity\StockTransaction;
use Drupal\Tests\commerce_stock\Kernel\CommerceStockKernelTestBase;

/**
 * Test the StockTransaction entity.
 *
 * @coversDefaultClass \Drupal\commerce_stock_local\Entity\StockTransaction
 *
 * @group commerce_stock
 */
class StockTransactionTest extends CommerceStockKernelTestBase {

  /**
   * {@inheritdoc}
   */
  public static $modules = [
    'commerce_stock_local',
    'commerce_product',
  ];

  /**
   * A sample user.
   *
   * @var \Drupal\user\UserInterface
   */
  protected $user;

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    $this->installEntitySchema('commerce_stock_transaction_type');
    $this->installEntitySchema('commerce_stock_transaction');
    $this->installEntitySchema('commerce_stock_location');
    $this->installEntitySchema('commerce_stock_location_type');
    $this->installConfig(['commerce_stock']);
    $this->installConfig(['commerce_stock_local']);

    $user = $this->createUser();
    $this->user = $this->reloadEntity($user);
  }

  /**
   * Test stock location.
   */
  public function testStockTransaction() {

    $transaction = StockTransaction::create([
      'type' => 'default',
    ]);

    self::assertInstanceOf(StockTransaction::class, $transaction);

    $location = StockLocation::create(['type' => 'default']);
    $transaction->setLocation($location);
    $transaction->setQuantity('33.33');
    $transaction->setOwnerId(1);
    $transaction->save();

    $transaction = $this->reloadEntity($transaction);
    self::assertEquals('33.33', $transaction->getQuantity());

  }

}
