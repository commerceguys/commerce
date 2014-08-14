<?php

/**
 * @file
 * Contains Drupal\commerce\CommerceProductListBuilder.
 */

namespace Drupal\commerce_product;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityListBuilder;
use Drupal\Core\Language\LanguageInterface;

/**
 * Provides a list controller for stores.
 */
class CommerceProductListBuilder extends EntityListBuilder {

  /**
   * {@inheritdoc}
   */
  public function buildHeader() {
    $header['title'] = t('Title');
    $header['sku'] = t('SKU');
    return $header + parent::buildHeader();
  }

  /**
   * {@inheritdoc}
   */
  public function buildRow(EntityInterface $entity) {
    /* @var $entity \Drupal\commerce_product\Entity\CommerceProduct */

    $uri = $entity->urlInfo();
    $options = $uri->getOptions();
    $options += ($langcode != LanguageInterface::LANGCODE_NOT_SPECIFIED && isset($languages[$langcode]) ? array('language' => $languages[$langcode]) : array());
    $uri->setOptions($options);
    $row['title']['data'] = array(
      '#type' => 'link',
      '#title' => $entity->label(),
    ) + $uri->toRenderArray();

    $row['sku'] = $entity->getSku();
    return $row + parent::buildRow($entity);
  }

}
