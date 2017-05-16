<?php

namespace Drupal\commerce_tax\Plugin\Commerce\TaxType;

use Drupal\commerce_tax\TaxZone;
use Drupal\Core\Form\FormStateInterface;

/**
 * Provides the Swiss VAT tax type.
 *
 * @CommerceTaxType(
 *   id = "swiss_vat",
 *   label = "Swiss VAT",
 * )
 */
class SwissVat extends LocalTaxTypeBase {

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    $form = parent::buildConfigurationForm($form, $form_state);
    $form['rates'] = $this->buildRateSummary();
    // Replace the phrase "tax rates" with "VAT rates" to be more precise.
    $form['rates']['#markup'] = $this->t('The following VAT rates are provided:');

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function getDisplayLabel() {
    return $this->t('VAT');
  }

  /**
   * {@inheritdoc}
   */
  public function shouldRound() {
    return TRUE;
  }

  /**
   * {@inheritdoc}
   */
  public function getZones() {
    $zones = [];
    $zones['ch'] = new TaxZone([
      'id' => 'ch',
      'label' => $this->t('Switzerland'),
      'territories' => [
        ['country_code' => 'CH'],
        ['country_code' => 'LI'],
        ['country_code' => 'DE', 'included_postal_codes' => '78266'],
        ['country_code' => 'IT', 'included_postal_codes' => '22060'],
      ],
      'rates' => [
        [
          'id' => 'standard',
          'label' => $this->t('Standard'),
          'amounts' => [
            ['amount' => '0.076', 'start_date' => '1995-01-01', 'end_date' => '2010-12-31'],
            ['amount' => '0.08', 'start_date' => '2011-01-01'],
          ],
          'default' => TRUE,
        ],
        [
          'id' => 'hotel',
          'label' => $this->t('Hotel'),
          'amounts' => [
            ['amount' => '0.036', 'start_date' => '1995-01-01', 'end_date' => '2010-12-31'],
            ['amount' => '0.038', 'start_date' => '2011-01-01'],
          ],
        ],
        [
          'id' => 'reduced',
          'label' => $this->t('Reduced'),
          'amounts' => [
            ['amount' => '0.024', 'start_date' => '1995-01-01', 'end_date' => '2010-12-31'],
            ['amount' => '0.025', 'start_date' => '2011-01-01'],
          ],
        ],
      ],
    ]);

    return $zones;
  }

}
