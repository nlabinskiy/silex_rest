<?php
require_once __DIR__ . '/../vendor/autoload.php';

use Symfony\Component\HttpFoundation\Request;

class Application extends Silex\Application
{
    use Silex\Application\TwigTrait;
    use Silex\Application\MonologTrait;
}

$app = new Application();
$app['debug'] = true;

// registers

$app->register(new Silex\Provider\UrlGeneratorServiceProvider());

$app->register(new Silex\Provider\TwigServiceProvider(), array(
    'twig.path' => __DIR__.'/views',
));

$app->register(new Silex\Provider\DoctrineServiceProvider(), array(
    'db.options' => array(
        'driver'   => 'pdo_sqlite',
        'path'     => __DIR__.'/data/app.sqlite',
    ),
));

$app->register(new Silex\Provider\MonologServiceProvider(), array(
    'monolog.logfile' => __DIR__.'/logs/monolog.log',
    'monolog.name' => 'Addresses',
));

// definitions

$app->error(function (\Exception $e, $code) use ($app) {
    $app['monolog']->addError(sprintf("Code '%d': %s", $code, $e->getMessage()));
    //return new Response('We are sorry, but something went terribly wrong.');
    return $app->render(
        '_error.twig',
        array(
            'title' => 'Hello world! - Error',
            'message' => 'We are sorry, but something went terribly wrong.'
        )
    );
});

$app->before(function (Request $request) use ($app) {
    if (0 === strpos($request->headers->get('Content-Type'), 'application/json')) {
        $data = json_decode($request->getContent(), true);
        if (json_last_error() != JSON_ERROR_NONE) {
            $app['monolog']->addError("Wrong input data. Can't decode as JSON string.");
        }

        $request->request->replace(is_array($data) ? $data : array());
    }
});