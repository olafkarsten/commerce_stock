<?php

namespace Drupal\commerce_stock_field\Plugin\Field\FieldWidget;

use Drupal\commerce\PurchasableEntityInterface;
use Drupal\commerce_stock\StockServiceManager;
use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\WidgetBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides the base structure for commerce stock level widgets.
 */
abstract class StockLevelWidgetBase extends WidgetBase implements ContainerFactoryPluginInterface {

  /**
   * The Stock Service Manager.
   *
   * @var \Drupal\commerce_stock\StockServiceManager
   */
  protected $stockServiceManager;

  /**
   * {@inheritdoc}
   */
  public function __construct(
    $plugin_id,
    $plugin_definition,
    FieldDefinitionInterface $field_definition,
    array $settings,
    array $third_party_settings,
    StockServiceManager $stock_service_manager
  ) {
    parent::__construct($plugin_id, $plugin_definition, $field_definition, $settings, $third_party_settings);
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
      $plugin_id,
      $plugin_definition,
      $configuration['field_definition'],
      $configuration['settings'],
      $configuration['third_party_settings'],
      $container->get('commerce_stock.service_manager'));
  }

  /**
   * {@inheritdoc}
   */
  public static function defaultSettings() {
    return [
        'transaction_note' => FALSE,
      ] + parent::defaultSettings();
  }

  /**
   * Submits the form.
   */
  public function submitForm($form, FormStateInterface $form_state) {
    parent::submitForm($form, $form_state);
    $this->messenger->addMessage(t('Updated Stock.'));
  }

  /**
   * {@inheritdoc}
   */
  public function settingsSummary() {
    $summary = [];
    $summary[] = $this->t('Transaction note: @transaction_note', ['@transaction_note' => $this->getSetting('transaction_note') ? 'Yes' : 'No']);
    return $summary;
  }

  /**
   * {@inheritdoc}
   */
  public function settingsForm(array $form, FormStateInterface $form_state) {
    $element = [];
    $element['transaction_note'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Provide note'),
      '#default_value' => $this->getSetting('transaction_note'),
      '#description' => $this->t('Provide an input box for a transaction note.'),
      '#states' => [
        'invisible' => [
          'select[name="fields[field_stock_level][settings_edit_form][settings][entry_system]"]' => ['value' => 'transactions'],
        ],
      ],
    ];
    return $element;
  }

  /**
   * Submits the form.
   */
  public function submitAll(array &$form, FormStateInterface $form_state) {
    $this->messenger->addMessage(t('Updated Stock.'));
  }

  /**
   * {@inheritdoc}
   */
  public function formElement(
    FieldItemListInterface $items,
    $delta,
    array $element,
    array &$form,
    FormStateInterface $form_state
  ) {
    $field = $items->first();
    $entity = $items->getEntity();

    // @ToDo Use ::isApplicable instead.
    if (!($entity instanceof PurchasableEntityInterface)) {
      // No stock if this is not a purchasable entity.
      return [];
    }
    if ($entity->isNew()) {
      // We can not work with entities before they are fully created.
      return [];
    }
    // If not a valid context.
    if (!$this->stockServiceManager->isValidContext($entity)) {
      return [];
    }

    // Get the available stock level.
    $level = $field->available_stock;

    if (empty($entity->id())) {
      // We don't have a product ID as yet.
      $element['#description'] = [
        '#type' => 'html_tag',
        '#tag' => 'strong',
        '#value' => $this->t('In order to set the stock level you need to save the product first!'),
      ];
      $element['#disabled'] = TRUE;
    }
    else {
      $element['value']['stocked_entity'] = [
        '#type' => 'value',
        '#value' => $entity,
      ];
      $element['value']['adjustment'] = [
        '#title' => $this->t('Stock level adjustment'),
        '#description' => $this->t('A positive number will add stock, a negative number will remove stock. Current stock level: @stock_level', ['@stock_level' => $level]),
        '#type' => 'number',
        '#step' => 1,
        '#default_value' => 0,
        '#size' => 7,
      ];
      if ($this->getSetting('transaction_note')) {
        $element['value']['stock_transaction_note'] = [
          '#title' => $this->t('Transaction note'),
          '#description' => $this->t('Add a note to this transaction.'),
          '#type' => 'textfield',
          '#default_value' => '',
          '#size' => 50,
        ];
      }
    }

    return $element;
  }

}
