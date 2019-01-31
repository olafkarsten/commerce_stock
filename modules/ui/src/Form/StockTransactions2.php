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

  protected function getTransactionTypes() {
    $options = [];
    $definitions = $this->stockTransactionTypesManager->getDefinitions();
    foreach ($definitions as $plugin_id => $definition) {
      $options[$plugin_id] = $definition;
    }
    return $options;
  }

  protected function getOptionLabels($options) {
    $labels = [];
    foreach ($options as $plugin_id => $definition) {
      $labels[$plugin_id] = $definition['label']->render();
    }
    return $labels;
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {

    if ($this->request->query->has('commerce_product_v_id')) {
      $variation_id = $this->request->query->get('commerce_product_v_id');
    }
    else {
      return $this->redirect('commerce_stock_ui.stock_transactions1');
    }

    $product_variation = $this->productVariationStorage->load($variation_id);
    $stockService = $this->stockServiceManager->getService($product_variation);
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
        ['purchasable_entity' => $product_variation,]
      );

    $form['transaction_form_container'] = [
      '#type' => 'container',
      '#title' => $this->t('Choose a transaction type'),
      '#weight' => 0
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
    $form['#transaction_types'] = $transactionTypes;
    $form['product_variation_id'] = [
      '#type' => 'value',
      '#value' => $variation_id,
    ];

    $form = $activeTransactionType->buildForm($form, $form_state);

    $form['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Submit'),
      '#weight' => 100
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   *
   * @todo: We need to check the product is managed by a stock service. Or
   * remove this override as it does nothing.
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    parent::validateForm($form, $form_state);
  }

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
      $this->messenger()->addMessage($this->t('@qty has been added to "@variation_title" using a "Received Stock" transaction.', ['@qty' => $qty, '@variation_title' => $product_variation->getTitle()]));
    }
    elseif ($transaction_type == 'sellStock') {
      $order_id = $form_state->getValue('order');
      $user_id = $form_state->getValue('user');
      $this->messenger()->addMessage($this->t('@qty has been removed from "@variation_title" using a "Sell Stock" transaction.', ['@qty' => $qty, '@variation_title' => $product_variation->getTitle()]));
    }
    elseif ($transaction_type == 'returnStock') {
      $order_id = $form_state->getValue('order');
      $user_id = $form_state->getValue('user');
      $this->stockServiceManager->returnStock($product_variation, $source_location, $source_zone, $qty, NULL, $currency_code = NULL, $order_id, $user_id, $transaction_note);
      $this->messenger()->addMessage($this->t('@qty has been added to "@variation_title" using a "Return Stock" transaction.', ['@qty' => $qty, '@variation_title' => $product_variation->getTitle()]));
    }
    elseif ($transaction_type == 'moveStock') {
      $target_location = $form_state->getValue('target_location');
      $target_zone = $form_state->getValue('target_zone');
      $this->stockServiceManager->moveStock($product_variation, $source_location, $target_location, $source_zone, $target_zone, $qty, NULL, $currency_code = NULL, $transaction_note);

      // Display notification for end users.
      $target_location_entity = \Drupal::entityTypeManager()->getStorage('commerce_stock_location')->load($target_location);
      $target_location_name = $target_location_entity->getName();
      $source_location_entity = \Drupal::entityTypeManager()->getStorage('commerce_stock_location')->load($source_location);
      $source_location_name = $source_location_entity->getName();
      $this->messenger()->addMessage($this->t('@qty has been moved from "@source_location" to "@target_location" for "@variation_title" using a "Move Stock" transaction.', [
        '@qty' => $qty,
        '@variation_title' => $product_variation->getTitle(),
        '@source_location' => $source_location_name,
        '@target_location' => $target_location_name,
      ]));
    }
  }

}
