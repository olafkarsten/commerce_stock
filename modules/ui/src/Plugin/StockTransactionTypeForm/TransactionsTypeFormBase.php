<?php

namespace Drupal\commerce_stock_ui\Plugin\StockTransactionTypeForm;

use Drupal\commerce\AjaxFormTrait;
use Drupal\commerce\Element\CommerceElementTrait;
use Drupal\commerce\PurchasableEntityInterface;
use Drupal\commerce_stock\ContextCreatorTrait;
use Drupal\commerce_stock\StockServiceManagerInterface;
use Drupal\Component\Plugin\PluginBase;
use Drupal\Component\Utility\Html;
use Drupal\Component\Utility\NestedArray;
use Drupal\Core\DependencyInjection\DependencySerializationTrait;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides the base class for forms.
 */
abstract class TransactionsTypeFormBase extends PluginBase implements StockTransactionTypeFormInterface {

  use StringTranslationTrait;
  use DependencySerializationTrait;
  use AjaxFormTrait;
  use ContextCreatorTrait;

  /**
   * The stock service.
   *
   * @var \Drupal\commerce_stock\StockServiceManagerInterface
   */
  protected $stockServiceManger;

  /**
   * The current user.
   *
   * @var \Drupal\Core\Session\AccountInterface
   */
  protected $currentUser;

  /**
   * The purchasable entity.
   *
   * @var \Drupal\commerce\PurchasableEntityInterface
   */
  protected $purchasableEntity;

