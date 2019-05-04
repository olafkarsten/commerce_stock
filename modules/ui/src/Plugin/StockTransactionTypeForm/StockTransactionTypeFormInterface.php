<?php

namespace Drupal\commerce_stock_ui\Plugin\StockTransactionTypeForm;

use Drupal\commerce\PurchasableEntityInterface;
use Drupal\Component\Plugin\ConfigurablePluginInterface;
use Drupal\Component\Plugin\PluginInspectionInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;

/**
 * Defines an interface for commerce StockTransactionTypeForm plugins.
 */
interface StockTransactionTypeFormInterface extends ConfigurablePluginInterface, PluginInspectionInterface, ContainerFactoryPluginInterface {

  /**
   * Sets the purchasable entity.
   *
   * @param \Drupal\commerce\PurchasableEntityInterface $purchasable_entity
   *   The purchasable entity.
   *
   * @return $this
   */
  public function setPurchasableEntity(PurchasableEntityInterface $purchasable_entity);

  /**
   * Gets the purchasable entity.
   *
   * @return \Drupal\commerce\PurchasableEntityInterface
   *   The purchasable entity.
   */
  public function getPurchasableEntity();

  /**
   * Gets the form label.
   *
   * @return string
   *   The transaction type form label.
   */
  public function getLabel();

  /**
   * Gets the the transaction type description.
   *
   * @return string
   *   The transaction type form description.
   */
  public function getDescription();

  /**
   * Get the transaction type form default log message.
   *
   * @return string|null
   *   The transaction type form default log message or null.
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
  public function validateTransactionTypeForm(array $form, FormStateInterface $form_state);

  /**
   * Submits the form.
   *
   * @param array $form
   *   The form, containing the following basic properties:
   *   - #parents: Identifies the location of the field values in $form_state.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The form state of the complete form.
   */
  public function submitTransactionTypeForm(array $form, FormStateInterface $form_state);

}
