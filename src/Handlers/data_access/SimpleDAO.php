<?php


namespace Handlers\data_access;


use Exception;

class SimpleDAO
{

    const REG_ACTIVO='1';
    const REG_DESACTIVADO='0';
    const REG_ACTIVO_TX='ACTIVE';
    const REG_DESACTIVADO_TX='INACTIVE';
    const REG_ACTIVO_Y='Y';
    const REG_DESACTIVADO_N='N';
    const EDIT=1;
    const INSERT=0;

    const NO_REMOVE_TAG=FALSE;
    const REMOVE_TAG=TRUE;

    const AND_JOIN = " AND ";
    const OR_JOIN = " OR ";

    static private $_vars;
    static private $conectado=false;
    public static $SQL_TAG ="<SQL>";


    /**
     * @var Connection[] $conections
     */
    private static $conections = array();

    public static $defaultConection = null;

    public static $enableDebugLog = false;
    protected static $escapeHTML = true;
    private static $debugTAG;


    /**
     * @var BDEngine[]
     */
    private static $engines;

    /**
     *
     * Conecta a una base de datos mysql y establese el charset a utf8
     * @param $host
     * @param $bd
     * @param $usuario
     * @param $pass
     * @param $conectionName string nombre de referencia de la coneccion
     * @param BDEngine|null $implementation
     * @return bool
     */
    static function connect($host,$bd,$usuario,$pass, $conectionName='db', BDEngine $implementation = null) {
        self::$conectado = false;

        if(!$implementation){
            $implementation = new MysqlImp();
        }

        $conn = $implementation->connect($host,$bd,$usuario,$pass);
        if($conn){
            $conn->alias_name = $conectionName;

            self::$conections[$conectionName] = $conn;

            self::$engines[$conectionName] = $implementation;

            //la coneccion por defecto es la ultima
            self::$defaultConection = $conectionName;

            self::$conectado = true;
        }

        return self::$conectado;
    }

    /**
     * @param $sql
     * @param bool $isSelect
     * @param QueryDynamicParams|null $queryparams
     * @param null $connectionName
     * @return QueryInfo
     * @throws Exception
     */
    static public function &execQuery($sql,$isSelect= true, QueryDynamicParams $queryparams= null, $connectionName=null){
        $connectionName = self::getConnectionName($connectionName);

        $implementation = self::$engines[$connectionName];

        if(!self::$conectado){
            throw new Exception('no conectado');
        }else if(!$implementation){
            throw new Exception('no hay ningún motor activo');
        }else{
            return $implementation->execQuery(self::$conections[$connectionName], $sql,$isSelect,$queryparams);
        }


    }

    /**
     * @param $sql
     * @param null $conectionName
     * @return QueryInfo
     */
    static public function &execNoQuery($sql, $conectionName=null){
        return self::execQuery($sql,false,null,$conectionName);
    }

    /**
     * @param $connectionName
     * @return string
     */
    static public function getConnectionName($connectionName=""){
        if($connectionName == null || $connectionName == ""){
            $connectionName = self::$defaultConection;
        }
        return $connectionName;
    }

    /**
     * @param QueryInfo $summary
     * @param bool $escape_html
     * @return array
     */
    static public function getAll(QueryInfo $summary, $escape_html=true){
        $valores = array();

        while($row = self::getNext($summary, $escape_html)){
            $valores[] = $row;
        }

        return $valores;
    }

    /**
     * @param QueryInfo $summary
     * @param bool $escape_html
     * @return mixed
     */
    static public function getNext(QueryInfo $summary, $escape_html=true){
        $connectionName = self::getConnectionName($summary->getConnectionName());
        $implementation = self::$engines[$connectionName];

        $row = $implementation->getNext($summary);

        if($escape_html){
            $row = self::escape_HTML($row);
        }


        return $row;
    }

