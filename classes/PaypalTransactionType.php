<?php
/**
 * Created by PhpStorm.
 * User: Antonshell
 * Date: 10.08.2015
 * Time: 22:37
 */

/**
 * Class PaypalTransactionType
 */
class PaypalTransactionType{
    const TRANSACTION_TYPE_SINGLE_PAY = "web_accept";
    const TRANSACTION_TYPE_SUBSCRIPTION = "subscr_payment";

    /**
     * @param $rawPostData
     * @return string
     */
    public static function getPaymentType($rawPostData){
        $post = self::getPostFromRawData($rawPostData);

        if(isset($post['subscr_id'])){
            return self::TRANSACTION_TYPE_SUBSCRIPTION;
        }
        else{
            return self::TRANSACTION_TYPE_SINGLE_PAY;
        }
    }

    /**
     * @param $raw_post_data
     * @return array
     */
    public static function getPostFromRawData($raw_post_data){
        $raw_post_array = explode('&', $raw_post_data);
        $myPost = array();
        foreach ($raw_post_array as $keyval) {
            $keyval = explode ('=', $keyval);
            if(count($keyval) == 2)
                $myPost[$keyval[0]] = urldecode($keyval[1]);
        }

        return $myPost;
    }
}