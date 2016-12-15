<?php
/**
 * Created by PhpStorm.
 * User: Antonshell
 * Date: 10.08.2015
 * Time: 20:57
 */

/**
 * Class PaypalService
 */
class PaypalService{
    const LOG_TABLE_NAME = 'paypal_log';

    const LOG_LEVEL_INFO = 'INFO';
    const LOG_LEVEL_ERROR = 'ERROR';
    const LOG_LEVEL_WARNING = 'WARNING';

    const ENVIRONMENT_MODE_LIVE = 'live';
    const ENVIRONMENT_MODE_SANDBOX = 'sandbox';

    const PAY_BUTTON_URL_LIVE = 'https://www.paypal.com/cgi-bin/websc';
    const PAY_BUTTON_URL_SANDBOX = 'https://www.sandbox.paypal.com/cgi-bin/websc';

    public $receiverEmail;
    public $environmentMode;
    public $payNowButtonUrl;

    protected $projectName;

    public function __construct(){
        $config = Config::get();
        $this->projectName = $config['paypal']['project_name'];

        $this->receiverEmail = $config['paypal']['receiver_email'];
        $this->environmentMode = $config['paypal']['environment_mode'];

        if($this->environmentMode == self::ENVIRONMENT_MODE_LIVE){
            $this->payNowButtonUrl = self::PAY_BUTTON_URL_LIVE;
        }
        else{
            $this->payNowButtonUrl = self::PAY_BUTTON_URL_SANDBOX;
        }
    }

    /**
     * @param $data
     * @param null $myPost
     */
    public static function log($data,$myPost = null){
        //@TODO refactor
        if($myPost){
            if(!isset($data['txn_id']))
                $data['txn_id'] = $myPost['txn_id'];

            if(!isset($data['subscr_id'])){
                $data['subscr_id'] = isset($myPost['subscr_id']) ? $myPost['subscr_id'] : null;
            }

            if(!isset($data['user_id']))
                $data['user_id'] = (string)$myPost['customData']['user_id'];

            if(!isset($data['data']))
                $data['data'] = json_encode($myPost);
        }

        $data['created'] = date('Y-m-d H:i:s');
        $db = new DatabaseService();
        $db->insertData(self::LOG_TABLE_NAME, $data);
    }

    /**
     * @param $myPost
     * @return mixed
     */
    public static function getCustomData($myPost){
        $customData = json_decode($myPost['custom'],true);
        return $customData;
    }
}