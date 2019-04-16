<?php

namespace Drupal\commerce_stock\Annotation;

use Drupal\Component\Annotation\Plugin;

/**
 * Defines a Stock events item annotation object.
 *
 * @see \Drupal\commerce_stock\Plugin\StockEventsManager
 * @see plugin_api
 *
 * @Annotation
 */
class StockTransactionTypes extends Plugin {

  /**
   * The plugin ID.
   *
   * @var string
   */
  public $id;

  /**
   * The label of the plugin.
   *
   * @var \Drupal\Core\Annotation\Translation
   *
   * @ingroup plugin_translatable
   */
  public $label;

  /**
   * The description of the plugin.
   *
   * @var \Drupal\Core\Annotation\Translation
   *
   * @ingroup plugin_translatable
   */
  public $description;

  /**
   * The default log message of the plugin.
   *
   * @var \Drupal\Core\Annotation\Translation
   *
   * @ingroup plugin_translatable
   */
  public $log_message;

}
