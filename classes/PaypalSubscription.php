<?php

/**
 * Class PaypalSubscription
 */
class PaypalSubscription extends PaypalService{

    const TXN_TYPE_SUBSCR_CANCEL = 'subscr_cancel';
    const TXN_TYPE_SUBSCR_PAYMENT = 'subscr_payment';
    const TXN_TYPE_SUBSCR_REFUND = 'subscr_refund';

    const ERROR_CUSTOM_DATA_MISMATCH = 'Error. Custom data subscription plan mismatch. service_provider | service_name don\'t match paln_id';
    const ERROR_SUBSCRIPTION_NOT_EXISTS = 'Error. Subscription doesn\'t exist.';
    const ERROR_WRONG_PAYMENT_AMOUNT = 'Error. Wrong payment amount';
    const ERROR_WRONG_RECEIVER_EMAIL = 'Error. Wrong receiver email';
    const ERROR_WRONG_CURRENCY = 'Error. Wrong currency';
    const ERROR_WRONG_PAYMENT_STATUS = 'Error. wrong payment status';
    const ERROR_LAST_TRANSACTION_NOT_EXIST = 'Error. Last transaction doesn\'t exist';
    const ERROR_WRONG_REFUND_AMOUNT = 'Error. Wrong Refund Amount';
    const ERROR_VALIDATION_FAILED = 'Error. Validation failed';
    const ERROR_ACTIVE_SUBSCRIPTION_DUPLICATE = 'Error. Active subscription duplicate';

    const INFO_SUBSCRIPTION_PAYMENT = 'Subscription payment';
    const INFO_TRANSACTION_CREATED = 'Transaction created';
    const INFO_SUBSCRIPTION_CANCELED = 'Subscription canceled';
    const INFO_SUBSCRIPTION_EXPIRED = 'Subscription expired';
    const INFO_SUBSCRIPTION_SIGNUP = 'Subscription signup';
    const INFO_SUBSCRIPTION_MODIFIED = 'Subscription modified';
    const INFO_PAYMENT_REFUND = 'Payment Refund';
    const INFO_SUBSCRIPTION_CREATED = 'Subscription created';

    const WARNING_DUPLICATE_TRANSACTION_IPN = 'Warning. Duplicate ipn Request';
    const VALIDATION_FAILED = 'Validation vailed';
    const ERROR_TRIAL_ACCOUNT = 'Error. Try to create subscription for trial account';
    const ERROR_USERS_COUNT_INCORRECT = 'Error. Users count incorrect';

    protected $apiCredentials = null;

    /**
     * @param $config
     */
    public function __construct($config){
        $this->apiCredentials = [
            'username' => $config['paypal']['username'],
            'password' => $config['paypal']['password'],
            'signature' => $config['paypal']['signature']
        ];

        parent::__construct();
    }

    /**
     * @param $post
     * @return SubscriptionPlan
     * @throws Exception
     */
    public function getSubscriptionPlan($post){
        $customData = $this->getCustomData($post);
        $serviceProvider = $customData['service_provider'];
        $serviceName = $customData['service_name'];

        $subscriptionPlan = new SubscriptionPlan();
        $subscriptionPlan->loadByServiceName($serviceProvider,$serviceName);

        return $subscriptionPlan;
    }

