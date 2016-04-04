<?php
/**
 * @file
 * Contains \Drupal\ultimate_cron\Plugin\ultimate_cron\Logger\CacheLogger.
 * Cache logger for Ultimate Cron.
 */

namespace Drupal\ultimate_cron\Plugin\ultimate_cron\Logger;

use Drupal\Core\Cache\Cache;
use Drupal\Core\Form\FormStateInterface;
use Drupal\ultimate_cron\Logger\LoggerBase;

/**
 * Cache Logger.
 *
 * @LoggerPlugin(
 *   id = "cache",
 *   title = @Translation("Cache"),
 *   description = @Translation("Stores the last log entry (and only the last) in the cache."),
 * )
 */
class CacheLogger extends LoggerBase {

  public $logEntryClass = '\Drupal\ultimate_cron\Logger\CacheLogEntry';

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    return array(
      'bin' => 'ultimate_cron_logger',
      'timeout' => Cache::PERMANENT,
    );
  }

  /**
   * {@inheritdoc}
   */
  public function load($name, $lock_id = NULL, array $log_types = [ULTIMATE_CRON_LOG_TYPE_NORMAL]) {
    $log_entry = new $this->logEntryClass($name, $this);
    $settings = $log_entry->logger->configuration;

    if (!$lock_id) {
      $cache =  \Drupal::cache($settings['bin'])->get('uc-name:' . $name, TRUE);
      if (empty($cache) || empty($cache->data)) {
        return $log_entry;
      }
      $lock_id = $cache->data;
    }
    $cache = \Drupal::cache($settings['bin'])->get('uc-lid:' . $lock_id, TRUE);

    if (!empty($cache->data)) {
      $log_entry->setData((array) $cache->data);
      $log_entry->finished = TRUE;
    }
    return $log_entry;
  }

  /**
   * {@inheritdoc}
   */
  public function getLogEntries($name, array $log_types, $limit = 10) {
    $log_entry = $this->load($name);
    return $log_entry->lid ? array($log_entry) : array();
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {

    $config = \Drupal::service('config.factory')->get('ultimate_cron.settings');
    $plugin_id = $this->pluginId;
    $defination = $this->getPluginDefinition();
    $form['logger_default']['#options'][$plugin_id] = $defination['title'];

    $form = [
      '#type' => 'fieldset',
      '#title' => $defination['title'],
      '#tree' => TRUE,
    ];

    $form['bin'] = [
      '#type' => 'textfield',
      '#title' => t('Cache bin'),
      '#description' => t('Select which cache bin to use for storing logs.'),
      '#default_value' => $config->get('logger.cache.bin'),
      '#fallback' => TRUE,
      '#required' => TRUE,
    ];

    $form['timeout'] = [
      '#type' => 'textfield',
      '#title' => t('Cache timeout'),
      '#description' => t('Seconds before cache entry expires (0 = never).'),
      '#default_value' => $config->get('logger.cache.timeout'),
      '#fallback' => TRUE,
      '#required' => TRUE,
    ];

    return $form;
  }

}
