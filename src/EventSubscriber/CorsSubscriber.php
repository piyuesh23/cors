<?php

namespace Drupal\cors\EventSubscriber;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\HttpKernel\Event\FilterResponseEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Drupal\Component\Utility\Unicode;

class CorsSubscriber implements EventSubscriberInterface {
  public function addAccessAllowOriginHeaders(FilterResponseEvent $event) {
    $domains = \Drupal::config('cors.config')->get('cors_domains');
    $request = $event->getRequest();
    $response= $event->getResponse();
    $query_path = $request->getRequestUri();
    $current_path = Unicode::strtolower(\Drupal::service('path.alias_storage')->lookupPathAlias($query_path, 'en'));
    $request_headers = $request->headers->all();
    $headers = array(
      'all' => array(
        'Access-Control-Allow-Origin' => array(),
        'Access-Control-Allow-Credentials' => array(),
      ),
      'OPTIONS' => array(
        'Access-Control-Allow-Methods' => array(),
        'Access-Control-Allow-Headers' => array(),
      ),
    );

    foreach ($domains as $path => $settings) {
      $settings = explode("|", $settings);
      $page_match = \Drupal::service('path.matcher')->matchPath($current_path, $path);
      if ($current_path != $query_path) {
        $page_match = $page_match || \Drupal::service('path.matcher')->matchPath($query_path, $path);
      }
      if ($page_match) {
        if (!empty($settings[0])) {
          $origins = explode(',', trim($settings[0]));
          foreach ($origins as $origin) {
            if ($origin === '<mirror>') {
              if (!empty($request_headers['origin'])) {
                $headers['all']['Access-Control-Allow-Origin'][] = $request_headers['origin'];
              }
            }
            else {
              $headers['all']['Access-Control-Allow-Origin'][] = $origin;
            }
          }

        }
        if (!empty($settings[1])) {
          $headers['OPTIONS']['Access-Control-Allow-Methods'] = explode(',', trim($settings[1]));
        }
        if (!empty($settings[2])) {
          $headers['OPTIONS']['Access-Control-Allow-Headers'] = explode(',', trim($settings[2]));
        }
        if (!empty($settings[3])) {
          $headers['all']['Access-Control-Allow-Credentials'] = explode(',', trim($settings[3]));
        }
      }
    }
    foreach ($headers as $method => $allowed) {
      if ($method === 'all' || $method === $_SERVER['REQUEST_METHOD']) {
        foreach ($allowed as $header => $values) {
          if (!empty($values)) {
            foreach ($values as $value) {
              $response->headers->set($header, $value);
            }
          }
        }
      }
    }
  }
  /**
  * {@inheritdoc}
  */
  static function getSubscribedEvents() {
    $events[KernelEvents::RESPONSE][] = array('addAccessAllowOriginHeaders');
    return $events;
  }
}
