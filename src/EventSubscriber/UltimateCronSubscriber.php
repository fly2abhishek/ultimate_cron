<?php

/**
 * @file
 * Contains \Drupal\ultimate_cron\UltimateCronSubscriber.
 */

namespace Drupal\ultimate_cron\EventSubscriber;


use Drupal\ultimate_cron\UltimateCronShutdownInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\PostResponseEvent;
use Symfony\Component\HttpKernel\KernelEvents;
use Drupal\Core\Database\Database;
use Drupal\Core\Config\ConfigFactory;
use Symfony\Component\HttpKernel\Event\GetResponseEvent;

/**
 * Class UltimateCronSubscriber.
 *
 * @package Drupal\ultimate_cron

/**
 * An event subscriber that adds Ultimate Cron database connection.
 */
class UltimateCronSubscriber implements EventSubscriberInterface {

  /**
   * Drupal\Core\Database\Driver\mysql\Connection definition.
   *
   * @var \Drupal\Core\Database\Driver\mysql\Connection
   */
  protected $database;

  /**
   * Drupal\Core\Session\SessionManager definition.
   *
   * @var \Drupal\Core\Session\SessionManager
   */
  protected $session_manager;

  /**
   * Drupal\Core\Config\ConfigFactory definition.
   *
   * @var \Drupal\Core\Config\ConfigFactory
   */
  protected $config;

  /**
   * The ultimate_cron manager service.
   *
   * @var \Drupal\ultimate_cron\UltimateCronManagerInterface
   */
  protected $ultimateCronManager;

  /**
   * The ultimate_cron manager service.
   *
   * @var \Drupal\ultimate_cron\UltimateCronShutdownInterface
   */
  protected $ultimateCronShutdown;

  /**
   * Constructor.
   */
  public function __construct(ConfigFactory $config_factory, UltimateCronShutdownInterface $ultimate_cron_shutdown) {
    $this->config = $config_factory->get('ultimate_cron.settings');
    $this->ultimateCronShutdown = $ultimate_cron_shutdown;
  }

  /**
   * {@inheritdoc}
   */
  static function getSubscribedEvents() {
    $events[KernelEvents::REQUEST][] = ['onRequest', 100];
    $events[KernelEvents::TERMINATE][] = ['onTerminate', 0];
    return $events;
  }

  /**
   * This method is called whenever the kernel.request event is
   * dispatched.
   *
   * @param GetResponseEvent $event
   */
  public function onRequest(GetResponseEvent $event) {
    if (!$this->config->get('bypass_transactional_safe_connection')) {
      $info = Database::getConnectionInfo('default');
      Database::addConnectionInfo('default', 'ultimate_cron', $info['default']);
    }
  }

  /**
   * This method is called whenever the kernel.terminate event is dispatched.
   *
   * @param GetTerminateEvent $event
   */
  public function onTerminate(PostResponseEvent $event) {
    $this->ultimateCronShutdown->outOfMemoryProtection();
  }

}
