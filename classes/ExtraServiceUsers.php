<?php
/**
 * Created by PhpStorm.
 * User: Antonshell
 * Date: 12.03.2015
 * Time: 11:16
 */

/**
 * Class ExtraServiceUsers
 */
class ExtraServiceUsers{
    protected $instance = [
        'id' => null,
        'user_id' => null,
        'service_id' => null,
        'items_count' => null,
        'permissions' => null,
        'created_date' => null
    ];

    const PERMISSIONS_ALL = 'all';
    const PERMISSIONS_USER = 'user';

    const TABLE_NAME = 'extra_services_users';

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

        $data = $db->execSQL($sql,$params,'i');
        if(count($data))
        {
            $this->instance = $data[0];
        }
    }

    /**
     * @param $service_id
     * @param $user_id
     * @throws Exception
     */
    protected function loadByExtraServiceItem($service_id,$user_id)
    {
        $db = new DatabaseService();

        $sql = 'SELECT *, sum(items_count) as sum_count  FROM '.self::TABLE_NAME.' WHERE (user_id = ? OR permissions = ? ) AND service_id = ? ';

        $permissionsAll = self::PERMISSIONS_ALL;

        $params = [&$user_id,&$permissionsAll,&$service_id];

        $data =  $db->execSQL($sql,$params,'iss');
        if(count($data))
        {
            $data[0]['items_count'] = $data[0]['sum_count'];
            unset($data[0]['sum_count']);
            $this->instance = $data[0];
        }
        else
        {
            $this->instance['service_id'] = $service_id;
            $this->instance['user_id'] = $user_id;
            $this->instance['items_count'] = 0;
        }
    }

    /**
     * @param $service_id
     * @param $user_id
     * @throws Exception
     */
    public function decrementItemsCount($service_id,$user_id){
        $db = new DatabaseService();

        $sql = 'SELECT * FROM '.self::TABLE_NAME.' WHERE (user_id = ? OR permissions = ? ) AND service_id = ? AND items_count > 0';

        $permissionsAll = self::PERMISSIONS_ALL;

        $params = [&$user_id,&$permissionsAll,&$service_id];

        $data = $db->execSQL($sql,$params,'iss');

        if(count($data))
        {
            $this->instance = $data[0];
            $this->instance['items_count'] = $this->instance['items_count'] - 1;
            $this->save();
        }
    }

    /**
     * @param $service_id
     * @param $user_id
     * @return ExtraServiceUsers
     */
    public static function getByExtraServiceItem($service_id,$user_id)
    {
        $object = new ExtraServiceUsers();
        $object->loadByExtraServiceItem($service_id,$user_id);
        return $object;
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
     * @return bool|mixed|mysqli_result
     * @throws Exception
     */
    public static function getPaymentHistory($userId){
        $db = new DatabaseService();

        $sql = 'SELECT * FROM '.self::TABLE_NAME.' eu
                INNER JOIN '.PaypalTransaction::TRANSACTIONS_TABLE_NAME.' pt
                ON eu.id = pt.relation_id
                INNER JOIN '.ExtraService::TABLE_NAME.' es
                ON es.id = eu.service_id
                WHERE eu.user_id = ? AND pt.relation_type = ?
                ORDER BY pt.payment_date';
        $relationType = PaypalTransaction::TRANSACTION_RELATION_EXTRA_SERVICE;
        $params = [&$userId,&$relationType];
        $data = $db->execSQL($sql,$params,'is');

        return $data;
    }
}