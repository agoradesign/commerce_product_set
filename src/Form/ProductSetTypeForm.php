<?php

namespace Drupal\commerce_product_set\Form;

use Drupal\commerce\EntityHelper;
use Drupal\commerce\Form\CommerceBundleEntityFormBase;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\language\Entity\ContentLanguageSettings;

/**
 * Defines the product set type add/edit form.
 */
class ProductSetTypeForm extends CommerceBundleEntityFormBase {

  /**
   * {@inheritdoc}
   */
  public function form(array $form, FormStateInterface $form_state) {
    $form = parent::form($form, $form_state);

    /** @var \Drupal\commerce_product_set\Entity\ProductSetTypeInterface $set_type */
    $set_type = $this->entity;

    $form['label'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Label'),
      '#maxlength' => 255,
      '#default_value' => $set_type->label(),
      '#required' => TRUE,
    ];
    $form['id'] = [
      '#type' => 'machine_name',
      '#default_value' => $set_type->id(),
      '#machine_name' => [
        'exists' => '\Drupal\commerce_product_set\Entity\ProductSetType::load',
      ],
      '#maxlength' => EntityTypeInterface::BUNDLE_MAX_LENGTH,
    ];
    $form = $this->buildTraitForm($form, $form_state);

    if ($this->moduleHandler->moduleExists('commerce_order')) {
      // Prepare a list of order item types used to purchase product sets.
      $order_item_type_storage = $this->entityTypeManager->getStorage('commerce_order_item_type');
      $order_item_types = $order_item_type_storage->loadMultiple();
      $order_item_types = array_filter($order_item_types, function ($order_item_type) {
        /** @var \Drupal\commerce_order\Entity\OrderItemTypeInterface $order_item_type */
        return $order_item_type->getPurchasableEntityTypeId() == 'commerce_product_set';
      });

      $form['orderItemType'] = [
        '#type' => 'select',
        '#title' => $this->t('Order item type'),
        '#default_value' => $set_type->getOrderItemTypeId(),
        '#options' => EntityHelper::extractLabels($order_item_types),
        '#empty_value' => '',
        '#required' => TRUE,
      ];
    }

    if ($this->moduleHandler->moduleExists('language')) {
      $form['language'] = [
        '#type' => 'details',
        '#title' => $this->t('Language settings'),
        '#group' => 'additional_settings',
      ];
      $form['language']['language_configuration'] = [
        '#type' => 'language_configuration',
        '#entity_information' => [
          'entity_type' => 'commerce_product_set',
          'bundle' => $set_type->id(),
        ],
        '#default_value' => ContentLanguageSettings::loadByEntityTypeBundle('commerce_product_set', $set_type->id()),
      ];
      $form['#submit'][] = 'language_configuration_element_submit';
    }

    return $this->protectBundleIdElement($form);
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    $this->validateTraitForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function save(array $form, FormStateInterface $form_state) {
    $status = $this->entity->save();

    $this->submitTraitForm($form, $form_state);

    $this->messenger()->addStatus($this->t('The product set type %label has been successfully saved.', ['%label' => $this->entity->label()]));
    $form_state->setRedirect('entity.commerce_product_set_type.collection');
    if ($status == SAVED_NEW) {
      commerce_product_set_add_stores_field($this->entity);
      commerce_product_set_add_body_field($this->entity);
      commerce_product_set_add_variations_field($this->entity);
    }
  }

}
