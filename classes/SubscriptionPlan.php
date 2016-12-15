<?php
/**
 * Created by PhpStorm.
 * User: Antonshell
 * Date: 12.03.2015
 * Time: 16:24
 */

/**
 * Class SubscriptionPlan
 */
class SubscriptionPlan
{
    protected $instance = [
        'id' => null,
        'service_provider' => null,
        'service_name' => null,
        'price' => null,
        'price_type' => null,
        'period' => null
    ];

    const TABLE_NAME_OPTIONS = 'subscription_plan_options';
    const TABLE_NAME = 'subscription_plans';

    /*
     * PRICE_TYPE_USER - price for each user. for example 25$/user/month
     *
     * PRICE_TYPE_ACCOUNT - price for all account.
     * for example some premium functional. you pay every month and it works for all users in your account
     */
    const PRICE_TYPE_USER = 'user';
    const PRICE_TYPE_ACCOUNT = 'account';

    /*
     * subscription periods
     */
    const PERIOD_MONTH = 'month';
    const PERIOD_YEAR = 'year';
    //const PERIOD_DAY = 'day';

    /**
     * @param $record_id
     * @throws Exception
     */
    public function load($record_id){
        $db = new DatabaseService();
        $sql = 'SELECT * FROM ' . self::TABLE_NAME . ' WHERE id = ?';
        $params = [&$record_id];

        $data = $db->execSQL($sql, $params, 'i');
        if (count($data)) {
            $this->instance = $data[0];
        }
    }

    /**
     * @param $service_provider
     * @param $service_name
     * @throws Exception
     */
    public function loadByServiceName($service_provider, $service_name){
        $db = new DatabaseService();
        $sql = 'SELECT * FROM ' . self::TABLE_NAME . ' WHERE service_provider = ? AND service_name = ?';
        $params = [&$service_provider, &$service_name];

        $data = $db->execSQL($sql, $params, 'ss');
        if (count($data)) {
            $this->instance = $data[0];
        } else {
            throw new Exception("Error. Service doesn't exist");
        }
    }

    /**
     * @param $property
     * @param $value
     */
    public function __set($property, $value){
        if (array_key_exists($property, $this->instance)) {
            $this->instance[$property] = $value;
        }
    }

    /**
     * @param $property
     * @return mixed
     */
    public function __get($property){
        $result = $this->instance[$property];
        return $result;
    }

    /**
     * @param $service_provider
     * @return bool|mixed|mysqli_result
     * @throws Exception
     */
    public static function getSubscriptionPlans($service_provider){
        $db = new DatabaseService();
        $sql = 'SELECT * FROM ' . self::TABLE_NAME . ' WHERE service_provider = ?';
        $params = [&$service_provider];
        $data = $db->execSQL($sql, $params, 's');
        return $data;
    }

    /**
     * @return array
     * @throws Exception
     */
    public function getSubscriptionPlanOptions(){
        $planId = $this->id;
        $db = new DatabaseService();
        $sql = 'SELECT * FROM ' . self::TABLE_NAME_OPTIONS . ' WHERE plan_id = ? ';
        $params = [&$planId];
        $data = $db->execSQL($sql, $params, 'i');
        $res = [];

        foreach($data as $option){
            $res[$option['name']] = $option['value'];
        }

        return $res;
    }
}