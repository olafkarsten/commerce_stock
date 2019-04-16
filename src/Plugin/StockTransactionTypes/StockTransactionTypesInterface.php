<?php

namespace Drupal\commerce_stock\Plugin\StockTransactionTypes;

use Drupal\Component\Plugin\ConfigurablePluginInterface;
use Drupal\Component\Plugin\PluginInspectionInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;

/**
 * Defines an interface for Stock events plugins.
 */
interface StockTransactionTypesInterface extends ConfigurablePluginInterface, PluginInspectionInterface, ContainerFactoryPluginInterface {

  const STOCK_IN = 1;

  const STOCK_OUT = 2;

  const STOCK_SALE = 4;

  const STOCK_RETURN = 5;

  const NEW_STOCK = 6;

  const MOVEMENT_FROM = 7;

  const MOVEMENT_TO = 8;

  /**
   * Sets the configuration for this plugin instance.
   *
   * The required configuration for each plugin is an entity
   * that implements Drupal/commerce/PurchasableEntityInterface.
   *
   * You can provide an transaction_log_message. This will override the
   * default log message provdided by the plugin definition.
   *
   * @code
   *   [
   *    'purchasable_entity' => $entity,
   *    'transaction_log_message' => $log,
   *   ]
   * @codeend
   *
   * @param array $configuration
   *   An associative array containing the plugin's configuration.
   *
   * @throws \InvalidArgumentException
   *   If the purchasable entity is missing.
   */
  public function setConfiguration(array $configuration);

  /**
   * Gets the form label.
   *
   * @return string
   *   The transaction type label.
   */
  public function getLabel();

  /**
   * Gets the the transaction type description.
   *
   * @return string
   *   The transaction type description.
   */
  public function getDescription();

  /**
   * Get the transaction types default log message.
   *
   * @return string | null
   *   The transaction type default log message or null.
   */
  public function getTransactionDefaultLogMessage();

  /**
   * Builds the form.
   *
   * @param array $form
   *   The form, containing the following basic properties:
   *   - #parents: Identifies the location of the field values in $form_state.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The form state of the complete form.
   *
   * @return array
   *   The built form.
   */
  public function buildForm(array $form, FormStateInterface $form_state);

  /**
   * Validates the form.
   *
   * @param array $form
   *   The form, containing the following basic properties:
   *   - #parents: Identifies the location of the field values in $form_state.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The form state of the complete form.
   */
  public function validateForm(array $form, FormStateInterface $form_state);

  /**
   * Submits the form.
   *
   * @param array $form
   *   The form, containing the following basic properties:
   *   - #parents: Identifies the location of the field values in $form_state.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The form state of the complete form.
   */
  public function submitForm(array $form, FormStateInterface $form_state);

}
