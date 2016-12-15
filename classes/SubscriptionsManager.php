<?php

class SubscriptionsManager
{
    protected $redirectUri = '/settings.php?id=subscription';

    public function checkSubscription(){
        $gsSubscriptions = Subscription::getActiveSubscriptions();

        $redirect = false;

        if(count($gsSubscriptions) == 0 && $_SERVER['REQUEST_METHOD'] == 'GET'){
            $redirect = true;

            if(strpos($_SERVER['REQUEST_URI'],$this->redirectUri) !== false){
                $redirect = false;
            }

            if(strpos($_SERVER['REQUEST_URI'],'logout') !== false || strpos($_SERVER['REQUEST_URI'],'settings.php?id=users')){
                $redirect = false;
            }

            /*$inactiveSubscriptions = Subscription::getGetScorecardSubscriptions();

            foreach($inactiveSubscriptions as $subscription){
                $updateDate = $subscription['updated_date'];
                $currentDate = date('Y-m-d');

                $updateDate = new DateTime($updateDate);
                $currentDate = new DateTime($currentDate);

                $dDiff = $updateDate->diff($currentDate);
                $days =  $dDiff->days;

                if($days === 0){
                    $redirect = false;
                }
            }*/
        }

        return !$redirect;
    }

    public function redirect(){
        header('Location: ' . trim($this->redirectUri,'/'));
        die();
    }

    public function getUserLicencesCount(){
        $gsSubscriptions = Subscription::getActiveSubscriptions();

        $licences = 0;

        if(count($gsSubscriptions) > 0){
            $licences = $gsSubscriptions[0]['items_count'];
        }

        return $licences;
    }

    public function validateUsersCount(){
        $sessionManager = new SessionManager();
        $userId = $_SESSION['user_id'];
        $userInfo = $sessionManager->getUserInfo($userId);
        $existingUsers = SubscriptionsIPN::getExistUsers($userInfo['db_identifier']);
        $customData = json_decode($_POST['customData'],true);
        return !($customData['items_count'] < count($existingUsers));
    }

    public function createFreeSubscription($customData = ''){
        $userId = $_SESSION['user_id'];

        /* clear subscription plan restrictions */
        unset($_SESSION['subscriptionPlanOptions']);
        /**/

        if(!$customData){
            $customData = json_decode($_POST['customData'],true);
        }


        $gsSubscriptions = Subscription::getActiveSubscriptions();

        if($customData['items_count'] != GetscorecardSubscriptionPlanFree::MAX_USERS_COUNT){
            return false;
        }

        if(count($gsSubscriptions) > 0){
            return false;
        }

        if($_SESSION['isDummy']){
            $trial = new trial();
            $trial->trialDeactivator();
            $trial->dropDummyDatabase();
            $sessionManager = new SessionManager();
            $sessionManager->reLogin();
        }

        $dbName = "gs_db_".$_SESSION['db_identifier'];

        $subscription = new Subscription();
        $subscription->user_id = $userId;
        $subscription->plan_id = $customData['plan_id'];
        $subscription->subscription_id = '';
        $subscription->created_date = date("Y-m-d H:i:s");
        $subscription->updated_date = date('Y-m-d H:i:s');
        $subscription->payment_date = '';
        $subscription->items_count = $customData['items_count'];
        $subscription->status = Subscription::STATUS_ACTIVE;
        $subscriptionId = $subscription->save($dbName);

        return $subscriptionId;
    }

    public function cancelSubscription($userId,$validateUsersCount = true){
        $result = [
            'error' => 1,
            'message' => "Error canceling subscription. Please contact support about this issue",
        ];

        $ipn = new SubscriptionsIPN();
        $ipn->configureDatabase($userId);

        /* clear subscription plan restrictions */
        unset($_SESSION['subscriptionPlanOptions']);
        /**/

        $gsSubscriptions = Subscription::getActiveSubscriptions();
        if(count($gsSubscriptions) != 1){
            PayPalIPN::log([
                'message' => "Error. Can\'t cancel subscription. User has no active subscriptions",
                'level' => PayPalIPN::LOG_LEVEL_ERROR,
                'user_id' => $userId
            ]);
        }

        $subscription = $gsSubscriptions[0];
        $subscriptionPlan = new SubscriptionPlan();
        $subscriptionPlan->load($subscription['plan_id']);

        if(!$this->validateUsersCount() && $validateUsersCount){
            $result = [
                'error' => 2,
                'message' => 'Error, wrong users count'
            ];
        }

        /* free subscription */
        if($subscriptionPlan->service_provider == 'getscorecard' && $subscriptionPlan->service_name == 'free'){
            $recordId = $subscription['subId'];

            $subscription = new Subscription();
            $subscription->load($recordId);

            $subscription->status = Subscription::STATUS_CANCELED;
            $subscription->save();

            $result = [
                'error' => 0,
                'message' => 'success. Subscription canceling.',
            ];
        }
        /**/
        else{
            if(!$subscription['subscription_id']){
                PayPalIPN::log([
                    'message' => "Error. Can\'t cancel subscription. Wrong subscription id",
                    'level' => PayPalIPN::LOG_LEVEL_ERROR,
                    'user_id' => $userId
                ]);
            }

            $lastTransaction = $ipn->getLastActiveTransactionBySubscription($subscription['subId']);
            $transactionId = $lastTransaction['txn_id'];
            $subscriptionId = $subscription['subscription_id'];

            $refundAmount = $ipn->getTransactionRefundAmount($lastTransaction);

            $cancelSubscriptionResult = $ipn->changeSubscriptionStatus( $subscriptionId, 'Cancel' );
            $refundTransactionResult = $ipn->refundTransaction($lastTransaction,$refundAmount);

            if($cancelSubscriptionResult['ACK'] == "Success" && $refundTransactionResult['ACK'] == "Success"){
                $subscription = new Subscription();
                $subscription->loadBySubscriptionId($subscriptionId);
                $subscription->status = Subscription::STATUS_CANCELING;

                $subscription->save();

                $result = [
                    'error' => 0,
                    'message' => 'success. Subscription canceling.',
                ];

                PayPalIPN::log([
                    'txn_id' => $transactionId,
                    'subscr_id' => $subscriptionId,
                    'message' => "Subscription canceling",
                    'level' => PayPalIPN::LOG_LEVEL_INFO,
                    'data' => '',
                    'user_id' => $userId
                ]);
            }
            else{
                PayPalIPN::log([
                    'txn_id' => $transactionId,
                    'subscr_id' => $subscriptionId,
                    'message' => "Error. Can\'t cancel subscription. Rejected by PayPal",
                    'level' => PayPalIPN::LOG_LEVEL_ERROR,
                    'data' => json_encode([
                        'cancelSubscriptionResult' => $cancelSubscriptionResult,
                        'refundTransactionResult' => $refundTransactionResult,
                    ]),
                    'user_id' => $userId
                ]);
            }
        }

        return $result;
    }

