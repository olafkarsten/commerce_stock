<?php

namespace Drupal\Tests\commerce_stock\Kernel;

use Drupal\commerce\Context;
use Prophecy\Argument;

class ChainTransactionLocationResolverTest extends CommerceStockKernelTestBase {

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
  public function testChainTransactionLocationResolver() {

    $prophecy = $this->prophesize('Drupal\commerce_stock\Resolver\TransactionLocationResolverInterface');
    $prophecy->resolve(Argument::any(), Argument::any(), Argument::any())
      ->willReturn(NULL);
    $resolverOne = $prophecy->reveal();

    $prophecy = $this->prophesize('Drupal\commerce_stock\Resolver\TransactionLocationResolverInterface');
    $prophecy->resolve(Argument::type('Drupal\commerce\PurchasableEntityInterface'), Argument::type('Drupal\commerce\Context'), Argument::any())
      ->willReturn('TESTRESULT');
    $resolverTwo = $prophecy->reveal();

    $chainResolver = $this->container->get('commerce_stock.chain_transaction_location_resolver');
    self::assertInstanceOf('Drupal\commerce_stock\Resolver\ChainTransactionLocationResolverInterface', $chainResolver);
    $chainResolver->addResolver($resolverOne);
    $chainResolver->addResolver($resolverTwo);

    $prophecy = $this->prophesize('Drupal\commerce\PurchasableEntityInterface');
    $purchasableEntity = $prophecy->reveal();

    $user = $this->createUser();
    $context = new Context($user, $this->store);

    self::assertEquals($chainResolver->resolve($purchasableEntity, $context, 3), 'TESTRESULT');

    $resolvers = $chainResolver->getResolvers();
    self::assertTrue(in_array($resolverOne, $resolvers));
    self::assertTrue(in_array($resolverTwo, $resolvers));
  }

}
