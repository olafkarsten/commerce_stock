<?php

namespace Drupal\commerce_stock_ui\Plugin;

use Drupal\commerce\PurchasableEntityInterface;
use Drupal\Component\Plugin\Exception\PluginException;
use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Plugin\DefaultPluginManager;

/**
 * Manages discovery and instantiation of commerce stock transaction types plugins.
 *
 * @see \Drupal\commerce_stock_ui\Annotation\StockTransactionTypeForm
 * @see plugin_api
 */
class StockTransactionTypeFormManager extends DefaultPluginManager {

  /**
   * Provides default values for all stock transaction type plugins.
   *
   * @see \Drupal\commerce_stock_ui\Annotation\StockTransactionTypeForm
   * @see plugin_api
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
    parent::__construct('Plugin/StockTransactionTypeForm', $namespaces, $module_handler, '\Drupal\commerce_stock_ui\Plugin\StockTransactionTypeForm\StockTransactionTypeFormInterface', '\Drupal\commerce_stock_ui\Annotation\StockTransactionTypeForm');
    $this->alterInfo('commerce_stock_transaction_type_form_info');
    $this->setCacheBackend($cache_backend, 'commerce_stock_transaction_type_form_plugins');
  }

  /**
   * {@inheritdoc}
   *
   * @return \Drupal\commerce_stock_ui\Plugin\StockTransactionTypeForm\StockTransactionTypeFormInterface
   *   The transaction type plugin.
   */
  public function createInstance($plugin_id, array $configuration = [], PurchasableEntityInterface $purchasable_entity = NULL) {
    $plugin = parent::createInstance($plugin_id, $configuration);
    if (!$purchasable_entity) {
      throw new \RuntimeException(sprintf('The %s transaction type form requires an purchasable entity.', $plugin_id));
    }
    $plugin->setPurchasableEntity($purchasable_entity);
    return $plugin;
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
