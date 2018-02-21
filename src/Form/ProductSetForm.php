<?php

namespace Drupal\commerce_product_set\Form;

use Drupal\Core\Datetime\DateFormatterInterface;
use Drupal\Component\Datetime\TimeInterface;
use Drupal\Core\Entity\ContentEntityForm;
use Drupal\Core\Entity\EntityManagerInterface;
use Drupal\Core\Entity\EntityTypeBundleInfoInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Link;
use Drupal\Core\Render\Element;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Defines the product set add/edit form.
 *
 * Based on ProductForm.
 */
class ProductSetForm extends ContentEntityForm {

  /**
   * The date formatter.
   *
   * @var \Drupal\Core\Datetime\DateFormatterInterface
   */
  protected $dateFormatter;

  /**
   * Constructs a new ProductForm object.
   *
   * @param \Drupal\Core\Entity\EntityManagerInterface $entity_manager
   *   The entity manager.
   * @param \Drupal\Core\Entity\EntityTypeBundleInfoInterface $entity_type_bundle_info
   *   The entity type bundle info.
   * @param \Drupal\Component\Datetime\TimeInterface $time
   *   The time.
   * @param \Drupal\Core\Datetime\DateFormatterInterface $date_formatter
   *   The date formatter.
   */
  public function __construct(EntityManagerInterface $entity_manager, EntityTypeBundleInfoInterface $entity_type_bundle_info, TimeInterface $time, DateFormatterInterface $date_formatter) {
    parent::__construct($entity_manager, $entity_type_bundle_info, $time);

    $this->dateFormatter = $date_formatter;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('entity.manager'),
      $container->get('entity_type.bundle.info'),
      $container->get('datetime.time'),
      $container->get('date.formatter')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    // Skip building the form if there are no available stores.
    $store_query = $this->entityManager->getStorage('commerce_store')->getQuery();
    if ($store_query->count()->execute() == 0) {
      $link = Link::createFromRoute($this->t('Add a new store.'), 'entity.commerce_store.add_page');
      $form['warning'] = [
        '#markup' => $this->t("Product sets can't be created until a store has been added. @link", ['@link' => $link->toString()]),
      ];
      return $form;
    }

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function form(array $form, FormStateInterface $form_state) {
    /* @var \Drupal\commerce_product_set\Entity\ProductSetInterface $product_set */
    $product_set = $this->entity;
    $form = parent::form($form, $form_state);

    $form['#tree'] = TRUE;
    $form['#theme'] = ['commerce_product_set_form'];
    $form['#attached']['library'][] = 'commerce_product_set/form';
    // Changed must be sent to the client, for later overwrite error checking.
    $form['changed'] = [
      '#type' => 'hidden',
      '#default_value' => $product_set->getChangedTime(),
    ];

    $form['footer'] = [
      '#type' => 'container',
      '#weight' => 99,
      '#attributes' => [
        'class' => ['product-set-form-footer'],
      ],
    ];
    $form['status']['#group'] = 'footer';

    $last_saved = $this->t('Not saved yet');
    if (!$product_set->isNew()) {
      $last_saved = $this->dateFormatter->format($product_set->getChangedTime(), 'short');
    }
    $form['meta'] = [
      '#attributes' => ['class' => ['entity-meta__header']],
      '#type' => 'container',
      '#group' => 'advanced',
      '#weight' => -100,
      'published' => [
        '#type' => 'html_tag',
        '#tag' => 'h3',
        '#value' => $product_set->isPublished() ? $this->t('Published') : $this->t('Not published'),
        '#access' => !$product_set->isNew(),
        '#attributes' => [
          'class' => ['entity-meta__title'],
        ],
      ],
      'changed' => [
        '#type' => 'item',
        '#wrapper_attributes' => [
          'class' => ['entity-meta__last-saved', 'container-inline'],
        ],
        '#markup' => '<h4 class="label inline">' . $this->t('Last saved') . '</h4> ' . $last_saved,
      ],
      'author' => [
        '#type' => 'item',
        '#wrapper_attributes' => [
          'class' => ['author', 'container-inline'],
        ],
        '#markup' => '<h4 class="label inline">' . $this->t('Author') . '</h4> ' . $product_set->getOwner()->getDisplayName(),
      ],
    ];
    $form['advanced'] = [
      '#type' => 'container',
      '#attributes' => ['class' => ['entity-meta']],
      '#weight' => 99,
    ];
    $form['visibility_settings'] = [
      '#type' => 'details',
      '#title' => $this->t('Visibility settings'),
      '#open' => TRUE,
      '#group' => 'advanced',
      '#access' => !empty($form['stores']['#access']),
      '#attributes' => [
        'class' => ['product-set-visibility-settings'],
      ],
      '#weight' => 30,
    ];
    $form['path_settings'] = [
      '#type' => 'details',
      '#title' => $this->t('URL path settings'),
      '#open' => !empty($form['path']['widget'][0]['alias']['#value']),
      '#group' => 'advanced',
      '#access' => !empty($form['path']['#access']) && $product_set->get('path')->access('edit'),
      '#attributes' => [
        'class' => ['path-form'],
      ],
      '#attached' => [
        'library' => ['path/drupal.path'],
      ],
      '#weight' => 60,
    ];
    $form['author'] = [
      '#type' => 'details',
      '#title' => $this->t('Authoring information'),
      '#group' => 'advanced',
      '#attributes' => [
        'class' => ['product-set-form-author'],
      ],
      '#weight' => 90,
      '#optional' => TRUE,
    ];
    if (isset($form['uid'])) {
      $form['uid']['#group'] = 'author';
    }
    if (isset($form['created'])) {
      $form['created']['#group'] = 'author';
    }
    if (isset($form['path'])) {
      $form['path']['#group'] = 'path_settings';
    }
    if (isset($form['stores'])) {
      $form['stores']['#group'] = 'visibility_settings';
      $form['#after_build'][] = [get_class($this), 'hideEmptyVisibilitySettings'];
    }

    return $form;
  }

  /**
   * Hides the visibility settings if the stores widget is a hidden element.
   *
   * @param array $form
   *   The form.
   *
   * @return array
   *   The modified visibility_settings element.
   */
  public static function hideEmptyVisibilitySettings(array $form) {
    if (isset($form['stores']['widget']['target_id'])) {
      $stores_element = $form['stores']['widget']['target_id'];
      if (!Element::getVisibleChildren($stores_element)) {
        $form['visibility_settings']['#printed'] = TRUE;
        // Move the stores widget out of the visibility_settings group to
        // ensure that its hidden element is still present in the HTML.
        unset($form['stores']['#group']);
      }
    }

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function save(array $form, FormStateInterface $form_state) {
    /** @var \Drupal\commerce_product_set\Entity\ProductSetInterface $product_set */
    $product_set = $this->getEntity();
    $product_set->save();
    drupal_set_message($this->t('The product set %label has been successfully saved.', ['%label' => $product_set->label()]));
    $form_state->setRedirect('entity.commerce_product_set.canonical', ['commerce_product_set' => $product_set->id()]);
  }

}
