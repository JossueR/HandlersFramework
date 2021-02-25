<?php


namespace Handlers\models;


abstract class AbstractBaseDAO extends SimpleDAO
{
    protected $sumary;
    public $autoconfigurable= FALSE;
    public $selectID;
    public $selectName;
    public $errors;
    public $logDesc;
    protected $map;
    protected $prototype;
    protected $baseSelect;
    protected $conectionName;
    protected $enableHistory;
    protected $historyTable;
    protected $historyMap = false;
    private $lastSelectQuery;
    private $execFind;

    private $fields_info;

    private static $cache;

    function __construct($tabla, $id, $baseSelect='', $map='', $prototype='') {
        parent::__construct($tabla, $id);
        $this->baseSelect= $baseSelect;
        $this->map= $map;
        $this->prototype = $prototype;
        $this->execFind =true;
    }

    function setHistory($table, $map){
        $this->enableHistory = true;

        $this->historyTable=$table;
        $this->historyMap=$map;
    }

    /**
     * @return QueryInfo
     */
    function getSumary(){
        return $this->sumary;
    }

    function &insert($searchArray){
        $this->sumary = parent::_insert(parent::getTableName(), $searchArray,$this->conectionName);
        $this->_recordLog(array("Action" => "INSERT"));
        $this->_history($searchArray);
        return $this->sumary;

    }

    function &update($searchArray, $condicion){

        $this->sumary = parent::_update(parent::getTableName(), $searchArray, $condicion,$this->conectionName);
        $this->_recordLog(array("Action" => "UPDATE"));
        //Update no hace history por que podria no estar actualizando algo solo por id, sino multiples registros
        return $this->sumary;

    }

    function &delete($prototype){
        $condicion = parent::mapToBd($prototype, $this->getDBMap());
        $condicion = parent::putQuoteAndNull($condicion, !self::REMOVE_TAG);

        $this->sumary= parent::_delete(parent::getTableName(), $condicion,$this->conectionName);
        $this->_recordLog(array("Action" => "DELETE"));
        //$this->_history($searchArray);
        return $this->sumary;

    }

    function deleteByID($prototype){
        $searchArray = parent::mapToBd($prototype, $this->getDBMap());
        $condicion = $this->getIdFromDBMap($searchArray);
        $condicion = parent::putQuoteAndNull($condicion);
        $this->sumary = parent::_delete(parent::getTableName(), $condicion,$this->conectionName);

        return $this->sumary->total > 0;
    }
    /***
     * Busca si existe por ID
     */
    function exist($searchArray){
        $searchArray = $this->getIdFromDBMap($searchArray);

        $sql = "SELECT COUNT(*) FROM " . parent::getTableName() . " WHERE " . parent::getSQLFilter($searchArray);
        return parent::execAndFetch($sql,$this->conectionName) > 0;
    }

    function existBy($searchArray){

        $sql = "SELECT COUNT(*) FROM " . parent::getTableName() . " WHERE " . parent::getSQLFilter($searchArray);
        return parent::execAndFetch($sql,$this->conectionName) > 0;
    }

    function getIdFromDBMap($searchArray){
        $condicion = array();

        foreach (parent::getId() as $key ) {
            $condicion[parent::getTableName() . "." . $key] = (isset($searchArray[$key]))? $searchArray[$key] : null;
        }

        return $condicion;
    }

    function getTotals(){
        return $this->sumary->total;
    }

    function getFields(){
        $fields = array();

        if($this->sumary->result){
            $total = mysqli_num_fields($this->sumary->result);

            for ($i=0; $i < $total; $i++) {
                $info_campo = mysqli_fetch_field_direct($this->sumary->result, $i);
                $fields[] = $info_campo->name;
            }
        }


        return $fields;
    }


    /**
     * Guarda los datos del prototypo
     * Aplica getDBMap a el prototypo para obtener los nombres de los campos
     * Si se establee $update, fuerza a generar un update
     */
    public function save($prototype, $update=2){

        $searchArray = parent::mapToBd($prototype, $this->getDBMap());

        if(!$this->validate($searchArray)){
            return false;
        }


        $searchArray = parent::putQuoteAndNull($searchArray);

        switch ($update) {
            case parent::INSERT:
            case parent::EDIT:

                break;

            default:

                $update = ($this->exist($searchArray))? parent::EDIT : parent::INSERT;
        }

        if($update === parent::INSERT ){

            $this->sumary = $this->insert($searchArray);

        }else{
            $condicion = array();

            foreach (parent::getId() as $key ) {
                $condicion[$key] = $searchArray[$key];
                unset($searchArray[$key]);
            }
            $this->sumary = $this->update($searchArray, $condicion);
            $this->_history(array_merge($searchArray,$condicion));
        }

        if($this->sumary->errorNo != 0){
            $this->errors["#DB"] = $this->sumary->error;
        }

        return ($this->sumary->errorNo == 0);
    }

