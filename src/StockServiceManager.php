<?php

namespace Drupal\commerce_stock;

use Drupal\commerce\PurchasableEntityInterface;
use Drupal\commerce_store\CurrentStoreInterface;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Session\AccountInterface;

/**
 * The stock service manager.
 *
 * Responsible for handling services and transactions.
 *
 * @see StockAvailabilityChecker.
 *
 * @package Drupal\commerce_stock
 */
class StockServiceManager implements StockServiceManagerInterface {

  use ContextCreatorTrait;

  /**
   * The stock services.
   *
   * @var \Drupal\commerce_stock\StockServiceInterface[]
   */
  protected $stockServices = [];

  /**
   * The config factory.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $configFactory;

  /**
   * The current store.
   *
   * @var \Drupal\commerce_store\Entity\Store
   */
  protected $currentStore;

  /**
   * The current user.
   *
   * @var \Drupal\Core\Session\AccountInterface
   */
  protected $currentUser;

  /**
   * Constructs a StockServiceManager object.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The config factory.
   * @param \Drupal\commerce_store\CurrentStoreInterface $current_store
   *   The current store.
   * @param \Drupal\Core\Session\AccountInterface $current_user
   *   The current user.
   */
  public function __construct(
    ConfigFactoryInterface $config_factory,
    CurrentStoreInterface $current_store,
    AccountInterface $current_user
  ) {
    $this->configFactory = $config_factory;
    $this->currentStore = $current_store;
    $this->currentUser = $current_user;
  }

  /**
   * {@inheritdoc}
   */
  public function addService(StockServiceInterface $stock_service) {
    $this->stockServices[$stock_service->getId()] = $stock_service;
  }

  /**
   * {@inheritdoc}
   */
  public function getService(PurchasableEntityInterface $entity) {
    $config = $this->configFactory->get('commerce_stock.service_manager');

    $default_service_id = $config->get('default_service_id');

    $entity_type = $entity->getEntityTypeId();
    $entity_bundle = $entity->bundle();
    $entity_config_key = $entity_type . '_' . $entity_bundle . '_service_id';
    $entity_service_id = $config->get($entity_config_key);

    $service_id = $entity_service_id ?: $default_service_id;

    return $this->stockServices[$service_id];
  }

  /**
   * {@inheritdoc}
   */
  public function listServices() {
    return $this->stockServices;
  }

  /**
   * {@inheritdoc}
   */
  public function listServiceIds() {
    $ids = [];
    foreach ($this->stockServices as $service) {
      $ids[$service->getId()] = $service->getName();
    }

    return $ids;
  }

}
