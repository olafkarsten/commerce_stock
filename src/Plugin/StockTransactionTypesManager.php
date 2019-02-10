<?php

namespace Drupal\commerce_stock\Plugin;

use Drupal\Component\Plugin\Exception\PluginException;
use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Plugin\DefaultPluginManager;

/**
 * Provides the StockTranscationTypes plugin manager.
 */
class StockTransactionTypesManager extends DefaultPluginManager implements StockTransactionTypesManagerInterface {

  /**
   * Provides default values for all stock transaction type plugins.
   *
   * @var array
   */
  protected $defaults = [
    // Add required and optional plugin properties.
    'id' => '',
    'label' => '',
    'description' => '',
    'log_message' => '',
  ];

  /**
   * Constructs a new StockEventsManager object.
   *
   * @param \Traversable $namespaces
   *   An object that implements \Traversable which contains the root paths
   *   keyed by the corresponding namespace to look for plugin implementations.
   * @param \Drupal\Core\Cache\CacheBackendInterface $cache_backend
   *   Cache backend instance to use.
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $module_handler
   *   The module handler to invoke the alter hook with.
   */
  public function __construct(
    \Traversable $namespaces,
    CacheBackendInterface $cache_backend,
    ModuleHandlerInterface $module_handler
  ) {
    parent::__construct('Plugin/StockTransactionTypes', $namespaces, $module_handler, '\Drupal\commerce_stock\Plugin\StockTransactionTypes\StockTransactionTypesInterface', '\Drupal\commerce_stock\Annotation\StockTransactionTypes');
    $this->alterInfo('commerce_stock_transaction_types_info');
    $this->setCacheBackend($cache_backend, 'commerce_stock_transaction_types_plugins');
  }

  /**
   * {@inheritdoc}
   */
  protected function getDiscovery() {
    return parent::getDiscovery();;
  }

  /**
   * {@inheritdoc}
   */
  public function processDefinition(&$definition, $plugin_id) {
    parent::processDefinition($definition, $plugin_id);

    foreach (['id', 'label', 'description'] as $required_property) {
      if (empty($definition[$required_property])) {
        throw new PluginException(sprintf('The transaction type form %s must define the %s property.', $plugin_id, $required_property));
      }
    }
  }

}
