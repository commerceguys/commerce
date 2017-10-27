<?php

namespace Drupal\commerce_payment\Entity;

use Drupal\Core\Entity\ContentEntityBase;
use Drupal\Core\Entity\EntityChangedTrait;
use Drupal\Core\Entity\EntityMalformedException;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Field\BaseFieldDefinition;
use Drupal\user\UserInterface;
use Drupal\profile\Entity\ProfileInterface;

/**
 * Defines the payment method entity class.
 *
 * @ContentEntityType(
 *   id = "commerce_payment_method",
 *   label = @Translation("Payment method"),
 *   label_collection = @Translation("Payment methods"),
 *   label_singular = @Translation("payment method"),
 *   label_plural = @Translation("payment methods"),
 *   label_count = @PluralTranslation(
 *     singular = "@count payment method",
 *     plural = "@count payment methods",
 *   ),
 *   bundle_label = @Translation("Payment method type"),
 *   bundle_plugin_type = "commerce_payment_method_type",
 *   handlers = {
 *     "access" = "Drupal\commerce_payment\PaymentMethodAccessControlHandler",
 *     "list_builder" = "Drupal\commerce_payment\PaymentMethodListBuilder",
 *     "storage" = "Drupal\commerce_payment\PaymentMethodStorage",
 *     "form" = {
 *       "edit" = "Drupal\commerce_payment\Form\PaymentMethodEditForm",
 *       "delete" = "Drupal\commerce_payment\Form\PaymentMethodDeleteForm"
 *     },
 *     "route_provider" = {
 *       "default" = "Drupal\Core\Entity\Routing\DefaultHtmlRouteProvider",
 *     },
 *   },
 *   base_table = "commerce_payment_method",
 *   admin_permission = "administer commerce_payment_method",
 *   fieldable = TRUE,
 *   entity_keys = {
 *     "id" = "method_id",
 *     "uuid" = "uuid",
 *     "bundle" = "type"
 *   },
 *   links = {
 *     "collection" = "/user/{user}/payment-methods",
 *     "canonical" = "/user/{user}/payment-methods/{commerce_payment_method}/edit",
 *     "edit-form" = "/user/{user}/payment-methods/{commerce_payment_method}/edit",
 *     "delete-form" = "/user/{user}/payment-methods/{commerce_payment_method}/delete",
 *   },
 * )
 */
class PaymentMethod extends ContentEntityBase implements PaymentMethodInterface {

  use EntityChangedTrait;

  /**
   * {@inheritdoc}
   */
  protected function urlRouteParameters($rel) {
    $uri_route_parameters = parent::urlRouteParameters($rel);
    $uri_route_parameters['user'] = $this->getOwnerId();
    return $uri_route_parameters;
  }

  /**
   * {@inheritdoc}
   */
  public function label() {
    return $this->getType()->buildLabel($this);
  }

  /**
   * {@inheritdoc}
   */
  public function getType() {
    $payment_method_type_manager = \Drupal::service('plugin.manager.commerce_payment_method_type');
    return $payment_method_type_manager->createInstance($this->bundle());
  }

  /**
   * {@inheritdoc}
   */
  public function getPaymentGateway() {
    return $this->get('payment_gateway')->entity;
  }

  /**
   * {@inheritdoc}
   */
  public function getPaymentGatewayId() {
    return $this->get('payment_gateway')->target_id;
  }

  /**
   * {@inheritdoc}
   */
  public function getPaymentGatewayMode() {
    return $this->get('payment_gateway_mode')->value;
  }

  /**
   * {@inheritdoc}
   */
  public function getOwner() {
    return $this->get('uid')->entity;
  }

  /**
   * {@inheritdoc}
   */
  public function getOwnerId() {
    return $this->get('uid')->target_id;
  }

