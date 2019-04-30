<?php

namespace Drupal\commerce_stock_local_test\Resolver;

use Drupal\commerce\Context;
use Drupal\commerce\PurchasableEntityInterface;
use Drupal\commerce_stock\Resolver\ChainAvailabilityLocationResolverInterface;
use Drupal\commerce_stock\Resolver\TransactionLocationResolverInterface;
use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Custom implementation for a transaction location resolver.
 * We don't use any availability location resolver directly. Instead we
 * use the ChainAvailabilityLocationResolver from commerce_core. This
 * allows other modules to provide AvailabilityLocationResolver resolvers
 * easily. Though thats not a must. The only thing the interface defines
 * is the ::resolve() method.
 *
 * @see commerce_stock_local_test.services.yml
 * @see \Drupal\commerce_stock\Resolver\ChainAvailabilityLocationResolver
 */
class TestLocalStockTransactionLocationResolver implements TransactionLocationResolverInterface, ContainerInjectionInterface {

  /**
   * The local stock location availability location resolver.
   *
   * @var \Drupal\commerce_stock\Resolver\ChainAvailabilityLocationResolverInterface
   */
  protected $chainAvailabilityLocationResolver;

  /**
   * Constructs a new LocalStockTransactionLocationResolver object.
   *
   * @param \Drupal\commerce_stock\Resolver\ChainAvailabilityLocationResolverInterface $chain_availability_location_resolver
   *   The chain availability location resolver.
   */
  public function __construct(
    ChainAvailabilityLocationResolverInterface $chain_availability_location_resolver
  ) {
    $this->chainAvailabilityLocationResolver = $chain_availability_location_resolver;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('commerce_stock.chain_availibility_location_resolver')
    );
  }

  /**
   * We are just returning the last of the provided locations, but you can
   * implement a much more sophisticated solution here.
   *
   * {@inheritdoc}
   */
  public function resolve(
    PurchasableEntityInterface $entity,
    $quantity,
    Context $context
  ) {
    $locations = $this->chainAvailabilityLocationResolver->resolve($entity, $context);
    return array_pop($locations);
  }

}