    static public function execAndFetch($sql, $conectionName= null, $inArray=null, $escape_html=true){
        $sumary = self::execQuery($sql, true,null,$conectionName);

        if($inArray !== null){
            $sumary->inArray=$inArray;
        }

        $row = self::getNext($sumary, $escape_html);

        $resp = null;

        if($sumary->errorNo == 0){
            //si solo se estaba buscando un campo
            if($row && count($row) == 1){
                //obtener el primer campo
                $resp = reset($row);
            }else{
                $resp =  $row;
            }
        }


        return $resp;
    }

    /**
     * @param $data
     * @return array|mixed
     */
    static public function escape_HTML($data){

        if(self::$escapeHTML && is_array($data)){
            foreach ($data as $key => $value) {
                $data[$key] = htmlspecialchars($value, ENT_QUOTES);
            }
        }

        return $data;
    }





    /**
     * Retorna un arreglo sin las posicieones vacias
     */
    static public function cleanEmptys($searchArray)
    {
        $cleanArray = array();

        foreach ($searchArray as $key => $value) {
            if(! empty($value) ){
                $cleanArray[$key] = $value;
            }
        }

        return $cleanArray;
    }

    /**
     *
     * Agrega comillas a todos los elementos del arreglo que sean string
     * @param array $array
     * @param bool $removeTag <SQL> indica que es fragmento sql
     * @param string $connectionName
     * @return array
     */
    static public function putQuoteAndNull($array, $removeTag = self::REMOVE_TAG, $connectionName = null){
        $connectionName = self::getConnectionName($connectionName);
        $use_engine = self::$engines[$connectionName];

        //si hay registros
        if(count($array)>0){

            //para cada elemento
            foreach ($array as $key => $value) {

                //si el valor no es un array
                if(!is_array($value)){

                    //Pone valor null si el elemento es nulo o vacio
                    if(is_null($value) || strlen($value) == 0){
                        $array[$key] = "null";
                    }else{

                        //Si el elemento contiene el tag <SQL>
                        if(substr_count($value, self::$SQL_TAG) > 0){

                            //Elimina el tag si se configura
                            if($removeTag){
                                //Elimina el tag <SQL>
                                $value = str_replace(self::$SQL_TAG, "", $value);
                            }


                            //no realiza ninguna conversion
                            $array[$key] =  $value;

                            //Elemente no tiene tag <SQL>
                        }else{

                            //Agrega comillas
                            $array[$key] = "'" . $use_engine->escape(self::$conections[$connectionName], $value) . "'";
                        }



                    }
                }else{
                    $value = self::putQuoteAndNull($value);

                    //Asocia nuevamente los datos escapados
                    $array[$key] =  $value;
                }
            }
        }

        //retorna los datos trabajados
        return $array;
    }

    /**
     *
     * Genera fragmento sql con filtros a partir del arreglo $filterArray
     * @param $filterArray
     * @param string $join
     * @return string sql con los filtos
     */
    static public function getSQLFilter($filterArray, $join = self::AND_JOIN, $connectionName=null){
        $connectionName = self::getConnectionName($connectionName);
        $use_engine = self::$engines[$connectionName];

        return $use_engine->getSQLFilter($filterArray, $join, self::$SQL_TAG);
    }

    static public function StartTransaction($connectionName=null){
        $connectionName = self::getConnectionName($connectionName);
        $use_engine = self::$engines[$connectionName];

        return $use_engine->StartTransaction(self::$conections[$connectionName]);
    }

    static public function CommitTransaction($connectionName=null){
        $connectionName = self::getConnectionName($connectionName);
        $use_engine = self::$engines[$connectionName];

        return $use_engine->CommitTransaction(self::$conections[$connectionName]);
    }

    static public function RollBackTransaction($connectionName=null){
        $connectionName = self::getConnectionName($connectionName);
        $use_engine = self::$engines[$connectionName];

        return $use_engine->RollBackTransaction(self::$conections[$connectionName]);
    }

