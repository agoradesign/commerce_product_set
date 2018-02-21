<?php

namespace Drupal\commerce_product_set\Plugin\Field\FieldWidget;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\WidgetBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Plugin implementation of 'commerce_product_set_item_default' field widget.
 *
 * @FieldWidget(
 *   id = "commerce_product_set_item_default",
 *   label = @Translation("Default"),
 *   field_types = {
 *     "commerce_product_set_item"
 *   }
 * )
 */
class ProductSetItemDefault extends WidgetBase implements ContainerFactoryPluginInterface {

  /**
   * The product variation storage.
   *
   * @var \Drupal\commerce_product\ProductVariationStorageInterface
   */
  protected $variationStorage;

  /**
   * Constructs a ProductSetItemDefault object.
   *
   * @param string $plugin_id
   *   The plugin_id for the widget.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\Core\Field\FieldDefinitionInterface $field_definition
   *   The definition of the field to which the widget is associated.
   * @param array $settings
   *   The widget settings.
   * @param array $third_party_settings
   *   Any third party settings.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   */
  public function __construct($plugin_id, $plugin_definition, FieldDefinitionInterface $field_definition, array $settings, array $third_party_settings, EntityTypeManagerInterface $entity_type_manager) {
    parent::__construct($plugin_id, $plugin_definition, $field_definition, $settings, $third_party_settings);

    $this->variationStorage = $entity_type_manager->getStorage('commerce_product_variation');
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $plugin_id,
      $plugin_definition,
      $configuration['field_definition'],
      $configuration['settings'],
      $configuration['third_party_settings'],
      $container->get('entity_type.manager')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function formElement(FieldItemListInterface $items, $delta, array $element, array &$form, FormStateInterface $form_state) {
    /** @var \Drupal\commerce_product_set\ProductSetItem $product_set_item */
    $product_set_item = $items[$delta]->toProductSetItem();
    $unit_price = $product_set_item ? $product_set_item->getUnitPrice() : NULL;

    $element['variation_id'] = [
      '#type' => 'entity_autocomplete',
      '#title' => $this->t('Selected variation'),
      '#weight' => 0,
      '#target_type' => 'commerce_product_variation',
      '#default_value' => $product_set_item && $product_set_item->getVariationId() ? $this->variationStorage->load($product_set_item->getVariationId()) : NULL,
    ];
    $element['quantity'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Selected quantity'),
      '#weight' => 1,
      '#default_value' => $product_set_item ? $product_set_item->getQuantity() : '',
    ];
    $element['sku'] = [
      '#type' => 'textfield',
      '#title' => $this->t('SKU'),
      '#weight' => 2,
      '#default_value' => $product_set_item ? $product_set_item->getSku() : '',
    ];
    $element['title'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Title'),
      '#weight' => 3,
      '#default_value' => $product_set_item ? $product_set_item->getTitle() : '',
    ];
    $element['unit_price'] = [
      '#type' => 'commerce_price',
      '#title' => $this->t('Unit price'),
      '#weight' => 4,
      '#default_value' => $unit_price ? $unit_price->toArray() : NULL,
    ];

    return $element;
  }

}
