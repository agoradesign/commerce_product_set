<?php

namespace Drupal\commerce_product_set\Plugin\Validation\Constraint;

use Symfony\Component\Validator\Constraint;

/**
 * Ensures product set SKU uniqueness across both sets and variations.
 *
 * @Constraint(
 *   id = "ProductSetSku",
 *   label = @Translation("The SKU of the product set.", context = "Validation")
 * )
 */
class ProductSetSkuConstraint extends Constraint {

  public $message = 'The SKU %sku is already in use and must be unique.';

}