    /**
     * @param $table
     * @param $searchArray
     * @param string $connectionName
     * @return QueryInfo
     */
    static public function &_insert($table, $searchArray, $connectionName= null){
        $connectionName = self::getConnectionName($connectionName);
        $use_engine = self::$engines[$connectionName];

        return $use_engine->_insert($table, $searchArray, self::$conections[$connectionName] );
    }

    /**
     * @param $table
     * @param $searchArray
     * @param $condition
     * @param string $connectionName
     * @return QueryInfo
     */
    static public function &_update($table, $searchArray, $condition, $connectionName= null){
        $connectionName = self::getConnectionName($connectionName);
        $use_engine = self::$engines[$connectionName];

        return $use_engine->_update($table,$searchArray,$condition, self::$conections[$connectionName],self::$SQL_TAG);
    }

    /**
     * @param $table
     * @param $condition
     * @param string $connectionName
     */
    static public function &_delete($table, $condition, $connectionName= null){
        $connectionName = self::getConnectionName($connectionName);
        $use_engine = self::$engines[$connectionName];

        return $use_engine->_delete($table,$condition,self::$conections[$connectionName],self::$SQL_TAG );
    }

    /**
     * Retorna un arreglo con los nombres de los campos de la BD
     * @param array $prototype es un arreglo con los nombre de los campos de un formulario
     * @param array $map Arreglo que contiene la equivalencia de [Nombre_Campo_del_Formulario]=campo_BD
     * @param bool $map_nulls si se estrablece a true, mapea incluso nulos
     * @return array
     */
    static public function mapToBd($prototype, $map, $map_nulls = false){

        $searchArray = array();
        foreach ($map as $key => $value) {

            if(isset($prototype[$key]) ||
                ($map_nulls && array_key_exists($key, $prototype))
            ){
                $searchArray[$value] = $prototype[$key];
            }
        }


        return $searchArray;
    }

    public static function quoteFieldNames($fields){
        $all = array();
        foreach ($fields as $key => $name) {
            //solo agrega la comillasi no la encuentra
            if (strpos($name, '`') === false) {
                $all[] = "`" . $name .  "`";
            }

        }

        return $all;
    }

    /*** Reemplaza los {tag} por el value o agrega el value al final de $sql
     * @param $sql
     * @param $tag
     * @param $value
     * @return string
     */
    static public  function embedParams($sql, $tag, $value){

        $pattern = "/\{(.+)\}/";

        preg_match_all($pattern, $sql, $matches, PREG_OFFSET_CAPTURE);

        for($i=0; $i < count($matches[0]); $i++){
            $foundKey = $matches[1][$i][0];

            $data_array = explode(" ", $foundKey);

            if(isset($data_array[0]) && $data_array[0] == $tag){

                if($value){
                    $replaceWith = $value;
                }else{
                    $replaceWith = $foundKey;
                }


                $sql = str_replace("{".$foundKey."}", $replaceWith, $sql);
            }


        }
//			var_dump($matches);
        if(count($matches[0]) == 0){
            $sql .= $value;
        }

        return $sql;
    }

    public static function validFieldExist($field, $sql){
        $valid = false;

        if(strpos($sql, $field)){
            $valid = true;
        }

        return $valid;
    }

    public static function valueNOW($connectionName = null){
        $connectionName = self::getConnectionName($connectionName);
        $use_engine = self::$engines[$connectionName];

        return $use_engine->valueNOW(self::$SQL_TAG);
    }

    public static function valueISNULL($connectionName = null){
        $connectionName = self::getConnectionName($connectionName);
        $use_engine = self::$engines[$connectionName];

        return $use_engine->valueISNULL(self::$SQL_TAG);
    }

    public static function disableForeignKeyCheck($connectionName=null){
        $connectionName = self::getConnectionName($connectionName);
        $use_engine = self::$engines[$connectionName];

        return $use_engine->disableForeignKeyCheck(self::$conections[$connectionName]);
    }

    public static function enableForeignKeyCheck($connectionName=null){
        $connectionName = self::getConnectionName($connectionName);
        $use_engine = self::$engines[$connectionName];

        return $use_engine->enableForeignKeyCheck(self::$conections[$connectionName]);
    }

