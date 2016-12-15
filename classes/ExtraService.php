<?php
/**
 * Created by PhpStorm.
 * User: Antonshell
 * Date: 12.03.2015
 * Time: 16:24
 */

/**
 * Class ExtraService
 */
class ExtraService
{
    protected $instance = [
        'id' => null,
        'service_provider' => null,
        'service_name' => null,
        'price' => null,
        'description' => null,
    ];

    const TABLE_NAME = 'extra_services';

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
     * @param $userId
     * @return mixed
     */
    public function getCount($userId){
        $userExtraService = ExtraServiceUsers::getByExtraServiceItem($this->id,$userId);
        return $userExtraService->items_count;
    }

    /**
     * @param $userId
     * @param $count
     * @return mixed
     */
    public function addItems($userId,$count){
        $extraService = new ExtraServiceUsers();

        $extraService->user_id = $userId;
        $extraService->service_id = $this->id;
        $extraService->items_count = $count;
        $extraService->permissions = ExtraServiceUsers::PERMISSIONS_ALL;
        $extraService->created_date = date("Y-m-d H:i:s");

        return $extraService->save();
    }

    /**
     * @param $userId
     */
    public function decrementItemCount($userId){
        $extraService = new ExtraServiceUsers();
        $extraService->decrementItemsCount($this->id,$userId);
    }

    /**
     * @param $count
     * @return mixed
     */
    public function getPriceByCount(){
        return $this->price;
    }

    /**
     * @param $count
     * @return mixed
     */
    public function getTotalPrice($count){
        return $this->price * $count;
    }

    /**
     * @return array
     */
    public static function getList(){
        $db = new DatabaseService();
        $data = $db->getTableData(self::TABLE_NAME,'*','');
        return $data;
    }
}