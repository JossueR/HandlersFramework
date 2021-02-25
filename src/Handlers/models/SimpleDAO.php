<?php
/**
 *Create Date: 07/22/2011 01:00:56
\*Author: Jossue O. Rodriguez C.   $LastChangedRevision: 124 $
 */

namespace Handlers\models;


use Handlers\components\ConfigParams;
use Handlers\components\Handler;

class SimpleDAO
{
    static private $_vars;
    static private $conectado=false;
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

    const IS_SELECT = TRUE;
    const IS_AUTOCONFIGURABLE = TRUE;

    protected $tableName;

    //arreglo con los nombres de los campos que son el id de la tabla
    protected $TablaId;

    public static $SQL_TAG ="<SQL>";

    public static $inAssoc =true;

    public static $inArray = true;

    /**
     * @var Connection $conections
     */
    private static $conections = array();

    public static $defaultConection = null;

    public static $enableRecordLog = false;

    public static $recordTable = "record";

    public static $debugTable = "debug_log";

    public static $enableDebugLog = false;

    protected static $escapeHTML = true;

    private static $debugTAG;

    function __construct($tableName, $id){
        $this->tableName=$tableName;

        $this->TablaId=$id;
    }

    function getTableName(){
        return $this->tableName;
    }

    function getId(){
        return $this->TablaId;
    }

    /**
     *
     * Conecta a una base de datos mysql y establese el charset a utf8
     * @param $host
     * @param $bd
     * @param $usuario
     * @param $pass
     * @param $conectionName string nombre de referencia de la coneccion
     * @return bool
     */
    static function connect($host,$bd,$usuario,$pass, $conectionName='db') {
        self::$conectado = false;


        $coneccion=mysqli_connect($host,$usuario,$pass);
        if($coneccion){
            $bdConexion=mysqli_select_db($coneccion, $bd);

            if($bdConexion){
                @mysqli_query($coneccion, "SET NAMES 'utf8'");
                @mysqli_query($coneccion, "SET time_zone = '-5:00'");
                self::$conectado = true;

                $conectionName = (!$conectionName)? count(self::$conections)+1 : $conectionName;

                self::$conections[$conectionName] = new Connection($host,$bd,$usuario,$pass, $coneccion);

                //la coneccion por defecto es la ultima
                self::$defaultConection = $conectionName;
            }
        }



        return self::$conectado;
    }

    /**
     * Escapa una cadena para ser enviada a la base de datos
     * @param $str
     * @return string
     */
    static public function escape($str){

        return mysqli_real_escape_string(self::getConnectionData()->connection, $str);
    }


    /**
     *
     * @param $sql string es el query.
     * @param bool $isSelect : boleano, default es true. Indica si se ejecuta un select.
     * Si se ejecuta un select , carga $array['total']
     * @param bool $isAutoConfigurable : boleano, default es false. Indica si Agrega limit order y campos adicionales de filtrado u paginacion
     * @param string $conectionName
     * @return QueryInfo
     */
    static public function &execQuery($sql,$isSelect= true, $isAutoConfigurable= false, $conectionName=null){
        $sumary = new QueryInfo();

        if(!$conectionName || !isset(self::$conections[$conectionName])){
            $conectionName = self::$defaultConection;
        }

        //agrega paginacion
        if($isAutoConfigurable){
            $sql = self::addGroups($sql);
            $sql = self::addFilters($sql);
            $sql = self::addOrder($sql);

            #excel no pagina
            if(Handler::getRequestAttr(Handler::OUTPUT_FORMAT) != Handler::FORMAT_EXCEL){
                $sql = self::addPagination($sql);
            }

        }

        //muestra el sql si se habilita el modo depuracion
        if(self::getDataVar("SQL_SHOW")){
            echo $sql . "<br />\n";
        }


        $sumary->result = @mysqli_query(self::$conections[$conectionName]->connection, $sql );


        if($isSelect){

            $sumary->total  = ($sumary->result)? intval(mysqli_num_rows($sumary->result)) : 0;
        }else{
            $sumary->total = mysqli_affected_rows(self::$conections[$conectionName]->connection);
            $sumary->new_id = mysqli_insert_id(self::$conections[$conectionName]->connection);
        }


        $sumary->errorNo = mysqli_errno(self::$conections[$conectionName]->connection);

        $sumary->error = mysqli_error(self::$conections[$conectionName]->connection);

        //almacena log si esta habilitado
        self::storeDebugLog(self::$conections[$conectionName]->connection, $sql);

        //almacena en el query info el ultimo sql
        $sumary->sql = $sql;

        // si hay paginacion
        if($isAutoConfigurable){
            $sql = "SELECT FOUND_ROWS();";
            $rows = @mysqli_query( self::$conections[$conectionName]->connection, $sql);
            $rows = mysqli_fetch_row($rows);


            $sumary->allRows = $rows[0];
        }else{
            $sumary->allRows = $sumary->total;
        }

        //muestra el sql si se habilita el modo depuracion
        if(self::getDataVar("SQL_SHOW")){
            echo $sumary->error;
        }



        return $sumary;
        #return new QueryInfo();
    }

