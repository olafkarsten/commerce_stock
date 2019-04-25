<?php

/**
 * @file
 * Contains commerce_stock_transaction.page.inc.
 *
 * Page callback for Stock transaction entities.
 */

use Drupal\Core\Render\Element;

/**
 * Prepares variables for Stock transaction templates.
 *
 * Default template: commerce_stock_transaction.html.twig.
 *
 * @param array $variables
 *   An associative array containing:
 *   - elements: An associative array containing the user information and any
 *   - attributes: HTML attributes for the containing element.
 */
function template_preprocess_commerce_stock_transaction(array &$variables) {
  // Fetch StockTransaction Entity Object.
  $commerce_stock_transaction = $variables['elements']['#commerce_stock_transaction'];

  // Helpful $content variable for templates.
  foreach (Element::children($variables['elements']) as $key) {
    $variables['content'][$key] = $variables['elements'][$key];
  }
}
