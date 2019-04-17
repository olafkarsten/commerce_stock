<?php

namespace Drupal\commerce_stock_ui\Form;

use Drupal\commerce_product\ProductVariationStorage;
use Drupal\commerce_stock_ui\Plugin\StockTransactionTypeFormManager;
use Drupal\commerce_stock\StockServiceManager;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\Request;
use Drupal\Component\Plugin\PluginManagerInterface;

/**
 * Provides a form to create single transactions for a certain product variation.
 *
 * The form allows for selecting a single product variation and after some
 * ajax magic, creating a transaction for the selected variation.
 */
class StockTransactionForm extends FormBase {

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
   * The stock transaction type form manager.
   *
   * @var \Drupal\Component\Plugin\PluginManagerInterface
   */
  protected $stockTransactionTypeFormManager;

  /**
   * Constructs a StockTransactions2 object.
   *
   * @param \Drupal\commerce_product\ProductVariationStorage $product_variation_storage
   *   The commerce product variation storage.
   * @param \Drupal\commerce_stock\StockServiceManager $stock_service_manager
   *   The stock service manager.
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   The current request.
   * @param Drupal\Component\Plugin\PluginManagerInterface $stock_transaction_type_form_manager
   *   The transaction type form manager.
   */
  public function __construct(
    ProductVariationStorage $product_variation_storage,
    StockServiceManager $stock_service_manager,
    Request $request,
    PluginManagerInterface $stock_transaction_type_form_manager
  ) {
    $this->productVariationStorage = $product_variation_storage;
    $this->stockServiceManager = $stock_service_manager;
    $this->request = $request;
    $this->stockTransactionTypeFormManager = $stock_transaction_type_form_manager;
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
      $container->get('plugin.manager.stock_transaction_type_form')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'commerce_stock_create_single_transaction';
  }

  /**
   * Get all transaction form type plugin definitions.
   *
   * @return \Drupal\Component\Plugin\Definition\PluginDefinitionInterface[]
   *   Plugin definitions for transcation type formes keyed by plugin id.
   */
  protected function getTransactionTypes() {
    $definitions = $this->stockTransactionTypeFormManager->getDefinitions();
    if (count($definitions) < 1) {
      throw new \RuntimeException('You need to define transaction type form plugins to use this form.');
    }
    return $definitions;
  }

  /**
   * Builds an array of options to use in select elements from the provided
   * plugin definitions.
   *
   * @param \Drupal\Component\Plugin\Definition\PluginDefinitionInterface[] $plugin_definitions
   *   The plugin definitions.
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

    $variation_id = NULL;
    // Using the same query parameter as commerce core here.
    // @see ProductVariationStorage::loadFromContext
    if ($this->request->query->has('v')) {
      $variation_id = $this->request->query->get('v');
    }
    else {
      // If we have some variation in the form state, use this.
      $variation_id = $form_state->getValue('product_variation');
    }

    $form['stock_transaction_form']['#attributes'] = ['id' => 'stock-transaction-type-form'];

    // We need all the wrappers here, to ensure the ajax callbacks have something
    // to attach their payload.
    $form['stock_transaction_form']['purchasable_wrapper'] = [
      '#type' => 'container',
      '#attributes' => ['id' => 'purchasable-wrapper'],
      '#name' => 'purchasable-wrapper',
    ];

    $form['stock_transaction_form']['purchasable_wrapper']['product_variation'] = [
      '#title' => $this->t('Select a product variation'),
      '#type' => 'entity_autocomplete',
      '#target_type' => 'commerce_product_variation',
      '#required' => TRUE,
      '#selection_handler' => 'default',
    ];

    $form['stock_transaction_form']['purchasable_wrapper']['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Select variation'),
      '#weight' => 100,
      '#submit' => ['::firstStepSubmit'],

    ];

    // We first need the purchasable entity, before we can build the entire form.
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

    $activeTransactionType = $this->stockTransactionTypeFormManager
      ->createInstance(
        $activeTransactionTypeId,
        [],
        $productVariation
      );

    $form['stock_transaction_form']['transaction_type_detail_form'] = [
      '#transaction_type_form' => $activeTransactionType,
      '#attributes' => ['#id' => 'transaction-details-wrapper'],
      '#parents' => ['stock_transaction_form', 'transaction_type_detail_form'],
    ];

    // Get the subform for the selected transaction type.
    $form['stock_transaction_form']['transaction_type_detail_form'] = $activeTransactionType->buildForm($form['stock_transaction_form']['transaction_type_detail_form'], $form_state);

    $form['stock_transaction_form']['transaction_form_container'] = [
      '#type' => 'container',
      '#title' => $this->t('Choose a transaction type'),
      '#weight' => 0,
    ];

    $form['stock_transaction_form']['transaction_form_container']['transaction_type_selection'] = [
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
   * Ajax callback for transaction type selection form element.
   *
   * @param array $form
   *   The form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The form state.
   *
   * @return array
   *   The (sub) form.
   */
  public static function ajaxRefresh(
    array $form,
    FormStateInterface $form_state
  ) {
    return $form['stock_transaction_form']['transaction_type_detail_form'];
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
   * Ajax callback for the product variatione selection part of the form.
   */
  public static function firstStepSubmit(
    array $form,
    FormStateInterface $form_state
  ) {
    if ($form_state->hasValue('product_variation')) {
      $form_state->setRebuild(TRUE);
    }
    return $form;
  }

  /**
   * Ajax callback for the product variation selection part of the form.
   */
  public static function ajaxRefreshForm(
    array $form,
    FormStateInterface $form_state
  ) {
    return $form;
  }

}
