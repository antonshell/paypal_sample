<?php
/**
 * Created by PhpStorm.
 * User: Antonshell
 * Date: 10.08.2015
 * Time: 21:43
 */

class Config{
    public static function get(){
        //require_once __DIR__ . '/../config.php';
        //return $config;

        $config = [
            'database'=>[
                'host'=>'localhost',
                'user'=>'root',
                'password'=>'',
                'database' => 'paypal_sample'
            ],
            'app_url' => $_SERVER['REQUEST_SCHEME'] . '://' . $_SERVER['HTTP_HOST'],
            'menu'=>[
                [
                    'page'=>'single_payment',
                    'label'=>'Single Payment',
                ],
                [
                    'page'=>'subscription',
                    'label'=>'Subscription'
                ],
            ],
            'paypal' => [
                /*'project_name' => 'paypal_sample',

                'receiver_email' => 'YOUR_PAYPAL_RECEIVER_EMAIL',
                'environment_mode' => 'sandbox',

                'username' => 'YOUR_PAYPAL_USERNAME',
                'password' => 'YOUR_PAYPAL_PASSWORD',
                'signature' => 'YOUR_PAYPAL_SIGNATURE',*/

                'project_name' => 'paypal_sample',
                'receiver_email' => 'antonshel-facilitator@gmail.com',
                'environment_mode' => 'sandbox',
                'username' => 'antonshel-facilitator_api1.gmail.com',
                'password' => 'LG4QW966KSZEDN22',
                'signature' => 'AFcWxV21C7fd0v3bYYYRCpSSRl31AvjKv59PR5PzFuyoTu9JpOggLUCs'
            ],

        ];

        return $config;
    }
}