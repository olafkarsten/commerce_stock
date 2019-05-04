<?php

namespace Drupal\commerce_stock;

use Drupal\commerce\Context;
use Drupal\commerce\PurchasableEntityInterface;
use Drupal\commerce_stock\Resolver\ChainAvailabilityLocationResolverInterface;
use Drupal\commerce_stock\Resolver\ChainTransactionLocationResolverInterface;
use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * The default stock service configuration class.
 *
 * Implementing modules can extend this class.
 */
class DefaultStockServiceConfig implements StockServiceConfigInterface, ContainerInjectionInterface {

  /**
   * The chain availability location resolver.
   *
   * @var Drupal\commerce_stock\Resolver\ChainAvailabilityLocationResolverInterface
   */
  protected $chain_transaction_location_resolver;

  /**
   * The chain transaction location resolver.
   *
   * @var Drupal\commerce_stock\Resolver\TransactionLocationResolverInterface
   */
  protected $chain_availability_location_resolver;

  /**
   * Constructs a new DefaultStockServiceConfig object.
   *
   * @param \Drupal\commerce_stock\Resolver\ChainAvailabilityLocationResolverInterface $chain_availability_location_resolver
   *   A chain availability location resolver.
   * @param \Drupal\commerce_stock\Resolver\ChainTransactionLocationResolverInterface $chain_transaction_location_resolver
   *   A chain transaction location resolver.
   */
  public function __construct(
    ChainAvailabilityLocationResolverInterface $chain_availability_location_resolver,
    ChainTransactionLocationResolverInterface $chain_transaction_location_resolver
  ) {
    $this->chain_availability_location_resolver = $chain_availability_location_resolver;
    $this->chain_transaction_location_resolver = $chain_transaction_location_resolver;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('commerce_stock.chain_availability_location_resolver'),
      $container->get('ommerce_stock.chain_transaction_location_resolver')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getAvailabilityLocations(
    PurchasableEntityInterface $entity,
    Context $context
  ) {
    return $this->chain_availability_location_resolver->resolve($entity, $context);
  }

  /**
   * {@inheritdoc}
   */
  public function getTransactionLocation(
    PurchasableEntityInterface $entity,
    $quantity,
    Context $context
  ) {
    return $this->chain_transaction_location_resolver->resolve($entity, $quantity, $context);
  }

}
