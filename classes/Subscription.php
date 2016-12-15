<?php
/**
 * Created by PhpStorm.
 * User: Antonshell
 * Date: 12.03.2015
 * Time: 11:16
 */

/**
 * Class Subscription
 */
class Subscription
{
    protected $instance = [
        'id' => null,
        'user_id' => null,
        'plan_id' => null,
        'subscription_id' => null,
        'permissions' => null,
        'created_date' => null,
        'updated_date' => null,
        'payment_date' => null,
        'items_count' => null,
        'status' => null
    ];

    const PERMISSIONS_ALL = 'all';
    const PERMISSIONS_USER = 'user';

    const TABLE_NAME = 'subscriptions';

    const STATUS_ACTIVE = 'active';
    const STATUS_CANCELED = 'canceled';
    const STATUS_CANCELING = 'canceling';
    const STATUS_EXPIRED = 'expired';

    const STATUS_UPDATING = 'updating';
    const STATUS_PROCESSING = 'processing';
    const STATUS_PREPROCESSING = 'preprocessing';

    const STATUS_NOT_EXIST = 'not_exist';
    const STATUS_TRIAL = 'trial';

    /**
     * @param null $record_id
     */
    public function __construct($record_id = null)
    {
        if($record_id != null)
        {
            $this->load($record_id);
        }
    }

    /**
     * @param $record_id
     * @throws Exception
     */
    public function load($record_id)
    {
        $db = new DatabaseService();
        $sql = 'SELECT * FROM '.self::TABLE_NAME.' WHERE id = ?';
        $params = [&$record_id];

        $data =  $db->execSQL($sql,$params,'i');
        if(count($data))
        {
            $this->instance = $data[0];
        }
    }

    /**
     * @param $subscrId
     * @throws Exception
     */
    public function loadBySubscriptionId($subscrId)
    {
        $db = new DatabaseService();
        $sql = 'SELECT * FROM '.self::TABLE_NAME.' WHERE subscription_id = ?';
        $params = [&$subscrId];

        $data =  $db->execSQL($sql,$params,'s');
        if(count($data))
        {
            $this->instance = $data[0];
        }
    }

    /**
     * @return mixed
     */
    public function save()
    {
        $db = new DatabaseService();
        $savedData = $this->instance;

        if($savedData['id'] == null)
        {
            unset($savedData['id']);
            $this->instance['id'] = $db->insertData(self::TABLE_NAME,$savedData);
        }
        else
        {
            $db->updateData(self::TABLE_NAME,$savedData,'id',$savedData['id']);
        }

        return $this->instance['id'];
    }

    /**
     * @param $property
     * @param $value
     */
    public function __set($property, $value)
    {
        if(array_key_exists($property,$this->instance))
        {
            $this->instance[$property] = $value;
        }
    }

    /**
     * @param $property
     * @return mixed
     */
    public function __get($property)
    {
        $result = $this->instance[$property];
        return $result;
    }

    /**
     * @param $userId
     * @param $serviceProvider
     * @return null
     * @throws Exception
     */
    public static function getActiveSubscriptions($userId,$serviceProvider){
        $db = new DatabaseService();

        $status = self::STATUS_ACTIVE;
        $sql = 'SELECT *,s.id as subId FROM '.self::TABLE_NAME.' s INNER JOIN '.SubscriptionPlan::TABLE_NAME.' sp ON sp.id = s.plan_id WHERE sp.service_provider = ? AND s.status = ? AND s.user_id = ?';

        $params = [&$serviceProvider,&$status,&$userId];
        $data =  $db->execSQL($sql,$params,'sss');

        if(count($data) > 1){
            throw new Exception('Logical error. Only one active subscription allowed');
        }

        return isset($data[0]) ? $data[0] : null;
    }

    /**
     * @param $userId
     * @param $serviceProvider
     * @return bool|mixed|mysqli_result
     * @throws Exception
     */
    public static function getSubscriptionsArchive($userId,$serviceProvider){
        $db = new DatabaseService();

        $sql = 'SELECT *,s.id as subId FROM '.self::TABLE_NAME.' s INNER JOIN '.SubscriptionPlan::TABLE_NAME.' sp ON sp.id = s.plan_id WHERE sp.service_provider = ? AND s.user_id = ?';

        $params = [&$serviceProvider,&$userId];
        $data =  $db->execSQL($sql,$params,'ss');

        return $data;
    }

