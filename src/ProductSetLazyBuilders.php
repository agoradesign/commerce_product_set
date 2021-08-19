<?php

namespace Drupal\commerce_product_set;

use Drupal\Core\Entity\EntityRepositoryInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Form\FormBuilderInterface;
use Drupal\Core\Form\FormState;
use Drupal\Core\Security\TrustedCallbackInterface;

/**
 * Provides #lazy_builder callbacks.
 */
class ProductSetLazyBuilders implements TrustedCallbackInterface {

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * The form builder.
   *
   * @var \Drupal\Core\Form\FormBuilderInterface
   */
  protected $formBuilder;

  /**
   * The entity repository.
   *
   * @var \Drupal\Core\Entity\EntityRepositoryInterface
   */
  protected $entityRepository;

  /**
   * Constructs a new ProductSetLazyBuilders object.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   * @param \Drupal\Core\Form\FormBuilderInterface $form_builder
   *   The form builder.
   * @param \Drupal\Core\Entity\EntityRepositoryInterface $entity_repository
   *   The entity repository.
   */
  public function __construct(EntityTypeManagerInterface $entity_type_manager, FormBuilderInterface $form_builder, EntityRepositoryInterface $entity_repository) {
    $this->entityTypeManager = $entity_type_manager;
    $this->formBuilder = $form_builder;
    $this->entityRepository = $entity_repository;
  }

  /**
   * Builds the add to cart form.
   *
   * @param string $product_set_id
   *   The product set ID.
   * @param string $view_mode
   *   The view mode used to render the product set.
   * @param bool $combine
   *   TRUE to combine order items containing the same product set.
   * @param string $langcode
   *   The langcode for the language that should be used in form.
   *
   * @return array
   *   A renderable array containing the cart form.
   */
  public function addToCartForm($product_set_id, $view_mode, $combine, $langcode) {
    /** @var \Drupal\commerce_order\OrderItemStorageInterface $order_item_storage */
    $order_item_storage = $this->entityTypeManager->getStorage('commerce_order_item');
    /** @var \Drupal\commerce_product_set\Entity\ProductSetInterface $product_set */
    $product_set = $this->entityTypeManager->getStorage('commerce_product_set')->load($product_set_id);
    // Load Product for current language.
    $product_set = $this->entityRepository->getTranslationFromContext($product_set, $langcode);

    $order_item = $order_item_storage->createFromPurchasableEntity($product_set);
    $order_item->set('product_set_items', $product_set->getProductSetItems());
    /** @var \Drupal\commerce_cart\Form\AddToCartFormInterface $form_object */
    $form_object = $this->entityTypeManager->getFormObject('commerce_order_item', 'add_to_cart');
    $form_object->setEntity($order_item);
    $form_object->setFormId($form_object->getBaseFormId() . '_commerce_product_set_' . $product_set_id);
    $form_state = (new FormState())->setFormState([
      'product_set' => $product_set,
      'view_mode' => $view_mode,
      'settings' => [
        'combine' => $combine,
      ],
    ]);

    return $this->formBuilder->buildForm($form_object, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public static function trustedCallbacks() {
    return ['addToCartForm'];
  }

}
