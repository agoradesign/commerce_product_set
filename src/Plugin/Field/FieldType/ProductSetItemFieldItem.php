<?php

namespace Drupal\commerce_product_set\Plugin\Field\FieldType;

use Drupal\commerce_product_set\ProductSetItem;
use Drupal\Core\Field\FieldItemBase;
use Drupal\Core\Field\FieldStorageDefinitionInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\TypedData\DataDefinition;

/**
 * Plugin implementation of the 'commerce_product_set_item' field type.
 *
 * @FieldType(
 *   id = "commerce_product_set_item",
 *   label = @Translation("Product set item"),
 *   description = @Translation("Represents a single item from a given product set."),
 *   category = @Translation("Commerce"),
 *   default_widget = "commerce_product_set_item_default",
 *   default_formatter = "commerce_product_set_item_default",
 * )
 */
class ProductSetItemFieldItem extends FieldItemBase {

  /**
   * {@inheritdoc}
   */
  public static function propertyDefinitions(FieldStorageDefinitionInterface $field_definition) {
    $properties['variation_id'] = DataDefinition::create('integer')
      ->setLabel(t('Product variation ID'))
      ->setRequired(FALSE);

    $properties['sku'] = DataDefinition::create('string')
      ->setLabel(t('SKU'))
      ->setRequired(FALSE);

    $properties['title'] = DataDefinition::create('string')
      ->setLabel(t('Title'))
      ->setRequired(FALSE);

    $properties['quantity'] = DataDefinition::create('string')
      ->setLabel(t('Quantity'))
      ->setRequired(FALSE);

    $properties['unit_price__number'] = DataDefinition::create('string')
      ->setLabel(t('Unit price number'))
      ->setRequired(FALSE);

    $properties['unit_price__currency_code'] = DataDefinition::create('string')
      ->setLabel(t('Unit price currency code'))
      ->setRequired(FALSE);

    return $properties;
  }

  /**
   * {@inheritdoc}
   */
  public static function schema(FieldStorageDefinitionInterface $field_definition) {
    return [
      'columns' => [
        'variation_id' => [
          'type' => 'int',
          'unsigned' => TRUE,
          'size' => 'normal',
        ],
        'sku' => [
          'type' => 'varchar',
          'length' => 255,
        ],
        'title' => [
          'type' => 'varchar',
          'length' => 255,
        ],
        'quantity' => [
          'type' => 'numeric',
          'precision' => 10,
          'scale' => 2,
        ],
        'unit_price__number' => [
          'type' => 'numeric',
          'precision' => 19,
          'scale' => 6,
        ],
        'unit_price__currency_code' => [
          'type' => 'varchar',
          'length' => 3,
        ],
      ],
    ];
  }

  /**
   * {@inheritdoc}
   */
  public static function defaultFieldSettings() {
    return [
      'available_currencies' => [],
    ] + parent::defaultFieldSettings();
  }

  /**
   * {@inheritdoc}
   */
  public function fieldSettingsForm(array $form, FormStateInterface $form_state) {
    $currencies = \Drupal::entityTypeManager()->getStorage('commerce_currency')->loadMultiple();
    $currency_codes = array_keys($currencies);

    $element = [];
    $element['available_currencies'] = [
      '#type' => count($currency_codes) < 10 ? 'checkboxes' : 'select',
      '#title' => $this->t('Available currencies'),
      '#description' => $this->t('If no currencies are selected, all currencies will be available.'),
      '#options' => array_combine($currency_codes, $currency_codes),
      '#default_value' => $this->getSetting('available_currencies'),
      '#multiple' => TRUE,
      '#size' => 5,
    ];

    return $element;
  }

  /**
   * {@inheritdoc}
   */
  public function getConstraints() {
    $manager = \Drupal::typedDataManager()->getValidationConstraintManager();
    $constraints = parent::getConstraints();
    $constraints[] = $manager->create('ComplexData', [
      'unit_price__number' => [
        'Regex' => [
          'pattern' => '/^[+-]?((\d+(\.\d*)?)|(\.\d+))$/i',
        ],
      ],
    ]);
    // @todo Currency validation only works for PriceItem objects. We can either
    // skip currency validation or copy the validation logic into an own class.
//    $available_currencies = $this->getSetting('available_currencies');
//    $constraints[] = $manager->create('Currency', ['availableCurrencies' => $available_currencies]);

    return $constraints;
  }

  /**
   * {@inheritdoc}
   */
  public function isEmpty() {
    return empty($this->variation_id) || empty($this->quantity);
  }

  /**
   * {@inheritdoc}
   */
  public function setValue($values, $notify = TRUE) {
    // Allow callers to pass a Price value object as the field item value.
    if ($values instanceof ProductSetItem) {
      $values = $values->toFlatArray();
    }
    parent::setValue($values, $notify);
  }

  /**
   * Gets the ProductSetItem value object for the current field item.
   *
   * @return \Drupal\commerce_product_set\ProductSetItem|null
   *   The ProductSetItem value object.
   */
  public function toProductSetItem() {
    if ($this->isEmpty()) {
      return NULL;
    }
    $values = $this->toArray();
    $values['unit_price'] = [
      'number' => $values['unit_price__number'],
      'currency_code' => $values['unit_price__currency_code'],
    ];
    return ProductSetItem::fromArray($values);
  }

}