    /**
     * @param string $customData
     * @return bool|mixed
     * @throws Exception
     */
    public function createFreeSubscription($userId,$planId,$itemsCount){
        $subscriptionPlan = new SubscriptionPlan();
        $subscriptionPlan->load($planId);
        $activeSubscriptions = Subscription::getActiveSubscriptions($userId,$subscriptionPlan->service_provider);

        if(count($activeSubscriptions) > 0){
            return false;
        }

        $subscription = new Subscription();
        $subscription->user_id = $userId;
        $subscription->plan_id = $planId;
        $subscription->subscription_id = '';
        $subscription->created_date = date("Y-m-d H:i:s");
        $subscription->updated_date = date('Y-m-d H:i:s');
        $subscription->payment_date = '';
        $subscription->items_count = $itemsCount;
        $subscription->status = Subscription::STATUS_ACTIVE;
        $subscriptionId = $subscription->save();

        return $subscriptionId;
    }

    /**
     * @param $userId
     * @param $serviceProvider
     * @return array|bool
     * @throws Exception
     */
    public function cancelSubscription($userId,$serviceProvider){
        $activeSubscription = Subscription::getActiveSubscriptions($userId,$serviceProvider);

        if(!$activeSubscription){
            PaypalSubscription::log([
                'message' => "Error. Can\'t cancel subscription. User has no active subscriptions",
                'level' => PaypalSubscription::LOG_LEVEL_ERROR,
                'user_id' => $userId
            ]);

            return false;
        }

        /* free subscription */
        if((int)$activeSubscription['price'] == 0){
            $recordId = $activeSubscription['subId'];

            $subscription = new Subscription();
            $subscription->load($recordId);
            $subscription->status = Subscription::STATUS_CANCELED;
            $subscription->save();

            $result = [
                'error' => 0,
                'message' => 'success. Subscription canceled.',
            ];

            return $result;
        }
        /**/
        else{
            if(!$activeSubscription['subscription_id']){
                PaypalSubscription::log([
                    'message' => "Error. Can\'t cancel subscription. Wrong subscription id",
                    'level' => PaypalSubscription::LOG_LEVEL_ERROR,
                    'user_id' => $userId
                ]);
                return false;
            }

            $config = Config::get();
            $paypalService = new PaypalSubscription($config);
            $lastTransaction = $paypalService->getLastActiveTransactionBySubscription($activeSubscription['subId']);
            $transactionId = $lastTransaction['txn_id'];
            $subscriptionId = $activeSubscription['subscription_id'];

            $refundAmount = $paypalService->getTransactionRefundAmount($lastTransaction);

            $cancelSubscriptionResult = $paypalService->changeSubscriptionStatus( $subscriptionId, 'Cancel' );
            $refundTransactionResult = $paypalService->refundTransaction($lastTransaction,$refundAmount);

            if($cancelSubscriptionResult['ACK'] == "Success" && $refundTransactionResult['ACK'] == "Success"){
                $subscription = new Subscription();
                $subscription->loadBySubscriptionId($subscriptionId);
                $subscription->status = Subscription::STATUS_CANCELING;

                $subscription->save();

                $result = [
                    'error' => 0,
                    'message' => 'success. Subscription canceling.',
                ];

                PaypalSubscription::log([
                    'txn_id' => $transactionId,
                    'subscr_id' => $subscriptionId,
                    'message' => "Subscription canceling",
                    'level' => PaypalSubscription::LOG_LEVEL_INFO,
                    'data' => '',
                    'user_id' => $userId
                ]);

                return $result;
            }
            else{
                PaypalSubscription::log([
                    'txn_id' => $transactionId,
                    'subscr_id' => $subscriptionId,
                    'message' => "Error. Can\'t cancel subscription. Rejected by PayPal",
                    'level' => PaypalSubscription::LOG_LEVEL_ERROR,
                    'data' => json_encode([
                        'cancelSubscriptionResult' => $cancelSubscriptionResult,
                        'refundTransactionResult' => $refundTransactionResult,
                    ]),
                    'user_id' => $userId
                ]);

                return false;
            }
        }
    }
}