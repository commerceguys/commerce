<?php

namespace Drupal\commerce_product\Form;

use Drupal\Core\Entity\BundleEntityFormBase;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\language\Entity\ContentLanguageSettings;

class ProductAttributeForm extends BundleEntityFormBase {

  /**
   * {@inheritdoc}
   */
  public function form(array $form, FormStateInterface $form_state) {
    $form = parent::form($form, $form_state);
    /** @var \Drupal\commerce_product\Entity\ProductAttributeInterface $attribute */
    $attribute = $this->entity;

    $form['label'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Name'),
      '#maxlength' => 255,
      '#default_value' => $attribute->label(),
      '#required' => TRUE,
    ];
    $form['id'] = [
      '#type' => 'machine_name',
      '#default_value' => $attribute->id(),
      '#machine_name' => [
        'exists' => '\Drupal\commerce_product\Entity\ProductAttribute::load',
      ],
      '#maxlength' => EntityTypeInterface::BUNDLE_MAX_LENGTH - 11,
    ];
    $form['elementType'] = [
      '#type' => 'select',
      '#default_value' => $attribute->getElementType(),
      '#options' => [
        'radios' => $this->t('Radio buttons'),
        'select' => $this->t('Select list'),
        'commerce_product_rendered_attribute' => $this->t('Rendered attribute'),
      ],
      '#description' => $this->t('Controls how the attribute is displayed on the add to cart form.'),
    ];

    if ($this->moduleHandler->moduleExists('language')) {
      $form['language'] = [
        '#type' => 'details',
        '#title' => $this->t('Language settings'),
      ];
      $form['language']['language_configuration'] = [
        '#type' => 'language_configuration',
        '#entity_information' => [
          'entity_type' => 'commerce_product_attribute_value',
          'bundle' => $attribute->id(),
        ],
        '#default_value' => ContentLanguageSettings::loadByEntityTypeBundle('commerce_product_attribute_value', $attribute->id()),
      ];
    }

    return $this->protectBundleIdElement($form);
  }

  /**
   * {@inheritdoc}
   */
  public function save(array $form, FormStateInterface $form_state) {
    $status = $this->entity->save();
    if ($status == SAVED_NEW) {
      drupal_set_message($this->t('Created the %label product attribute.', ['%label' => $this->entity->label()]));
      $form_state->setRedirectUrl($this->entity->toUrl('overview-form'));
    }
    else {
      drupal_set_message($this->t('Updated the %label product attribute.', ['%label' => $this->entity->label()]));
      $form_state->setRedirectUrl($this->entity->toUrl('collection'));
    }
  }

}
