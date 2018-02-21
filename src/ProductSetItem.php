<?php

namespace Drupal\commerce_product_set;

use Drupal\commerce_price\Calculator;
use Drupal\commerce_price\Price;
use Drupal\commerce_product\Entity\ProductVariationInterface;

/**
 * Provides a value object for a single product set item.
 */
final class ProductSetItem {

  /**
   * The product variation ID.
   *
   * @var int
   */
  protected $variationId;

  /**
   * The SKU.
   *
   * @var string
   */
  protected $sku;

  /**
   * The title.
   *
   * @var string
   */
  protected $title;

  /**
   * The quantity (as bcmath compatible numeric string).
   *
   * @var string
   */
  protected $quantity;

  /**
   * The unit price.
   *
   * @var \Drupal\commerce_price\Price
   */
  protected $unitPrice;

  /**
   * Constructs a new ProductSetItem object.
   *
   * @param int $variationId
   *   The product variation ID.
   * @param string $quantity
   *   The quantity (as bcmath compatible numeric string).
   * @param string $sku
   *   The SKU.
   * @param string $title
   *   The title.
   * @param \Drupal\commerce_price\Price|null $unitPrice
   *   The unit price.
   */
  public function __construct($variationId, $quantity, $sku = '', $title = '', Price $unitPrice = NULL) {
    $this->variationId = $variationId;
    $this->sku = $sku;
    $this->title = $title;
    $this->quantity = $quantity;
    $this->unitPrice = $unitPrice;
  }

  /**
   * Factory method allowing the object to be instantiated by an array.
   *
   * @param array $values
   *   The values. @see static::toArray() for more info on the proper format.
   *
   * @return static
   *   A new ProductSetItem object.
   *
   * @throws \InvalidArgumentException
   *   Thrown when mandatory keys are missing or values are invalid.
   */
  public static function fromArray(array $values) {
    if (empty($values['variation_id']) || empty($values['quantity'])) {
      throw new \InvalidArgumentException();
    }
    self::assertValidQuantity($values['quantity']);
    $price = NULL;
    if (!empty($values['unit_price']['number']) && !empty($values['unit_price']['currency_code'])) {
      $price = new Price($values['unit_price']['number'], $values['unit_price']['currency_code']);
    }
    return new static($values['variation_id'], $values['quantity'], isset($values['sku']) ? $values['sku'] : '', isset($values['title']) ? $values['title'] : '', $price);
  }

  /**
   * Factory method allowing to instantiat a new object by a product variation.
   *
   * @param \Drupal\commerce_product\Entity\ProductVariationInterface $variation
   *   The product variation.
   * @param int $quantity
   *   The quantity. Defaults to 1.
   *
   * @return static
   *   A new ProductSetItem object.
   */
  public static function fromProductVariation(ProductVariationInterface $variation, $quantity = 1) {
    return new static($variation->id(), $quantity, $variation->getSku(), $variation->getTitle(), $variation->getPrice());
  }

  /**
   * Gets the variation ID.
   *
   * @return int
   *   The variation ID.
   */
  public function getVariationId() {
    return $this->variationId;
  }

  /**
   * Gets the SKU.
   *
   * @return string
   *   The SKU.
   */
  public function getSku() {
    return $this->sku;
  }

  /**
   * Gets the title.
   *
   * @return string
   *   The title.
   */
  public function getTitle() {
    return $this->title;
  }

  /**
   * Gets the quantity.
   *
   * @return string
   *   The quantity.
   */
  public function getQuantity() {
    return $this->quantity;
  }

  /**
   * Gets the unit price.
   *
   * @return \Drupal\commerce_price\Price|null
   *   The unit price.
   */
  public function getUnitPrice() {
    return $this->unitPrice;
  }

  /**
   * Sets the unit price.
   *
   * By the default, the unresolved price is set. If the caller wants to display
   * the calculated price instead, it must be calculated first and then set with
   * the help of this setter function.
   *
   * @param \Drupal\commerce_price\Price $unit_price
   *   The unit price.
   */
  public function setUnitPrice(Price $unit_price) {
    $this->unitPrice = $unit_price;
  }

  /**
   * Gets the array representation of the set item.
   *
   * @return array
   *   The array representation of the set item.
   */
  public function toArray() {
    return [
      'variation_id' => $this->variationId,
      'sku' => $this->sku,
      'title' => $this->title,
      'quantity' => $this->quantity,
      'unit_price' => $this->unitPrice ? $this->unitPrice->toArray() : [],
    ];
  }

  /**
   * Gets a flat array representation of the set item.
   *
   * In difference, to toArray(), the price field parts will be normalized into
   * the array's main hierarchy level, following the exactly needed column names
   * for storing the array into the field's database table.
   *
   * @return array
   *   The array representation of the set item.
   */
  public function toFlatArray() {
    $values = $this->toArray();
    $values['unit_price__number'] = !empty($values['unit_price']) ? $values['unit_price']['number'] : '';
    $values['unit_price__currency_code'] = !empty($values['unit_price']) ? $values['unit_price']['currency_code'] : '';
    unset($values['unit_price']);
    return $values;
  }

  /**
   * Asserts that the quantity is valid.
   *
   * @param string $quantity
   *   The currency code.
   *
   * @throws \InvalidArgumentException
   *   Thrown when the quantity is invalid.
   */
  protected static function assertValidQuantity($quantity) {
    if (!is_numeric($quantity) || Calculator::compare($quantity, '0.0') == -1) {
      throw new \InvalidArgumentException();
    }
  }

}