    public function find($sql){
        $this->lastSelectQuery = $sql;

        if($this->execFind){
            $this->sumary = parent::execQuery($sql, true, $this->autoconfigurable,$this->conectionName);
        }else{
            //habilita la ejecucion del query
            $this->enableExecFind();
        }

    }


    public function get()
    {
        if($this->sumary->result){
            return parent::getNext($this->sumary);
        }else{
            return false;
        }
    }

    public function fetchAll()
    {
        if($this->sumary->result){
            return parent::getAll($this->sumary);
        }else{
            return false;
        }
    }

    public function getBy($proto){
        $searchArray = parent::mapToBd($proto, $this->getDBMap());

        $temp = array();
        foreach ($searchArray as $key => $value) {
            if (strpos($key, '.') === false){
                $temp[$this->tableName . "." . $key] = $value;
            }

        }
        $searchArray = $temp;

        $searchArray = parent::putQuoteAndNull($searchArray);
        $sql_where = $this->getSQLFilter($searchArray);

        $sql = $this->getBaseSelec();
        $sql .= " $sql_where";

        $this->find($sql);
    }

    public function getById($proto, $use_cashe = true){
        $protoDB = parent::mapToBd($proto, $this->getDBMap());
        $searchArray = $this->getIdFromDBMap($protoDB);

        $searchArray = parent::putQuoteAndNull($searchArray);
        $sql_where = $this->getSQLFilter($searchArray);

        $sql = $this->getBaseSelec();
        $sql .= " $sql_where";

        $classname = get_class($this);
        $cache = self::getCache($classname);

        //si ya existe y es el mismo y esta habilitado el uso de cache
        if($use_cashe && $cache && $cache->equals($proto)){
            //var_dump("ya existe $classname id:" . json_encode($proto));


            //establece el sumary del cashe
            $this->sumary = $cache->getSummary();

            //resetea el puntero
            $this->resetGetData();

        }else{
            //var_dump("busca primera ves $classname id:" . json_encode($proto));

            //busca
            $this->find($sql);

            $cache = new CasheFindData($proto, $this->sumary);

            //almacena en cashe
            self::$cache[$classname] = $cache;
        }

    }

    public function getFilledPrototype($prototype = null){
        if(!$prototype){
            $prototype = $this->getPrototype();
        }

        $map = $this->getDBMap();
        $row_data = $this->get();

        foreach ($prototype as $proto_key => $value) {
            if(isset($map[$proto_key])){
                $prototype[$proto_key] = $row_data[$map[$proto_key]];
            }else if(isset($row_data[$proto_key])){
                $prototype[$proto_key] = $row_data[$proto_key];
            }

        }

        return $prototype;
    }



    public function validate($searchArray){
        $errors = array();

        $fields = array_keys($searchArray);
        $fields_all = implode(',', $this->quoteFieldNames($fields));
        $sql = "SELECT " . $fields_all . " FROM " . $this->tableName . " LIMIT 0";
        $sumary = parent::execQuery($sql, true);

        $i = 0;
        $total = parent::getNumFields($sumary);

        while ($i < $total) {
            $f = $fields[$i];
            $type = parent::getFieldType($sumary, $i);
            $len = parent::getFieldLen($sumary, $i);
            $flag = explode(" ", parent::getFieldFlags($sumary, $i));

            //verifica requerido
            if(in_array("not_null", $flag)){

                if($searchArray[$f] === null || $searchArray[$f] === "null" || $searchArray[$f] === ""){
                    //error
                    $errors[$f] = "required";
                }

            }

            //verifica tipo
            if($type == "string"){

                //verifica maxlen
                if(strlen($searchArray[$f]) > ($len / 3)){
                    //error maxlen
                    $errors[$f] = "too_long";
                }

            }

            if($type == "int"){


                //verifica si es entero
                if(($searchArray[$f] != "" && !is_numeric($searchArray[$f])) || $searchArray[$f] - intval($searchArray[$f]) != 0){
                    //error no es numero entero
                    $errors[$f] = "no_int";
                }
            }

            if($type == "real"){
                //verifica si es real
                if( ($searchArray[$f] != "" && !is_numeric($searchArray[$f])) || floatval($searchArray[$f]) - $searchArray[$f] != 0 ){
                    //error no es numero real
                    $errors[$f] = "no_decimal";
                }
            }


            $i++;
        }


        $this->errors = $errors;
        return (count($errors) == 0);
    }

    /**
     * Retorna un arreglo asociativo donde los key , son los distintos campos que se buscaran en el request
     * y se cargaran automaticamente.
     * sirve para enmascarar los nombres reales de los campos
     */
    function getPrototype(){
        return $this->prototype;
    }

    /**
     * Retorna un arreglo asociativo donde los key son los nombres de un prototipo y los value son los nombres de los campos en la base de datos
     */
    function getDBMap(){
        return $this->map;
    }



    /**
     * Es usado para obtener los datos por el id
     */
    function getBaseSelec(){
        return $this->baseSelect;
    }

    function resetGetData(){
        parent::resetPointer($this->sumary);
    }

