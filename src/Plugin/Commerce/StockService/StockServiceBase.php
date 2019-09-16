<?php

namespace Drupal\commerce_stock\Plugin\Commerce\StockService;

use Drupal\commerce\Context;
use Drupal\commerce\PurchasableEntityInterface;
use Drupal\commerce_stock\Resolver\ChainAvailabilityLocationResolverInterface;
use Drupal\commerce_stock\Resolver\ChainTransactionLocationResolverInterface;
use Drupal\Component\Utility\NestedArray;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Plugin\PluginBase;
use Drupal\Core\Plugin\PluginWithFormsTrait;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides the base class for stock services.
 */
abstract class StockServiceBase extends PluginBase implements StockServiceInterface, ContainerFactoryPluginInterface {

  use PluginWithFormsTrait;

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * The ID of the parent config entity.
   *
   * @var string
   */
  protected $entityId;

  /**
   * The chain availability location resolver.
   *
   * @var Drupal\commerce_stock\Resolver\ChainAvailabilityLocationResolverInterface
   */
  protected $chain_transaction_location_resolver;

  /**
   * The chain transaction location resolver.
   *
   * @var Drupal\commerce_stock\Resolver\TransactionLocationResolverInterface
   */
  protected $chain_availability_location_resolver;

  /**
   * Constructs a new StockServiceBase object.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   * @param \Drupal\commerce_stock\Resolver\ChainAvailabilityLocationResolverInterface $chain_availability_location_resolver
   *   A chain availability location resolver.
   * @param \Drupal\commerce_stock\Resolver\ChainTransactionLocationResolverInterface $chain_transaction_location_resolver
   *   A chain transaction location resolver.
   */
  public function __construct(
    array $configuration,
    $plugin_id,
    $plugin_definition,
    EntityTypeManagerInterface $entity_type_manager,
    ChainAvailabilityLocationResolverInterface $chain_availability_location_resolver,
    ChainTransactionLocationResolverInterface $chain_transaction_location_resolver
  ) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->entityTypeManager = $entity_type_manager;
    if (array_key_exists('_entity_id', $configuration)) {
      $this->entityId = $configuration['_entity_id'];
      unset($configuration['_entity_id']);
    }
    $this->setConfiguration($configuration);
    $this->chain_availability_location_resolver = $chain_availability_location_resolver;
    $this->chain_transaction_location_resolver = $chain_availability_location_resolver;
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
      $container->get('commerce_stock.chain_availability_location_resolver'),
      $container->get('ommerce_stock.chain_transaction_location_resolver')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getLabel() {
    return $this->pluginDefinition['label'];
  }

  /**
   * {@inheritdoc}
   */
  public function getDisplayLabel(){
    return $this->pluginDefinition['display_label'];
  }

  /**
   * {@inheritdoc}
   */
  public function calculateDependencies() {
    return [];
  }

  /**
   * {@inheritdoc}
   */
  public function getConfiguration() {
    return $this->configuration;
  }

  /**
   * {@inheritdoc}
   */
  public function setConfiguration(array $configuration) {
    $this->configuration = NestedArray::mergeDeep($this->defaultConfiguration(), $configuration);
  }

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    return [];
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(
    array $form,
    FormStateInterface $form_state
  ) {
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateConfigurationForm(
    array &$form,
    FormStateInterface $form_state
  ) {
  }

  /**
   * {@inheritdoc}
   */
  public function submitConfigurationForm(
    array &$form,
    FormStateInterface $form_state
  ) {
  }

  /**
   * {@inheritdoc}
   */
  public function getAvailabilityLocations(
    PurchasableEntityInterface $entity,
    Context $context
  ) {
    return $this->chain_availability_location_resolver->resolve($entity, $context);
  }

  /**
   * {@inheritdoc}
   */
  public function getTransactionLocation(
    PurchasableEntityInterface $entity,
    $quantity,
    Context $context
  ) {
    return $this->chain_transaction_location_resolver->resolve($entity, $quantity, $context);
  }



}
