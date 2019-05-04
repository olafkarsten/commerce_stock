<?php

namespace Drupal\Tests\commerce_stock\Kernel;

use Drupal\commerce\Context;
use Prophecy\Argument;

/**
 * Class ChainAvailabilityLocationResolverTest.
 *
 * @group commerce_stock
 */
class ChainAvailabilityLocationResolverTest extends CommerceStockKernelTestBase {

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    $this->installConfig('commerce_stock');
  }

  /**
   * {@inheritdoc}
   */
  public function testChainAvailabilityLocationResolver() {

    $prophecy = $this->prophesize('Drupal\commerce_stock\Resolver\AvailabilityLocationResolverInterface');
    $prophecy->resolve(Argument::any(), Argument::any())->willReturn(NULL);
    $resolverOne = $prophecy->reveal();

    $prophecy = $this->prophesize('Drupal\commerce_stock\Resolver\AvailabilityLocationResolverInterface');
    $prophecy->resolve(Argument::type('Drupal\commerce\PurchasableEntityInterface'), Argument::type('Drupal\commerce\Context'))
      ->willReturn('TESTRESULT');
    $resolverTwo = $prophecy->reveal();

    $chainResolver = $this->container->get('commerce_stock.chain_availability_location_resolver');
    self::assertInstanceOf('Drupal\commerce_stock\Resolver\ChainAvailabilityLocationResolverInterface', $chainResolver);
    $chainResolver->addResolver($resolverOne);
    $chainResolver->addResolver($resolverTwo);

    $prophecy = $this->prophesize('Drupal\commerce\PurchasableEntityInterface');
    $purchasableEntity = $prophecy->reveal();

    $user = $this->createUser();
    $context = new Context($user, $this->store);

    self::assertEquals($chainResolver->resolve($purchasableEntity, $context), 'TESTRESULT');

    $resolvers = $chainResolver->getResolvers();
    self::assertTrue(in_array($resolverOne, $resolvers));
    self::assertTrue(in_array($resolverTwo, $resolvers));
  }

}