    /**
     * @param $subscriptionPlan
     * @param $myPost
     * @return bool
     */
    protected function validateSubscription($subscriptionPlan,$myPost){
        $userId = $myPost['customData']['user_id'];
        $userService = new UserService();
        $userInfo = $userService->getUserData($userId);

        $customData = $this->getCustomData($myPost);

        //@TODO enable this condition

        /*if($customData['plan_id'] != $subscriptionPlan->id){
            //if plan_id changed
            self::log([
                'message' => self::ERROR_CUSTOM_DATA_MISMATCH,
                'level' => self::LOG_LEVEL_ERROR
            ], $myPost);

            return false;
        }*/

        if($myPost['txn_type'] == self::TXN_TYPE_SUBSCR_CANCEL){
            //validation for subscr_cancel
            $subscription = new Subscription();
            $subscription->loadBySubscriptionId($myPost['subscr_id']);

            if(!$subscription->id){

                self::log([
                    'message' => self::ERROR_SUBSCRIPTION_NOT_EXISTS,
                    'level' => self::LOG_LEVEL_ERROR
                ], $myPost);

                return false;
            }
        }
        elseif($myPost['txn_type'] == self::TXN_TYPE_SUBSCR_PAYMENT){
            /*if($subscriptionPlan->price * $myPost['customData']['items_count'] != $myPost['mc_gross']){

                self::log([
                    'message' => self::ERROR_WRONG_PAYMENT_AMOUNT,
                    'level' => self::LOG_LEVEL_ERROR
                ], $myPost);

                return false;
            }*/

            //@TODO check if subscription plan exist, check if price correct
            if($myPost['mc_gross'] == 0){
                self::log([
                    'message' => self::ERROR_WRONG_PAYMENT_AMOUNT,
                    'level' => self::LOG_LEVEL_ERROR
                ], $myPost);

                return false;
            }

            if($myPost['receiver_email'] != $this->receiverEmail){
                self::log([
                    'message' => self::ERROR_WRONG_RECEIVER_EMAIL,
                    'level' => self::LOG_LEVEL_ERROR
                ], $myPost);

                return false;
            }

            if($myPost['mc_currency'] != 'USD'){
                self::log([
                    'message' => self::ERROR_WRONG_CURRENCY,
                    'level' => self::LOG_LEVEL_ERROR
                ], $myPost);

                return false;
            }

            if($myPost['payment_status'] != 'Completed'){
                self::log([
                    'message' => self::ERROR_WRONG_PAYMENT_STATUS,
                    'level' => self::LOG_LEVEL_ERROR
                ], $myPost);

                return false;
            }
        }
        elseif($myPost['reason_code'] == 'refund' && $myPost['payment_status'] == 'Refunded'){
            $subscription = new Subscription();
            $subscription->loadBySubscriptionId($myPost['subscr_id']);

            $lastTransaction = $this->getLastActiveTransactionBySubscription($subscription->id);
            //$lastTransaction = $this->getLastActiveTransactionBySubscription($myPost['subscr_id']);

            if(!$lastTransaction){

                self::log([
                    'message' => self::ERROR_LAST_TRANSACTION_NOT_EXIST,
                    'level' => self::LOG_LEVEL_ERROR
                ], $myPost);

                return false;
            }

            if(abs($myPost['mc_gross']) > $lastTransaction['mc_gross']){
                self::log([
                    'message' => self::ERROR_WRONG_REFUND_AMOUNT,
                    'level' => self::LOG_LEVEL_ERROR
                ], $myPost);

                return false;
            }
        }

        return true;
    }

