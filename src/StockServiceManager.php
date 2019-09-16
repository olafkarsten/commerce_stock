<?php

namespace Drupal\commerce_stock;

use Drupal\commerce\Context;
use Drupal\commerce\PurchasableEntityInterface;
use Drupal\commerce_order\Entity\OrderInterface;

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

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * The chain stock service resolver.
   *
   * @var \Drupal\commerce_stock\Resolver\ChainStockServiceResolverInterface
   */
  protected $chainStockServiceResolver;

  /**
   * Constructs a new StockServiceManager object.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   * @param \Drupal\commerce_stock\Resolver\ChainStockServiceResolverInterface $chain_stock_service_resolver
   *   The chain checkout flow resolver.
   */
  public function __construct(
    EntityTypeManagerInterface $entity_type_manager,
    ChainStockServiceResolverInterface $chain_stock_service_resolver
  ) {
    $this->entityTypeManager = $entity_type_manager;
    $this->chainStockServiceResolver = $chain_stock_service_resolver;
  }

  /**
   * {@inheritdoc}
   */
  public function getService(
    PurchasableEntityInterface $entity,
    Context $context,
    $quantity = NULL,
    OrderInterface $order = NULL
  ) {
    return $this->chainStockServiceResolver->resolve($entity, $context, $quantity, $order);
  }

}
