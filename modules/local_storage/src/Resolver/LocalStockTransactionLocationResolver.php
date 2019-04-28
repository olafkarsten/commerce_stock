<?php

namespace Drupal\commerce_stock_local\Resolver;

use Drupal\commerce\Context;
use Drupal\commerce\PurchasableEntityInterface;
use Drupal\commerce_stock\Resolver\ChainAvailabilityLocationResolverInterface;
use Drupal\commerce_stock\Resolver\TransactionLocationResolverInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;

/**
 * Returns the first active local stock locations, if known.
 */
class LocalStockTransactionLocationResolver implements TransactionLocationResolverInterface, ContainerInjectionInterface {

  /**
   * The local stock location storage.
   *
   * @var \Drupal\commerce_stock_local\StockLocationStorageInterface
   */
  protected $storage;

  /**
   * The local stock location availability location resolver.
   *
   * @var \Drupal\commerce_stock\Resolver\ChainAvailabilityLocationResolverInterface
   */
  protected $chainAvailabilityLocationResolver;

  /**
   * Constructs a new LocalStockTransactionLocationResolver object.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   *
   * @param \Drupal\commerce_stock\Resolver\ChainAvailabilityLocationResolverInterface $availability_location_resolver
   *   The chain availability location resolver.
   */
  public function __construct(
    EntityTypeManagerInterface $entity_type_manager,
    ChainAvailabilityLocationResolverInterface $chain_availability_location_resolver
  ) {
    $this->storage = $entity_type_manager->getStorage('commerce_stock_location');
    $this->chainAvailabilityLocationResolver = $chain_availability_location_resolver;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container){
    return new static(
      $container->get('entity_type.manager'),
      $container->get('commerce_stock.chain_availibility_location_resolver')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function resolve(
    PurchasableEntityInterface $entity,
    Context $context,
    $quantity
  ) {
    $store = $context->getStore();
    // Make sure we have the availability field for the location.
    if ($store->hasField('field_stock_allocation_location')) {
      // Get the available locations.
      $locations = $store->field_stock_allocation_location->getValue();
      if (!empty($locations)) {
        // Allocation field is empty.
        $location_id = array_shift($locations)['target_id'];
        $store_location = $this->storage->load($location_id);
        return $store_location;
      }
    }

    $locations = $this->chainAvailabilityLocationResolver->resolve($entity, $context);
    return array_shift($locations);
  }
}
