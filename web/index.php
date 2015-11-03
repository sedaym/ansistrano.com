<?php

use Ansistrano\DeploymentsCounter;
use Symfony\Component\HttpFoundation\JsonResponse;

require_once __DIR__.'/../vendor/autoload.php';

$app = new Silex\Application();

$app['debug'] = true;

$app->register(new Silex\Provider\TwigServiceProvider(), array(
    'twig.path' => __DIR__.'/../views',
));

$app['now'] = new \DateTimeImmutable('now', new \DateTimeZone('UTC'));

$app['redis'] = $app->share(function() {
    return new \Predis\Client();
});

$app['stats_repository'] = $app->share(function($app) {
    return new \Ansistrano\RedisStatsRepository($app['redis']);
});

$app->post('/deploy', function() use($app) {
    try {
        $dc = new DeploymentsCounter($app['stats_repository']);
        $dc->addDeployment($app['now']);
    } catch(\Exception $e) {

        $res = ['error' => true];
        if ($app['debug']) {
            $res['msg'] = $e->getMessage();
        }

        return new JsonResponse($res, 500);
    }

    return new JsonResponse(['success' => true]);
});

$app->get('/', function() use($app) {
    return $app['twig']->render('home.twig', (new DeploymentsCounter($app['redis']))->statsFor($app['now']));
});

$app->run();
