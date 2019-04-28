<?php

namespace Drupal\commerce_stock_local;

use Drupal\commerce\CommerceContentEntityStorage;
use Drupal\commerce\PurchasableEntityInterface;
use Drupal\commerce_stock_local\Event\FilterLocationsEvent;
use Drupal\commerce_stock_local\Event\LocalStockEvents;
use Drupal\commerce\Context;

/**
 * Defines the local stock location storage.
 */
class StockLocationStorage extends CommerceContentEntityStorage implements StockLocationStorageInterface {

  /**
   * Loads the enabled locations for the given Purchasable Entity.
   *
   * @param \Drupal\commerce\PurchasableEntityInterface $entity
   *   The purchasable entity.
   *
   * @return \Drupal\commerce_stock_local\Entity\StockLocation[]
   *   The enabled stock locations.
   */
  private function loadEnabled(PurchasableEntityInterface $entity) {
    // Speed up loading by filtering out the IDs of disabled locations.
    $query = $this->getQuery()
      ->condition('status', TRUE);
    $result = $query->execute();
    if (empty($result)) {
      return [];
    }
    return $this->loadMultiple($result);
  }

  /**
   * {@inheritdoc}
   */
  public function loadFromContext(Context $context, PurchasableEntityInterface $entity) {

    $store = $context->getStore();
    // Make sure we have the availability field for the location.
    if ($store->hasField('field_available_stock_locations')) {
      // Get the available locations.
      $locations = $store->field_available_stock_locations->getValue();
      // If no store locations.
      if (empty($locations)) {
        // Return the enabled locations.
        return $this->loadEnabled($entity);
      }
      // Load them.
      $store_locations = [];
      foreach ($locations as $location) {
        $store_locations[$location['target_id']] = $location['target_id'];
      }
      $store_locations = $this->loadMultiple($store_locations);
      // Remove if not enabled.
      foreach ($store_locations as $id => $location) {
        if (!$location->isActive()) {
          unset($store_locations[$id]);
        }
      }
      // If no active store locations.
      if (empty($locations)) {
        // Return the enabled locations.
        return $this->loadEnabled($entity);
      }
      return $store_locations;
    }
    // If store locations has not been enabled.
    else {
      // Return the enabled locations.
      return $this->loadEnabled($entity);
    }

  }

  /**
   * {@inheritdoc}
   */
  public function getTransactionLocation(Context $context, PurchasableEntityInterface $entity) {
    $store = $context->getStore();
    // Make sure we have the availability field for the location.
    if ($store->hasField('field_stock_allocation_location')) {
      // Get the available locations.
      $locations = $store->field_stock_allocation_location->getValue();
      if (empty($locations)) {
        // Allocation field is empty.
        $locations = $this->loadFromContext($context, $entity);
        return empty($locations) ? NULL : array_shift($locations);
      }
      else {
        $location_id = array_shift($locations)['target_id'];
        $store_location = $this->load($location_id);
        return $store_location;
      }
    }
    else {
      // No stock allocation field.
      $locations = $this->loadFromContext($context, $entity);
      return empty($locations) ? NULL : array_shift($locations);
    }
  }

  private function filterLocations(Context $context, PurchasableEntityInterface $entity, $locations){
    // Allow modules to apply own filtering.
    $event = new FilterLocationsEvent($context, $entity, $locations);
    $this->eventDispatcher->dispatch(LocalStockEvents::FILTER_STOCK_LOCATIONS, $event);
    return $event->getLocations();
  }

}
