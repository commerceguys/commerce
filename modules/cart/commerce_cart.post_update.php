<?php

/**
 * @file
 * Post update functions for Cart.
 */

/**
 * Revert Cart views to fix broken Price fields.
 */
function commerce_cart_post_update_1() {
  /** @var \Drupal\commerce\Config\ConfigUpdaterInterface $config_updater */
  $config_updater = \Drupal::service('commerce.config_updater');
  $result = $config_updater->revert([
    'views.view.commerce_cart_block',
    'views.view.commerce_cart_form',
    'views.view.commerce_carts',
  ], FALSE);
  $message = implode('<br>', $result->getFailed());

  return $message;
}

/**
 * Revert the cart block and form views.
 */
function commerce_cart_post_update_2() {
  /** @var \Drupal\commerce\Config\ConfigUpdaterInterface $config_updater */
  $config_updater = \Drupal::service('commerce.config_updater');
  $result = $config_updater->revert([
    'views.view.commerce_cart_block',
    'views.view.commerce_cart_form',
  ]);
  $message = implode('<br>', $result->getFailed());

  return $message;
}

/**
 * Revert cart admin view to add exposed filters.
 */
function commerce_cart_post_update_2() {
  /** @var \Drupal\commerce\Config\ConfigUpdaterInterface $config_updater */
  $config_updater = \Drupal::service('commerce.config_updater');

  $views = [
    'views.view.commerce_carts',
  ];
  $result = $config_updater->revert($views, FALSE);

  $success_results = $result->getSucceeded();
  $failure_results = $result->getFailed();
  if ($success_results) {
    $message = t('Succeeded:') . '<br>';
    foreach ($success_results as $success_message) {
      $message .= $success_message . '<br>';
    }
    $message .= '<br>';
  }
  if ($failure_results) {
    $message .= t('Failed:') . '<br>';
    foreach ($failure_results as $failure_message) {
      $message .= $failure_message . '<br>';
    }
  }

  return $message;
}
