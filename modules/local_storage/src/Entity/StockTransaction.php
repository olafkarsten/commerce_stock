<?php

namespace Drupal\commerce_stock_local\Entity;

use Drupal\commerce\Entity\CommerceContentEntityBase;
use Drupal\commerce_order\Entity\OrderInterface;
use Drupal\commerce_price\Price;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Field\BaseFieldDefinition;
use Drupal\user\UserInterface;

/**
 * Defines the Stock transaction entity.
 *
 * @ingroup commerce_stock_local
 *
 * @ContentEntityType(
 *   id = "commerce_stock_transaction",
 *   label = @Translation("Stock transaction"),
 *   bundle_label = @Translation("Stock transaction type"),
 *   handlers = {
 *     "view_builder" = "Drupal\Core\Entity\EntityViewBuilder",
 *     "list_builder" = "Drupal\commerce_stock_local\StockTransactionListBuilder",
 *     "views_data" = "Drupal\commerce_stock_local\Entity\StockTransactionViewsData",
 *     "translation" = "Drupal\commerce_stock_local\StockTransactionTranslationHandler",
 *
 *     "form" = {
 *       "default" = "Drupal\commerce_stock_local\Form\StockTransactionForm",
 *       "add" = "Drupal\commerce_stock_local\Form\StockTransactionForm",
 *       "edit" = "Drupal\commerce_stock_local\Form\StockTransactionForm",
 *       "delete" = "Drupal\commerce_stock_local\Form\StockTransactionDeleteForm",
 *     },
 *     "access" = "Drupal\commerce_stock_local\StockTransactionAccessControlHandler",
 *     "route_provider" = {
 *       "html" = "Drupal\commerce_stock_local\StockTransactionHtmlRouteProvider",
 *     },
 *   },
 *   base_table = "commerce_stock_transaction",
 *   data_table = "commerce_stock_transaction_field_data",
 *   translatable = TRUE,
 *   admin_permission = "administer stock transaction entities",
 *   entity_keys = {
 *     "id" = "id",
 *     "bundle" = "type",
 *     "label" = "name",
 *     "uuid" = "uuid",
 *     "uid" = "uid",
 *     "langcode" = "langcode",
 *   },
 *   links = {
 *     "canonical" = "/commerce_stock_transaction/{commerce_stock_transaction}",
 *     "add-page" = "/commerce_stock_transaction/add",
 *     "add-form" = "/commerce_stock_transaction/add/{commerce_stock_transaction_type}",edit",
 *     "collection" = "/admin/commerce/stock_transactions",
 *   },
 *   bundle_entity_type = "commerce_stock_transaction_type",
 *   field_ui_base_route = "entity.commerce_stock_transaction_type.edit_form"
 * )
 */
class StockTransaction extends CommerceContentEntityBase implements StockTransactionInterface {

  /**
   * {@inheritdoc}
   */
  public function getCreatedTime() {
    return $this->get('created')->value;
  }