    /**
     * @param $myPost
     * @throws Exception
     */
    public function processPayment($myPost){
        $customData = $this->getCustomData($myPost);
        $userId = $customData['user_id'];

        $userService = new UserService();
        $userInfo = $userService->getUserData($userId);

        $userEmail = $userInfo['email'];

        $subscriptionPlan = $this->getSubscriptionPlan($myPost);

        if($this->validateSubscription($subscriptionPlan,$myPost)){
            $subscription = new Subscription();
            $subscription->loadBySubscriptionId($myPost['subscr_id']);

            $transactionService = new PaypalTransaction();
            $transaction = $transactionService->getTransactionById($myPost['txn_id']);

            if($subscription->id){
                //subscription exists
                if($myPost['txn_type'] == 'subscr_payment'){

                    if(!$transaction){
                        $subscription->status = Subscription::STATUS_ACTIVE;
                        $subscription->payment_date = $myPost['payment_date'];
                        $subscription->updated_date = date('Y-m-d H:i:s');
                        $subscription->save();

                        self::log([
                            'message' => self::INFO_SUBSCRIPTION_PAYMENT,
                            'data' => '',
                            'level' => self::LOG_LEVEL_INFO
                        ], $myPost);

                        $myPost['relation_id'] = $subscription->id;

                        //@TODO remake it
                        $transactionService = new PaypalTransaction();
                        $myPost['relation_type'] = PaypalTransaction::TRANSACTION_RELATION_SUBSCRIPTION;
                        $transactionService->createTransaction($myPost);

                        self::log([
                            'message' => self::INFO_TRANSACTION_CREATED,
                            'data' => '',
                            'level' => self::LOG_LEVEL_INFO
                        ], $myPost);
                    }
                    else{
                        self::log([
                            'message' => self::WARNING_DUPLICATE_TRANSACTION_IPN,
                            'data' => '',
                            'level' => self::LOG_LEVEL_WARNING
                        ], $myPost);
                    }
                }

                if($myPost['txn_type'] == 'subscr_cancel'){
                    $subscription->status = Subscription::STATUS_CANCELED;
                    $subscription->updated_date = date('Y-m-d H:i:s');
                    $subscription->save();

                    self::log([
                        'message' => self::INFO_SUBSCRIPTION_CANCELED,
                        'data' => '',
                        'level' => self::LOG_LEVEL_INFO
                    ], $myPost);
                }

                if($myPost['txn_type'] == 'subscr_eot'){
                    $subscription->status = Subscription::STATUS_CANCELED;
                    $subscription->updated_date = date('Y-m-d H:i:s');
                    $subscription->save();

                    self::log([
                        'message' => self::INFO_SUBSCRIPTION_EXPIRED,
                        'level' => self::LOG_LEVEL_WARNING
                    ], $myPost);
                }

                if($myPost['txn_type'] == 'subscr_signup'){
                    self::log([
                        'message' => self::INFO_SUBSCRIPTION_SIGNUP,
                        'data' => '',
                        'level' => self::LOG_LEVEL_INFO
                    ], $myPost);
                }

                if($myPost['txn_type'] == 'subscr_modify'){
                    $subscription->status = Subscription::STATUS_CANCELED;
                    $subscription->updated_date = date('Y-m-d H:i:s');
                    $subscription->save();

                    self::log([
                        'message' => self::INFO_SUBSCRIPTION_MODIFIED,
                        'level' => self::LOG_LEVEL_WARNING
                    ], $myPost);
                }

                if($myPost['payment_status'] == 'Refunded' && $myPost['reason_code'] == 'refund'){
                    //refund transaction

                    $transactionService = new PaypalTransaction();
                    $transactionService->updateTransaction($myPost['parent_txn_id'],['payment_status' => 'Refunded']);

                    $myPost['txn_type'] = self::TXN_TYPE_SUBSCR_REFUND;
                    $myPost['relation_id'] = $subscription->id;

                    //@TODO refactor this
                    $myPost['relation_type'] = PaypalTransaction::TRANSACTION_RELATION_SUBSCRIPTION;

                    $transactionService = new PaypalTransaction();
                    $transactionService->createTransaction($myPost);

                    self::log([
                        'message' => self::INFO_PAYMENT_REFUND,
                        'level' => self::LOG_LEVEL_INFO
                    ], $myPost);
                }
            }
            else{
                if($myPost['txn_type'] == 'subscr_payment'){

                    //@TODO remake it
                    $serviceProvider = $subscriptionPlan->service_provider;
                    $activeSubscriptions = Subscription::getActiveSubscriptions($userId,$serviceProvider);

                    /* check duplicate subscriptions*/
                    if(count($activeSubscriptions) > 0){
                        self::log([
                            'message' => self::ERROR_ACTIVE_SUBSCRIPTION_DUPLICATE,
                            'level' => self::LOG_LEVEL_ERROR
                        ], $myPost);
                    }
                    elseif(!$transaction){
                        $subscription = new Subscription();
                        $subscription->user_id = $userId;
                        $subscription->plan_id = $subscriptionPlan->id;
                        $subscription->subscription_id = $myPost['subscr_id'];
                        $subscription->created_date = date("Y-m-d H:i:s");
                        $subscription->updated_date = date('Y-m-d H:i:s');
                        $subscription->payment_date = $myPost['payment_date'];
                        $subscription->items_count = $customData['items_count'];
                        $subscription->status = Subscription::STATUS_ACTIVE;
                        $subscriptionId = $subscription->save();

                        self::log([
                            'message' => self::INFO_SUBSCRIPTION_CREATED,
                            'data' => '',
                            'level' => self::LOG_LEVEL_INFO
                        ], $myPost);

                        $myPost['relation_id'] = $subscriptionId;

                        //@TODO refactor this
                        $myPost['relation_type'] = PaypalTransaction::TRANSACTION_RELATION_SUBSCRIPTION;

                        $transactionService = new PaypalTransaction();
                        $transactionService->createTransaction($myPost);

                        self::log([
                            'message' => self::INFO_TRANSACTION_CREATED,
                            'data' => '',
                            'level' => self::LOG_LEVEL_INFO
                        ], $myPost);
                    }
                    else{
                        self::log([
                            'message' => self::WARNING_DUPLICATE_TRANSACTION_IPN,
                            'data' => '',
                            'level' => self::LOG_LEVEL_WARNING
                        ], $myPost);
                    }
                }

                if($myPost['txn_type'] == 'subscr_signup'){
                    self::log([
                        'message' => self::INFO_SUBSCRIPTION_SIGNUP,
                        'data' => '',
                        'level' => self::LOG_LEVEL_INFO
                    ], $myPost);
                }

                if($myPost['txn_type'] == 'subscr_modify'){
                    self::log([
                        'message' => self::INFO_SUBSCRIPTION_MODIFIED,
                        'level' => self::LOG_LEVEL_WARNING
                    ], $myPost);
                }
            }
        }
        else{
            self::log([
                'message' => self::VALIDATION_FAILED,
                'level' => self::LOG_LEVEL_ERROR
            ], $myPost);

            echo self::VALIDATION_FAILED;
            die();
        }
    }

