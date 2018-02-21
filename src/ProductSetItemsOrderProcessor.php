<?php

namespace Drupal\commerce_product_set;

use Drupal\commerce_order\Entity\OrderInterface;
use Drupal\commerce_order\OrderProcessorInterface;
use Drupal\commerce_product_set\Entity\ProductSetInterface;

/**
 * Provides an order processor that keeps product set items up to date.
 */
class ProductSetItemsOrderProcessor implements OrderProcessorInterface {

  /**
   * {@inheritdoc}
   */
  public function process(OrderInterface $order) {
    foreach ($order->getItems() as $order_item) {
      $purchased_entity = $order_item->getPurchasedEntity();
      if ($purchased_entity instanceof ProductSetInterface && $order_item->hasField('product_set_items')) {
        $order_item->set('product_set_items', $purchased_entity->getProductSetItems())->save();
      }
    }
  }

}
