<?php
/**
 * Created by PhpStorm.
 * User: Antonshell
 * Date: 12.08.2015
 * Time: 21:40
 */

/**
 * Class UserService
 */
class UserService{
    const TABLE_NAME = 'users';

    /**
     * @param $userId
     * @return array
     */
    public function getUserData($userId){
        $db = new DatabaseService();
        $data = $db->getRecordData(self::TABLE_NAME,$userId);

        return $data;
    }

    /**
     * @return array
     */
    public function getUsers(){
        $db = new DatabaseService();
        $data = $db->getTableData(self::TABLE_NAME,'*','');

        return $data;
    }

    /**
     * @return null
     */
    public function getSelectedUserId(){
        if(isset($_SESSION['userId']) && $_SESSION['userId']){
            return $_SESSION['userId'];
        }
        else{
            $data = $this->getUsers();
            return isset($data[0]['id']) ? $data[0]['id'] : null;
        }
    }

    /**
     * @param $userId
     */
    public function setSelectedUser($userId){
        $_SESSION['userId'] = $userId;
    }
}