    public static function enableDebugLog($tag=''){
        self::$enableDebugLog = true;
        self::$debugTAG = $tag;

        foreach (self::$engines as $db_engine){
            $db_engine->debug_log = true;
        }
    }

    public static function disableDebugLog(){
        self::$enableDebugLog = FALSE;
        self::$debugTAG = "";

        foreach (self::$engines as $db_engine){
            $db_engine->debug_log = false;
        }
    }

    public static function setDataVar($key, $value){
        self::$_vars[$key] = $value;
    }

    public static function getDataVar($key){
        $value = null;

        if(isset(self::$_vars) && isset(self::$_vars[$key])){
            $value = self::$_vars[$key];
        }

        return $value;
    }

    static function resetPointer(QueryInfo &$summary, $pos = 0, $connectionName=null){
        $connectionName = self::getConnectionName($connectionName);
        $use_engine = self::$engines[$connectionName];

        return $use_engine->resetPointer($summary, $pos = 0);
    }

    static public function escapeHTML_ON(){
        self::$escapeHTML=true;
    }

    static public function escapeHTML_OFF(){
        self::$escapeHTML=false;
    }

    static public function getNumFields(QueryInfo &$sumary, $connectionName=null){
        $connectionName = self::getConnectionName($connectionName);
        $use_engine = self::$engines[$connectionName];

        return $use_engine->getNumFields($sumary);
    }

    static public function getFieldInfo(QueryInfo &$sumary, $i, $connectionName=null){
        $connectionName = self::getConnectionName($connectionName);
        $use_engine = self::$engines[$connectionName];

        return $use_engine->getFieldInfo($sumary, $i);
    }

    static public function getFieldType(QueryInfo &$sumary, $i){

        $info_campo = self::getFieldInfo($sumary, $i);
        return $info_campo->type;
    }

    static public function getFieldLen(QueryInfo &$sumary, $i){
        $info_campo = self::getFieldInfo($sumary, $i);
        return $info_campo->max_length;
    }

    static public function getFieldFlagsBin(QueryInfo &$sumary, $i){
        $info_campo = self::getFieldInfo($sumary, $i);

        //convierte a binario, invierte y divide de uno en uno
        $bin_flags = str_split(strrev(decbin($info_campo->flags)),1);




        return $bin_flags;
    }

    static public function getFieldFlags(QueryInfo &$sumary, $i){


        //convierte a binario, invierte y divide de uno en uno
        $bin_flags = self::getFieldFlagsBin($sumary, $i);

        $flags = array();

        if($bin_flags[0] == 1){
            $flags[] = "not_null";
        }


        return implode(" ", $flags);
    }

    static function addPagination($sql, QueryDynamicParams $params=null, $connectionName=null){
        $connectionName = self::getConnectionName($connectionName);
        $use_engine = self::$engines[$connectionName];

        return $use_engine->addPagination($sql,$params);
    }

    static protected function addOrder($sql, QueryDynamicParams $params=null, $connectionName=null){
        $connectionName = self::getConnectionName($connectionName);
        $use_engine = self::$engines[$connectionName];



        return $use_engine->addOrder($sql,$params);
    }

    static protected function addFilters($sql, QueryDynamicParams $params=null, $mergeTag=null, $connectionName=null){
        $connectionName = self::getConnectionName($connectionName);
        $use_engine = self::$engines[$connectionName];

        return $use_engine->addFilters($sql,$params, self::$conections[$connectionName], $mergeTag);
    }


    /**
     * @param $table
     * @param $searchArray
     * @param null $connectionName
     * @return bool
     */
    static function _existBy($table, $searchArray, $connectionName=null){
        $connectionName = self::getConnectionName($connectionName);
        $use_engine = self::$engines[$connectionName];

        return $use_engine->existBy($table, $searchArray,self::$conections[$connectionName], self::$SQL_TAG);
    }
}