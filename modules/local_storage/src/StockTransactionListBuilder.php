<?php

namespace Drupal\commerce_stock_local;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityListBuilder;
use Drupal\Core\Link;

/**
 * Defines a class to build a listing of Stock transaction entities.
 *
 * @ingroup commerce_stock_local
 */
class StockTransactionListBuilder extends EntityListBuilder {


  /**
   * {@inheritdoc}
   */
  public function buildHeader() {
    $header['id'] = $this->t('Stock transaction ID');
    $header['name'] = $this->t('Name');
    return $header + parent::buildHeader();
  }

  /**
   * {@inheritdoc}
   */
  public function buildRow(EntityInterface $entity) {
    /* @var $entity \Drupal\commerce_stock_local\Entity\StockTransaction */
    $row['id'] = $entity->id();
    $row['name'] = Link::createFromRoute(
      $entity->label(),
      'entity.commerce_stock_transaction.edit_form',
      ['commerce_stock_transaction' => $entity->id()]
    );
    return $row + parent::buildRow($entity);
  }

}
