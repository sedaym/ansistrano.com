<?php

use Ansistrano\DeploymentsCounter;
use Ansistrano\RedisStatsRepository;
use Geocoder\Provider\GeocoderServiceProvider;
use Predis\Client;
use Symfony\Component\HttpFoundation\JsonResponse;

require_once __DIR__.'/../vendor/autoload.php';

$app = new Silex\Application();

$app['debug'] = true;

$app->register(new GeocoderServiceProvider());

$app->register(new Silex\Provider\TwigServiceProvider(), array(
    'twig.path' => __DIR__.'/../views',
));

$app['twig'] = $app->share($app->extend('twig', function($twig, $app) {
    $twig->addFilter('to_weekday_name', new Twig_SimpleFilter('to_weekday_name', function($string) {
        return ['Sunday', 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday'][$string];
    }));

    return $twig;
}));

$app['now'] = new \DateTimeImmutable('now', new \DateTimeZone('UTC'));

$app['redis'] = $app->share(function() {
    return new Client();
});

$app['stats_repository'] = $app->share(function($app) {
    // return new \Ansistrano\RandomStatsRepository();
    return new RedisStatsRepository($app['redis']);
});

$app['deployments_counter'] = $app->share(function($app) {
    return new DeploymentsCounter($app['stats_repository']);
});

$app->post('/deploy', function() use($app) {
    try {
        $app['deployments_counter']->addDeployment($app['now']);
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
    return $app['twig']->render('home.twig', $app['deployments_counter']->statsFor($app['now']));
});

return $app;