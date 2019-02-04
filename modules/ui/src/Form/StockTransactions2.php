<?php

namespace Drupal\commerce_stock_ui\Form;

use Drupal\commerce_product\ProductVariationStorage;
use Drupal\commerce_stock\Plugin\StockTransactionTypesManager;
use Drupal\commerce_stock\StockServiceManager;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\Request;

/**
 * The second part of a two part create stock transaction form.
 */
class StockTransactions2 extends FormBase {

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
   * @param \Drupal\commerce_product\ProductVariationStorage $productVariationStorage
   *   The commerce product variation storage.
   * @param \Drupal\commerce_stock\StockServiceManager $stockServiceManager
   *   The stock service manager.
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   The current request.
   * @param \Drupal\commerce_stock\Plugin\StockTransactionTypesManager $stock_transaction_types_manager
   *   The transaction types manager.
   */
  public function __construct(
    ProductVariationStorage $product_variation_storage,
    StockServiceManager $stock_service_manager,
    Request $request,
    StockTransactionTypesManager $stock_transaction_types_manager
  ) {
    $this->productVariationStorage = $product_variation_storage;
    $this->stockServiceManager = $stock_service_manager;
    $this->request = $request;
    $this->stockTransactionTypesManager = $stock_transaction_types_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('entity_type.manager')
        ->getStorage('commerce_product_variation'),
      $container->get('commerce_stock.service_manager'),
      $container->get('request_stack')->getCurrentRequest(),
      $container->get('plugin.manager.stock_transaction_types')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'commerce_stock_transactions2';
  }

  /**
   * Get all transaction type plugin definitions.
   *
   * @return \Drupal\Component\Plugin\Definition\PluginDefinitionInterface[]
   *   Array of plugin definitions for transcation types keyed by plugin id.
   *
   * @return array|mixed[]|null
   */
  protected function getTransactionTypes() {
    $definitions = $this->stockTransactionTypesManager->getDefinitions();
    if (count($definitions) < 1) {
      throw new \RuntimeException('You need to define transaction types (plugins) to use this form.');
    }
    return $definitions;
  }

  /**
   * Builds an array of options to use in select elements from the provided
   * plugin definitions.
   *
   * @param \Drupal\Component\Plugin\Definition\PluginDefinitionInterface[]
   *   The plugin definitons
   *
   * @return array
   *   The options.
   */
  protected function getOptionLabels($plugin_definitions) {
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

    $variation_id = NULL;
    if ($this->request->query->has('commerce_product_v_id')) {
      $variation_id = $this->request->query->get('commerce_product_v_id');
    }
    else {
      // If we have some variation in the form state, use this.
      $variation_id = $form_state->getValue('product_variation');
    }

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

    $form['purchasable_wrapper']['product_variation'] = [
      '#title' => $this->t('Select a product variation'),
      '#type' => 'entity_autocomplete',
      '#target_type' => 'commerce_product_variation',
      '#required' => TRUE,
      '#selection_handler' => 'default',
    ];

    $form['purchasable_wrapper']['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Select variation'),
      '#weight' => 100,
      '#submit' => ['::firstStepSubmit'],
    ];

    if (!$variation_id) {
      return $form;
    }

    $productVariation = $this->productVariationStorage->load($variation_id);

    $stockService = $this->stockServiceManager->getService($productVariation);
    $locations = $stockService->getStockChecker()->getLocationList(TRUE);
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
        'callback' => '::ajaxRefresh',
        'wrapper' => 'transaction-details-wrapper',
      ],
      '#access' => count($transactionOptions) > 1,
    ];
    $form['#transaction_types'] = $transactionTypes;
    $form['product_variation_id'] = [
      '#type' => 'value',
      '#value' => $productVariation->id(),
    ];

    $form = $activeTransactionType->buildForm($form, $form_state);

    $form['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Submit'),
      '#weight' => 100,

    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   *
   * @todo: We need to check the product is managed by a stock service. Or
   * remove this override as it does nothing useful. :olafkarsten
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    parent::validateForm($form, $form_state);
  }

  /**
   *
   *
   * @param array $form
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *
   * @return mixed
   */
  public static function ajaxRefresh(
    array $form,
    FormStateInterface $form_state
  ) {
    return $form['transaction_details_form'];
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {

    $transaction_type = $form_state->getValue('transaction_type');
    $product_variation_id = $form_state->getValue('product_variation_id');
    $source_location = $form_state->getValue('source_location');
    $source_zone = $form_state->getValue('source_zone');
    $qty = $form_state->getValue('transaction_qty');
    $transaction_note = $form_state->getValue('transaction_note');
    $product_variation = $this->productVariationStorage->load($product_variation_id);

    if ($transaction_type == 'receiveStock') {
      $this->stockServiceManager->receiveStock($product_variation, $source_location, $source_zone, $qty, NULL, $currency_code = NULL, $transaction_note);
    }
    elseif ($transaction_type == 'sellStock') {
      $order_id = $form_state->getValue('order');
      $user_id = $form_state->getValue('user');
      $this->stockServiceManager->sellStock($product_variation, $source_location, $source_zone, $qty, NULL, $currency_code = NULL, $order_id, $user_id, $transaction_note);
    }
    elseif ($transaction_type == 'returnStock') {
      $order_id = $form_state->getValue('order');
      $user_id = $form_state->getValue('user');
      $this->stockServiceManager->returnStock($product_variation, $source_location, $source_zone, $qty, NULL, $currency_code = NULL, $order_id, $user_id, $transaction_note);
    }
    elseif ($transaction_type == 'moveStock') {
      $target_location = $form_state->getValue('target_location');
      $target_zone = $form_state->getValue('target_zone');
      $this->stockServiceManager->moveStock($product_variation, $source_location, $target_location, $source_zone, $target_zone, $qty, NULL, $currency_code = NULL, $transaction_note);
    }
  }

  /**
   * Should only be called if javasript is disabled.
   */
  public function firstStepSubmit(
    array &$form,
    FormStateInterface $form_state
  ) {
    if ($form_state->hasValue('product_variation')) {
      $form_state->setRebuild(TRUE);
    }
    return $form;
  }

}
