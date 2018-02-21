<?php

namespace Drupal\commerce_product_set\Plugin\Validation\Constraint;

use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;

/**
 * Validates the ProductSetSku constraint.
 */
class ProductSetSkuConstraintValidator extends ConstraintValidator {

  /**
   * {@inheritdoc}
   */
  public function validate($items, Constraint $constraint) {
    if (!$item = $items->first()) {
      return;
    }

    $sku = $item->value;
    if (isset($sku) && $sku !== '') {
      $sku_exists = (bool) \Drupal::entityQuery('commerce_product_set')
        ->condition('sku', $sku)
        ->condition('product_set_id', (int) $items->getEntity()->id(), '<>')
        ->range(0, 1)
        ->count()
        ->execute();

      if (!$sku_exists) {
        // Check additionally against product variation SKUs.
        $sku_exists = (bool) \Drupal::entityQuery('commerce_product_variation')
          ->condition('sku', $sku)
          ->range(0, 1)
          ->count()
          ->execute();
      }

      if ($sku_exists) {
        $this->context->buildViolation($constraint->message)
          ->setParameter('%sku', $this->formatValue($sku))
          ->addViolation();
      }
    }
  }

}
