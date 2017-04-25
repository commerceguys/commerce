<?php

namespace Drupal\Tests\commerce_tax\Kernel\Plugin\Commerce\TaxType;

use Drupal\commerce_order\Entity\Order;
use Drupal\commerce_order\Entity\OrderItem;
use Drupal\commerce_order\Entity\OrderItemType;
use Drupal\commerce_price\Price;
use Drupal\commerce_store\Entity\Store;
use Drupal\commerce_tax\Plugin\Commerce\TaxType\Custom;
use Drupal\Tests\commerce\Kernel\CommerceKernelTestBase;
use Drupal\profile\Entity\Profile;

/**
 * @coversDefaultClass \Drupal\commerce_tax\Plugin\Commerce\TaxType\Custom
 * @group commerce
 */
class CustomTest extends CommerceKernelTestBase {

  /**
   * The tax type plugin.
   *
   * @var \Drupal\commerce_tax\Plugin\Commerce\TaxType\TaxTypeInterface
   */
  protected $plugin;

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
    'commerce_tax',
  ];

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    $this->installEntitySchema('profile');
    $this->installEntitySchema('commerce_order');
    $this->installEntitySchema('commerce_order_item');
    $this->installConfig('commerce_order');

    // An order item type that doesn't need a purchasable entity, for simplicity.
    OrderItemType::create([
      'id' => 'test',
      'label' => 'Test',
      'orderType' => 'default',
    ])->save();

    $user = $this->createUser();
    $this->user = $this->reloadEntity($user);

    $configuration = [
      '_entity_id' => 'serbian_vat',
      'display_inclusive' => TRUE,
      'display_label' => 'vat',
      'round' => TRUE,
      'rates' => [
        [
          'id' => 'standard',
          'label' => 'Standard',
          'amount' => '0.2',
        ],
        [
          'id' => 'reduced',
          'label' => 'Reduced',
          'amount' => '0.1',
        ],
      ],
      'territories' => [
        ['country_code' => 'RS'],
      ],
    ];
    $this->plugin = Custom::create($this->container, $configuration, 'custom', ['label' => 'Custom']);
  }

  /**
   * @covers ::getDisplayLabel
   * @covers ::isDisplayInclusive
   * @covers ::shouldRound
   * @covers ::getZones
   */
  public function testGetters() {
    $this->assertEquals(t('VAT'), $this->plugin->getDisplayLabel());
    $this->assertTrue($this->plugin->isDisplayInclusive());
    $this->assertTrue($this->plugin->shouldRound());

    $zones = $this->plugin->getZones();
    $zone = reset($zones);
    $rates = $zone->getRates();
    $this->assertCount(1, $zones);
    $this->assertCount(2, $rates);
    $this->assertEquals('standard', $rates[0]->getId());
    $this->assertEquals('Standard', $rates[0]->getLabel());
    $this->assertEquals('0.2', $rates[0]->getAmount()->getAmount());
    $this->assertTrue($rates[0]->isDefault());
    $this->assertEquals('reduced', $rates[1]->getId());
    $this->assertEquals('Reduced', $rates[1]->getLabel());
    $this->assertEquals('0.1', $rates[1]->getAmount()->getAmount());
    $this->assertFalse($rates[1]->isDefault());
  }

  /**
   * @covers ::applies
   * @covers ::apply
   */
  public function testApplication() {
    // US store, US customer.
    $order = $this->buildOrder('US', 'US');
    $this->assertFalse($this->plugin->applies($order));

    // US store registered to collect tax in Serbia, US customer.
    $order = $this->buildOrder('US', 'US', ['RS']);
    $this->assertTrue($this->plugin->applies($order));
    $this->plugin->apply($order);
    $this->assertCount(0, $order->collectAdjustments());

    // US store registered to collect tax in Serbia, Serbian customer.
    $order = $this->buildOrder('RS', 'US', ['RS']);
    $this->assertTrue($this->plugin->applies($order));
    $this->plugin->apply($order);
    $this->assertCount(1, $order->collectAdjustments());

    // Serbian store and US customer.
    $order = $this->buildOrder('US', 'RS');
    $this->assertTrue($this->plugin->applies($order));
    $this->plugin->apply($order);
    $this->assertCount(0, $order->collectAdjustments());

    // Serbian store and customer.
    $order = $this->buildOrder('RS', 'RS');
    $this->assertTrue($this->plugin->applies($order));
    $this->plugin->apply($order);
    $this->assertCount(1, $order->collectAdjustments());

    // Confirm the adjustment format.
    $adjustments = $order->collectAdjustments();
    $adjustment = reset($adjustments);
    $this->assertEquals('tax', $adjustment->getType());
    $this->assertEquals($this->plugin->getDisplayLabel(), $adjustment->getLabel());
    $this->assertEquals(new Price('2.07', 'USD'), $adjustment->getAmount());
    $this->assertEquals('serbian_vat|default|standard', $adjustment->getSourceId());
    $this->assertTrue($adjustment->isIncluded());

    // Test tax inclusive prices.
    $store = $order->getStore();
    $store->set('prices_include_tax', TRUE);
    $store->save();
    $order->clearAdjustments();
    $this->plugin->apply($order);
    $adjustments = $order->collectAdjustments();
    $adjustment = reset($adjustments);
    $this->assertEquals(new Price('1.72', 'USD'), $adjustment->getAmount());
    $order_items = $order->getItems();
    $order_item = reset($order_items);
    $this->assertEquals(new Price('10.33', 'USD'), $order_item->getUnitPrice());

    // Test tax inclusive prices + non-display-inclusive taxes.
    $configuration = $this->plugin->getConfiguration();
    $configuration['display_inclusive'] = FALSE;
    $this->plugin->setConfiguration($configuration);
    $order->clearAdjustments();
    $this->plugin->apply($order);
    $adjustments = $order->collectAdjustments();
    $adjustment = reset($adjustments);
    $this->assertEquals(new Price('1.72', 'USD'), $adjustment->getAmount());
    $order_items = $order->getItems();
    $order_item = reset($order_items);
    $this->assertEquals(new Price('8.61', 'USD'), $order_item->getUnitPrice());
  }

  /**
   * Builds an order for testing purposes.
   *
   * @return \Drupal\commerce_order\Entity\OrderInterface
   *   The order.
   */
  protected function buildOrder($customer_country, $store_country, array $store_registrations = []) {
    $store = Store::create([
      'type' => 'default',
      'label' => 'My store',
      'address' => [
        'country_code' => $store_country,
      ],
      'prices_include_tax' => FALSE,
      'tax_registrations' => $store_registrations,
    ]);
    $store->save();
    $customer_profile = Profile::create([
      'type' => 'customer',
      'uid' => $this->user->id(),
      'address' => [
        'country_code' => $customer_country,
      ],
    ]);
    $customer_profile->save();
    $order_item = OrderItem::create([
      'type' => 'test',
      'quantity' => 1,
      // Intentionally uneven number, to test rounding.
      'unit_price' => new Price('10.33', 'USD'),
    ]);
    $order_item->save();
    $order = Order::create([
      'type' => 'default',
      'uid' => $this->user->id(),
      'store_id' => $store,
      'billing_profile' => $customer_profile,
      'order_items' => [$order_item],
    ]);
    $order->save();

    return $order;
  }

}
