<?php

namespace Drupal\commerce\PluginForm;

use Drupal\Component\Plugin\PluginInspectionInterface;

/**
 * Provides an interface for objects that depend on a plugin.
 */
interface PluginAwareInterface {

  /**
   * Sets the plugin for this object.
   *
   * @param \Drupal\Component\Plugin\PluginInspectionInterface $plugin
   *   The plugin.
   */
  public function setPlugin(PluginInspectionInterface $plugin);

}
