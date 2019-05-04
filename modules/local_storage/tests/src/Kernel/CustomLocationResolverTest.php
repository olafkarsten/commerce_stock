<?php

namespace Drupal\Tests\commerce_stock_local\Kernel;

use Drupal\commerce\Context;
use Drupal\commerce_stock_local_test\Resolver\TestLocalStockTransactionLocationResolver;
use Drupal\Tests\commerce_stock\Kernel\CommerceStockKernelTestBase;

/**
 * Test and show case for a custom resolver. We test a custom availability
 * location resolver and a custom transaction location resolver.
 *
 * @see the comerce_stock_local_test module.
 *
 * @group commerce_stock
 */
class CustomLocationResolverTest extends CommerceStockKernelTestBase {

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
    'commerce_stock_local_test',
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
   * @throws \Drupal\Core\Entity\EntityStorageException
   */
  public function testLocalStockCustomResolver() {
    $availibilityResolver = $this->container->get('commerce_stock_local.test_availability_location_resolver');
    $chainResolver = $this->container->get('commerce_stock.chain_availability_location_resolver');
    $chainResolver->addResolver($availibilityResolver);

    $resolver = new TestLocalStockTransactionLocationResolver($chainResolver);
    self::assertInstanceOf('Drupal\commerce_stock_local_test\Resolver\TestLocalStockTransactionLocationResolver', $resolver);

    $user = $this->createUser();
    $context = new Context($user, $this->store);

    $dummyPurchasable = $this->prophesize('Drupal\commerce\PurchasableEntityInterface');
    $resolvedLocation = $resolver->resolve($dummyPurchasable->reveal(), 3, $context);
    $this->assertEquals($resolvedLocation->id(), 5, 'Returned the last of the available locations.');
    $this->assertEquals($resolvedLocation->getName(), 'TESTLOCATION-5', 'Returned the last of the available locations.');
  }

}
