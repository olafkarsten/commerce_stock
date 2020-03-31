<?php

namespace Drupal\commerce_stock_local\Plugin\QueueWorker;

use Drupal\commerce_stock\StockServiceManagerInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Queue\QueueWorkerBase;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Commerce Stock Local location level update worker.
 *
 * @QueueWorker(
 *   id = "commerce_stock_local_stock_level_updater",
 *   title = @Translation("Commerce Stock Local stock level updater"),
 *   cron = {"time" = 10}
 * )
 *
 * @ToDo Inject the config factory instead of calling \Drupal::
 */
class StockLevelUpdater extends QueueWorkerBase implements ContainerFactoryPluginInterface {

  /**
   * The entity type manager service.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * The Stock Service Manager.
   *
   * @var \Drupal\commerce_stock\StockServiceManager
   */
  protected $stockServiceManager;

  /**
   * Constructs a new class instance.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   Entity type manager service.
   * @param \Drupal\commerce_stock\StockServiceManagerInterface $stock_service_manager
   *   The stock service manager.
   */
  public function __construct(
    array $configuration,
    $plugin_id,
    $plugin_definition,
    EntityTypeManagerInterface $entity_type_manager,
    StockServiceManagerInterface $stock_service_manager
  ) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->entityTypeManager = $entity_type_manager;
    $this->stockServiceManager = $stock_service_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(
    ContainerInterface $container,
    array $configuration,
    $plugin_id,
    $plugin_definition
  ) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('entity_type.manager'),
      $container->get('commerce_stock.service_manager')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function processItem($data) {
    $storage = $this->entityTypeManager->getStorage($data['entity_type']);
    $entity = $storage->load($data['entity_id']);
    if (!$entity) {
      return;
    }
    // Load the Stockupdate Service.
    $service = \Drupal::service('commerce_stock.local_stock_service');
    /** @var \Drupal\commerce_stock_local\LocalStockUpdater $updater */
    $updater = $service->getStockUpdater();

    /** @var \Drupal\commerce_stock_local\StockLocationStorage $locationStorage */
    $locationStorage = $this->entityTypeManager->getStorage('commerce_stock_location');
    $locations = $locationStorage->loadEnabled($entity);

    foreach ($locations as $location) {
      $updater->updateLocationStockLevel($location->getId(), $entity);
    }
  }

}
