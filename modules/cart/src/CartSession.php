<?php

namespace Drupal\commerce_cart;

use Symfony\Component\HttpFoundation\Session\SessionInterface;

/**
 * Default implementation of the cart session.
 */
class CartSession implements CartSessionInterface {

  /**
   * The session.
   *
   * @var \Symfony\Component\HttpFoundation\Session\SessionInterface
   */
  protected $session;

  /**
   * Constructs a new CartSession object.
   *
   * @param \Symfony\Component\HttpFoundation\Session\SessionInterface $session
   *   The session.
   */
  public function __construct(SessionInterface $session) {
    $this->session = $session;
  }

  /**
   * {@inheritdoc}
   */
  public function getActiveCartIds() {
    return $this->session->get('commerce_cart_orders', []);
  }

  /**
   * {@inheritdoc}
   */
  public function addActiveCartId($cart_id) {
    $ids = $this->session->get('commerce_cart_orders', []);
    $ids[] = $cart_id;
    $this->session->set('commerce_cart_orders', array_unique($ids));
  }

  /**
   * {@inheritdoc}
   */
  public function hasActiveCartId($cart_id) {
    $ids = $this->session->get('commerce_cart_orders', []);
    return in_array($cart_id, $ids);
  }

  /**
   * {@inheritdoc}
   */
  public function hasCompletedCartId($cart_id) {
    $ids = $this->session->get('commerce_cart_completed_orders', []);
    return in_array($cart_id, $ids);
  }

  /**
   * {@inheritdoc}
   */
  public function deleteActiveCartId($cart_id) {
    $ids = $this->session->get('commerce_cart_orders', []);
    $ids = array_diff($ids, [$cart_id]);
    if (!empty($ids)) {
      $this->session->set('commerce_cart_orders', $ids);
    }
    else {
      // Remove the empty list to allow the system to clean up empty sessions.
      $this->session->remove('commerce_cart_orders');
    }
  }

  /**
   * {@inheritdoc}
   */
  public function addCompletedCartId($cart_id) {
    // Keep a list of completed order for access checks to completed orders.
    $completed_ids = $this->session->get('commerce_cart_completed_orders', []);
    $completed_ids = array_merge($completed_ids, [$cart_id]);
    $this->session->set('commerce_cart_completed_orders', $completed_ids);
  }

}
