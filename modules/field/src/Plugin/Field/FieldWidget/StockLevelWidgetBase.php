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
        'step' => '1',
      ] + parent::defaultSettings();
  }

  /**
   * {@inheritdoc}
   */
  public function settingsSummary() {
    $summary = [];
    if ($this->getSetting('step') == 1) {
      $summary[] = $this->t('Decimal stock levels not allowed');
    }
    else {
      $summary[] = $this->t('Decimal stock levels allowed');
      $summary[] = $this->t('Step: @step', ['@step' => $this->getSetting('step')]);
    }
    $summary[] = $this->t('Transaction note: @transaction_note', ['@transaction_note' => $this->getSetting('transaction_note') ? 'Yes' : 'No']);
    return $summary;
  }

  /**
   * {@inheritdoc}
   */
  public function settingsForm(array $form, FormStateInterface $form_state) {
    $field_name = $this->fieldDefinition->getName();
    $element = [];
    $element['transaction_note'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Custom transaction note'),
      '#default_value' => $this->getSetting('transaction_note'),
      '#description' => $this->t('Allow a custom transaction note.'),
    ];
    // Shameless borrowed from commerce quantity field.
    $step = $this->getSetting('step');
    $element['#element_validate'][] = [get_class($this), 'validateSettingsForm'];
    $element['allow_decimal'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Allow decimal quantities'),
      '#default_value' => $step != '1',
    ];
    $element['step'] = [
      '#type' => 'select',
      '#title' => $this->t('Step'),
      '#description' => $this->t('Only stock levels that are multiples of the selected step will be allowed. Maximum precision is 2 (0.01).'),
      '#default_value' => $step != '1' ? $step : '0.1',
      '#options' => [
        '0.1' => '0.1',
        '0.01' => '0.01',
        '0.25' => '0.25',
        '0.5' => '0.5',
        '0.05' => '0.05',
      ],
      '#states' => [
        'visible' => [
          ':input[name="fields[' . $field_name . '][settings_edit_form][settings][allow_decimal]"]' => ['checked' => TRUE],
        ],
      ],
      '#required' => TRUE,
    ];
    return $element;
  }

  /**
   * Validates the settings form.
   *
   * @param array $element
   *   The settings form element.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The form state.
   */
  public static function validateSettingsForm(array $element, FormStateInterface $form_state) {
    $value = $form_state->getValue($element['#parents']);
    if (empty($value['allow_decimal'])) {
      $value['step'] = '1';
    }
    unset($value['allow_decimal']);
    $form_state->setValue($element['#parents'], $value);
  }

  /**
   * Submits the form.
   */
  public function submitForm($form, FormStateInterface $form_state, $messenger) {
    $messenger->addMessage(t('Updated Stock.'));
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
    // @ToDo Consider how this may change
    // @see https://www.drupal.org/project/commerce_stock/issues/2949569
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
        '#step' => $this->getSetting('step'),
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
