<?php
/**
 * Created by PhpStorm.
 * User: Antonshell
 * Date: 10.08.2015
 * Time: 21:53
 */

/**
 * Class DatabaseService
 */
class DatabaseService {
    private $config;
    private $mysqli;

    /**
     * @throws Exception
     */
    public function __construct(){
        $config = Config::get();
        $this->config = $config['database'];

        $this->mysqli = new mysqli(
            $config['database']['host'],
            $config['database']['user'],
            $config['database']['password'],
            $config['database']['database']
        );

        if ($this->mysqli->connect_error) {
            throw new Exception('Database connection failed: '.$this->mysqli->connect_error);
        }
    }

    /**
     * @param $table_name
     * @param $post
     * @param $dbname
     * @param array $_logColumns
     * @return bool|mixed
     */
    public function insertData($table_name, $post){
        $dbname = $this->config['database'];

        $sql = "SHOW COLUMNS FROM ".$dbname.".`".$table_name."`";
        $sql_table_def = $this->mysqli->query($sql);
        $columns = array();
        while($row = $sql_table_def->fetch_assoc()){
            array_push($columns, $row['Field']);
        }

        $values = array();

        foreach($post as $key=>$val) {

            if(in_array($key, $columns)){
                $outPutValue = "'".$this->mysqli->escape_string(trim($val))."'";
                $temp = '`'.$key.'`= '.$outPutValue;
                array_push($values, $temp);
            }
        }

        $sql = "INSERT INTO ".$dbname.".`".$table_name."` set ".implode(', ', $values);
        $result = $this->mysqli->query($sql);
        $recordID = $this->mysqli->insert_id;

        if ($result) {
            return $recordID;
        }
        return  false;
    }

    /**
     * @param $sql
     * @param array $params
     * @param string $types
     * @param int $fetchHow
     * @param $db
     * @return bool|mixed|mysqli_result
     * @throws Exception
     */
    public function execSQL($sql, &$params = array(),$types = "", $fetchHow = MYSQLI_ASSOC){
        $db = $this->config['database'];

        $r = mysqli_select_db($this->mysqli,$db);
        if(!$r)
        {
            throw new Exception('Can\'t select database');
        }

        $stmt = $this->mysqli->prepare($sql);

        if(count($params) && strlen($types))
        {
            $callParams[] = & $types;
            $ref    = new ReflectionClass('mysqli_stmt');
            $method = $ref->getMethod("bind_param");
            $method->invokeArgs($stmt,array_merge($callParams,$params));
        }
        $stmt->execute();
        $res = $stmt->get_result();

        if(!$res)
        {
            return $res;
        }

        return $res->fetch_all($fetchHow);
    }

    /**
     * @param $table_name
     * @param $post
     * @param $field
     * @param $match
     * @param string $db
     * @param array $_submod
     * @return bool|mysqli_result
     */
    public function updateData($table_name, $post, $field, $match){
        $dbname = $this->config['database'];

        /* get columns */
        $sqlcols = "SHOW COLUMNS FROM ".$dbname.".`".$table_name."`";

        $sql_table_def = $this->mysqli->query($sqlcols);
        $columns = array();

        while($row = $sql_table_def->fetch_assoc()){
            array_push($columns, $row['Field']);
        }
        /**/

        $values = array();
        $fields = array('`id`');

        foreach($post as $key=>$val) {

            if(in_array($key, $columns)){
                $temp = '`'.$key.'`="'.$this->mysqli->escape_string(trim($val)).'"';
                array_push($values, $temp);
                $log_data[$key] = array('old_value'=>'', 'new_value'=>$val);
                $fields[] = '`'.$key.'`';
            }
        }

        $sql = 'UPDATE '.$dbname.'.'.$table_name.' SET '.implode(', ', $values).' WHERE `'.$field.'` = "'.$match.'"';
        $result = $this->mysqli->query($sql);

        return $result;
    }

    /**
     * @param $_moduleID
     * @param $_recordID
     * @param $dbName
     * @return array
     */
    public function getRecordData($_moduleID, $_recordID) {
        $dbName = $this->config['database'];

        $_moduleID = $this->mysqli->real_escape_string($_moduleID);
        $_recordID = $this->mysqli->real_escape_string($_recordID);
        $sql = "SELECT * FROM ".$dbName.".`".$_moduleID."` WHERE id = ?";
        $stmt = $this->mysqli->prepare($sql);
        $stmt -> bind_param("s", $_recordID);
        $stmt->execute(); $res = $stmt->get_result();
        $row = $res->fetch_assoc();

        return $row;
    }

    /**
     * @param $table
     * @param $columns
     * @param $filters
     * @param null $orderby
     * @return array
     */
    public function getTableData($table, $columns, $filters, $orderby = null) {
        $this->_rows = array();
        $table = $this->mysqli->real_escape_string($table);
        $sql = "SELECT ".$columns." FROM `".$table."` ".$filters;

        if($orderby != null){
            $sql.=" ORDER BY ".$orderby;
        }

        $stmt = $this->mysqli->prepare($sql);
        $stmt->execute(); $res = $stmt->get_result();

        $rows = [];

        while ($row = $res->fetch_assoc()) {
            $rows[] = $row;
        }

        // Return rows as an array
        return $rows;
    }
}