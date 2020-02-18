<?php

namespace Drupal\commerce_stock_test\Form;

use Drupal\commerce_product\ProductVariationStorage;
use Drupal\commerce_stock\ContextCreatorTrait;
use Drupal\commerce_stock\Plugin\StockTransactionTypesManager;
use Drupal\commerce_stock\StockServiceManager;
use Drupal\commerce_stock_ui\Plugin\StockTransactionTypeFormManager;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a form for testing transaction types plugins.
 *
 * The form allows for selecting a single product variation and after some
 * ajax magic, creating a transaction for the selected variation.
 */
class TransactionTypeFormPluginTestForm extends FormBase {

  use ContextCreatorTrait;

  /**
   * The product variation storage.
   *
   * @var \Drupal\commerce_product\ProductVariationStorage
   */
  protected $productVariationStorage;

  /**
   * The stock service manager.
   *
   * @var \Drupal\commerce_stock\StockServiceManager
   */
  protected $stockServiceManager;

  /**
   * The current request.
   *
   * @var \Symfony\Component\HttpFoundation\Request
   */
  protected $request;

  /**
   * The stock transaction type manager.
   *
   * @var \Drupal\commerce_stock\Plugin\StockTransactionTypesManagerInterface
   */
  protected $stockTransactionTypesManager;

  /**
   * Constructs a StockTransactions2 object.
   *
   * @param \Drupal\commerce_product\ProductVariationStorage $product_variation_storage
   *   The commerce product variation storage.
   * @param \Drupal\commerce_stock\StockServiceManager $stock_service_manager
   *   The stock service manager.
   * @param \Drupal\commerce_stock_ui\Plugin\StockTransactionTypeFormManager $stock_transaction_type_form_manager
   *   The transaction type form manager.
   */
  public function __construct(
    ProductVariationStorage $product_variation_storage,
    StockServiceManager $stock_service_manager,
    StockTransactionTypeFormManager $stock_transaction_type_form_manager
  ) {
    $this->productVariationStorage = $product_variation_storage;
    $this->stockServiceManager = $stock_service_manager;
    $this->stockTransactionTypesManager = $stock_transaction_type_form_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('entity_type.manager')
        ->getStorage('commerce_product_variation'),
      $container->get('commerce_stock.service_manager'),
      $container->get('plugin.manager.stock_transaction_type_form')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'commerce_stock_test_transaction_types_plugins';
  }

  /**
   * Get all transaction type plugin definitions.
   *
   * @return \Drupal\Component\Plugin\Definition\PluginDefinitionInterface[]
   *   Array of plugin definitions for transcation types keyed by plugin id.
   */
  protected function getTransactionTypes() {
    $definitions = $this->stockTransactionTypesManager->getDefinitions();
    if (count($definitions) < 1) {
      throw new \RuntimeException('No transaction type form (plugins) to test.');
    }
    return $definitions;
  }

  /**
   * Builds an array of options to use in select elements from the provided
   * plugin definitions.
   *
   * @param \Drupal\Component\Plugin\Definition\PluginDefinitionInterface[] $plugin_definitions
   *   The plugin definitons.
   *
   * @return array
   *   The options.
   */
  protected function getOptionLabels(array $plugin_definitions) {
    $options = [];
    foreach ($plugin_definitions as $plugin_id => $definition) {
      $options[$plugin_id] = $definition['label']->render();
    }
    return $options;
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {

    $productVariation = $this->productVariationStorage->create([
      'type' => 'default',
      'sku' => 'TestTransactionTypesPlugins',
      'status' => 1,
      'title' => 'TestTitle',
    ]);
    $form['purchasable_wrapper'] = [
      '#type' => 'container',
    ];

    $form['transaction_details_form'] = [
      '#type' => 'details',
      '#open' => FALSE,
      '#title' => $this->t('Transaction details'),
      '#attributes' => ['id' => 'transaction-details-wrapper'],
      '#weight' => 10,
    ];

    $form['purchasable_wrapper']['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Select variation'),
      '#weight' => 100,
      '#submit' => ['::firstStepSubmit'],
    ];

    $stockService = $this->stockServiceManager->getService($productVariation);
    $context = $this->getContext($productVariation);
    $locations = $stockService->getConfiguration()->getAvailabilityLocations($context, $productVariation);
    $location_options = [];
    /** @var \Drupal\commerce_stock\StockLocationInterface $location */
    foreach ($locations as $location) {
      $location_options[$location->getId()] = $location->getName();
    }
    $transactionTypes = $this->getTransactionTypes();
    $transactionOptions = $this->getOptionLabels($transactionTypes);
    $activeTransactionTypeId = 'stock_in';

    if (!empty($form_state->getValue('transaction_type_selection'))) {
      $activeTransactionTypeId = $form_state->getValue('transaction_type_selection');
    }

    $activeTransactionType = $this->stockTransactionTypesManager
      ->createInstance(
        $activeTransactionTypeId,
        ['purchasable_entity' => $productVariation]
      );

    $form['transaction_type'] = [
      '#type' => 'value',
      '#value' => $activeTransactionType,
    ];

    // Get the subform for the selected transaction type.
    $form = $activeTransactionType->buildForm($form, $form_state);

    $form['transaction_form_container'] = [
      '#type' => 'container',
      '#title' => $this->t('Choose a transaction type'),
      '#weight' => 0,
    ];

    $form['transaction_form_container']['transaction_type_selection'] = [
      '#type' => 'select',
      '#title' => $this->t('Transaction type'),
      '#options' => $transactionOptions,
      '#default_value' => $activeTransactionType->getPluginId(),
      '#ajax' => [
        'callback' => [get_class($this), 'ajaxRefresh'],
        'wrapper' => 'transaction-details-wrapper',
      ],
      '#access' => count($transactionOptions) > 1,
    ];

    $form['product_variation'] = [
      '#type' => 'value',
      '#value' => $productVariation,
    ];

    $form['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Submit'),
      '#weight' => 100,
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    parent::validateForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {

  }

}