    /**
     * Performs an Express Checkout NVP API operation as passed in $action.
     *
     * Although the PayPal Standard API provides no facility for cancelling a subscription, the PayPal
     * Express Checkout  NVP API can be used.
     *
     * see for more details
     * http://thereforei.am/2012/07/03/cancelling-subscriptions-created-with-paypal-standard-via-the-express-checkout-api/
     */
    public function changeSubscriptionStatus($profile_id, $action){
        $api_request = 'USER=' . urlencode( $this->apiCredentials['username'] )
            .  '&PWD=' . urlencode( $this->apiCredentials['password'] )
            .  '&SIGNATURE=' . urlencode( $this->apiCredentials['signature'] )
            .  '&VERSION=76.0'
            .  '&METHOD=ManageRecurringPaymentsProfileStatus'
            .  '&PROFILEID=' . urlencode( $profile_id )
            .  '&ACTION=' . urlencode( $action )
            .  '&NOTE=' . urlencode( 'Profile cancelled at store' );

        $ch = curl_init();

        if($this->environmentMode === 'sandbox'){
            curl_setopt( $ch, CURLOPT_URL, 'https://api-3t.sandbox.paypal.com/nvp' ); // For live transactions, change to 'https://api-3t.paypal.com/nvp'
        }
        else{
            curl_setopt( $ch, CURLOPT_URL, 'https://api-3t.paypal.com/nvp' ); // For live transactions, change to 'https://api-3t.paypal.com/nvp'
        }

        curl_setopt( $ch, CURLOPT_VERBOSE, 1 );

        // Uncomment these to turn off server and peer verification

        if(ENVIRONMENT === 'production'){
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 1);
        }
        else{
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
        }

        //curl_setopt( $ch, CURLOPT_SSL_VERIFYPEER, FALSE );
        // curl_setopt( $ch, CURLOPT_SSL_VERIFYHOST, FALSE );

        curl_setopt( $ch, CURLOPT_RETURNTRANSFER, 1 );
        curl_setopt( $ch, CURLOPT_POST, 1 );

        // Set the API parameters for this transaction
        curl_setopt( $ch, CURLOPT_POSTFIELDS, $api_request );

        // Request response from PayPal
        $response = curl_exec( $ch );

        // If no response was received from PayPal there is no point parsing the response
        if( ! $response ){
            self::log([
                /*'txn_id' => $txnId,*/
                'message' => "Can't connect to PayPal to validate IPN message: " . curl_error($ch) . PHP_EOL,
                'level' => self::LOG_LEVEL_ERROR,
                /*'user_id' => (string)$this->customData->user_id*/
            ]);

            return false;
            //die( 'Calling PayPal to change_subscription_status failed: ' . curl_error( $ch ) . '(' . curl_errno( $ch ) . ')' );
        }


        curl_close( $ch );

        // An associative array is more usable than a parameter string
        parse_str( $response, $parsed_response );

