<?php
/**
 * Application configuration shared by all test types
 */

use tests\codeception\_support\MailHelper;

return [
    'controllerMap' => [
        'fixture' => [
            'class' => 'yii\faker\FixtureController',
            'fixtureDataPath' => '@tests/codeception/fixtures',
            'templatePath' => '@tests/codeception/templates',
            'namespace' => 'tests\codeception\fixtures',
        ],
    ],
    'components' => [
        'db' => [
            'dsn' => 'mysql:host=localhost;dbname=imshop_tests',
        ],
        'mailer' => [
            'useFileTransport' => true,
            'on afterSend' => function ($event) {
                if ($event->isSuccessful) {
                    MailHelper::$mails[] = $event->message;
                }
            },
        ],
        'urlManager' => [
            'showScriptName' => true,
        ],
    ],
];
