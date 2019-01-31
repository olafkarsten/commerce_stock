<?php

namespace Drupal\commerce_stock\Plugin\StockTransactionTypes;

use Drupal\commerce\PurchasableEntityInterface;
use Drupal\commerce_stock\StockServiceManagerInterface;
use Drupal\Component\Plugin\PluginBase;
use Drupal\Component\Utility\NestedArray;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides the base class for forms.
 */
abstract class TransactionTypeFormBase extends PluginBase implements StockTransactionTypesInterface {

  use StringTranslationTrait;

  /**
   * The stock service.
   *
   * @var \Drupal\commerce_stock\StockServiceManagerInterface
   */
  protected $stockServiceManger;

  /**
   * @var \Drupal\Core\Session\AccountInterface
   */
  protected $currentUser;

  /**
   * StockIn constructor.
   *
   * @param array $configuration
   * @param $plugin_id
   * @param $plugin_definition
   * @param \Drupal\commerce_stock\StockServiceManagerInterface $stock_service_manager
   * @param \Drupal\Core\Session\AccountInterface
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
  public function setConfiguration(array $configuration) {
    $this->configuration = NestedArray::mergeDeep($this->defaultConfiguration(), $configuration);
  }

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    return ['purchasable_entity' => NULL];
  }

  /**
   * Gets the required configuration for this plugin.
   *
   * @return string[]
   *   The required configuration keys.
   */
  protected function requiredConfiguration() {
    return ['purchasable_entity'];
  }

  /**
   * Validates configuration.
   *
   * @throws \RuntimeException
   *   Thrown if a configuration value is invalid.
   */
  protected function validateConfiguration() {

    if (empty($this->configuration['purchasable_entity'])) {
      throw new \RuntimeException(sprintf('The "%s" plugin requires the "purchasable_entity" configuration key.', $this->pluginId));
    }

    foreach ($this->requiredConfiguration() as $key) {
      if ($key === 'purchasable_entity') {
        if (!($this->configuration['purchasable_entity'] instanceof PurchasableEntityInterface)) {
          throw new \RuntimeException(sprintf('The "%s" plugin requires a Drupal\commerce\PurchasableEntityInterface entity.', $this->pluginId));
        }
        continue;
      }
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
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {

    $config = $this->getConfiguration();
    $stockService = $this->stockServiceManager->getService($config['purchasable_entity']);
    $form['locations'] = $stockService->getStockChecker()
      ->getLocationList(TRUE);

    // Allow forms to modify the page title.
    $form['#process'][] = [get_class($this), 'updatePageTitle'];

    $form['transaction_details_form'] = [
      '#tree' => TRUE,
      '#type' => 'details',
      '#open' => TRUE,
      '#title' => $this->t('Transaction details'),
      '#attributes' => ['id' => 'transaction-details-wrapper'],
      '#weight' => 10,
    ];

    $form['transaction_details_form']['transaction_qty'] = [
      '#type' => 'number',
      '#title' => $this->t('Quantity'),
      '#default_value' => '1',
      '#step' => '0.01',
      '#required' => TRUE,
      '#weight' => 0,
    ];

    $form['transaction_details_form']['user_id'] = [
      '#type' => 'hidden',
      '#value' => $this->currentUser->id(),
    ];

    $form['transaction_details_form']['transaction_note'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Note'),
      '#description' => $this->t('A note for the transaction'),
      '#maxlength' => 255,
      '#size' => 64,
      '#default_value' => '',
      '#weight' => 50,
    ];

    return $form;
  }

  /**
   * Builds an array of options to use in forms.
   * Keyed by locationId.
   *
   * @param array Drupal\commerce_stock_local\Entity\StockLocation[] $locations
   *
   * @return array
   *   the options.
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
  public function validateForm(array &$form, FormStateInterface $form_state) {
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
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
    if (!empty(form['#page_title'])) {
      $complete_form['#title'] = $form['#page_title'];
    }
    return $form;
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
   * @inheritdoc
   */
  public function getTransactionDefaultLogMessage() {
    return $this->configuration['log_message'] ? $this->t($this->configuration['log_message']) : NULL;
  }

}