    function getNewID(){
        return $this->sumary->new_id;
    }

    function _recordLog($searchArray){
        if(self::$enableRecordLog){
            $searchArray["desc"] = $this->logDesc;
            $searchArray["tabla"] = parent::getTableName();
            if(isset($_SESSION["USER_ID"])) $searchArray["user_id"] = $_SESSION["USER_ID"];
            $searchArray = parent::putQuoteAndNull($searchArray);

            $sum = parent::_insert(self::$recordTable, $searchArray);

        }
    }

    function setConnectionName($name){
        $this->conectionName = $name;

    }

    function getPrefixedID($sequence = null){
        if(!$sequence){
            $sequence = $this->tableName;
        }

        if(APP_ENABLE_BD_FUNCTION){
            $sql = "SELECT GET_NEXT_ID('$sequence')";

            $newID = $this->execAndFetch($sql);
        }else{
            //busca secuencial
            $sql = "SELECT prefix, size, fill_with, last_id , sufix, eval
				        
						FROM secuential 
						WHERE seq_name = '$sequence' FOR UPDATE";

            $row = $this->execAndFetch($sql);

            //si no existe secuencial lo crea
            if(!$row){
                $sql = "INSERT INTO secuential (seq_name, size, last_id, fill_with,prefix,sufix)
							VALUES ( '$sequence', 8, 0, '','','')";
                $this->execNoQuery($sql);

                $row = array(
                    "prefix"=>"",
                    "size"=>"8",
                    "fill_with"=>"",
                    "sufix"=>"",
                    "last_id"=>"0"
                );
            }

            $_next_id = $row["last_id"] + 1;

            //actualiza
            $sql = "UPDATE secuential SET last_id='$_next_id' WHERE seq_name = '$sequence'";
            $this->execNoQuery($sql);

            //retrorna nuevo
            $sql = "SELECT CONCAT(
						ifnull('".$row["prefix"]."',''),
						IFNULL( LPAD('$_next_id', ".$row["size"]." , '".$row["fill_with"]."'), '$_next_id'),
						ifnull( '".$row["sufix"]."','')
										
						) as _result";
            $newID = $this->execAndFetch($sql);
        }

        return $newID;

    }

    function lastExecutionOk(){
        return ($this->sumary->errorNo == 0);
    }

    function _history($searchArray){
        if($this->enableHistory){


            //$searchArray = parent::putQuoteAndNull($searchArray);

            parent::_insert($this->historyTable, $searchArray, $this->conectionName);

        }
    }

    function disableExecFind(){
        $this->execFind = false;
    }

    function enableExecFind(){
        $this->execFind = true;
    }

    function findLast(){
        $this->enableExecFind();
        $this->find($this->lastSelectQuery);
    }

    function getNumAllRows(){
        return $this->sumary->allRows;
    }

    /**
     * @param $searchArray array asociativo con los campos de la BD
     */
    protected function getFieldsInfo($searchArray){
        $field_info = array();

        $fields = array_keys($searchArray);
        $fields_all = implode(',', $this->quoteFieldNames($fields));
        $sql = "SELECT " . $fields_all . " FROM " . $this->tableName . " LIMIT 0";
        $sumary = parent::execQuery($sql, true, false, $this->conectionName);

        $i = 0;
        $total = parent::getNumFields($sumary);

        while ($i < $total) {
            $f = $fields[$i];
            //$type = parent::getFieldType($sumary, $i);
            //$len = parent::getFieldLen($sumary, $i);
            $flag = explode(" ", parent::getFieldFlags($sumary, $i));

            $field_info[$f] = $flag;


            $i++;
        }


        $this->fields_info = $field_info;
        return $field_info;
    }

    public function checkFieldRequired($proto_field_name, $prototype = null, $force_reload_info = false){
        $status = false;
        $map = $this->getDBMap();

        if($prototype){

            $searchArray = self::mapToBd($prototype, $map, true);

            //si se envia la lista de parametros
            if($force_reload_info || is_null($this->fields_info)){

                //actualiza la info
                $this->getFieldsInfo($searchArray);
            }
        }

        //si el campo buscado existe esta mapeado a la bd
        if(isset($map[$proto_field_name])){

            $field_name = $map[$proto_field_name];

            //si no hay info del campo
            if(!isset($this->fields_info[$field_name])){

                $searchArray = parent::mapToBd(array($field_name => null), $map);

                //busca solo el campo actual
                $this->getFieldsInfo($searchArray);
            }

            if(in_array("not_null", $this->fields_info[$field_name])){
                $status = true;
            }
        }

        return $status;
    }

    public function truncate(){
        $sql = "TRUNCATE " . $this->tableName;

        return self::execNoQuery($sql);
    }

    /**
     * Retorna el cache de una clase
     * @param $classname string Nombre de la clase
     * @return CasheFindData;
     */
    protected static function getCache($classname){
        $cashe = null;

        if(isset(self::$cache[$classname])){
            $cashe = self::$cache[$classname];
        }
        return $cashe;
    }
}