<?php
/**
 * Created by PhpStorm.
 * User: Antonshell
 * Date: 10.08.2015
 * Time: 20:55
 */

require_once 'autoload.php';

session_start();

// Kickstart the framework
$f3 = require(dirname(__FILE__) . '/libs/fatfree/lib/base.php');

$f3->set('DEBUG',1);
if ((float)PCRE_VERSION<7.9)
    trigger_error('PCRE version is out of date');

// Load configuration
$f3->config('config.ini');

$f3->route('GET /',
    function($f3) {
        $f3->set('page','/');
        $f3->set('content','main.php');
        echo View::instance()->render('layout.php');
    }
);

/* single payment */
$f3->route('GET /single_payment',
    function($f3) {
        $f3->set('page','single_payment');
        $f3->set('content','single_payment.php');
        echo View::instance()->render('layout.php');
    }
);
/**/

/* subscription */
$f3->route('GET /subscription',
    function($f3) {
        $f3->set('page','subscription');
        $f3->set('content','subscription.php');
        echo View::instance()->render('layout.php');
    }
);
/**/

/* select current user */
$f3->route('GET /select_user/@userId',
    function($f3) {
        $userId = $f3->get('PARAMS.userId');
        $userService = new UserService();
        $userService->setSelectedUser($userId);
        header('Location: /');
        die();
    }
);
/**/

/* cancel subscription */
$f3->route('GET|POST /cancel_subscription',
    function($f3) {
        $userService = new UserService();
        $subscriptionService = new Subscription();

        $serviceProvider = $f3->get('POST.serviceProvider');
        $userId = $userService->getSelectedUserId();

        $result = $subscriptionService->cancelSubscription($userId,$serviceProvider);
        echo json_encode($result);
        die();
    }
);
/**/

$f3->route('GET|POST /createFreeSubscription',
    function($f3) {
        $userService = new UserService();
        $subscriptionService = new Subscription();

        $userId = $userService->getSelectedUserId();
        $itemsCount = $f3->get('POST.itemsCount');
        $planId = $f3->get('POST.planId');
        $serviceProvider = $f3->get('POST.serviceProvider');

        $activeSubscription = Subscription::getActiveSubscriptions($userId,$serviceProvider);

        if($activeSubscription){
            $result = $subscriptionService->cancelSubscription($userId,$serviceProvider);
        }

        $result = $subscriptionService->createFreeSubscription($userId,$planId,$itemsCount);

        json_encode($result);
        die();
    }
);

$f3->route('GET|POST /ipn',
    function($f3) {
        $postData = file_get_contents('php://input');
        file_put_contents('ipn_log.txt',$postData . "\n\n", FILE_APPEND);

        $ipn = new PaypalIpn();
        $ipn->createIpnListener();
    }
);

$f3->run();