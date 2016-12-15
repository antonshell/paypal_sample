<?php
/**
 * Created by PhpStorm.
 * User: Antonshell
 * Date: 10.08.2015
 * Time: 22:07
 */

/**
 * Class PaypalTransaction
 */
class PaypalTransaction{
    const TRANSACTIONS_TABLE_NAME = 'paypal_transactions';

    const TRANSACTION_RELATION_EXTRA_SERVICE = 'extra_service';
    const TRANSACTION_RELATION_SUBSCRIPTION = 'subscription';

    /**
     * @param $txn_id
     * @return null
     * @throws Exception
     */
    public function getTransactionById($txn_id){
        $sql = 'SELECT * FROM '.self::TRANSACTIONS_TABLE_NAME.' WHERE txn_id=?';
        $params = [&$txn_id];
        $db = new DatabaseService();
        $data = $db->execSQL($sql,$params,'s');
        return count($data) ? $data[0] : null;
    }

    /**
     * @param $data
     */
    public function createTransaction($data){
        $data['created_date'] = date('Y-m-d H:i:s');
        $db = new DatabaseService();
        $db->insertData(self::TRANSACTIONS_TABLE_NAME,$data);
    }

    /**
     * @param $transactionId
     * @param $post
     * @return mixed
     */
    public function updateTransactionStatus($transactionId, $status){
        $sql = 'UPDATE '.self::TRANSACTIONS_TABLE_NAME.' SET payment_status = ? WHERE txn_id = ?';
        $params = [&$status,$transactionId];
        $db = new DatabaseService();
        $data = $db->execSQL($sql,$params,'ss');
        return count($data) ? $data[0] : null;
    }


}