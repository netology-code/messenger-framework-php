# Simple framework for Facebook Messenger webhook

## Install

```bash
$ composer require neto/messenger-framework
```

## Usage

```php
<?php

require_once __DIR__ . '/vendor/autoload.php';
$app = new Neto\Messenger\Client(HOOK_TOKEN, [
  'app' => [
    'id'      => getenv('APP_ID'),
    'secret'  => getenv('APP_SECRET),
  ],
  'hook' => [
    'subscribe_token' => getenv('HOOK_SUBSCRIBE_TOKEN'),
  ],
]);

$app->message(function ($event) use ($app) {
  $senderId = $event['sender']['id'];
  $message = $event['message'];
  $app->sendTextMessage($senderId, $message['text']);
});

$app->run();

```