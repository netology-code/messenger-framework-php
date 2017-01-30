<?php

namespace Neto\Messenger;

use Closure;

class Handler
{
  protected $handler;
  protected $checker;
  
  public function __construct(Closure $handler, Closure $checker = null) 
  {
    $this->handler = $handler;
    $this->checker = $checker;
  }
  
  protected function executeChecker($event)
  {
    if (is_null($this->checker)) {
      return true;
    } elseif (is_callable($this->checker)) {
      return call_user_func($this->checker, $event);
    }
    return false;
  }
  
  protected function executeHandler($event)
  {
    if (is_callable($this->handler)) {
      return call_user_func($this->handler, $event);
    }
    return true;
  }
  
  public function call($event)
  {
    if (!$this->executeChecker($event)) {
      return true;
    }
    return $this->executeHandler($event);
  }
}