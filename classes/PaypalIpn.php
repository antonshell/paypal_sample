<?php
/**
 * Created by PhpStorm.
 * User: Antonshell
 * Date: 10.08.2015
 * Time: 22:43
 */

/**
 * Class PaypalIpn
 */
class PaypalIpn extends PaypalService{

    private $debug = true;
    private $service;

    /**
     * @throws Exception
     */
    public function createIpnListener(){
        $postData = file_get_contents('php://input');
        $transactionType = PaypalTransactionType::getPaymentType($postData);

        $config = Config::get();

        if($transactionType == PaypalTransactionType::TRANSACTION_TYPE_SINGLE_PAY){
            $this->service = new PaypalSinglePayment();
        }
        elseif($transactionType == PaypalTransactionType::TRANSACTION_TYPE_SUBSCRIPTION){
            $this->service = new PaypalSubscription($config);
        }
        else{
            throw new Exception('Wrong payment type');
        }


        // CONFIG: Enable debug mode. This means we'll log requests into 'ipn.log' in the same directory.
        // Especially useful if you encounter network errors or other intermittent problems with IPN (validation).
        // Set this to 0 once you go live or don't require logging.
        //define("DEBUG", 1);
        //define("DEBUG", 0);
        // Set to 0 once you're ready to go live

        if($this->environmentMode === 'sandbox'){
            define("USE_SANDBOX", 1);
        }

        define("LOG_FILE", "./ipn.log");
        // Read POST data
        // reading posted data directly from $_POST causes serialization
        // issues with array data in POST. Reading raw POST data from input stream instead.

        $raw_post_data = file_get_contents('php://input');

        //file_put_contents('C:\xampp\htdocs\ipn_test.txt',$raw_post_data);

        $raw_post_array = explode('&', $raw_post_data);
        $myPost = array();
        foreach ($raw_post_array as $keyval) {
            $keyval = explode ('=', $keyval);
            if (count($keyval) == 2)
                $myPost[$keyval[0]] = urldecode($keyval[1]);
        }

        //$this->customData = json_decode($myPost['custom']);
        //$customData = json_decode($myPost['custom'],true);
        $customData = self::getCustomData($myPost);
        $userId = $customData['user_id'];
        //$this->configureDatabase($userId,$myPost);

        // read the post from PayPal system and add 'cmd'
        $req = 'cmd=_notify-validate';
        if(function_exists('get_magic_quotes_gpc')) {
            $get_magic_quotes_exists = true;
        }
        else{
            $get_magic_quotes_exists = false;
        }


        foreach ($myPost as $key => $value) {
            if($get_magic_quotes_exists == true && get_magic_quotes_gpc() == 1) {
                $value = urlencode(stripslashes($value));
            } else {
                $value = urlencode($value);
            }
            $req .= "&$key=$value";
        }

        $myPost['customData'] = $customData;

        // Post IPN data back to PayPal to validate the IPN data is genuine
        // Without this step anyone can fake IPN data

        $paypal_url = $this->payNowButtonUrl;

        $res = $this->sendRequest($paypal_url,$req,$myPost['txn_id'],$customData);

        // Inspect IPN validation result and act accordingly
        // Split response headers and payload, a better way for strcmp
        $tokens = explode("\r\n\r\n", trim($res));
        $res = trim(end($tokens));

        /**/
        //@TODO remove this
        //$this->service->processPayment($myPost);

        /**/
        if (strcmp ($res, "VERIFIED") == 0) {
            $this->service->processPayment($myPost);
        } else if (strcmp ($res, "INVALID") == 0) {
            // log for manual investigation
            // Add business logic here which deals with invalid IPN messages

            self::log([
                'message' => "Invalid IPN: $req" . PHP_EOL,
                'level' => self::LOG_LEVEL_ERROR
            ], $myPost);
        }
        /**/
    }

    /**
     * @param $paypal_url
     * @param $req
     * @param $txnId
     * @param $customData
     * @return bool|mixed
     */
    private function sendRequest($paypal_url,$req){
        $debug = $this->debug;

        $ch = curl_init($paypal_url);
        if ($ch == FALSE) {
            return FALSE;
        }
        curl_setopt($ch, CURLOPT_HTTP_VERSION, CURL_HTTP_VERSION_1_1);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER,1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $req);

        /*if(ENVIRONMENT === 'production'){
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 1);
        }
        else{
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
        }*/

        /* @TODO fix issue wth CURLOPT_SSL_VERIFYPEER */
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);

        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 2);
        curl_setopt($ch, CURLOPT_FORBID_REUSE, 1);
        if($debug == true) {
            curl_setopt($ch, CURLOPT_HEADER, 1);
            curl_setopt($ch, CURLINFO_HEADER_OUT, 1);
        }

        // CONFIG: Optional proxy configuration
        //curl_setopt($ch, CURLOPT_PROXY, $proxy);
        //curl_setopt($ch, CURLOPT_HTTPPROXYTUNNEL, 1);

        // Set TCP timeout to 30 seconds
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 30);
        //curl_setopt($ch, CURLOPT_HTTPHEADER, array('Connection: Close'));

        curl_setopt($ch, CURLOPT_HTTPHEADER, array('Connection: Close', 'User-Agent: ' . $this->projectName));

        // CONFIG: Please download 'cacert.pem' from "http://curl.haxx.se/docs/caextract.html" and set the directory path
        // of the certificate as shown below. Ensure the file is readable by the webserver.
        // This is mandatory for some environments.
        //$cert = __DIR__ . "/cacert.pem";
        //curl_setopt($ch, CURLOPT_CAINFO, $cert);

        $res = curl_exec($ch);
        curl_close($ch);

        return $res;
    }
}