  /**
   * Constructs a new TransactionType object.
   *
   * @param array $configuration
   *   The configuration.
   * @param string $plugin_id
   *   The plugin id.
   * @param mixed $plugin_definition
   *   The plugin definition.
   * @param \Drupal\commerce_stock\StockServiceManagerInterface $stock_service_manager
   *   The stock service manager.
   * @param \Drupal\Core\Session\AccountInterface $current_user
   *   The current user.
   */
  public function __construct(
    array $configuration,
    $plugin_id,
    $plugin_definition,
    StockServiceManagerInterface $stock_service_manager,
    AccountInterface $current_user
  ) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->stockServiceManager = $stock_service_manager;
    $this->setConfiguration($configuration);
    $this->validateConfiguration();
    $this->currentUser = $current_user;
  }

  /**
   * @inheritdoc
   */
  public static function create(
    ContainerInterface $container,
    array $configuration,
    $plugin_id,
    $plugin_definition
  ) {
    $stockServiceManager = $container->get('commerce_stock.service_manager');

    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $stockServiceManager,
      $container->get('current_user')
    );
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
  public function getPurchasableEntity() {
    return $this->purchasableEntity;
  }

  /**
   * {@inheritdoc}
   */
  public function setPurchasableEntity(
    PurchasableEntityInterface $purchasable_entity
  ) {
    $this->purchasableEntity = $purchasable_entity;
    return $this;
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
    return [
      'quantity_step' => 0.01,
    ];
  }

  /**
   * Gets the required configuration for this plugin.
   *
   * @return string[]
   *   The required configuration keys.
   */
  protected function requiredConfiguration() {
    return [];
  }

  /**
   * Validates configuration.
   *
   * @throws \RuntimeException
   *   Thrown if a configuration value is invalid.
   */
  protected function validateConfiguration() {
    foreach ($this->requiredConfiguration() as $key) {
      if (empty($this->configuration[$key])) {
        throw new \RuntimeException(sprintf('The "%s" plugin requires the "%s" configuration key', $this->pluginId, $key));
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public function getLabel() {
    return $this->pluginDefinition['label'];
  }

  /**
   * @inheritdoc
   */
  public function getDescription() {
    return $this->pluginDefinition['description'] ? $this->pluginDefinition['description']->render() : NULL;
  }

  /**
   * @inheritdoc
   */
  public function getTransactionDefaultLogMessage() {
    return $this->pluginDefinition['log_message'] ? $this->pluginDefinition['log_message']->render() : NULL;
  }

  /**
   * {@inheritdoc}
   *
   * Parts of this proudly borrowed from.
   *
   * @see \Drupal\commerce\Plugin\Commerce\InlineForm\InlineFormBase
   */
  public function buildForm(array $form, FormStateInterface $form_state) {

    $form['#tree'] = TRUE;
    $form['#transaction_type_form'] = $this;
    // Workaround for core bug #2897377.
    $form['#id'] = Html::getId('edit-' . implode('-', $form['#parents']));
    $form['#process'][] = [CommerceElementTrait::class, 'attachElementSubmit'];
    $form['#element_validate'][] = [
      CommerceElementTrait::class,
      'validateElementSubmit',
    ];
    $form['#element_validate'][] = [get_class($this), 'runValidate'];
    $form['#commerce_element_submit'][] = [get_class($this), 'runSubmit'];
    // Allow forms to modify the page title.
    $form['#process'][] = [get_class($this), 'updatePageTitle'];

    $purchasableEntity = $this->getPurchasableEntity();
    $stockService = $this->stockServiceManager->getService($purchasableEntity);
    $context = $this->getContext($purchasableEntity);

    $form['locations'] = [
      '#type' => 'value',
      '#value' => $stockService->getConfiguration()->getAvailabilityLocations($context, $purchasableEntity),
    ];

    $form['transaction_details_form'] = [
      '#tree' => TRUE,
      '#type' => 'details',
      '#open' => TRUE,
      '#title' => $this->t('Transaction details'),
      '#attributes' => ['id' => 'transaction-details-wrapper'],
      '#weight' => 10,
    ];

    $form['transaction_details_form']['#description'] = $this->getDescription();

    $form['transaction_details_form']['quantity'] = [
      '#type' => 'number',
      '#title' => $this->t('Quantity'),
      '#default_value' => '1',
      '#step' => '0.01',
      '#required' => TRUE,
      '#weight' => 0,
    ];

    $form['transaction_details_form']['order'] = [
      '#type' => 'entity_autocomplete',
      '#title' => $this->t('Optional order number'),
      '#target_type' => 'commerce_order',
      '#selection_handler' => 'default',
      '#weight' => 40,
      '#required' => FALSE,
    ];

    $form['transaction_details_form']['transaction_note'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Note'),
      '#description' => $this->t('A note for the transaction'),
      '#maxlength' => 255,
      '#size' => 64,
      '#default_value' => $this->getTransactionDefaultLogMessage(),
      '#weight' => 50,
    ];

    $form['transaction_details_form']['user_id'] = [
      '#type' => 'value',
      '#value' => $this->currentUser->id(),
    ];

    $form['transaction_details_form']['purchasable_entity'] = [
      '#type' => 'value',
      '#value' => $this->getPurchasableEntity(),
    ];

    $form['transaction_details_form']['create_transaction'] = [
      '#type' => 'submit',
      '#value' => t('Create transaction'),
      '#name' => 'create_transaction',
      '#limit_validation_errors' => [
        $form['#parents'],
      ],
      '#submit' => [
        [get_called_class(), 'createTransaction'],
      ],
      '#weight' => 100,
    ];

    return $form;
  }

  /**
   * Builds an array of options to use in forms.
   * Keyed by locationId.
   *
   * @param Drupal\commerce_stock_local\Entity\StockLocation[] $locations
   *   The stock locations.
   *
   * @return array
   *   The options.
   */
  protected function getLocationOptions(array $locations) {
    $options = [];
    /** @var \Drupal\commerce_stock_local\Entity\StockLocation $location */
    foreach ($locations as $location) {
      $options[$location->id()] = $location->getName();
    }
    return $options;
  }

  /**
   * {@inheritdoc}
   */
  public function validateTransactionTypeForm(
    array $form,
    FormStateInterface $form_state
  ) {
    // Check if location is selected. If we have only one,
    // set the target/location value.
    // Check if an order is selected and ensure the selected
    // product variation is part of the order.
    // Verify that the product variation belongs to the
    // store and the selected location.
    xdebug_break();
  }

  /**
   * {@inheritdoc}
   */
  public function submitTransactionTypeForm(
    array $form,
    FormStateInterface $form_state
  ) {
    xdebug_break();
  }

  /**
   * Runs the transaction type form validation.
   *
   * @param array $form
   *   The form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   */
  public static function runValidate(
    array &$form,
    FormStateInterface $form_state
  ) {
    /** @var \Drupal\commerce_stock_ui\Plugin\StockTransactionTypeForm\StockTransactionTypeInterface $plugin */
    $plugin = $form['#transaction_type_form'];
    $plugin->validateTransactionTypeForm($form, $form_state);
  }

  /**
   * Runs the transaction type form submission.
   *
   * @param array $form
   *   The form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   */
  public static function runSubmit(
    array &$form,
    FormStateInterface $form_state
  ) {
    /** @var \Drupal\commerce_stock_ui\Plugin\StockTransactionTypeForm\StockTransactionTypeInterface $plugin */
    $plugin = $form['#transaction_type_form'];
    $plugin->submitTransactionTypeForm($form, $form_state);
  }

  /**
   * Updates the page title based on the form's #page_title property.
   *
   * @param array $form
   *   The form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   * @param array $complete_form
   *   The complete form structure.
   *
   * @return array
   *   The form element.
   */
  public static function updatePageTitle(
    array &$form,
    FormStateInterface $form_state,
    array &$complete_form
  ) {
    if (!empty($form['#page_title'])) {
      $complete_form['#title'] = $form['#page_title'];
    }
    return $form;
  }

  /**
   * Prepoplate data for the stock transaction for all the common stuff like
   * quantity and user.
   *
   * @param array $form
   *   The form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The form state.
   */
  public static function extractTransactionData(
    array $form,
    FormStateInterface $form_state
  ) {
    $data = [];

    $data['source']['location'] = $form_state->hasValue([
      'transaction_details_form',
      'source',
      'location',
    ]) ? $form_state->getValue([
      'transaction_details_form',
      'source',
      'location',
    ]) : NULL;

    $data['source']['zone'] = $form_state->hasValue([
      'transaction_details_form',
      'source',
      'zone',
    ]) ? $form_state->getValue([
      'transaction_details_form',
      'source',
      'zone',
    ]) : NULL;

    $data['target']['location'] = $form_state->hasValue([
      'transaction_details_form',
      'target',
      'location',
    ]) ? $form_state->getValue([
      'transaction_details_form',
      'target',
      'location',
    ]) : NULL;

    $data['target']['zone'] = $form_state->hasValue([
      'transaction_details_form',
      'target',
      'zone',
    ]) ? $form_state->getValue([
      'transaction_details_form',
      'target',
      'zone',
    ]) : NULL;
    $data['quantity'] = $form_state->getValue([
      'transaction_details_form',
      'quantity',
    ]);

    $data['user_id'] = $form_state->getValue([
      'transaction_details_form',
      'user_id',
    ]);

    $data['transaction_note'] = $form_state->hasValue([
      'transaction_details_form',
      'transaction_note',
    ]) ? $form_state->getValue([
      'transaction_details_form',
      'transaction_note',
    ]) : '';

    $data['order_id'] = $form_state->hasValue([
      'transaction_details_form',
      'order',
    ]) ? $form_state->getValue([
      'transaction_details_form',
      'order',
    ]) : NULL;

    return $data;
  }

  /**
   * Creates a transaction.
   *
   * @param array $form
   *   The form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The form state.
   */
  public static function createTransaction(array $form, FormStateInterface $form_state) {
    $triggering_element = $form_state->getTriggeringElement();
    $parents = array_slice($triggering_element['#parents'], 0, -1);
    $transaction_type_form = NestedArray::getValue($form, $parents);
    // Clear the transaction quantity field.
    $user_input = &$form_state->getUserInput();
    NestedArray::setValue($user_input, array_merge($parents, ['qty']), 0);

    $form_state->setRebuild();
  }

}
