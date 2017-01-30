<?php

namespace Neto\Messenger;

error_reporting(E_ALL);
ini_set('display_errors','on');

use \Closure;
use \ReflectionFunction;
use \Facebook\Facebook;
use \Neto\Messenger\Request;
use \Neto\Messenger\Handler;

class Client 
{
  protected $api = null;
  protected $subscribeHookToken = '';
  protected $handlers = [];
  
  public function __construct($token, $config) 
  {
    $this->api = new Facebook([
      'app_id' => $config['app']['id'],
      'app_secret' => $config['app']['secret'],
      'default_graph_version' => isset($config['app']['version']) ? $config['app']['version'] : 'v2.6',
    ]);
    $this->api->setDefaultAccessToken($token);
    $this->subscribeHookToken = $config['hook']['subscribe_token'];
  }
  
  public function run($request = null) 
  {
    $request = $request ? $request : new Request();
    switch ($request->getMethod()) {
      case 'GET':
        $this->handleSubscribe($request);
        break;
      case 'POST':
        $this->handleHook($request);
        break;
    }
  }
  
  public function sendMessage($message) {
    $this->api->post('/me/messages', $message);
  }
  
  public function sendTextMessage($recipientId, $text) {
    $message = [
      'recipient' => [
        'id'    => $recipientId,
      ],
      'message'   => [
        'text'  => $text,
      ],
    ];
  
    $this->sendMessage($message);
  }
  
  public function sendImage($recipientId, $url) {
    $message = [
      'recipient' => [
        'id'  => $recipientId,
      ],
      'message'   => [
        'attachment'  => [
          'type'    => 'image',
          'payload' => [
            'url' => $url,
          ],
        ],
      ],
    ];
    $this->sendMessage($message);
  }
  
  public function on(Closure $handler, Closure $checker = null) 
  {
    $this->handlers[] = new Handler($handler, $checker);
  }
  
  public function postback($name, Closure $action)
  {
    return $this->on(self::getEvent($action), self::getPostbackChecker($name));
  }
  
  public function message(Closure $action, Closure $checker = null)
  {
    return $this->on(self::getEvent($action), self::getMessageChecker($checker));
  }
  
  protected function handleHook($request)
  {
    $data = json_decode($request->getPostData(), true);
    if (isset($data['entry']) && is_array($data['entry'])) {
      foreach ($data['entry'] as $page) {
        $this->handlePage($page);
      }  
    }
  }
  
  protected function handlePage($page) 
  {
    foreach ($page['messaging'] as $event) {
      $this->handleEvent($event);
    }
  }
  
  protected function handleEvent($event) 
  {
    foreach ($this->handlers as $handler) {
      if (!$handler->call($event)) {
        break;
      }
    }
  }
  
  protected function handleSubscribe($request) 
  {
    if ($request->getQueryParam('hub_mode') === 'subscribe' 
        && $request->getQueryParam('hub_verify_token') === $this->subscribeHookToken) {
        echo $request->getQueryParam('hub_challenge');
    } else {
        header('HTTP/1.0 403 Forbidden', true, 403);
    }
    die();
  }
  
  protected static function getEvent(Closure $handler)
  {
    return function ($event) use ($handler) {
      $parameters = [ $event ];
      $handler = new ReflectionFunction($handler);
      if (count($parameters) >= $handler->getNumberOfRequiredParameters()) {
        return $handler->invokeArgs($parameters);
      }
      return true;
    };
  }
  
  protected static function getPostbackChecker($name)
  {
    return function ($event) use ($name) {
      if (!isset($event['postback']) || !$event['postback']) {
        return false;
      }
      return $event['postback']['payload'] === $name;
    };
  }
  
  protected static function getMessageChecker($checker)
  {
    return function ($event) use ($checker) {
      if (!isset($event['message']) || !$event['message']) {
        return false;
      }
      return function ($event) use ($checker) {
        $parameters = [ $event ];
        $checker = new ReflectionFunction($checker);
        if (count($parameters) >= $checker->getNumberOfRequiredParameters()) {
          return $checker->invokeArgs($parameters);
        }
      };
    };
  }
}