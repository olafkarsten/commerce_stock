<?php

namespace Drupal\commerce_stock_local;

use Drupal\Core\Entity\EntityAccessControlHandler;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Access\AccessResult;

/**
 * Access controller for the Stock transaction entity.
 *
 * @see \Drupal\commerce_stock_local\Entity\StockTransaction.
 */
class StockTransactionAccessControlHandler extends EntityAccessControlHandler {

  /**
   * {@inheritdoc}
   */
  protected function checkAccess(EntityInterface $entity, $operation, AccountInterface $account) {
    /** @var \Drupal\commerce_stock_local\Entity\StockTransactionInterface $entity */
    switch ($operation) {
      case 'view':
        if (!$entity->isPublished()) {
          return AccessResult::allowedIfHasPermission($account, 'view unpublished stock transaction entities');
        }
        return AccessResult::allowedIfHasPermission($account, 'view published stock transaction entities');

      case 'update':
        return AccessResult::allowedIfHasPermission($account, 'edit stock transaction entities');

      case 'delete':
        return AccessResult::allowedIfHasPermission($account, 'delete stock transaction entities');
    }

    // Unknown operation, no opinion.
    return AccessResult::neutral();
  }

  /**
   * {@inheritdoc}
   */
  protected function checkCreateAccess(AccountInterface $account, array $context, $entity_bundle = NULL) {
    return AccessResult::allowedIfHasPermission($account, 'add stock transaction entities');
  }

}
