<?php

namespace Drupal\commerce_order\Plugin\Commerce\Condition;

use Drupal\commerce\Plugin\Commerce\Condition\ConditionBase;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides the condition for limiting promotion for number of orders per user.
 *
 * @CommerceCondition(
 *   id = "orders_per_user",
 *   label = @Translation("Number of orders"),
 *   display_label = @Translation("Limit by orders per user"),
 *   category = @Translation("Order"),
 *   entity_type = "commerce_order",
 * )
 */
class OrdersPerUser extends ConditionBase implements ContainerFactoryPluginInterface{

  /**
   * The order storage.
   *
   * @var \Drupal\Core\Entity\EntityStorageInterface
   */
  protected $orderStorage;

  /**
   * Constructs a new OrderCurrency object.
   *
   * @param array $configuration
   *   The plugin configuration, i.e. an array with configuration values keyed
   *   by configuration option name.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, EntityTypeManagerInterface $entity_type_manager) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);

    $this->orderStorage = $entity_type_manager->getStorage('commerce_order');
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('entity_type.manager')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    return [
        'operator' => '>',
        'orders' => 1,
      ] + parent::defaultConfiguration();
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    $form = parent::buildConfigurationForm($form, $form_state);

    $form['operator'] = [
      '#type' => 'select',
      '#title' => t('Operator'),
      '#options' => $this->getComparisonOperators(),
      '#default_value' => $this->configuration['operator'],
      '#required' => TRUE,
    ];
    $form['orders'] = [
      '#type' => 'number',
      '#title' => t('Orders'),
      '#default_value' => $this->configuration['orders'],
      '#min' => 1,
      '#required' => TRUE,
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitConfigurationForm(array &$form, FormStateInterface $form_state) {
    parent::submitConfigurationForm($form, $form_state);

    $values = $form_state->getValue($form['#parents']);
    $this->configuration['operator'] = $values['operator'];
    $this->configuration['orders'] = $values['orders'];
  }

  /**
   * {@inheritdoc}
   */
  public function evaluate(EntityInterface $entity) {
    $this->assertEntity($entity);
    /* @var \Drupal\commerce_order\Entity\OrderInterface $order */
    $order = $entity;
    $user_id = $order->getCustomerId();

    $orders = $this->orderStorage->getQuery()
      ->condition('cart', FALSE)
      ->condition('uid', $user_id)
      ->count()
      ->execute();

    switch ($this->configuration['operator']) {
      case '>=':
        return $orders >= $this->configuration['orders'];

      case '>':
        return $orders > $this->configuration['orders'];

      case '<=':
        return $orders <= $this->configuration['orders'];

      case '<':
        return $orders < $this->configuration['orders'];

      case '==':
        return $orders == $this->configuration['orders'];

      default:
        throw new \InvalidArgumentException("Invalid operator {$this->configuration['operator']}");
    }
  }

}