    static public function execNoQuery($sql,$conectionName=null){
        $sumary = SimpleDAO::execQuery($sql,false,FALSE,$conectionName);

        return ($sumary->errorNo == 0);
    }

    /**
     *
     * Arma querys tipo select
     * Reemplaza los tokens CON EL FORMATO {nombre_token} por el valor en el array respectivo
     * @param string $sql
     * @param array $array
     * @return mixed|string|string[]
     */
    static private function builtQuery($sql, $array){
        $pattern = "#/\*\{(.*)\}\*/#";
        preg_match_all($pattern, $sql, $matches, PREG_OFFSET_CAPTURE);


        for($i=0; $i < count($matches[0]); $i++){
            switch ($matches[0][$i][0]) {
                case "/*{create_date}*/":
                case "/*{modify_date}*/":
                    $replaceWith = "NOW()";
                    break;

                case "/*{create_user}*/":
                case "/*{modify_user}*/":
                    $replaceWith = $_SESSION['USER_ID'];
                    break;

                default:
                    if(!isset($array[$matches[1][$i][0]]) || $array[$matches[1][$i][0]] == null){
                        $replaceWith = "null";
                    }else{
                        $replaceWith =$array[$matches[1][$i][0]];
                    }
                    break;
            }
            $sql = str_replace($matches[0][$i][0], $replaceWith, $sql);
        }
        return $sql;
    }




    static public function execAndFetch($sql, $conectionName= null, $inArray=null){
        $sumary = self::execQuery($sql, true,false,$conectionName);

        if($inArray !== null){
            $sumary->inArray=$inArray;
        }

        $row = self::getNext($sumary);

        $resp = null;

        if($sumary->errorNo == 0){
            //si solo se estaba buscando un campo
            if($row && self::getNumFields($sumary) == 1){
                //obtener el primer campo
                $resp = reset($row);
            }else{
                $resp =  $row;
            }
        }


        return $resp;
    }

    static public function getNext(QueryInfo $sumary){

        if(!isset($sumary->total) || $sumary->total == 0){
            return null;
        }else if(self::$inArray){

            if(self::$inAssoc){
                $type=MYSQLI_ASSOC;
            }else{
                $type= MYSQLI_NUM;
            }

            return self::escape_HTML(mysqli_fetch_array($sumary->result, $type));
        }else{
            return self::escape_HTML(mysqli_fetch_row($sumary->result));
        }
    }

