<?php

/**
 * @file
 * Contains \Drupal\commerce_price\Entity\NumberFormat.
 */

namespace Drupal\commerce_price\Entity;

use CommerceGuys\Intl\NumberFormat\NumberFormatInterface;
use Drupal\Core\Config\Entity\ConfigEntityBase;

/**
 * Defines the Currency configuration entity.
 *
 * @ConfigEntityType(
 *   id = "commerce_number_format",
 *   label = @Translation("Number Format"),
 *   handlers = {
 *     "form" = {
 *       "add" = "Drupal\commerce_price\Form\NumberFormatForm",
 *       "edit" = "Drupal\commerce_price\Form\NumberFormatForm",
 *       "delete" = "Drupal\Core\Entity\EntityDeleteForm"
 *     },
 *     "list_builder" = "Drupal\commerce_price\NumberFormatListBuilder",
 *   },
 *   admin_permission = "administer stores",
 *   config_prefix = "commerce_number_format",
 *   entity_keys = {
 *     "id" = "locale",
 *     "label" = "name",
 *     "uuid" = "uuid",
 *     "status" = "status"
 *   },
 *   config_export = {
 *     "locale",
 *     "name",
 *     "numberingSystem",
 *     "decimalSeparator",
 *     "plusSign",
 *     "minusSign",
 *     "percentSign",
 *     "decimalPattern",
 *     "percentPattern",
 *     "currencyPattern",
 *     "accountingCurrencyPattern",
 *     "groupingSeparator",
 *     "status",
 *   },
 *   links = {
 *     "edit-form" = "/admin/commerce/config/number-format/{commerce_number_format}",
 *     "delete-form" = "/admin/commerce/config/number-format/{commerce_number_format}/delete",
 *     "collection" = "/admin/commerce/config/number-format"
 *   }
 * )
 */
class NumberFormat extends ConfigEntityBase implements NumberFormatInterface {

  /**
   * The locale (i.e. "en_US").
   *
   * @var string
   */
  protected $locale;

  /**
   * The human-readable name.
   *
   * @var string
   */
  protected $name;

  /**
   * The numbering system.
   *
   * @var string
   */
  protected $numberingSystem = [];

  /**
   * The decimal separator.
   *
   * @var string
   */
  protected $decimalSeparator = [];

  /**
   * The grouping separator.
   *
   * @var string
   */
  protected $groupingSeparator = [];

  /**
   * The plus sign.
   *
   * @var string
   */
  protected $plusSign = [];

  /**
   * The number symbols.
   *
   * @var string
   */
  protected $minusSign = [];

  /**
   * The percent sign.
   *
   * @var string
   */
  protected $percentSign = [];

  /**
   * The number pattern used to format decimal numbers.
   *
   * @var string
   */
  protected $decimalPattern;

  /**
   * The number pattern used to format percentages.
   *
   * @var string
   */
  protected $percentPattern;

  /**
   * The number pattern used to format currency amounts.
   *
   * @var string
   */
  protected $currencyPattern;

  /**
   * The number pattern used to format accounting currency amounts.
   *
   * @var string
   */
  protected $accountingCurrencyPattern;

  /**
   * Overrides \Drupal\Core\Entity\Entity::id().
   */
  public function id() {
    return $this->getLocale();
  }

  /**
   * The human readable name.
   *
   * @return string
   *   The name.
   */
  public function getName() {
    return $this->name;
  }

  /**
   * Sets the name.
   *
   * @param string $name
   *   The new name.
   *
   * @return $this
   */
  public function setName($name) {
    $this->name = $name;

    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getLocale() {
    return $this->locale;
  }

  /**
   * {@inheritdoc}
   */
  public function setLocale($locale) {
    $this->locale = $locale;

    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getNumberingSystem() {
    return $this->numberingSystem;
  }

  /**
   * {@inheritdoc}
   */
  public function setNumberingSystem($numberingSystem) {
    $this->numberingSystem = $numberingSystem;
  }

  /**
   * {@inheritdoc}
   */
  public function getDecimalSeparator() {
    return $this->decimalSeparator;
  }

  /**
   * {@inheritdoc}
   */
  public function setDecimalSeparator($decimalSeparator) {
    $this->decimalSeparator = $decimalSeparator;
  }

  /**
   * {@inheritdoc}
   */
  public function getGroupingSeparator() {
    return $this->groupingSeparator;
  }

  /**
   * {@inheritdoc}
   */
  public function setGroupingSeparator($groupingSeparator) {
    $this->groupingSeparator = $groupingSeparator;
  }

  /**
   * {@inheritdoc}
   */
  public function getPlusSign() {
    return $this->plusSign;
  }

  /**
   * {@inheritdoc}
   */
  public function setPlusSign($plusSign) {
    $this->plusSign = $plusSign;
  }

  /**
   * {@inheritdoc}
   */
  public function getMinusSign() {
    return $this->minusSign;
  }

  /**
   * {@inheritdoc}
   */
  public function setMinusSign($minusSign) {
    $this->minusSign = $minusSign;
  }

  /**
   * {@inheritdoc}
   */
  public function getPercentSign() {
    return $this->percentSign;
  }

  /**
   * {@inheritdoc}
   */
  public function setPercentSign($percentSign) {
    $this->percentSign = $percentSign;
  }

  /**
   * {@inheritdoc}
   */
  public function getDecimalPattern() {
    return $this->decimalPattern;
  }

  /**
   * {@inheritdoc}
   */
  public function setDecimalPattern($decimalPattern) {
    $this->decimalPattern = $decimalPattern;

    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getPercentPattern() {
    return $this->percentPattern;
  }

  /**
   * {@inheritdoc}
   */
  public function setPercentPattern($percentPattern) {
    $this->percentPattern = $percentPattern;

    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getCurrencyPattern() {
    return $this->currencyPattern;
  }

  /**
   * {@inheritdoc}
   */
  public function setCurrencyPattern($currencyPattern) {
    $this->currencyPattern = $currencyPattern;

    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getAccountingCurrencyPattern() {
    return $this->accountingCurrencyPattern;
  }

  /**
   * {@inheritdoc}
   */
  public function setAccountingCurrencyPattern($accountingCurrencyPattern) {
    $this->accountingCurrencyPattern = $accountingCurrencyPattern;

    return $this;
  }

}
