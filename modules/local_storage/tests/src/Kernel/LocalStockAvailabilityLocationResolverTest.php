<?php
/**
 * This file is part of the commerce_contrib package.
 *
 * @author Olaf Karsten <olaf.karsten@beckerundkarsten.de>
 */

namespace Drupal\Tests\commerce_stock_local\Kernel;

use Drupal\commerce\Context;
use Drupal\commerce_stock_local\Entity\StockLocation;
use Drupal\Tests\commerce_stock\Kernel\CommerceStockKernelTestBase;

/**
 * @coversDefaultClass \Drupal\commerce_stock_local\Resolver\LocalStockAvailabilityLocationResolver
 */
class LocalStockAvailabilityLocationResolverTest extends CommerceStockKernelTestBase {

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
    }

    $resolver = $this->container->get('commerce_stock_local.availability_location_resolver');
    self::assertInstanceOf('Drupal\commerce_stock_local\Resolver\LocalStockAvailabilityLocationResolver', $resolver);

    $user = $this->createUser();
    $context = new Context($user, $this->store);

    $dummyPurchasable = $this->prophesize('Drupal\commerce\PurchasableEntityInterface');
    $locations = $resolver->resolve($dummyPurchasable->reveal(), $context);
    $this->assertEquals(3, count($locations), '3 out of 5 locations are enabled and resolved.');
  }

}