        return $parsed_response;
    }

    /**
     * @param $transaction
     * @param null $amount
     * @return bool
     */
    public function refundTransaction($transaction,$amount = null){

        $transaction_id = $transaction['txn_id'];

        $refundType = 'Full';

        if($amount){
            $amount = round($amount, 2, PHP_ROUND_HALF_DOWN);
            $amount = str_replace(',','.',$amount);
            $refundType = 'Partial';
        }

        $api_request = 'USER=' . urlencode( $this->apiCredentials['username'] )
            .  '&PWD=' . urlencode( $this->apiCredentials['password'] )
            .  '&SIGNATURE=' . urlencode( $this->apiCredentials['signature'] )
            .  '&VERSION=119'
            .  '&METHOD=RefundTransaction'
            .  '&TRANSACTIONID=' . urlencode( $transaction_id )
            .  '&REFUNDTYPE=' . urlencode( $refundType )
            .  '&CURRENCYCODE=' . urlencode( 'USD' );

        if($amount){
            $api_request .= '&AMT=' . urlencode( $amount );
        }


        $ch = curl_init();

        if($this->environmentMode === 'sandbox'){
            curl_setopt( $ch, CURLOPT_URL, 'https://api-3t.sandbox.paypal.com/nvp' ); // For live transactions, change to 'https://api-3t.paypal.com/nvp'
        }
        else{
            curl_setopt( $ch, CURLOPT_URL, 'https://api-3t.paypal.com/nvp' ); // For live transactions, change to 'https://api-3t.paypal.com/nvp'
        }

        curl_setopt( $ch, CURLOPT_VERBOSE, 1 );

        // Uncomment these to turn off server and peer verification

        if(ENVIRONMENT === 'production'){
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 1);
        }
        else{
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
        }

        //curl_setopt( $ch, CURLOPT_SSL_VERIFYPEER, FALSE );
        // curl_setopt( $ch, CURLOPT_SSL_VERIFYHOST, FALSE );
        curl_setopt( $ch, CURLOPT_RETURNTRANSFER, 1 );
        curl_setopt( $ch, CURLOPT_POST, 1 );

        // Set the API parameters for this transaction
        curl_setopt( $ch, CURLOPT_POSTFIELDS, $api_request );

        // Request response from PayPal
        $response = curl_exec( $ch );

        // If no response was received from PayPal there is no point parsing the response
        if( ! $response ){
            self::log([
                /*'txn_id' => $txnId,*/
                'message' => "Can't connect to PayPal to validate IPN message: " . curl_error($ch) . PHP_EOL,
                'level' => self::LOG_LEVEL_ERROR,
                /*'user_id' => (string)$this->customData->user_id*/
            ]);

            //die( 'Calling PayPal to change_subscription_status failed: ' . curl_error( $ch ) . '(' . curl_errno( $ch ) . ')' );
            return false;
        }


        curl_close( $ch );

        // An associative array is more usable than a parameter string
        parse_str( $response, $parsed_response );

        return $parsed_response;
    }

    /**
     * @param $transaction
     * @return float|mixed
     */
    public static function getTransactionRefundAmount($transaction){
        $paymentDate = date('Y-m-d',strtotime($transaction['payment_date']));
        $currentDate = date('Y-m-d');
        //$currentDate = date('2015-05-07');

        $paymentDate = new DateTime($paymentDate);
        $currentDate = new DateTime($currentDate);

        $dDiff = $paymentDate->diff($currentDate);
        $days =  $dDiff->days;
        $daysInMonth = cal_days_in_month(CAL_GREGORIAN,$currentDate->format('m'),$currentDate->format('Y'));

        $amount = $transaction['mc_gross'] - $transaction['mc_gross'] * $days / $daysInMonth;
        $amount = round($amount, 2, PHP_ROUND_HALF_DOWN);
        $amount = str_replace(',','.',$amount);

        return $amount;
    }

    /**
     * @param $id
     * @return mixed
     * @throws Exception
     */
    public function getLastActiveTransactionBySubscription($id){
        //@TODO refactor it
        $db = new DatabaseService();
        $relationType = PaypalTransaction::TRANSACTION_RELATION_SUBSCRIPTION;

        $paymentStatus = 'Completed';
        $sql = 'SELECT * FROM '.PaypalTransaction::TRANSACTIONS_TABLE_NAME.' WHERE relation_id = ? AND relation_type = ? AND payment_status = ? ORDER BY created_date';
        $params = [&$id,&$relationType,&$paymentStatus];
        $data =  $db->execSQL($sql,$params,'sss');

        return $data[0];
    }
}