    /*
     * get subscription plan options for current subscription
     */
    public function getOptions(){
        $gsSubscriptions = Subscription::getActiveSubscriptions();

        $sessionManager = new SessionManager();
        $userInfo = $sessionManager->getUserInfo($_SESSION['user_id']);

        $subscriptionsTempStatus = Subscription::getSubscriptionTemporaryStatus();

        $result = [];

        if(count($gsSubscriptions)){
            $planId = $gsSubscriptions[0]['plan_id'];
            $subscriptionPlan = new SubscriptionPlan();
            $subscriptionPlan->load($planId);

            $subscriptionPlanOptions = $subscriptionPlan->getSubscriptionPlanOptions();

            $result['subscription_status'] = Subscription::STATUS_ACTIVE;
            $result['options'] = $subscriptionPlanOptions;
        }
        elseif($subscriptionsTempStatus == Subscription::STATUS_PROCESSING){
            $result['subscription_status'] = Subscription::STATUS_PROCESSING;
            $result['options'] = [];
        }
        elseif($subscriptionsTempStatus == Subscription::STATUS_UPDATING){
            $gsSubscriptions = Subscription::getGetScorecardSubscriptions();

            if(isset($gsSubscriptions[0])){
                $planId = $gsSubscriptions[0]['plan_id'];
                $subscriptionPlan = new SubscriptionPlan();
                $subscriptionPlan->load($planId);

                $subscriptionPlanOptions = $subscriptionPlan->getSubscriptionPlanOptions();

                $result['subscription_status'] = Subscription::STATUS_UPDATING;
                $result['options'] = $subscriptionPlanOptions;
            }
            else{
                $result['subscription_status'] = Subscription::STATUS_NOT_EXIST;
                $result['options'] = [];
            }
        }
        elseif($userInfo['dummy_data_live']){
            $result['subscription_status'] = Subscription::STATUS_TRIAL;
            $result['options'] = [];
        }
        else{
            $result['subscription_status'] = Subscription::STATUS_NOT_EXIST;
            $result['options'] = [];
        }

        return $result;
    }

    public function checkModuleRestrictions($action){
        $restrictionError = -1;
        $tf = new DatabaseService();

        if(isset($_SESSION['subscriptionPlanOptions'])){
            $subscriptionPlanOptions = $_SESSION['subscriptionPlanOptions'];
        }
        else{
            $subscriptionPlanOptions = $this->getOptions();
            $_SESSION['subscriptionPlanOptions'] = $subscriptionPlanOptions;
        }

        $records = $tf->getTableData($action,'*','');
        if(isset($subscriptionPlanOptions['options'][$action]) && $subscriptionPlanOptions['options'][$action] !== '-1' && count($records) >= $subscriptionPlanOptions['options'][$action]){
            echo $restrictionError;
            die();
        }
    }

    public function checkFeatureRestrictions($feature){
        /* adapter for security template restrictions */
        $features2options = [
            'settings:teams'                => 'teams',
            'settings:territories'          => 'territories',

            'settings:saleslifecycle'       => 'salesLifecycle',
            'settings:targets'              => 'targetSettings',
            'settings:commissions'          => 'commissionSettings',

            'settings:healthscore'          => 'healthScore',
            'settings:automation'           => 'taskAutomation',

            'settings:accessmanagement'     => 'accessManager',

            'settings:integration'          => '3rdPartyGoogle'
        ];

        if(isset($_SESSION['subscriptionPlanOptions']) && in_array($_SESSION['subscriptionPlanOptions']['subscription_status'],[Subscription::STATUS_ACTIVE,Subscription::STATUS_CANCELED])){
            $subscriptionPlanOptions = $_SESSION['subscriptionPlanOptions'];
        }
        else{
            $subscriptionPlanOptions = $this->getOptions();
            $_SESSION['subscriptionPlanOptions'] = $subscriptionPlanOptions;
        }

        /* no restrictions for trial account*/
        if($subscriptionPlanOptions['subscription_status'] == Subscription::STATUS_TRIAL){
            return true;
        }
        /**/

        if(isset($subscriptionPlanOptions['options'][$feature])){
            $result = ($subscriptionPlanOptions['options'][$feature] == -1);
        }
        else{
            if(isset($features2options[$feature])){
                //check restrictions for security template adapter
                $option = $features2options[$feature];

                if(isset($subscriptionPlanOptions['options'][$option])){
                    $result = ($subscriptionPlanOptions['options'][$option] == -1);
                }
                else{
                    $result = true;
                }
            }
            else{
                $result = true;
            }
        }

        return $result;
    }
}