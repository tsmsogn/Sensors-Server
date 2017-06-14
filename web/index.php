<?php
require('../vendor/autoload.php');

define('FILE_PARAMETER', getenv('FILE_PARAMETER') ?: 'file');
define('UPLOAD_DIR', __DIR__ . '/uploads');

use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Finder\Finder;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

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

$app->get('files/', function (Request $request) use ($app) {
  $finder = new Finder();
  $finder->files()->in(UPLOAD_DIR)->name('*.zip');

  // display files
  return $app['twig']->render('files.twig', array(
    'finder' => $finder
  ));
});

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

$app->get('files/fetch/{filename}', function (Request $request, $filename) use ($app) {
  $fs = new Filesystem();

  $path_to_file = UPLOAD_DIR . '/' . $filename;
  if ($fs->exists($path_to_file)) {
    $response = new BinaryFileResponse($path_to_file);

    $disposition = $response->headers->makeDisposition(
      ResponseHeaderBag::DISPOSITION_ATTACHMENT,
      $filename
    );

    $response->headers->set('Content-Disposition', $disposition);
    return $response;
  }

  throw new NotFoundHttpException();
});

$app->run();
