<?php

namespace Drupal\Tests\commerce_cart\FunctionalJavascript;

use Drupal\commerce_product\Entity\Product;
use Drupal\Core\Entity\Entity\EntityFormDisplay;
use Drupal\language\Entity\ConfigurableLanguage;
use Drupal\Tests\commerce\FunctionalJavascript\JavascriptTestTrait;
use Drupal\commerce_order\Entity\Order;
use Drupal\commerce_product\Entity\ProductVariation;
use Drupal\commerce_product\Entity\ProductVariationType;
use Drupal\Tests\commerce_cart\Functional\CartBrowserTestBase;

/**
 * Tests the add to cart form for multilingual.
 *
 * @group commerce
 */
class AddToCartMultilingualTest extends CartBrowserTestBase {

  use JavascriptTestTrait;

  /**
   * The variations to test with.
   *
   * @var \Drupal\commerce_product\Entity\ProductVariation[]
   */
  protected $variations;

  /**
   * The product to test against.
   *
   * @var \Drupal\commerce_product\Entity\Product
   */
  protected $product;

  /**
   * The color attributes to test with.
   *
   * @var \Drupal\commerce_product\Entity\ProductAttributeValueInterface[]
   */
  protected $colorAttributes;

  /**
   * The size attributes to test with.
   *
   * @var \Drupal\commerce_product\Entity\ProductAttributeValueInterface[]
   */
  protected $sizeAttributes;

  /**
   * {@inheritdoc}
   */
  public static $modules = [
    'language',
  ];

  /**
   * {@inheritdoc}
   */
  public function setUp() {
    parent::setUp();
    // Add a new language.
    ConfigurableLanguage::createFromLangcode('fr')->save();

    /** @var \Drupal\commerce_product\Entity\ProductVariationTypeInterface $variation_type */
    $variation_type = ProductVariationType::load($this->variation->bundle());

    $color_attributes = $this->createAttributeSet($variation_type, 'color', [
      'red' => 'Red',
      'blue' => 'Blue',
    ]);
    $updated_color_attributes = [];
    foreach ($color_attributes as $key => $color_attribute) {
      $color_attribute_fr = $color_attribute->toArray();
      $color_attribute_fr['name'] = 'FR ' . $color_attribute->label();
      $color_attribute->addTranslation('fr', $color_attribute_fr)->save();
      $updated_color_attributes[$key] = $color_attribute;
    }
    $size_attributes = $this->createAttributeSet($variation_type, 'size', [
      'small' => 'Small',
      'medium' => 'Medium',
      'large' => 'Large',
    ]);
    $updated_size_attributes = [];
    foreach ($size_attributes as $key => $size_attribute) {
      $size_attribute_fr = $size_attribute->toArray();
      $size_attribute_fr['name'] = 'FR ' . $size_attribute->label();
      $size_attribute->addTranslation('fr', $size_attribute_fr)->save();
      $updated_size_attributes[$key] = $size_attribute;
    }

    // Reload the variation since we have new fields.
    $this->variation = ProductVariation::load($this->variation->id());
    $product = $this->variation->getProduct();
    $product->setTitle('Title');
    $product->save();
    // Add translation
    $product_fr = $product->toArray();
    $product_fr['title'] = 'FR title';
    $product->addTranslation('fr', $product_fr)->save();

    // Update first variation to have the attribute's value.
    $this->variation->attribute_color = $color_attributes['red']->id();
    $this->variation->attribute_size = $size_attributes['small']->id();
    $this->variation->save();

    $variation_fr = $this->variation->toArray();
    $this->variation->addTranslation('fr', $variation_fr)->save();

    // The matrix is intentionally uneven, blue / large is missing.
    $attribute_values_matrix = [
      ['red', 'small'],
      ['red', 'medium'],
      ['red', 'large'],
      ['blue', 'small'],
      ['blue', 'medium'],
    ];
    $variations = [
      $this->variation,
    ];
    // Generate variations off of the attributes values matrix.
    foreach ($attribute_values_matrix as $key => $value) {
      /** @var \Drupal\commerce_product\Entity\ProductVariationInterface $variation */
      $variation = $this->createEntity('commerce_product_variation', [
        'type' => $variation_type->id(),
        'sku' => $this->randomMachineName(),
        'price' => [
          'number' => 999,
          'currency_code' => 'USD',
        ],
        'attribute_color' => $updated_color_attributes[$value[0]],
        'attribute_size' => $updated_size_attributes[$value[1]],
      ]);
      $variation->save();
      $variation_fr = $variation->toArray();
      $variation->addTranslation('fr', $variation_fr)->save();
      $variation = ProductVariation::load($variation->id());
      $variations[] = $variation;
      $product->variations->appendItem($variation);
    }
    $product->save();
    $this->product = Product::load($product->id());

    $this->variations = $variations;
    $this->colorAttributes = $updated_color_attributes;
    $this->sizeAttributes = $updated_size_attributes;

  }

