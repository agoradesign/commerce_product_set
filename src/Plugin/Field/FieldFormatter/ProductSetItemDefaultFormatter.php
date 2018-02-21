<?php

namespace Drupal\commerce_product_set\Plugin\Field\FieldFormatter;

use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\FormatterBase;

/**
 * Plugin implementation of the 'commerce_product_set_item_default' formatter.
 *
 * This is formatter is less likely to be used at all, as additional order item
 * fieldss are by default not rendered in the cart, nor within the order view.
 *
 * @FieldFormatter(
 *   id = "commerce_product_set_item_default",
 *   label = @Translation("Default"),
 *   field_types = {
 *     "commerce_product_set_item"
 *   }
 * )
 */
class ProductSetItemDefaultFormatter extends FormatterBase {

  /**
   * {@inheritdoc}
   */
  public function viewElements(FieldItemListInterface $items, $langcode) {
    $elements = [];
    if (!$items->isEmpty()) {
      $set_items = [];
      foreach ($items as $delta => $item) {
        /** @var \Drupal\commerce_product_set\ProductSetItem $product_set_item */
        $product_set_item = $item->toProductSetItem();
        $set_items[$delta] = $product_set_item;
      }
      $elements[0] = [
        '#theme' => 'commerce_product_set_items',
        '#items' => $set_items,
      ];
    }
    return $elements;
  }

}