    static public function getAll(QueryInfo $sumary){
        $valores = array();

        while($row = self::getNext($sumary)){
            $valores[] = $row;
        }

        return $valores;
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
     * Agrega comilla a todos los elementos del arraglo que sean string
     * @param array $array
     * @param string $removeTag <SQL> indica que es fragmento sql
     * @return array
     */
    static public function putQuoteAndNull($array, $removeTag = self::REMOVE_TAG ){
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
                            $array[$key] = "'" . self::escape($value) . "'";
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
    static public function getSQLFilter($filterArray, $join = self::AND_JOIN){

        //pone datos nulos y comillas
        //$searchArray = self::putQuoteAndNull($filterArray,self::NO_REMOVE_TAG);
        $searchArray = $filterArray;

        //inicializa el campo q sera devuelto
        $campos = array();

        //Si el arreglo de filtros no esta vacio
        if(count($filterArray)>0){

            //para cara elemento, ya escapado
            foreach ($searchArray as $key => $value) {
                //si no tiene las comillas las pone
                if (strpos($key, '.') === false && strpos($key, '`') === false) {
                    $key = "`" . $key .  "`";
                }

                //Si el elemento no es nulo
                if($value != null){

                    //si es un arreglo genera un IN
                    if(is_array($value)){

                        //Une los valores del array y los separa por comas
                        $value = implode(" ,", $value);



                        //si no hay negacion
                        if(strpos($key, "!") === false){
                            //almacena el filtro IN
                            $campos[] = "$key IN(". $value . ") ";
                        }else{
                            $key = str_replace("!", "", $key);

                            //almacena el filtro IN
                            $campos[] = "$key NOT IN(". $value . ") ";
                        }

                        //Si no es un arreglo
                    }else{

                        //Si el elemento contiene el tag <SQL>
                        if(substr_count($value, self::$SQL_TAG) > 0){


                            //Elimina el tag <SQL>
                            $value = str_replace(self::$SQL_TAG, "", $value);

                            $campos[] = "$key $value";

                            //Elemente no tiene tag <SQL>
                        }else{
                            if($value == "null"){
                                $campos[] = "$key IS NULL";
                            }else{

                                //si no hay porcentaje
                                if(strpos($value, "%") === false){

                                    //si no hay negacion
                                    if(strpos($key, "!") === false){

                                        //usa igual
                                        $campos[] = "$key=".$value;
                                    }else{
                                        $key = str_replace("!", "", $key);

                                        //usa distinto
                                        $campos[] = "$key <> ".$value;



                                    }

                                }else{

                                    //si hay porcentaje usa like
                                    $campos[] = "$key LIKE ".$value;
                                }

                            }
                        }
                    }
                }
            }

        }
        $campos = implode($join, $campos);
        return " (" . $campos . ") ";
    }

    static public function StartTransaction($conectionName=null){
        $sql = "START TRANSACTION";
        self::execQuery($sql, false,false,$conectionName);
    }

    static public function CommitTransaction($conectionName=null){
        $sql = "COMMIT";
        self::execQuery($sql, false,false,$conectionName);
    }

    static public function RollBackTransaction($conectionName=null){
        $sql = "ROLLBACK";
        self::execQuery($sql, false,false,$conectionName);
    }

    /***
     * Genera un insert de la tabla con los datos de el searcharray
     * @param $table string
     * @param $searchArray array
     * @param string $conectionName
     * @return QueryInfo
     */
    static public function &_insert($table, $searchArray, $conectionName= null){

        //Obtiene nombre de los campos
        $def=array_keys($searchArray);

        //Para cada campo
        for ($i=0; $i < count($def); $i++) {

            //Agrega comillas
            $def[$i] = "`" . $def[$i] . "`";
        }

        //genera insert
        $sql = "INSERT INTO $table(". implode(",", $def) . ") VALUES(" . implode(",", $searchArray) . ")";

        //ejecuta
        return self::execQuery($sql, false,false,$conectionName);
    }

    /***
     * Genera un update de la tabla con los datos de el searcharray
     * @param string $table
     * @param array $searchArray
     * @param array $condicion
     * @param string $conectionName
     * @return QueryInfo
     */
    static public function &_update($table, $searchArray, $condicion, $conectionName= null){


        /** @noinspection SqlWithoutWhere */
        $sql = "UPDATE $table SET ";
        $total = count($searchArray);
        $x=0;
        foreach ($searchArray as $key => $value) {
            $sql .= "`$key` = $value";

            if($x < $total-1){

                $sql .= ", ";
                $x++;
            }
        }

        $sql .= " WHERE ";

        $sql .=  self::getSQLFilter($condicion);

        //echo $sql;
        return self::execQuery($sql, false,false,$conectionName);
    }

    /***
     * Genera un update de la tabla con los datos de el searcharray
     */
    static public function &_delete($table, $condicion, $conectionName= null){

        /** @noinspection SqlWithoutWhere */
        $sql = "DELETE FROM $table ";


        $sql .= " WHERE ";

        $sql .=  self::getSQLFilter($condicion);

        //echo $sql;
        return self::execQuery($sql, false,false,$conectionName);
    }

    /**
     * Retorna un arreglo con los nombres de los campos de la BD
     * @param array $prototype es un arreglo con los nombre de los campos de un formulario
     * @param array $map Arreglo que contiene la equivalencia de [Nombre_Campo_del_Formulario]=campo_BD
     * @param bool $map_nulls si se estrablece a true, mapea incluso nulos
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

    static protected function addPagination($sql){
        $page = intval( Handler::getRequestAttr("PAGE") );

        //agrega limit si page es un numero mayor a cero
        if($page >= 0){
            //agrega SQL_CALC_FOUND_ROWS al query
            $sql = trim($sql);
            $sql = str_replace("\n", " ", $sql);
            $exploded = explode(" ", $sql);
            $exploded[0] .= " SQL_CALC_FOUND_ROWS ";
            $sql = implode(" ", $exploded);


            $desde = ($page) * ConfigParams::$APP_DEFAULT_LIMIT_PER_PAGE;
            $sql .= " LIMIT $desde, " . ConfigParams::$APP_DEFAULT_LIMIT_PER_PAGE;
        }
        return $sql;
    }

    static protected function addOrder($sql){
        $field = Handler::getRequestAttr("FIELD");
        $asc = Handler::getRequestAttr("ASC");
        $val = null;

        //agrega SQL_CALC_FOUND_ROWS al query
        $sql = trim($sql);

        if(!is_null($field) && $field != ""){
            if(is_array($field)){
                $all_orders = array();
                foreach ($field as $order_name => $order_type) {

                    if(self::validFieldExist($order_name, $sql)){
                        $order_name = "`$order_name`";
                        $all_orders[] = $order_name . " " . $order_type;
                    }
                }
                $val = " ORDER BY " . implode(",", $all_orders);
            }else{

                if(self::validFieldExist($field, $sql)){
                    #remover orden default [ORDER BY XYZ ASD]

                    // solo acepta A o D
                    $asc = ( $asc == 'D')? 'DESC':'ASC';

                    $val = " ORDER BY $field $asc ";
                }

            }


        }

        $sql = self::embedParams($sql, "ORDER", $val);

        return $sql;
    }

    static protected  function embedParams($sql, $tag, $value){

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


    static protected function addFilters($sql){
        $filters = Handler::getRequestAttr("FILTER");
        $columns = Handler::getRequestAttr("FILTER_KEYS");

        if($filters){

            $filters = explode(" ", $filters);
            $columns = explode(",", $columns);



            $sql_filter = array();
            //echo var_dump($columns);
            //echo count($columns);
            for($x=0; $x < count($columns); $x++){
                //echo $x . " ";
                if(empty($columns[$x]) || !strpos($sql, $columns[$x])){
                    continue;
                }

                $sql_filter[] = "`" . $columns[$x] . "` LIKE '%%'";
            }
            $sql_filter = implode(" OR ", $sql_filter);
            $sql_filter = "($sql_filter)";

            $all_filters = array();
            foreach ($filters as $text) {
                $advance = explode("::", $text);

                //si son tres trextos separados por dos puntos y el primer texto esta en el query
                if(count($advance) == 3 && strpos($sql, $advance[0]) ){

                    $advance[2] = str_replace(';;', ' ', $advance[2]);

                    $val_org = $advance[2];


                    $advance[2] = "'" . self::escape($advance[2]) . "'";


                    switch ($advance[1]) {
                        case 'eq':
                            $all_filters[] = $advance[0] . " = " . $advance[2] ;
                            break;

                        case 'ne':
                            $all_filters[] = $advance[0] . " <> " . $advance[2] ;
                            break;

                        case 'lk':
                            $all_filters[] = $advance[0] . " LIKE '%" . $val_org  . "%'";
                            break;

                        case 'gt':
                            $all_filters[] = $advance[0] . " > " . $advance[2] ;
                            break;

                        case 'ge':
                            $all_filters[] = $advance[0] . " >= " . $advance[2] ;
                            break;

                        case 'lt':
                            $all_filters[] = $advance[0] . " < " . $advance[2]  ;
                            break;

                        case 'le':
                            $all_filters[] = $advance[0] . " <= " . $advance[2]  ;
                            break;

                        case 'be':
                            $advance[2] = str_replace("'", "", $advance[2]);
                            $fx = explode(",",$advance[2]);
                            if(count($fx) >= 2){
                                $all_filters[] = $advance[0] . " BETWEEN '" . $fx[0] . "' AND '" . $fx[1] ."'";
                            }
                            break;
                    }
                }else{
                    $all_filters[] = str_replace("%%", "%$text%", $sql_filter);
                }
            }
            $all_filters = implode(" AND ", $all_filters);


            $sql .= " HAVING $all_filters ";
        }

        return $sql;
    }

    static protected function addGroups($sql){
        $groups = Handler::getRequestAttr("GROUPS");


        if($groups){
            $columns = explode(",", $groups);

            $sql_groups = array();
            //echo var_dump($columns);
            for($x=0; $x < count($columns); $x++){
                //echo $x . " ";
                if(!strpos($sql, $columns[$x])){
                    continue;
                }

                $sql_groups[] = $columns[$x];
            }
            $sql_groups = implode(", ", $sql_groups);

            $sql .= " GROUP BY $sql_groups";
        }

        return $sql;
    }

    static protected function getConfigs($orderField, $asc=true, $page=-1, $limitPerPage=0, $groupDefault=null){
        //injecta en el post valores para agregar paginacion  y ordenado


        if(!Handler::getRequestAttr('PAGE')){
            Handler::setRequestAttr('FIELD',$orderField);
            Handler::setRequestAttr('ASC',$asc);
            Handler::setRequestAttr('PAGE',$page);

        }

        if($groupDefault && !Handler::getRequestAttr('GROUPS')){
            Handler::setRequestAttr('GROUPS',$groupDefault);
        }

    }



    static public function getNumFields(QueryInfo &$sumary){
        return mysqli_num_fields($sumary->result);
    }

    static public function getFieldInfo(QueryInfo &$sumary, $i){
        return mysqli_fetch_field_direct($sumary->result, $i);
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

    static public function escaoeHTML_ON(){
        self::$escapeHTML=true;
    }

    static public function escaoeHTML_OFF(){
        self::$escapeHTML=false;
    }

    static public function escape_HTML($data){

        if(self::$escapeHTML && is_array($data)){
            foreach ($data as $key => $value) {
                $data[$key] = htmlspecialchars($value, ENT_QUOTES , "UTF-8");
            }
        }

        return $data;
    }

    function resetPointer(QueryInfo &$sumary, $pos = 0){
        $status = false;

        if($sumary->allRows > 0){
            $status = mysqli_data_seek( $sumary->result , $pos);
        }
        return $status;
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

    public static function valueNOW(){
        return self::$SQL_TAG . " NOW() ";
    }

    public static function valueISNULL(){
        return self::$SQL_TAG . " IS NULL ";
    }

    public static function validFieldExist($field, $sql){
        $valid = false;

        if(strpos($sql, $field)){
            $valid = true;
        }

        return $valid;
    }

    public static function storeDebugLog($conectionName, $sql){

        if(self::$enableDebugLog){
            $sql = self::escape($sql);

            //genera insert
            /** @noinspection SqlResolve */
            $sql_ins = "INSERT INTO ".self::$debugTable."(date,exec_sql,tag) VALUES( NOW(), '$sql', '".self::$debugTAG."' )";

            @mysqli_query($conectionName, $sql_ins);
        }
    }

    public static function enableDebugLog($tag=''){
        self::$enableDebugLog = true;
        self::$debugTAG = $tag;
    }

    public static function disableDebugLog(){
        self::$enableDebugLog = FALSE;
        self::$debugTAG = "";
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

    public static function disableForeignKeyCheck($conectionName=null){
        $sql = "SET foreign_key_checks = 0";

        return self::execNoQuery($sql, $conectionName);
    }

    public static function enableForeignKeyCheck($conectionName=null){
        $sql = "SET foreign_key_checks = 1";

        return self::execNoQuery($sql, $conectionName);
    }

    /**
     * @return Connection
     */
    static function getConnectionData($conectionName= null){
        $conn = null;

        if(!$conectionName || !isset(self::$conections[$conectionName])){
            $conectionName = self::$defaultConection;
        }

        $conn = self::$conections[$conectionName];

        return $conn;
    }
}