  /**
   * {@inheritdoc}
   */
  public function setCreatedTime($timestamp) {
    $this->set('created', $timestamp);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getOwner() {
    return $this->get('uid')->entity;
  }

  /**
   * {@inheritdoc}
   */
  public function getOwnerId() {
    return $this->get('uid')->target_id;
  }

  /**
   * {@inheritdoc}
   */
  public function setOwnerId($uid) {
    $this->set('uid', $uid);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function setOwner(UserInterface $account) {
    $this->set('uid', $account->id());
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public static function baseFieldDefinitions(EntityTypeInterface $entity_type
  ) {
    $fields = parent::baseFieldDefinitions($entity_type);

    $fields['uid'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(t('Authored by'))
      ->setDescription(t('The user ID of author of the Stock transaction entity.'))
      ->setRevisionable(TRUE)
      ->setSetting('target_type', 'user')
      ->setSetting('handler', 'default')
      ->setTranslatable(TRUE)
      ->setDisplayOptions('view', [
        'label' => 'hidden',
        'type' => 'author',
        'weight' => 0,
      ])
      ->setDisplayOptions('form', [
        'type' => 'entity_reference_autocomplete',
        'weight' => 5,
        'settings' => [
          'match_operator' => 'CONTAINS',
          'size' => '60',
          'autocomplete_type' => 'tags',
          'placeholder' => '',
        ],
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['stock_location'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(t('Transaction Location'))
      ->setDescription(t('The stock location ID.'))
      ->setSetting('target_type', 'commerce_stock_location')
      ->setSetting('handler', 'default')
      ->setRequired(TRUE)
      ->setDisplayOptions('form', [
        'type' => 'entity_reference_autocomplete',
        'weight' => 5,
        'settings' => [
          'match_operator' => 'CONTAINS',
          'size' => '60',
          'placeholder' => '',
        ],
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['zone'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Zone/Bin'))
      ->setDescription(t('The zone or bin for the transaction.'))
      ->setSettings([
        'max_length' => 255,
        'text_processing' => 0,
      ])
      ->setDefaultValue('')
      ->setDisplayOptions('view', [
        'label' => 'above',
        'type' => 'string',
        'weight' => -4,
      ])
      ->setDisplayOptions('form', [
        'type' => 'string_textfield',
        'weight' => -4,
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['purchasable_entity'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(t('Purchasable entity'))
      ->setDescription(t('The purchasable entity.'))
      ->setRequired(TRUE)
      ->setDisplayOptions('form', [
        'type' => 'entity_reference_autocomplete',
        'weight' => -1,
        'settings' => [
          'match_operator' => 'CONTAINS',
          'size' => '60',
          'placeholder' => '',
        ],
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['quantity'] = BaseFieldDefinition::create('decimal')
      ->setLabel(t('Quantity'))
      ->setDescription(t('The transaction quantity.'))
      ->setDisplayOptions('form', [
        'type' => 'number',
        'weight' => 1,
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['unit_cost'] = BaseFieldDefinition::create('commerce_price')
      ->setLabel(t('Unit cost'))
      ->setDescription(t('The cost of a single unit.'))
      ->setDisplayOptions('form', [
        'type' => 'commerce_price_default',
        'weight' => 2,
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['log_message'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Logmessage'))
      ->setDescription(t('The log message for the transaction.'))
      ->setSettings([
        'max_length' => 255,
        'text_processing' => 0,
      ])
      ->setDefaultValue('')
      ->setDisplayOptions('view', [
        'label' => 'above',
        'type' => 'string',
        'weight' => -4,
      ])
      ->setDisplayOptions('form', [
        'type' => 'string_textfield',
        'weight' => -4,
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['data'] = BaseFieldDefinition::create('map')
      ->setLabel(t('Data'))
      ->setDescription(t('A serialized array of additional data.'));

    $fields['created'] = BaseFieldDefinition::create('created')
      ->setLabel(t('Created'))
      ->setDescription(t('The time that the entity was created.'));

    return $fields;
  }

  /**
   * {@inheritdoc}
   */
  public static function bundleFieldDefinitions(
    EntityTypeInterface $entity_type,
    $bundle,
    array $base_field_definitions
  ) {
    /** @var \Drupal\commerce_stock_local\Entity\StockTransactionTypeInterface $stock_transaction_type */
    $stock_transaction_type = StockTransaction::load($bundle);
    $purchasable_entity_type = $stock_transaction_type->getPurchasableEntityTypeId();
    $fields = [];
    $fields['purchasable_entity'] = clone $base_field_definitions['purchasable_entity'];
    $fields['purchasable_entity']->setSetting('target_type', $purchasable_entity_type);

    return $fields;
  }

  /**
   * {@inheritdoc}
   */
  public function getPurchasableEntity() {
    return $this->getTranslatedReferencedEntity('purchasable_entity');
  }

  /**
   * {@inheritdoc}
   */
  public function getPurchasableEntityId() {
    return $this->get('purchasable_entity')->target_id;
  }

  /**
   * {@inheritdoc}
   */
  public function setLocation(LocalStockLocationInterface $location) {
    $this->set('stock_location', $location);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getLocation() {
    return $this->getTranslatedReferencedEntity('stock_location');
  }

  /**
   * {@inheritdoc}
   */
  public function setZone($zone) {
    $this->set('zone', $zone);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getZone() {
    return $this->get('zone');
  }

  /**
   * {@inheritdoc}
   */
  public function setOrder(OrderInterface $order) {
    $this->set('order', $order);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getOrder() {
    return $this->getTranslatedReferencedEntity('order');
  }

  /**
   * {@inheritdoc}
   */
  public function setRelatedTransaction(StockTransaction $transaction) {
    $this->set('related_transaction', $transaction);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getRelatedTransaction() {
    return $this->get('related_transaction');
  }

  /**
   * {@inheritdoc}
   */
  public function setQuantity($quantity) {
    $this->set('quantity', (string) $quantity);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getQuantity() {
    return (string) $this->get('quantity')->value;
  }

  /**
   * {@inheritdoc}
   */
  public function setTransactionUnitCost(Price $unit_cost) {
    $this->set('unit_cost', $unit_cost);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getTransactionUnitCost() {
    if (!$this->get('unit_cost')->isEmpty()) {
      return $this->get('unit_cost')->first()->toPrice();
    }
  }

  /**
   * {@inheritdoc}
   */
  public function getData($key) {
    $data = [];
    if (!$this->get('data')->isEmpty()) {
      $data = $this->get('data')->first()->getValue();
    }
    return isset($data[$key]) ? $data[$key] : NULL;
  }

  /**
   * {@inheritdoc}
   */
  public function setData($key, $value) {
    $this->get('data')->__set($key, $value);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function setTransactionLogMessage($message) {
    $this->set('log_message', $message);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getTransactionLogMessage() {
    return $this->get('log_message');
  }
}
