<?php

namespace Drupal\Tests\commerce_payment\Kernel\Entity;

use Drupal\commerce_order\Entity\Order;
use Drupal\commerce_order\Entity\OrderItem;
use Drupal\commerce_order\Entity\OrderItemType;
use Drupal\commerce_payment\Entity\PaymentGateway;
use Drupal\commerce_payment\Entity\Payment;
use Drupal\commerce_payment\Plugin\Commerce\PaymentType\PaymentDefault;
use Drupal\commerce_price\Price;
use Drupal\Tests\commerce\Kernel\CommerceKernelTestBase;
use Symfony\Component\HttpFoundation\Response;

/**
 * Tests the payment entity.
 *
 * @coversDefaultClass \Drupal\commerce_payment\Entity\Payment
 *
 * @group commerce
 */
class PaymentTest extends CommerceKernelTestBase {

  /**
   * A sample order.
   *
   * @var \Drupal\commerce_order\Entity\OrderInterface
   */
  protected $order;

  /**
   * A sample user.
   *
   * @var \Drupal\user\UserInterface
   */
  protected $user;

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = [
    'entity_reference_revisions',
    'profile',
    'state_machine',
    'commerce_product',
    'commerce_order',
    'commerce_payment',
    'commerce_payment_example',
  ];

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    $this->installEntitySchema('profile');
    $this->installEntitySchema('commerce_order');
    $this->installEntitySchema('commerce_order_item');
    $this->installEntitySchema('commerce_payment');
    $this->installConfig('commerce_order');
    $this->installConfig('commerce_payment');

    PaymentGateway::create([
      'id' => 'example',
      'label' => 'Example',
      'plugin' => 'example_onsite',
    ])->save();

    $user = $this->createUser();
    $this->user = $this->reloadEntity($user);

    // An order item type that doesn't need a purchasable entity.
    OrderItemType::create([
      'id' => 'test',
      'label' => 'Test',
      'orderType' => 'default',
    ])->save();

    $order_item = OrderItem::create([
      'title' => 'Membership subscription',
      'type' => 'test',
      'quantity' => 1,
      'unit_price' => [
        'number' => '30.00',
        'currency_code' => 'USD',
      ],
    ]);
    $order_item->save();

