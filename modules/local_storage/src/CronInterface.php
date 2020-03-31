<?php

namespace Drupal\commerce_stock;

/**
 * Provides the interface for the commerce stock module's cron.
 *
 * Queues stock transactions for updating the location stock levels.
 */
interface CronInterface {

  /**
   * Runs the cron.
   */
  public function run();

}
