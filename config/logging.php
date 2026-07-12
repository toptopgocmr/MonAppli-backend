<?php

use Monolog\Handler\NullHandler;
use Monolog\Handler\StreamHandler;
use Monolog\Handler\SyslogUdpHandler;

return [

    /*
    |--------------------------------------------------------------------------
    | Default Log Channel
    |--------------------------------------------------------------------------
    |
    | This option defines the default log channel that gets used when writing
    | messages to the logs. The name specified in this option should match
    | one of the channels defined in the "channels" configuration array.
    |
    */

    'default' => env('LOG_CHANNEL', 'stack'),

    /*
    |--------------------------------------------------------------------------
    | Log Channels
    |--------------------------------------------------------------------------
    |
    | Here you may configure the log channels for your application. Laravel
    | uses the Monolog PHP logging library. This gives you a variety of
    | powerful log handlers / formatters to utilize.
    |
    */

    'channels' => [

        'stack' => [
            'driver' => 'stack',
            // ✅ 'stderr' ajouté : Railway ne montre dans son onglet "Logs" que
            // la sortie stdout/stderr du conteneur — jamais le contenu de
            // storage/logs/laravel.log (fichier interne au conteneur). Sans
            // ça, aucune erreur PHP n'était visible dans Railway, quel que
            // soit l'onglet consulté, d'où l'impossibilité de diagnostiquer
            // les erreurs 500 depuis l'interface Railway.
            'channels' => ['single', 'stderr'],
            'ignore_exceptions' => false,
        ],

        'single' => [
            'driver' => 'single',
            'path' => storage_path('logs/laravel.log'),
            'level' => env('LOG_LEVEL', 'error'), // ne log que les erreurs
        ],

        'daily' => [
            'driver' => 'daily',
            'path' => storage_path('logs/laravel.log'),
            'level' => env('LOG_LEVEL', 'error'),
            'days' => 14,
        ],

        'slack' => [
            'driver' => 'slack',
            'url' => env('LOG_SLACK_WEBHOOK_URL'),
            'username' => 'Laravel Log',
            'emoji' => ':boom:',
            'level' => env('LOG_LEVEL', 'critical'),
        ],

        'stderr' => [
            'driver' => 'monolog',
            'level' => env('LOG_LEVEL', 'error'),
            'handler' => StreamHandler::