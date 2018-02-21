<?php

namespace Drupal\commerce_product_set\Plugin\Field\FieldFormatter;

use Drupal\commerce\Context;
use Drupal\commerce_order\AdjustmentTypeManager;
use Drupal\commerce_order\PriceCalculatorInterface;
use Drupal\commerce_product_set\ProductSetItem;
use Drupal\commerce_store\CurrentStoreInterface;
use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\FormatterBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Session\AccountInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Defines a field formatter for listing the parts of a product set.
 *
 * @FieldFormatter(
 *   id = "product_set_variations",
 *   label = @Translation("Product set variations"),
 *   description = @Translation("Display the product set parts (variations), using a Twig Template."),
 *   field_types = {
 *     "entity_reference_quantity"
 *   }
 * )
 */
class ProductSetVariationsFormatter extends FormatterBase implements ContainerFactoryPluginInterface {

  /**
   * The adjustment type manager.
   *
   * @var \Drupal\commerce_order\AdjustmentTypeManager
   */
  protected $adjustmentTypeManager;

  /**
   * The current user.
   *
   * @var \Drupal\Core\Session\AccountInterface
   */
  protected $currentUser;

  /**
   * The current store.
   *
   * @var \Drupal\commerce_store\CurrentStoreInterface
   */
  protected $currentStore;

  /**
   * The price calculator.
   *
   * @var \Drupal\commerce_order\PriceCalculatorInterface
   */
  protected $priceCalculator;

  /**
   * Constructs a new ProductSetVariationsFormatter object.
   *
   * @param string $plugin_id
   *   The plugin_id for the formatter.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\Core\Field\FieldDefinitionInterface $field_definition
   *   The definition of the field to which the formatter is associated.
   * @param array $settings
   *   The formatter settings.
   * @param string $label
   *   The formatter label display setting.
   * @param string $view_mode
   *   The view mode.
   * @param array $third_party_settings
   *   Any third party settings settings.
   * @param \Drupal\commerce_order\AdjustmentTypeManager $adjustment_type_manager
   *   The adjustment type manager.
   * @param \Drupal\commerce_store\CurrentStoreInterface $current_store
   *   The current store.
   * @param \Drupal\Core\Session\AccountInterface $current_user
   *   The current user.
   * @param \Drupal\commerce_order\PriceCalculatorInterface $price_calculator
   *   The price calculator.
   */
  public function __construct($plugin_id, $plugin_definition, FieldDefinitionInterface $field_definition, array $settings, $label, $view_mode, array $third_party_settings, AdjustmentTypeManager $adjustment_type_manager, CurrentStoreInterface $current_store, AccountInterface $current_user, PriceCalculatorInterface $price_calculator) {
    parent::__construct($plugin_id, $plugin_definition, $field_definition, $settings, $label, $view_mode, $third_party_settings);

    $this->adjustmentTypeManager = $adjustment_type_manager;
    $this->currentStore = $current_store;
    $this->currentUser = $current_user;
    $this->priceCalculator = $price_calculator;
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
      $configuration['label'],
      $configuration['view_mode'],
      $configuration['third_party_settings'],
      $container->get('plugin.manager.commerce_adjustment_type'),
      $container->get('commerce_store.current_store'),
      $container->get('current_user'),
      $container->get('commerce_order.price_calculator')
    );
  }

  /**
   * {@inheritdoc}
   */
  public static function defaultSettings() {
    return [
      'title' => t('Set consisting of:'),
      'adjustment_types' => [],
    ] + parent::defaultSettings();
  }

  /**
   * {@inheritdoc}
   */
  public function settingsForm(array $form, FormStateInterface $form_state) {
    $elements = [];

    $elements['title'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Optional title.'),
      '#description' => $this->t('The title is independent from the field label and is used as heading for listing the set items.'),
      '#default_value' => $this->getSetting('title'),
    ];

    $elements['adjustment_types'] = [
      '#type' => 'checkboxes',
      '#title' => $this->t('Adjustments'),
      '#options' => [],
      '#default_value' => $this->getSetting('adjustment_types'),
    ];
    foreach ($this->adjustmentTypeManager->getDefinitions() as $plugin_id => $definition) {
      if (!in_array($plugin_id, ['custom'])) {
        $label = $this->t('Apply @label to the calculated price', ['@label' => $definition['plural_label']]);
        $elements['adjustment_types']['#options'][$plugin_id] = $label;
      }
    }

    return $elements;
  }

  /**
   * {@inheritdoc}
   */
  public function settingsSummary() {
    $summary = [];

    $title = $this->getSetting('title');
    if (empty($title)) {
      $summary[] = $this->t('No title is used.');
    }
    else {
      $summary[] = $this->t('Title: @title', ['@title' => $title]);
    }

    $enabled_adjustment_types = array_filter($this->getSetting('adjustment_types'));
    foreach ($this->adjustmentTypeManager->getDefinitions() as $plugin_id => $definition) {
      if (in_array($plugin_id, $enabled_adjustment_types)) {
        $summary[] = $this->t('Apply @label to the calculated price', ['@label' => $definition['plural_label']]);
      }
    }

    return $summary;
  }

  /**
   * {@inheritdoc}
   */
  public function viewElements(FieldItemListInterface $items, $langcode) {
    $elements = [];
    if (!$items->isEmpty()) {
      $set_items = [];
      foreach ($items as $delta => $item) {
        /** @var \Drupal\commerce_product\Entity\ProductVariationInterface $variation */
        $variation = $item->entity;
        $qty = $item->quantity;
        $product_set_item = ProductSetItem::fromProductVariation($variation, $qty);
        $context = new Context($this->currentUser, $this->currentStore->getStore());
        $adjustment_types = array_filter($this->getSetting('adjustment_types'));
        $result = $this->priceCalculator->calculate($variation, $qty, $context, $adjustment_types);
        $calculated_price = $result->getCalculatedPrice();
        $product_set_item->setUnitPrice($calculated_price);
        $set_items[$delta] = $product_set_item;
      }
      $elements[0] = [
        '#theme' => 'commerce_product_set_items',
        '#items' => $set_items,
        '#title' => $this->getSetting('title'),
      ];
    }
    return $elements;
  }

  /**
   * {@inheritdoc}
   */
  public static function isApplicable(FieldDefinitionInterface $field_definition) {
    $entity_type = $field_definition->getTargetEntityTypeId();
    $field_name = $field_definition->getName();
    return $entity_type == 'commerce_product_set' && $field_name == 'variations';
  }

}
