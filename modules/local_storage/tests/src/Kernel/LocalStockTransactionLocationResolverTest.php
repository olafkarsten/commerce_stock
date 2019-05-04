<?php

namespace Drupal\Tests\commerce_stock_local\Kernel;

use Drupal\commerce\Context;
use Drupal\commerce_stock_local\Entity\StockLocation;
use Drupal\commerce_stock_local\Resolver\LocalStockTransactionLocationResolver;
use Drupal\Tests\commerce_stock\Kernel\CommerceStockKernelTestBase;

/**
 * @coversDefaultClass \Drupal\commerce_stock_local\Resolver\LocalStockTransactionLocationResolver
 * @group commerce_stock
 */
class LocalStockTransactionLocationResolverTest extends CommerceStockKernelTestBase {

  /**
   * The stock location storage.
   *
   * @var \Drupal\commerce_stock_local\StockLocationStorage
   */
  protected $locationStorage;

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = [
    'commerce_stock_local',
  ];

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    $this->installEntitySchema('commerce_stock_location');
    $this->installEntitySchema('commerce_stock_location_type');
    $this->installConfig(['commerce_stock']);
    $this->installConfig(['commerce_stock_local']);

    $this->locationStorage = $this->container->get('entity_type.manager')
      ->getStorage('commerce_stock_location');
  }

  /**
   * @covers ::resolve
   *
   * @throws \Drupal\Core\Entity\EntityStorageException
   */
  public function testResolver() {
    for ($i = 1; $i <= 5; $i++) {
      $location = StockLocation::create([
        'type' => 'default',
        'name' => $this->randomString(),
        'status' => $i % 2,
      ]);
      $location->save();
      if ($i == 1) {
        $testLocation = $location;
      }
    }
    $availibilityResolver = $this->container->get('commerce_stock_local.availability_location_resolver');
    $chainResolver = $this->container->get('commerce_stock.chain_availability_location_resolver');
    $chainResolver->addResolver($availibilityResolver);
    $entity_type_manager = $this->container->get('entity_type.manager');

    $resolver = new LocalStockTransactionLocationResolver($entity_type_manager, $chainResolver);
    self::assertInstanceOf('Drupal\commerce_stock_local\Resolver\LocalStockTransactionLocationResolver', $resolver);

    $user = $this->createUser();
    $context = new Context($user, $this->store);

    $dummyPurchasable = $this->prophesize('Drupal\commerce\PurchasableEntityInterface');
    $resolvedLocation = $resolver->resolve($dummyPurchasable->reveal(), 3, $context);
    $this->assertEquals($resolvedLocation->id(), $testLocation->id(), 'Returned the first of available locations.');
  }

}
