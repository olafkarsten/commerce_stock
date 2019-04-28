<?php

namespace Drupal\commerce_stock_local\Resolver;

use Drupal\commerce\Context;
use Drupal\commerce\PurchasableEntityInterface;
use Drupal\commerce_stock\Resolver\AvailabilityLocationResolverInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;

/**
 * Returns the active local stock locations, if known.
 */
class LocalStockAvailabilityLocationResolver implements AvailabilityLocationResolverInterface {

  /**
   * The local stock location storage.
   *
   * @var \Drupal\commerce_stock_local\StockLocationStorageInterface
   */
  protected $storage;

  /**
   * Constructs a new LocalStockAvailabilityLocationResolver object.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   */
  public function __construct(EntityTypeManagerInterface $entity_type_manager) {
    $this->storage = $entity_type_manager->getStorage('commerce_stock_location');
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('entity_type.manager')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function resolve(
    PurchasableEntityInterface $entity,
    Context $context
  ) {

    $store = $context->getStore();
    // Make sure we have the availability field for the location.
    if ($store->hasField('field_available_stock_locations')) {
      // Get the available locations.
      $locations = $store->field_available_stock_locations->getValue();
      if (!empty($locations)) {
        $store_locations = [];
        foreach ($locations as $location) {
          $store_locations[$location['target_id']] = $location['target_id'];
        }
        $store_locations = $this->loadMultiple($store_locations);
        // We use the enabled locations only.
        foreach ($store_locations as $id => $location) {
          if (!$location->isActive()) {
            unset($store_locations[$id]);
          }
        }
      }
      if (!empty($store_locations)) {
        // Return the enabled locations.
        return $store_locations;
      }
    }

    return $this->storage->loadEnabled($entity);
  }
}
