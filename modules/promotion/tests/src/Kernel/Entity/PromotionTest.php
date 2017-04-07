<?php

namespace Drupal\Tests\commerce_promotion\Kernel\Entity;

use Drupal\commerce\TimeInterface;
use Drupal\commerce_order\Entity\Order;
use Drupal\commerce_order\Entity\OrderItem;
use Drupal\commerce_order\Entity\OrderItemType;
use Drupal\commerce_order\Entity\OrderType;
use Drupal\commerce_price\Price;
use Drupal\commerce_promotion\Entity\Coupon;
use Drupal\commerce_promotion\Entity\Promotion;
use Drupal\Core\Datetime\DrupalDateTime;
use Drupal\Tests\commerce\Kernel\CommerceKernelTestBase;

/**
 * Tests the Promotion entity.
 *
 * @coversDefaultClass \Drupal\commerce_promotion\Entity\Promotion
 *
 * @group commerce
 */
class PromotionTest extends CommerceKernelTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = [
    'entity_reference_revisions',
    'profile',
    'state_machine',
    'commerce_order',
    'commerce_product',
    'commerce_promotion',
  ];

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    $this->installEntitySchema('profile');
    $this->installEntitySchema('commerce_order');
    $this->installEntitySchema('commerce_order_item');
    $this->installEntitySchema('commerce_promotion');
    $this->installEntitySchema('commerce_promotion_coupon');
    $this->installConfig([
      'profile',
      'commerce_order',
      'commerce_promotion',
    ]);

    OrderItemType::create([
      'id' => 'test',
      'label' => 'Test',
      'orderType' => 'default',
    ])->save();
  }

  /**
   * @covers ::getName
   * @covers ::setName
   * @covers ::getDescription
   * @covers ::setDescription
   * @covers ::getOrderTypes
   * @covers ::setOrderTypes
   * @covers ::getOrderTypeIds
   * @covers ::setOrderTypeIds
   * @covers ::getStores
   * @covers ::setStores
   * @covers ::setStoreIds
   * @covers ::getStoreIds
   * @covers ::getCouponIds
   * @covers ::getCoupons
   * @covers ::setCoupons
   * @covers ::hasCoupons
   * @covers ::addCoupon
   * @covers ::removeCoupon
   * @covers ::hasCoupon
   * @covers ::getUsageLimit
   * @covers ::setUsageLimit
   * @covers ::getStartDate
   * @covers ::setStartDate
   * @covers ::getEndDate
   * @covers ::setEndDate
   * @covers ::isEnabled
   * @covers ::setEnabled
   */
  public function testPromotion() {
    $order_type = OrderType::load('default');
    $promotion = Promotion::create([
      'status' => FALSE,
    ]);

    $promotion->setName('My Promotion');
    $this->assertEquals('My Promotion', $promotion->getName());

    $promotion->setDescription('My Promotion Description');
    $this->assertEquals('My Promotion Description', $promotion->getDescription());

    $promotion->setOrderTypes([$order_type]);
    $order_types = $promotion->getOrderTypes();
    $this->assertEquals($order_type->id(), $order_types[0]->id());

    $promotion->setOrderTypeIds([$order_type->id()]);
    $this->assertEquals([$order_type->id()], $promotion->getOrderTypeIds());

    $promotion->setStores([$this->store]);
    $this->assertEquals([$this->store], $promotion->getStores());

    $promotion->setStoreIds([$this->store->id()]);
    $this->assertEquals([$this->store->id()], $promotion->getStoreIds());

    $coupon1 = Coupon::create([
      'code' => $this->randomMachineName(),
      'status' => TRUE,
    ]);
    $coupon1->save();
    $coupon2 = Coupon::create([
      'code' => $this->randomMachineName(),
      'status' => TRUE,
    ]);
    $coupon2->save();
    $coupon1 = Coupon::load($coupon1->id());
    $coupon2 = Coupon::load($coupon2->id());
    $coupons = [$coupon1, $coupon2];
    $coupon_ids = [$coupon1->id(), $coupon2->id()];

    $this->assertFalse($promotion->hasCoupons());
    $promotion->setCoupons($coupons);
    $this->assertTrue($promotion->hasCoupons());
    $this->assertEquals($coupons, $promotion->getCoupons());
    $this->assertEquals($coupon_ids, $promotion->getCouponIds());
    $this->assertTrue($promotion->hasCoupon($coupon1));
    $promotion->removeCoupon($coupon1);
    $this->assertFalse($promotion->hasCoupon($coupon1));
    $promotion->addCoupon($coupon1);
    $this->assertTrue($promotion->hasCoupon($coupon1));

    $promotion->setUsageLimit(10);
    $this->assertEquals(10, $promotion->getUsageLimit());

    $promotion->setStartDate(new DrupalDateTime('2017-01-01'));
    $this->assertEquals('2017-01-01', $promotion->getStartDate()->format('Y-m-d'));

    $promotion->setEndDate(new DrupalDateTime('2017-01-31'));
    $this->assertEquals('2017-01-31', $promotion->getEndDate()->format('Y-m-d'));

    $promotion->setEnabled(TRUE);
    $this->assertEquals(TRUE, $promotion->isEnabled());
  }

  /**
   * @covers ::available
   */
  public function testAvailability() {
    $this->installSchema('commerce_promotion', ['commerce_promotion_usage']);
    $order_item = OrderItem::create([
      'type' => 'test',
      'quantity' => 1,
      'unit_price' => new Price('12.00', 'USD'),
    ]);
    $order_item->save();
    $order = Order::create([
      'type' => 'default',
      'state' => 'draft',
      'mail' => 'test@example.com',
      'ip_address' => '127.0.0.1',
      'order_number' => '6',
      'store_id' => $this->store,
      'uid' => $this->createUser(),
      'order_items' => [$order_item],
    ]);
    $order->setRefreshState(Order::REFRESH_SKIP);
    $order->save();

    $promotion = Promotion::create([
      'order_types' => ['default'],
      'stores' => [$this->store->id()],
      'usage_limit' => 1,
      'start_date' => '2017-01-01',
      'status' => TRUE,
    ]);
    $promotion->save();
    $this->assertTrue($promotion->available($order));

    $promotion->setEnabled(FALSE);
    $this->assertFalse($promotion->available($order));
    $promotion->setEnabled(TRUE);

    $promotion->setOrderTypeIds(['test']);
    $this->assertFalse($promotion->available($order));
    $promotion->setOrderTypeIds(['default']);

    $promotion->setStoreIds(['90']);
    $this->assertFalse($promotion->available($order));
    $promotion->setStoreIds([$this->store->id()]);

    $fake_time = $this->prophesize(TimeInterface::class);
    $fake_time->getRequestTime()->willReturn(mktime(0, 0, 0, '01', '15', '2016'));
    $this->container->set('commerce.time', $fake_time->reveal());
    $this->assertFalse($promotion->available($order));

    $fake_time = $this->prophesize(TimeInterface::class);
    $fake_time->getRequestTime()->willReturn(mktime(0, 0, 0, '01', '15', '2017'));
    $this->container->set('commerce.time', $fake_time->reveal());
    $promotion->setEndDate(new DrupalDateTime('2017-01-14'));
    $this->assertFalse($promotion->available($order));
    $promotion->setEndDate(NULL);

    \Drupal::service('commerce_promotion.usage')->addUsage($order, $promotion);
    $this->assertFalse($promotion->available($order));
  }

}
