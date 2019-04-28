<?php

namespace Drupal\commerce_stock\Resolver;

use Drupal\commerce\Context;
use Drupal\commerce\PurchasableEntityInterface;

/**
 * Chain availability location resolver.
 */
class ChainAvailabilityLocationResolver implements ChainAvailabilityLocationResolverInterface {

  /**
   * The resolvers.
   *
   * @var \Drupal\commerce_stock\Resolver\AvailabilityLocationResolverInterface[]
   */
  protected $resolvers = [];

  /**
   * Constructs a new ChainAvailabilityLocationResolver object.
   *
   * @param \Drupal\commerce_stock\Resolver\AvailabilityLocationResolverInterface[] $resolvers
   *   The resolvers.
   */
  public function __construct(array $resolvers = []) {
    $this->resolvers = $resolvers;
  }

  /**
   * {@inheritdoc}
   */
  public function addResolver(AvailabilityLocationResolverInterface $resolver) {
    $this->resolvers[] = $resolver;
  }

  /**
   * {@inheritdoc}
   */
  public function getResolvers() {
    return $this->resolvers;
  }

  /**
   * {@inheritdoc}
   */
  public function resolve(
    PurchasableEntityInterface $entity,
    Context $context
  ) {
    foreach ($this->resolvers as $resolver) {
      $result = $resolver->resolve($entity, $context);
      if ($result) {
        return $result;
      }
    }
  }

}
