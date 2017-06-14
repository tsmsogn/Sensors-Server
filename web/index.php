<?php
require('../vendor/autoload.php');

define('FILE_PARAMETER', getenv('FILE_PARAMETER') ?: 'file');
define('UPLOAD_DIR', __DIR__ . '/uploads');

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

$app = new Silex\Application();
$app['debug'] = true;

// Register the monolog logging service
$app->register(new Silex\Provider\MonologServiceProvider(), array(
  'monolog.logfile' => 'php://stderr',
));

// Register view rendering
$app->register(new Silex\Provider\TwigServiceProvider(), array(
  'twig.path' => __DIR__ . '/views',
));

// Our web handlers

$app->post('files/upload', function (Request $request) use ($app) {
  $file = $request->files->get(FILE_PARAMETER);
  $filename = $file->getClientOriginalName();

  if ($file->move(UPLOAD_DIR, $filename)) {
    exit;
  }

  // 500
  $response = new Response();
  $response->setStatusCode(500);
  return $response;
});

$app->run();
