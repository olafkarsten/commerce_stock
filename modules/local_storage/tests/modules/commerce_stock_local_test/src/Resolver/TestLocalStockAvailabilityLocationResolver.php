<?php

namespace Drupal\commerce_stock_local_test\Resolver;

use Drupal\commerce\Context;
use Drupal\commerce\PurchasableEntityInterface;
use Drupal\commerce_stock\Resolver\AvailabilityLocationResolverInterface;
use Drupal\commerce_stock_local\Entity\StockLocation;

/**
 * Custom availibility location resolver.
 *
 * It's a naiv implementation. One could easily code a more sophisticated
 * resolver. To inject other services simply implement drupals
 * ContainerInjectionInterface and extend the *.services.yml configuration
 * as usual.
 *
 * Note that we nowhere inject this resolver in the custom test transaction
 * location resolver of the some test module. That automagically happens
 * by collecting this service through service tags. Drupal service collector
 * magic.
 *
 * @see commerce_stock_local_test.services.yml
 * @see \Drupal\commerce_stock\Resolver\ChainAvailabilityLocationResolver
 */
class TestLocalStockAvailabilityLocationResolver implements AvailabilityLocationResolverInterface {

  /**
   * {@inheritdoc}
   */
  public function resolve(
    PurchasableEntityInterface $entity,
    Context $context
  ) {
    $locations = [];
    for ($i = 1; $i <= 5; $i++) {
      $location = StockLocation::create([
        'type' => 'default',
        'name' => 'TESTLOCATION-' . $i,
        'status' => $i % 2,
      ]);
      $location->save();
      $locations[] = $location;
    }
    return $locations;
  }

}
