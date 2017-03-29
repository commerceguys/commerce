<?php

namespace Drupal\commerce_promotion\Form;

use Drupal\Core\Entity\ContentEntityForm;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Link;

/**
 * Defines the promotion add/edit form.
 */
class PromotionForm extends ContentEntityForm {

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    // Skip building the form if there are no available stores.
    $store_query = $this->entityManager->getStorage('commerce_store')->getQuery();
    if ($store_query->count()->execute() == 0) {
      $link = Link::createFromRoute('Add a new store.', 'entity.commerce_store.add_page');
      $form['warning'] = [
        '#markup' => t("Promotions can't be created until a store has been added. @link", ['@link' => $link->toString()]),
      ];
      return $form;
    }

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function form(array $form, FormStateInterface $form_state) {
    /* @var \Drupal\commerce_promotion\Entity\Promotion $promotion */
    $promotion = $this->entity;

    $form = parent::form($form, $form_state);
    $form['#tree'] = TRUE;
    $form['#theme'] = ['commerce_promotion_form'];
    $form['#attached']['library'][] = 'commerce_promotion/form';

    $form['advanced'] = [
      '#type' => 'container',
      '#attributes' => ['class' => ['entity-meta']],
      '#weight' => 99,
    ];
    $form['option_details'] = [
      '#type' => 'container',
      '#title' => $this->t('Options'),
      '#group' => 'advanced',
      '#attributes' => ['class' => ['entity-meta__header']],
      '#weight' => -100,
    ];
    $form['date_details'] = [
      '#type' => 'details',
      '#open' => TRUE,
      '#title' => $this->t('Dates'),
      '#group' => 'advanced',
    ];
    $form['usage_details'] = [
      '#type' => 'details',
      '#open' => TRUE,
      '#title' => $this->t('Usage'),
      '#group' => 'advanced',
    ];
    $form['compatibility_details'] = [
      '#type' => 'details',
      '#open' => TRUE,
      '#title' => $this->t('Compatibility'),
      '#group' => 'advanced',
    ];

    $field_details_mapping = [
      'status' => 'option_details',
      'weight' => 'option_details',
      'order_types' => 'option_details',
      'stores' => 'option_details',
      'start_date' => 'date_details',
      'end_date' => 'date_details',
      'usage_limit' => 'usage_details',
      'compatibility' => 'compatibility_details',
    ];

    foreach ($field_details_mapping as $field => $group) {
      if (isset($form[$field])) {
        $form[$field]['#group'] = $group;
      }
    }

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    /** @var \Drupal\commerce_promotion\Entity\Promotion $promotion */
    $promotion = parent::validateForm($form, $form_state);

    // We need to validate that the target entity of conditions match the offer.
    if (!$promotion->get('offer')->isEmpty()) {
      /** @var \Drupal\commerce_promotion\Plugin\Commerce\PromotionOffer\PromotionOfferInterface $offer */
      $offer = $promotion->get('offer')->first()->getTargetInstance();
      $target_entity_type_id = $offer->getTargetEntityType();
      /** @var \Drupal\commerce\Plugin\Field\FieldType\PluginItem $condition */
      foreach ($promotion->get('conditions') as $delta => $condition) {
        // Skip empty values.
        if ($condition->isEmpty()) {
          continue;
        }
        /** @var \Drupal\commerce_promotion\Plugin\Commerce\PromotionCondition\PromotionConditionInterface $plugin */
        $plugin = $condition->getTargetInstance();
        if ($plugin->getTargetEntityType() != $target_entity_type_id) {
          $target_definition = $this->entityTypeManager->getDefinition($target_entity_type_id);
          $form_state->setError($form['conditions']['widget'][$delta], $this->t('The %label condition does not match the offer target type of %type', [
            '%label' => $plugin->getPluginDefinition()['label'],
            '%type' => $target_definition->getLabel(),
          ]));
        }
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public function save(array $form, FormStateInterface $form_state) {
    $this->entity->save();
    drupal_set_message($this->t('Saved the %label promotion.', ['%label' => $this->entity->label()]));
    $form_state->setRedirect('entity.commerce_promotion.collection');
  }

}
