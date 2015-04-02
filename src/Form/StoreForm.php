<?php

/**
 * @file
 * Contains Drupal\commerce\Form\StoreForm.
 */

namespace Drupal\commerce\Form;

use Drupal\Core\Entity\ContentEntityForm;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Language\Language;

/**
 * Form controller for the store edit form.
 */
class StoreForm extends ContentEntityForm {

  /**
   * Overrides Drupal\Core\Entity\EntityFormController::form().
   */
  public function form(array $form, FormStateInterface $form_state) {
    /* @var $entity \Drupal\commerce\Entity\Store */
    $form = parent::form($form, $form_state);
    $entity = $this->entity;

    $enabledCurrencies = entity_load_multiple_by_properties('commerce_currency', array('status' => 1));
    $currency_options = array();
    foreach ($enabledCurrencies as $currency_code => $currency) {
      $currency_options[$currency_code] = $currency->getName();
    }

    $form['default_currency'] = array(
      '#type' => 'select',
      '#title' => t('Default currency'),
      '#options' => $currency_options,
    );

    return $form;
  }

  /**
   * Overrides Drupal\Core\Entity\EntityFormController::save().
   */
  public function save(array $form, FormStateInterface $form_state) {
    try {
      $this->entity->save();
      drupal_set_message($this->t('Saved the %label store.', array(
        '%label' => $this->entity->label(),
      )));
    }
    catch (\Exception $e) {
      drupal_set_message($this->t('The store could not be saved.'), 'error');
      $this->logger('commerce')->error($e);
    }
    $form_state->setRedirect('entity.commerce_store.collection');
  }

}
