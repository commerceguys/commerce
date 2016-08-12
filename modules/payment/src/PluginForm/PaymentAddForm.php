<?php

namespace Drupal\commerce_payment\PluginForm;

use Drupal\commerce_payment\Plugin\Commerce\PaymentGateway\SupportsAuthorizationsInterface;
use Drupal\Core\Form\FormStateInterface;

class PaymentAddForm extends PaymentGatewayFormBase {

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    /** @var \Drupal\commerce_payment\Entity\PaymentInterface $payment */
    $payment = $this->entity;
    $order = $payment->getOrder();
    if (!$order) {
      throw new \InvalidArgumentException('Payment entity with no order reference given to PaymentAddForm.');
    }
    // @todo The order needs a getter for total_price.
    // @todo Implement a balance method (unpaid portion of the total).
    $order_total = $order->get('total_price')->first()->toPrice();

    $form['amount'] = [
      '#type' => 'commerce_price',
      '#title' => t('Amount'),
      '#default_value' => $order_total,
      '#required' => TRUE,
    ];
    $form['transaction_type'] = [
      '#type' => 'radios',
      '#title' => t('Transaction type'),
      '#title_display' => 'invisible',
      '#options' => [
        'authorize' => t('Authorize only'),
        'capture' => t('Authorize and capture'),
      ],
      '#default_value' => 'capture',
      '#access' => $this->plugin instanceof SupportsAuthorizationsInterface,
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitConfigurationForm(array &$form, FormStateInterface $form_state) {
    $values = $form_state->getValue($form['#parents']);
    /** @var \Drupal\commerce_payment\Entity\PaymentInterface $payment */
    $payment = $this->entity;
    $payment->setAmount($values['amount']);
    /** @var \Drupal\commerce_payment\Plugin\Commerce\PaymentGateway\PaymentGatewayInterface $payment_gateway_plugin */
    $payment_gateway_plugin = $this->plugin;
    $payment_gateway_plugin->createPayment($payment, $values['transaction_type']);
  }

}
