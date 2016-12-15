<?php
/**
 * Created by PhpStorm.
 * User: Antonshell
 * Date: 11.08.2015
 * Time: 22:38
 */

require_once 'autoload.php';

$ipn = new PaypalIpn();
$ipn->createIpnListener();