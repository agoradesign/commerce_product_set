<?php

namespace Drupal\commerce_product_set\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\Core\Cache\Cache;
use Drupal\Core\Entity\EntityDisplayRepositoryInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Routing\RouteMatchInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a product block listing the parts of the given set.
 *
 * This block will only show content on detail pages of product sets.
 *
 * @Block(
 *   id = "product_set_parts",
 *   admin_label = @Translation("Product set parts"),
 *   category = "Commerce Product Set"
 * )
 */
class ProductSetPartsBlock extends BlockBase implements ContainerFactoryPluginInterface {

  /**
   * The entity display repository.
   *
   * @var \Drupal\Core\Entity\EntityDisplayRepositoryInterface
   */
  protected $entityDisplayRepository;

  /**
   * The product variation storage.
   *
   * @var \Drupal\commerce\CommerceContentEntityStorage
   */
  protected $productVariationStorage;

  /**
   * The product variation view builder.
   *
   * @var \Drupal\Core\Entity\EntityViewBuilderInterface
   */
  protected $productVariationViewBuilder;

  /**
   * The route match.
   *
   * @var \Drupal\Core\Routing\RouteMatchInterface
   */
  protected $routeMatch;

  /**
   * The current product set (the active page set).
   *
   * @var \Drupal\commerce_product_set\Entity\ProductSetInterface|null|bool
   */
  protected $currentProductSet;

  /**
   * Constructs a new ProductSetPartsBlock object.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\Core\Routing\RouteMatchInterface $route_match
   *   The route match.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   * @param \Drupal\Core\Entity\EntityDisplayRepositoryInterface $entity_display_repository
   *   The entity display repository.
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, RouteMatchInterface $route_match, EntityTypeManagerInterface $entity_type_manager, EntityDisplayRepositoryInterface $entity_display_repository) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);

    $this->entityDisplayRepository = $entity_display_repository;
    $this->productVariationStorage = $entity_type_manager->getStorage('commerce_product_variation');
    $this->productVariationViewBuilder = $entity_type_manager->getViewBuilder('commerce_product_variation');
    $this->routeMatch = $route_match;
    // Initialize the current product set with bool FALSE, so we can distinct in
    // our getter between an already checked NULL value and not initialized yet.
    $this->currentProductSet = FALSE;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('current_route_match'),
      $container->get('entity_type.manager'),
      $container->get('entity_display.repository')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    return [
      'view_mode' => 'default',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function blockForm($form, FormStateInterface $form_state) {
    $form['view_mode'] = [
      '#type' => 'select',
      '#title' => $this->t('View mode'),
      '#description' => $this->t('The view mode that will be used for rendering the banner in the block.'),
      '#default_value' => $this->configuration['view_mode'],
      '#options' => $this->getAvailableViewModes(),
    ];
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function blockSubmit($form, FormStateInterface $form_state) {
    $this->configuration['view_mode'] = $form_state->getValue('view_mode');
  }

  /**
   * {@inheritdoc}
   */
  public function build() {
    /** @var \Drupal\commerce_product_set\Entity\ProductSetInterface $product_set */
    $product_set = $this->getCurrentProductSet();
    if (empty($product_set)) {
      return [];
    }

    $output = [];
    $items = [];
    foreach ($product_set->getProductSetItems() as $item) {
      $variation = $this->productVariationStorage->load($item->getVariationId());
      $items[$variation->id()] = $this->productVariationViewBuilder->view($variation, $this->configuration['view_mode']);
    }
    $output['#theme'] = 'commerce_product_set_parts';
    $output['#items'] = $items;
    return $output;
  }

  /**
   * {@inheritdoc}
   */
  public function getCacheContexts() {
    return Cache::mergeContexts(parent::getCacheContexts(), ['route']);
  }

  /**
   * {@inheritdoc}
   */
  public function getCacheTags() {
    $tags = parent::getCacheTags();
    $product_set = $this->getCurrentProductSet();
    if (!empty($product_set)) {
      $tags = Cache::mergeTags($tags, ['commerce_product_set:' . $product_set->id()]);
    }
    return $tags;
  }

  /**
   * Gets available view modes of banner entities for block form configuration.
   */
  protected function getAvailableViewModes() {
    $options = [
      // Always add the 'default' view mode.
      'default' => 'Default',
    ];
    $form_modes = $this->entityDisplayRepository->getViewModes('commerce_product_set');
    foreach ($form_modes as $id => $info) {
      $options[$id] = $info['label'];
    }
    return $options;
  }

  /**
   * Returns the current/active product set.
   *
   * @return \Drupal\commerce_product_set\Entity\ProductSetInterface|null
   */
  protected function getCurrentProductSet() {
    if ($this->currentProductSet === FALSE) {
      $this->currentProductSet = NULL;
      if ($this->routeMatch->getRouteName() == 'entity.commerce_product_set.canonical') {
        /** @var \Drupal\commerce_product_set\Entity\ProductSetInterface $product_set */
        if ($product_set = $this->routeMatch->getParameter('commerce_product_set')) {
          $this->currentProductSet = $product_set;
        }
      }
    }
    return $this->currentProductSet;
  }

}
