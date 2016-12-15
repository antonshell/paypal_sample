<?php
/**
 * Created by PhpStorm.
 * User: Antonshell
 * Date: 30.03.2015
 * Time: 20:14
 */

/**
 * Class PaypalSinglePayment
 */
class PaypalSinglePayment extends PaypalService{

    /**
     * @param $post
     * @return ExtraService
     * @throws Exception
     */
    public function getExtraService($post){
        $customData = $this->getCustomData($post);
        $serviceProvider = $customData['service_provider'];
        $serviceName = $customData['service_name'];

        $extraService = new ExtraService();
        $extraService->loadByServiceName($serviceProvider,$serviceName);

        return $extraService;
    }

    /**
     * @param $extraService
     * @param $userId
     * @param $quantity
     * @return mixed
     */
    public function createRelation($extraService,$userId,$quantity){
        return $extraService->addItems($userId,$quantity);
    }

    /**
     * // check whether the payment_status is Completed
     * // check that txn_id has not been previously processed
     * // check that receiver_email is your PayPal email
     * // check that payment_amount/payment_currency are correct
     * // process payment and mark item as paid.
     * // assign posted variables to local variables
     *
     * @param $myPost
     */
    public function processPayment($myPost){

        $customData = $this->getCustomData($myPost);
        $userId = $customData['user_id'];

        $userService = new UserService();
        $userInfo = $userService->getUserData($userId);

        $transactionService = new PaypalTransaction();
        $transaction = $transactionService->getTransactionById($myPost['txn_id']);

        if($transaction === null){
            $extraService = $this->getExtraService($myPost);

            if($this->validateTransaction($myPost,$extraService)){
                // @TODO remake this
                $relationId = $this->createRelation($extraService,$userId,$myPost['quantity']);
                $myPost['relation_id'] = $relationId;
                $myPost['relation_type'] = $transactionService::TRANSACTION_RELATION_EXTRA_SERVICE;
                $transactionService->createTransaction($myPost);

                self::log([
                    'message' => "Success. Payment processed",
                    'level' => self::LOG_LEVEL_INFO
                ], $myPost);
            }
        }
        else{
            self::log([
                'message' => "Duplicate. Transaction {$myPost['txn_id']} already processed",
                'level' => self::LOG_LEVEL_WARNING
            ], $myPost);
        }
    }

    /**
     * @param $myPost
     * @param $extraService
     * @return bool
     */
    protected function validateTransaction($myPost,$extraService){
        $valid = true;

        if($extraService->getTotalPrice($myPost['quantity']) != $myPost['payment_gross']){
            $valid = false;

            self::log([
                'message' => "Wrong payment info. Prices don't match. Local price: {$extraService->getTotalPrice($myPost['quantity'])}. Paypal price: {$myPost['payment_gross']}",
                'level' => self::LOG_LEVEL_ERROR
            ], $myPost);
        }
        elseif($myPost['payment_gross'] == 0){
            $valid = false;

            self::log([
                'message' => "Wrong payment info. Zero prices aren't allowed",
                'level' => self::LOG_LEVEL_ERROR
            ], $myPost);
        }
        elseif($myPost['payment_status'] !== 'Completed'){
            $valid = false;

            self::log([
                'message' => "Wrong payment status. Payment status is {$myPost['payment_status']}",
                'level' => self::LOG_LEVEL_ERROR
            ], $myPost);
        }
        elseif($myPost['receiver_email'] != $this->receiverEmail){
            $valid = false;

            self::log([
                'message' => "Wrong Receiver Email. Local price: {$this->receiverEmail}. Paypal price: {$myPost['receiver_email']}",
                'level' => self::LOG_LEVEL_ERROR
            ], $myPost);
        }
        elseif($myPost['mc_currency'] != 'USD'){
            //check currency
            $valid = false;

            self::log([
                'message' => "Wrong currency: {$myPost['mc_currency']}. Only USD allowed.",
                'level' => self::LOG_LEVEL_ERROR
            ], $myPost);
        }

        return $valid;
    }
}