<?php

/**
 * @file
 * Contains \Drupal\ultimate_cron\UltimateCronShutdown.
 */

namespace Drupal\ultimate_cron;

/**
 * Class UltimateCronShutdown.
 *
 * @package Drupal\ultimate_cron
 */
class UltimateCronShutdown implements UltimateCronShutdownInterface {

  /**
   * Shutdown callbacks and arguments
   * @var array
   *   An array with each element containing the following keys:
   *     callback - callable
   *     arguments - arguments to send to this function
   */
  protected $shutdown_calls = [];

  /**
   * @inheritdoc
   */
  public function outOfMemoryProtection() {
    unset($GLOBALS['__RESERVED_MEMORY']);
    foreach ($this->shutdown_calls as $shutdown_call) {
      call_user_func_array($shutdown_call['callback'], $shutdown_call['arguments']);
    }
  }

  /**
   * @inheritdoc
   */
  public function registerShutdownFunction(callable $callback, $arguments = []) {
    $this->shutdown_calls[] = [
      'callback' => $callback,
      'arguments' => $arguments,
    ];
  }

}
