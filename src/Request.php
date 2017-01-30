<?php

namespace Neto\Messenger;

class Request
{
  public function getMethod() {
    return isset($_SERVER['REQUEST_METHOD']) ? $_SERVER['REQUEST_METHOD'] : 'CLI';
  }
  
  public function getQueryParam($name) 
  {
    return isset($_GET[$name]) ? $_GET[$name] : null;
  }
  
  public function getPostData() 
  {
    return file_get_contents('php://input');
  }
}