<?php

namespace Drupal\commerce_stock\Resolver;

/**
 * Defines the interface for availability location resolvers.
 */
interface ChainAvailabilityLocationResolverInterface extends AvailabilityLocationResolverInterface {

  /**
   * Adds a resolver.
   *
   * @param \Drupal\commerce_stock\Resolver\AvailabilityLocationResolverInterface $resolver
   *   The resolver.
   */
  public function addResolver(AvailabilityLocationResolverInterface $resolver);

  /**
   * Gets all added resolvers.
   *
   * @return \Drupal\commerce_stock\AvailabilityLocationResolverInterface[]
   *   The resolvers.
   */
  public function getResolvers();

}
