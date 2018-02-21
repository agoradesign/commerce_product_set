<?php

namespace Drupal\commerce_product_set\Plugin\Field\FieldFormatter;

use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\FormatterBase;

/**
 * Plugin implementation of the 'commerce_product_set_item_default' formatter.
 *
 * This is formatter is less likely to be used at all, as additional order item
 * fields are by default not rendered in the cart, nor within the order view.
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
      /** @var \Drupal\commerce_price\Price|null $total_price */
      $total_price = NULL;
      $set_items = [];
      foreach ($items as $delta => $item) {
        /** @var \Drupal\commerce_product_set\ProductSetItem $product_set_item */
        $product_set_item = $item->toProductSetItem();
        $set_items[$delta] = $product_set_item;
        if ($total_price) {
          $total_price = $total_price->add($product_set_item->getUnitPrice()->multiply($product_set_item->getQuantity()));
        }
        else {
          $total_price = $product_set_item->getUnitPrice()->multiply($product_set_item->getQuantity());
        }
      }
      $elements[0] = [
        '#theme' => 'commerce_product_set_items',
        '#items' => $set_items,
        '#total_price' => $total_price,
      ];
    }
    return $elements;
  }

}
