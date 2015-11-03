# Ansistrano.com

## Install assets
    bower install

## Set up the project
    curl -sS https://getcomposer.org/installer | php
    php composer.phar install

## Start the web server
    php -S localhost:8080 -t web

## Start your Redis
    redis-server

## Generate deploys
    brew install httpie
    http POST http://localhost:8080/deploy

## Visit the home
    http://localhost:8080