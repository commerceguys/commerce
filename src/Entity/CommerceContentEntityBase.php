<?php

namespace Drupal\commerce\Entity;

use Drupal\Core\Entity\ContentEntityBase;
use Drupal\Core\Language\LanguageInterface;

/**
 * Provides the base class for Commerce content entities.
 */
class CommerceContentEntityBase extends ContentEntityBase implements CommerceContentEntityInterface {

  /**
   * {@inheritdoc}
   */
  public function getTranslatedReferencedEntities($field_name) {
    $refereced_entities = $this->get($field_name)->referencedEntities();
    return $this->ensureTranslations($refereced_entities);
  }

  /**
   * Ensures that the provided entities are in the current entity's language if
   * entity is translatable or current interface language otherwise.
   *
   * @param \Drupal\Core\Entity\ContentEntityInterface[] $entities
   *   The entities to process.
   *
   * @return \Drupal\Core\Entity\ContentEntityInterface[]
   *   The processed entities.
   */
  protected function ensureTranslations(array $entities) {
    if ($this->isTranslatable()) {
      $langcode = $this->language()->getId();
    }
    else {
      $langcode = $this->languageManager()->getCurrentLanguage(LanguageInterface::TYPE_INTERFACE)->getId();
    }
    foreach ($entities as $index => $entity) {
      /** @var \Drupal\Core\Entity\ContentEntityInterface $entity */
      if ($entity->hasTranslation($langcode)) {
        $entities[$index] = $entity->getTranslation($langcode);
      }
    }

    return $entities;
  }

}
