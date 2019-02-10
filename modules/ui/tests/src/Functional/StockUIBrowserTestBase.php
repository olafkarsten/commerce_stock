<?php

namespace Drupal\Tests\commerce_stock_ui\Functional;

use Drupal\Tests\commerce\FunctionalJavascript\JavascriptTestTrait;
use Drupal\Tests\commerce_stock\Functional\StockBrowserTestBase;

/**
 * Defines base class for commerce_stock_enforcement test cases.
 */
abstract class StockUIBrowserTestBase extends StockBrowserTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = [
    'commerce_stock_ui_test',
    'commerce_stock_ui',
    'commerce_stock_local',
    'commerce_stock',
  ];

}