    $order = Order::create([
      'type' => 'default',
      'uid' => $this->user->id(),
      'store_id' => $this->store->id(),
      'order_items' => [$order_item],
    ]);
    $order->save();
    $this->order = $this->reloadEntity($order);
  }

  /**
   * @covers ::getType
   * @covers ::getPaymentGatewayId
   * @covers ::getPaymentGatewayMode
   * @covers ::getOrder
   * @covers ::getOrderId
   * @covers ::getRemoteId
   * @covers ::setRemoteId
   * @covers ::getRemoteState
   * @covers ::setRemoteState
   * @covers ::getBalance
   * @covers ::getAmount
   * @covers ::setAmount
   * @covers ::getRefundedAmount
   * @covers ::setRefundedAmount
   * @covers ::getState
   * @covers ::setState
   * @covers ::getAuthorizedTime
   * @covers ::setAuthorizedTime
   * @covers ::isExpired
   * @covers ::getExpiresTime
   * @covers ::setExpiresTime
   * @covers ::isCompleted
   * @covers ::getCompletedTime
   * @covers ::setCompletedTime
   */
  public function testPayment() {
    /** @var \Drupal\commerce_payment\Entity\PaymentInterface $payment */
    $payment = Payment::create([
      'type' => 'payment_default',
      'payment_gateway' => 'example',
      'order_id' => $this->order->id(),
      'amount' => new Price('30', 'USD'),
      'refunded_amount' => new Price('10', 'USD'),
      'state' => 'refunded',
    ]);
    $payment->save();

    $this->assertInstanceOf(PaymentDefault::class, $payment->getType());
    $this->assertEquals('example', $payment->getPaymentGatewayId());
    $this->assertEquals('test', $payment->getPaymentGatewayMode());

    $this->assertEquals($this->order, $payment->getOrder());
    $this->assertEquals($this->order->id(), $payment->getOrderId());

    $payment->setRemoteId('123456');
    $this->assertEquals('123456', $payment->getRemoteId());

    $payment->setRemoteState('pending');
    $this->assertEquals('pending', $payment->getRemoteState());

    $this->assertTrue($payment->getAmount()->equals(new Price('30', 'USD')));
    $this->assertTrue($payment->getRefundedAmount()->equals(new Price('10', 'USD')));
    $this->assertTrue($payment->getBalance()->equals(new Price('20', 'USD')));

    $payment->setAmount(new Price('40', 'USD'));
    $this->assertTrue($payment->getAmount()->equals(new Price('40', 'USD')));
    $payment->setRefundedAmount(new Price('15', 'USD'));
    $this->assertTrue($payment->getRefundedAmount()->equals(new Price('15', 'USD')));

    $this->assertEquals('refunded', $payment->getState()->getId());
    $payment->setState('completed');
    $this->assertEquals('completed', $payment->getState()->getId());

    $this->assertEmpty($payment->getAuthorizedTime());
    $payment->setAuthorizedTime(635879600);
    $this->assertEquals(635879600, $payment->getAuthorizedTime());

    $this->assertEmpty($payment->isExpired());
    $payment->setExpiresTime(635879700);
    $this->assertTrue($payment->isExpired());
    $this->assertEquals(635879700, $payment->getExpiresTime());

    $this->assertEmpty($payment->isCompleted());
    $payment->setCompletedTime(635879700);
    $this->assertEquals(635879700, $payment->getCompletedTime());
    $this->assertTrue($payment->isCompleted());
  }

  /**
   * Tests the order integration (total_paid field).
   *
   * @covers ::postSave
   * @covers ::postDelete
   */
  public function testOrderIntegration() {
    $this->assertTrue($this->order->getTotalPaid()->equals(new Price('0', 'USD')));
    $this->assertTrue($this->order->getBalance()->equals(new Price('30', 'USD')));

    /** @var \Drupal\commerce_payment\Entity\PaymentInterface $payment */
    $payment = Payment::create([
      'type' => 'payment_default',
      'payment_gateway' => 'example',
      'order_id' => $this->order->id(),
      'amount' => new Price('30', 'USD'),
      'state' => 'completed',
    ]);
    $payment->save();
    $this->order->save();
    $this->assertTrue($this->order->getTotalPaid()->equals(new Price('30', 'USD')));
    $this->assertTrue($this->order->getBalance()->equals(new Price('0', 'USD')));

    $payment->setRefundedAmount(new Price('15', 'USD'));
    $payment->setState('partially_refunded');
    $payment->save();
    $this->order->save();
    $this->assertTrue($this->order->getTotalPaid()->equals(new Price('15', 'USD')));
    $this->assertTrue($this->order->getBalance()->equals(new Price('15', 'USD')));

    $payment->delete();
    // Confirm that if the order isn't explicitly saved, it will be saved
    // at the end of the request.
    $request = $this->container->get('request_stack')->getCurrentRequest();
    $kernel = $this->container->get('kernel');
    $kernel->terminate($request, new Response());
    $this->order = $this->reloadEntity($this->order);
    $this->assertTrue($this->order->getTotalPaid()->equals(new Price('0', 'USD')));
    $this->assertTrue($this->order->getBalance()->equals(new Price('30', 'USD')));
  }

  /**
   * Tests the preSave logic.
   *
   * @covers ::preSave
   */
  public function testPreSave() {
    $request_time = \Drupal::time()->getRequestTime();
    /** @var \Drupal\commerce_payment\Entity\PaymentInterface $payment */
    $payment = Payment::create([
      'type' => 'payment_default',
      'payment_gateway' => 'example',
      'order_id' => $this->order->id(),
      'amount' => new Price('30', 'USD'),
      'state' => 'authorization',
    ]);
    $this->assertEmpty($payment->getPaymentGatewayMode());
    $this->assertEmpty($payment->getRefundedAmount());
    $this->assertEmpty($payment->getAuthorizedTime());
    $this->assertEmpty($payment->getCompletedTime());
    // Confirm that getBalance() works before the payment is saved.
    $this->assertTrue($payment->getBalance()->equals(new Price('30', 'USD')));

    $payment->save();
    $this->assertEquals('test', $payment->getPaymentGatewayMode());
    $this->assertTrue($payment->getRefundedAmount()->equals(new Price('0', 'USD')));
    $this->assertEquals($request_time, $payment->getAuthorizedTime());
    $this->assertEmpty($payment->getCompletedTime());

    $payment->setState('completed');
    $payment->save();
    $this->assertEquals($request_time, $payment->getCompletedTime());
  }

}