  /**
   * {@inheritdoc}
   */
  public function setOwnerId($uid) {
    $this->set('uid', $uid);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function setOwner(UserInterface $account) {
    $this->set('uid', $account->id());
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getRemoteId() {
    return $this->get('remote_id')->value;
  }

  /**
   * {@inheritdoc}
   */
  public function setRemoteId($remote_id) {
    $this->set('remote_id', $remote_id);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getBillingProfile() {
    return $this->get('billing_profile')->entity;
  }

  /**
   * {@inheritdoc}
   */
  public function setBillingProfile(ProfileInterface $profile) {
    $this->set('billing_profile', $profile);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function isReusable() {
    return $this->get('reusable')->value;
  }

  /**
   * {@inheritdoc}
   */
  public function setReusable($reusable) {
    $this->set('reusable', $reusable);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function isDefault() {
    return $this->get('is_default')->value;
  }

  /**
   * {@inheritdoc}
   */
  public function setDefault($default) {
    $this->set('is_default', $default);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function isExpired() {
    $expires = $this->getExpiresTime();
    return $expires > 0 && $expires <= \Drupal::time()->getRequestTime();
  }

  /**
   * {@inheritdoc}
   */
  public function getExpiresTime() {
    return $this->get('expires')->value;
  }

  /**
   * {@inheritdoc}
   */
  public function setExpiresTime($timestamp) {
    $this->set('expires', $timestamp);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getCreatedTime() {
    return $this->get('created')->value;
  }

  /**
   * {@inheritdoc}
   */
  public function setCreatedTime($timestamp) {
    $this->set('created', $timestamp);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function isDeleted() {
    return $this->get('deleted')->value;
  }

  /**
   * {@inheritdoc}
   */
  public function setDeleted($deleted) {
    $this->set('deleted', $deleted);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function preSave(EntityStorageInterface $storage) {
    parent::preSave($storage);

    $payment_gateway = $this->getPaymentGateway();
    if (!$payment_gateway) {
      throw new EntityMalformedException(sprintf('Required payment method field "payment_gateway" is empty.'));
    }
    // Populate the payment_gateway_mode automatically.
    if ($this->get('payment_gateway_mode')->isEmpty()) {
      $this->set('payment_gateway_mode', $payment_gateway->getPlugin()->getMode());
    }
  }

  /**
   * {@inheritdoc}
   */
  public static function baseFieldDefinitions(EntityTypeInterface $entity_type) {
    $fields = parent::baseFieldDefinitions($entity_type);

    $fields['payment_gateway'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(t('Payment gateway'))
      ->setDescription(t('The payment gateway.'))
      ->setRequired(TRUE)
      ->setSetting('target_type', 'commerce_payment_gateway');

    $fields['payment_gateway_mode'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Payment gateway mode'))
      ->setDescription(t('The payment gateway mode.'))
      ->setRequired(TRUE);

    $fields['uid'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(t('Owner'))
      ->setDescription(t('The payment method owner.'))
      ->setSetting('target_type', 'user')
      ->setSetting('handler', 'default')
      ->setDefaultValueCallback('Drupal\commerce_payment\Entity\PaymentMethod::getCurrentUserId')
      ->setDisplayOptions('view', [
        'label' => 'above',
        'type' => 'author',
        'weight' => 0,
      ])
      ->setDisplayConfigurable('view', TRUE);

    $fields['remote_id'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Remote ID'))
      ->setDescription(t('The payment method remote ID.'))
      ->setSetting('max_length', 255)
      ->setDisplayConfigurable('view', TRUE);

    $fields['billing_profile'] = BaseFieldDefinition::create('entity_reference_revisions')
      ->setLabel(t('Billing profile'))
      ->setDescription(t('Billing profile'))
      ->setSetting('target_type', 'profile')
      ->setSetting('handler', 'default')
      ->setSetting('handler_settings', ['target_bundles' => ['customer']])
      ->setDisplayOptions('form', [
        'type' => 'options_select',
        'weight' => 0,
        'settings' => [],
      ])
      ->setDisplayOptions('view', [
        'label' => 'hidden',
        'type' => 'entity_reference_entity_view',
        'weight' => 2,
      ])
      ->setDisplayConfigurable('view', TRUE);

    $fields['reusable'] = BaseFieldDefinition::create('boolean')
      ->setLabel(t('Reusable'))
      ->setDescription(t('Whether the payment method is reusable.'))
      ->setDefaultValue(TRUE)
      ->setDisplayConfigurable('view', TRUE);

    // 'default' is a reserved SQL word, hence the 'is_' prefix.
    $fields['is_default'] = BaseFieldDefinition::create('boolean')
      ->setLabel(t('Default'))
      ->setDescription(t("Whether this is the user's default payment method."));

    $fields['expires'] = BaseFieldDefinition::create('timestamp')
      ->setLabel(t('Expires'))
      ->setDescription(t('The time when the payment method expires. 0 for never.'))
      ->setDisplayOptions('view', [
        'label' => 'hidden',
        'type' => 'timestamp',
        'weight' => 1,
        'settings' => [
          'date_format' => 'custom',
          'custom_date_format' => 'n/Y',
        ],
      ])
      ->setDisplayConfigurable('view', TRUE)
      ->setDefaultValue(0);

    $fields['created'] = BaseFieldDefinition::create('created')
      ->setLabel(t('Created'))
      ->setDescription(t('The time when the payment method was created.'));

    $fields['changed'] = BaseFieldDefinition::create('changed')
      ->setLabel(t('Changed'))
      ->setDescription(t('The time when the payment method was last edited.'));

    $fields['deleted'] = BaseFieldDefinition::create('boolean')
      ->setLabel(t('Deleted'))
      ->setDescription(t('Whether the payment method is soft-deleted.'))
      ->setDefaultValue(FALSE);

    return $fields;
  }

  /**
   * Default value callback for 'uid' base field definition.
   *
   * @see ::baseFieldDefinitions()
   *
   * @return array
   *   An array of default values.
   */
  public static function getCurrentUserId() {
    return [\Drupal::currentUser()->id()];
  }

  /**
   * {@inheritdoc}
   */
  public static function preDelete(EntityStorageInterface $storage, array $entities) {
    parent::preDelete($storage, $entities);

    // Check if payment method is in use (referenced by order or a payment),
    // and if yes, do a soft-delete (save with deleted=TRUE) instead of entity deletion.
    /** @var \Drupal\commerce_order\OrderStorage $order_storage */
    $order_storage = \Drupal::service('entity_type.manager')
      ->getStorage('commerce_order');
    /** @var \Drupal\commerce_payment\PaymentStorageInterface $payment_storage */
    $payment_storage = \Drupal::service('entity_type.manager')
      ->getStorage('commerce_payment');

    /** @var \Drupal\commerce_payment\Entity\PaymentMethodInterface $payment_method */
    foreach ($entities as $payment_method) {
      $orders = $order_storage->loadMultipleByPaymentMethod($payment_method);
      $payments = $payment_storage->loadMultipleByPaymentMethod($payment_method);
      if (!empty($orders) || !empty($payments)) {
        // Set entity 'deleted' field to TRUE, if it is not already.
        if (!$payment_method->isDeleted()) {
          $payment_method->setDeleted(TRUE);
          $payment_method->save();
        }
      }
      else {
        // Update payment method if payment method was deleted before, and had
        // referenced order/payment, which no longer exist. This is to allow deletion.
        if ($payment_method->isDeleted()) {
          $payment_method->setDeleted(FALSE);
          $payment_method->save();
        }
      }
    }
  }

}
