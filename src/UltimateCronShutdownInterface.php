<?php

/**
 * @file
 * Contains \Drupal\ultimate_cron\UltimateCronShutdownInterface.
 */

namespace Drupal\ultimate_cron;

/**
 * Interface UltimateCronShutdownInterface.
 *
 * @package Drupal\ultimate_cron
 */
interface UltimateCronShutdownInterface {

  /**
   * Shutdown handler that unleash the memory reserved.
   */
  public function outOfMemoryProtection();

  /**
   * Registers a function for execution on shutdown.
   *
   * Wrapper for register_shutdown_function() that catches thrown exceptions to
   * avoid "Exception thrown without a stack frame in Unknown".
   *
   * This is a duplicate of the built-in functionality in Drupal, however we
   * need to perform our tasks before that.
   *
   * @param callable $callback
   *   The shutdown callable to register.
   * @param mixed[] $arguments
   *   Arguments to pass to the shutdown function.
   *
   * @see register_shutdown_function()
   *
   * @ingroup php_wrappers
   */
  public function registerShutdownFunction(callable $callback, $arguments = []);

}