  /**
   * Tests adding a product to the cart using
   * 'commerce_product_variation_attributes' widget.
   */
  public function te2222stProductVariationAttributesWidget() {
    $this->drupalGet($this->product->toUrl());
    $this->assertAttributeSelected('purchased_entity[0][attributes][attribute_color]', 'Red');
    $this->assertAttributeSelected('purchased_entity[0][attributes][attribute_size]', 'Small');
    $this->assertAttributeExists('purchased_entity[0][attributes][attribute_color]', $this->colorAttributes['blue']->id());
    $this->assertAttributeExists('purchased_entity[0][attributes][attribute_size]', $this->sizeAttributes['medium']->id());
    $this->assertAttributeExists('purchased_entity[0][attributes][attribute_size]', $this->sizeAttributes['large']->id());
    $this->getSession()->getPage()->pressButton('Add to cart');

    // Change the site language.
    $this->config('system.site')->set('default_langcode', 'fr')->save();
    $this->drupalGet($this->product->toUrl());
    // Use AJAX to change the size to Medium, keeping the color on Red.
    $this->getSession()->getPage()->selectFieldOption('purchased_entity[0][attributes][attribute_size]', 'FR Medium');
    $this->waitForAjaxToFinish();
    $this->assertAttributeSelected('purchased_entity[0][attributes][attribute_color]', 'FR Red');
    $this->assertAttributeSelected('purchased_entity[0][attributes][attribute_size]', 'FR Medium');
    $this->assertAttributeExists('purchased_entity[0][attributes][attribute_color]', $this->colorAttributes['blue']->id());
    $this->assertAttributeExists('purchased_entity[0][attributes][attribute_size]', $this->sizeAttributes['small']->id());
    $this->assertAttributeExists('purchased_entity[0][attributes][attribute_size]', $this->sizeAttributes['large']->id());

    // Use AJAX to change the color to Blue, keeping the size on Medium.
    $this->getSession()->getPage()->selectFieldOption('purchased_entity[0][attributes][attribute_color]', 'FR Blue');
    $this->waitForAjaxToFinish();
    $this->assertAttributeSelected('purchased_entity[0][attributes][attribute_color]', 'FR Blue');
    $this->assertAttributeSelected('purchased_entity[0][attributes][attribute_size]', 'FR Medium');
    $this->assertAttributeExists('purchased_entity[0][attributes][attribute_color]', $this->colorAttributes['red']->id());
    $this->assertAttributeExists('purchased_entity[0][attributes][attribute_size]', $this->sizeAttributes['small']->id());
    $this->assertAttributeDoesNotExist('purchased_entity[0][attributes][attribute_size]', $this->sizeAttributes['large']->id());
    $this->getSession()->getPage()->pressButton('Add to cart');

    $this->cart = Order::load($this->cart->id());
    $order_items = $this->cart->getItems();
    $this->assertOrderItemInOrder($this->variations[0]->getTranslation('fr'), $order_items[0]);
    $this->assertOrderItemInOrder($this->variations[5]->getTranslation('fr'), $order_items[1]);
  }

  /**
   * Tests adding a product to the cart using 'commerce_product_variation_title'
   * widget.
   */
  public function testProductVariationTitleWidget() {
    // @todo
    $order_item_form_display = EntityFormDisplay::load('commerce_order_item.default.add_to_cart');
    $order_item_form_display->setComponent('purchased_entity', [
      'type' => 'commerce_product_variation_title'
    ]);
    $order_item_form_display->save();

    $this->drupalGet($this->product->toUrl());
    $this->assertSession()->selectExists('purchased_entity[0][variation]');
    $this->assertAttributeSelected('purchased_entity[0][variation]', 'Title - Red, Small');
    $this->assertAttributeExists('purchased_entity[0][variation]', 'Title - Red, Medium');
    $this->getSession()->getPage()->pressButton('Add to cart');

    // Change the site language.
    $this->config('system.site')->set('default_langcode', 'fr')->save();
    $this->drupalGet($this->product->toUrl());
    // Use AJAX to change the size to Medium, keeping the color on Red.
    $this->assertAttributeSelected('purchased_entity[0][variation]', 'Title - FR Red, FR Small');
    $this->assertAttributeExists('purchased_entity[0][variation]', 'Title - FR Red, FR Medium');
    $this->assertAttributeExists('purchased_entity[0][variation]', 'Title - FR Red, FR Large');
    $this->assertAttributeExists('purchased_entity[0][variation]', 'Title - FR Blue, FR Small');
    $this->assertAttributeExists('purchased_entity[0][variation]', 'Title - FR Blue, FR Medium');
    $this->getSession()->getPage()->selectFieldOption('purchased_entity[0][variation]', 'Title - FR Red, FR Medium');
    $this->waitForAjaxToFinish();
    $this->assertAttributeSelected('purchased_entity[0][variation]', 'Title - FR Red, FR Medium');
    $this->assertSession()->pageTextContains('Title - FR Red, FR Medium');

    // Use AJAX to change the color to Blue, keeping the size on Medium.
    $this->getSession()->getPage()->selectFieldOption('purchased_entity[0][variation]', 'Title - FR Blue, FR Medium');
    $this->waitForAjaxToFinish();
    $this->assertAttributeSelected('purchased_entity[0][variation]', 'FR title - FR Blue, FR Medium');
    $this->assertSession()->pageTextContains('Title - FR Blue, FR Medium');
    $this->getSession()->getPage()->pressButton('Add to cart');

    $this->cart = Order::load($this->cart->id());
    $order_items = $this->cart->getItems();
    $this->assertOrderItemInOrder($this->variations[0]->getTranslation('fr'), $order_items[0]);
    $this->assertOrderItemInOrder($this->variations[5]->getTranslation('fr'), $order_items[1]);
  }

}
