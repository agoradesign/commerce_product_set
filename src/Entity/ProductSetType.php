<?php

namespace Drupal\commerce_product_set\Entity;

use Drupal\commerce\Entity\CommerceBundleEntityBase;

/**
 * Defines the product set type entity class.
 *
 * @ConfigEntityType(
 *   id = "commerce_product_set_type",
 *   label = @Translation("Product set type"),
 *   label_collection = @Translation("Product set types"),
 *   label_singular = @Translation("product set type"),
 *   label_plural = @Translation("product set types"),
 *   label_count = @PluralTranslation(
 *     singular = "@count product set type",
 *     plural = "@count product set types",
 *   ),
 *   handlers = {
 *     "access" = "Drupal\commerce\CommerceBundleAccessControlHandler",
 *     "list_builder" = "Drupal\commerce_product_set\ProductSetTypeListBuilder",
 *     "form" = {
 *       "add" = "Drupal\commerce_product_set\Form\ProductSetTypeForm",
 *       "edit" = "Drupal\commerce_product_set\Form\ProductSetTypeForm",
 *       "delete" = "Drupal\commerce\Form\CommerceBundleEntityDeleteFormBase"
 *     },
 *     "route_provider" = {
 *       "default" = "Drupal\Core\Entity\Routing\DefaultHtmlRouteProvider",
 *     },
 *   },
 *   config_prefix = "commerce_product_set_type",
 *   admin_permission = "administer commerce_product_type",
 *   bundle_of = "commerce_product_set",
 *   entity_keys = {
 *     "id" = "id",
 *     "label" = "label",
 *     "uuid" = "uuid"
 *   },
 *   config_export = {
 *     "id",
 *     "label",
 *     "orderItemType",
 *     "traits",
 *     "locked",
 *   },
 *   links = {
 *     "add-form" = "/admin/commerce/config/product-set-types/add",
 *     "edit-form" = "/admin/commerce/config/product-set-types/{commerce_product_set_type}/edit",
 *     "delete-form" = "/admin/commerce/config/product-set-types/{commerce_product_set_type}/delete",
 *     "collection" =  "/admin/commerce/config/product-set-types"
 *   }
 * )
 */
class ProductSetType extends CommerceBundleEntityBase implements ProductSetTypeInterface {

  /**
   * The order item type ID.
   *
   * @var string
   */
  protected $orderItemType;

  /**
   * {@inheritdoc}
   */
  public function getOrderItemTypeId() {
    return $this->orderItemType;
  }

  /**
   * {@inheritdoc}
   */
  public function setOrderItemTypeId($order_item_type_id) {
    $this->orderItemType = $order_item_type_id;
    return $this;
  }

}
