<?php

namespace Drupal\commerce_product_set;

use Drupal\Core\Entity\Display\EntityViewDisplayInterface;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityViewBuilder;

/**
 * Defines the entity view builder for product sets.
 */
class ProductSetViewBuilder extends EntityViewBuilder {

  /**
   * {@inheritdoc}
   */
  protected function alterBuild(array &$build, EntityInterface $entity, EntityViewDisplayInterface $display, $view_mode) {
    if ($display->getComponent('add_to_cart_form')) {
      $build['add_to_cart_form'] = [
        '#lazy_builder' => [
          'commerce_product_set.lazy_builders:addToCartForm', [
            $entity->id(),
            $view_mode,
            // @todo make 'combine' behaviour configurable somehow (on the extra field? if not, on the entity type configuration)
            TRUE,
            $entity->language()->getId(),
          ],
        ],
        '#create_placeholder' => TRUE,
      ];
    }
  }

}
