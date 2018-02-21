<?php

namespace Drupal\commerce_product_set\Plugin\Commerce\EntityTrait;

use Drupal\commerce\Plugin\Commerce\EntityTrait\EntityTraitBase;
use Drupal\Core\Field\FieldStorageDefinitionInterface;
use Drupal\entity\BundleFieldDefinition;

/**
 * Provides the "order_item_product_set_items" trait.
 *
 * @CommerceEntityTrait(
 *   id = "order_item_product_set_items",
 *   label = @Translation("Product set items"),
 *   entity_types = {"commerce_order_item"}
 * )
 */
class OrderItemProductSetItems extends EntityTraitBase {

  /**
   * {@inheritdoc}
   */
  public function buildFieldDefinitions() {
    $fields = [];
    $fields['product_set_items'] = BundleFieldDefinition::create('commerce_product_set_item')
      ->setLabel('Product set items')
      ->setCardinality(FieldStorageDefinitionInterface::CARDINALITY_UNLIMITED)
      ->setDisplayOptions('form', [
        'type' => 'commerce_product_set_item_default',
        'weight' => 90,
      ]);

    return $fields;
  }

}
