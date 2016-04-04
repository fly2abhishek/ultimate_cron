<?php
/**
 * @file
 * Contains \Drupal\ultimate_cron\Logger\CacheLogEntry.
 */
namespace Drupal\ultimate_cron\Logger;


use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\ultimate_cron\Entity\CronJob;
use Symfony\Component\DependencyInjection\ContainerInterface;

class CacheLogEntry extends LogEntry {

  /**
   * Save log entry.
   */
  public function save() {
    if (!$this->lid) {
      return;
    }

    $settings = $this->logger->getConfiguration();

    $expire = $settings['timeout'] > 0 ? time() + $settings['timeout'] : $settings['timeout'];

    \Drupal::cache($settings['bin'])
      ->set('uc-name:' . $this->name, $this->lid, $expire);
    \Drupal::cache($settings['bin'])
      ->set('uc-lid:' . $this->lid, $this->getData(), $expire);

  }
}
