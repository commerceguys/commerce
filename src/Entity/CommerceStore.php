<?php

/**
 * @file
 * Contains \Drupal\commerce\Entity\CommerceStore.
 */

namespace Drupal\commerce\Entity;

use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Field\FieldDefinition;
use Drupal\Core\Entity\ContentEntityBase;
use Drupal\commerce\CommerceStoreInterface;
use Drupal\Core\Entity\EntityTypeInterface;

/**
 * Defines the Commerce Store entity type.
 *
 * @ContentEntityType(
 *   id = "commerce_store",
 *   label = @Translation("Store"),
 *   bundle_label = @Translation("Store type"),
 *   controllers = {
 *     "view_builder" = "Drupal\Core\Entity\EntityViewBuilder",
 *     "list_builder" = "Drupal\commerce\CommerceStoreListBuilder",
 *     "form" = {
 *       "add" = "Drupal\commerce\Form\CommerceStoreForm",
 *       "edit" = "Drupal\commerce\Form\CommerceStoreForm",
 *       "delete" = "Drupal\commerce\Form\CommerceStoreDeleteForm"
 *     },
 *     "translation" = "Drupal\content_translation\ContentTranslationHandler"
 *   },
 *   base_table = "commerce_store",
 *   admin_permission = "administer commerce_store entities",
 *   fieldable = TRUE,
 *   translatable = TRUE,
 *   entity_keys = {
 *     "id" = "store_id",
 *     "bundle" = "type",
 *     "label" = "name",
 *     "uuid" = "uuid"
 *   },
 *   links = {
 *     "edit-form" = "commerce.store_edit",
 *     "delete-form" = "commerce.store_delete",
 *     "admin-form" = "commerce.store_type_edit"
 *   },
 *   bundle_entity_type = "commerce_store_type"
 * )
 */
class CommerceStore extends ContentEntityBase implements CommerceStoreInterface {
  /**
   * {@inheritdoc}
   */
  public function id() {
    return $this->get('store_id')->value;
  }

  /**
   * {@inheritdoc}
   */
  public function getName() {
    return $this->get('name')->value;
  }

  /**
   * {@inheritdoc}
   */
  public function setName($name) {
    $this->set('name', $name);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getEmail() {
    return $this->get('mail')->value;
  }

  /**
   * {@inheritdoc}
   */
  public function setEmail($mail) {
    $this->set('mail', $mail);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getDefaultCurrency() {
    return $this->get('default_currency')->value;
  }

  /**
   * {@inheritdoc}
   */
  public function setDefaultCurrency($currency_code) {
    $this->set('default_currency', $currency_code);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public static function baseFieldDefinitions(EntityTypeInterface $entity_type) {
    $fields['store_id'] = FieldDefinition::create('integer')
      ->setLabel(t('Store ID'))
      ->setDescription(t('The ID of the store.'))
      ->setReadOnly(TRUE);

    $fields['uuid'] = FieldDefinition::create('uuid')
      ->setLabel(t('UUID'))
      ->setDescription(t('The UUID of the store.'))
      ->setReadOnly(TRUE);

    $fields['langcode'] = FieldDefinition::create('language')
      ->setLabel(t('Language code'))
      ->setDescription(t('The language code of store.'));

    $fields['name'] = FieldDefinition::create('string')
      ->setLabel(t('Name'))
      ->setDescription(t('The name of the store.'))
      ->setTranslatable(TRUE)
      ->setSettings(array(
        'default_value' => '',
        'max_length' => 255,
        'text_processing' => 0,
      ));

    $fields['type'] = FieldDefinition::create('string')
      ->setLabel(t('Type'))
      ->setDescription(t('The type of the store.'))
      ->setRequired(TRUE);

    $fields['mail'] = FieldDefinition::create('email')
      ->setLabel(t('Email'))
      ->setDescription(t('The e-mail address of this store.'))
      ->setSetting('default_value', '');

    $fields['default_currency'] = FieldDefinition::create('string')
      ->setLabel(t('Default currency'))
      ->setDescription(t('The default currency of this store.'))
      ->setPropertyConstraints('value', array('length' => array('max' => 3)))
      ->setSetting('max_length', 32);

    // @todo make enabled currencies a store setting as well.

    return $fields;
  }
}
