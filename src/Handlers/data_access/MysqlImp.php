<?php


namespace Handlers\data_access;




class MysqlImp extends BDEngine
{
    const MERGE_TYPE_HAVING = " HAVING ";
    const MERGE_TYPE_WHERE = " WHERE ";
    const MERGE_TYPE_AND = " AND ";
    const MERGE_TYPE_OR = " OR ";

    public function connect($host, $bd, $usuario, $pass)
    {
        $conn = null;

        $coneccion=mysqli_connect($host,$usuario,$pass);
        if($coneccion){
            $bdConexion=mysqli_select_db($coneccion, $bd);

            if($bdConexion){
                @mysqli_query($coneccion, "SET NAMES 'utf8'");
                @mysqli_query($coneccion, "SET time_zone = '-5:00'");

                $conn = new Connection($host,$bd,$usuario,$pass, $coneccion);

            }
        }

        return $conn;
    }



    function &execQuery(Connection $connection, $sql, $isSelect = true, QueryDynamicParams $queryparams = null)
    {
        $sumary = new QueryInfo($connection->alias_name);

        if($queryparams){
            $sql = $this->addFilters($sql, $queryparams, $connection, $queryparams->filter_marge_tag);
            $sql = $this->addOrder($sql, $queryparams);
            $sql = $this->addPagination($sql, $queryparams);


        }

        $sumary->result = @mysqli_query($connection->connection, $sql );


        if($isSelect){

            $sumary->total  = ($sumary->result)? intval(mysqli_num_rows($sumary->result)) : 0;
        }else{
            $sumary->total = mysqli_affected_rows($connection->connection);
            $sumary->new_id = mysqli_insert_id($connection->connection);
        }


        $sumary->errorNo = mysqli_errno($connection->connection);

        $sumary->error = mysqli_error($connection->connection);

        //almacena log si esta habilitado
        $this->storeDebugLog($connection, $sql);

        //almacena en el query info el ultimo sql
        $sumary->sql = $sql;

        // si hay paginacion
        if($queryparams){
            $sql = "SELECT FOUND_ROWS();";
            $rows = @mysqli_query( $connection->connection, $sql);
            $rows = mysqli_fetch_row($rows);


            $sumary->allRows = $rows[0];
        }else{
            $sumary->allRows = $sumary->total;
        }

        //muestra el sql si se habilita el modo depuracion
        if($this->verbose){
            echo $sumary->error;
        }


        return $sumary;
    }


    function storeDebugLog(Connection $conection, $last_sql)
    {
        if($this->debug_log){
            $sql = self::escape($conection, $last_sql);

            //genera insert
            /** @noinspection SqlResolve */
            $sql_ins = "INSERT INTO ".$this->debug_table."(date,exec_sql,tag) VALUES( NOW(), '$sql', '".$this->debug_tag."' )";

            @mysqli_query($conection->connection, $sql_ins);
        }
    }


    function getNext(QueryInfo $sumary)
    {

        if(!$sumary->result || !isset($sumary->total) || $sumary->total == 0){
            return null;
        }else if($sumary->inArray){

            if($sumary->inAssoc){
                $type=MYSQLI_ASSOC;
            }else{
                $type= MYSQLI_NUM;
            }

            return @mysqli_fetch_array($sumary->result, $type);
        }else{
            return @mysqli_fetch_row($sumary->result);
        }
    }


    public function escape(Connection $connection, $str)
    {
        return mysqli_real_escape_string($connection->connection, $str);
    }

    function StartTransaction(Connection $connectionName)
    {
        $sql = "START TRANSACTION";
        $summary = $this->execQuery($connectionName, $sql, false);

        $this->transaction_in_process = ($summary->error == 0);

        return $this->transaction_in_process;
    }

    function CommitTransaction(Connection $connectionName)
    {
        $sql = "COMMIT";
        $this->execQuery($connectionName, $sql, false);

        $this->transaction_in_process = false;

        return true;
    }

    function RollBackTransaction(Connection $connectionName)
    {
        $sql = "ROLLBACK";
        $this->execQuery($connectionName, $sql, false);

        $this->transaction_in_process = false;

        return true;
    }


    function &_insert($table, $searchArray, Connection $connectionName)
    {
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
        return $this->execQuery($connectionName, $sql, false);
    }

    function &_update($table, $searchArray, $condition, Connection $connectionName, $noToshTag)
    {
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

        $sql .=  $this->getSQLFilter($condition,"AND", $noToshTag);

        //ejecuta
        return $this->execQuery($connectionName, $sql, false);
    }

    function &_delete($table, $condition, $connectionName, $noToshTag)
    {
        /** @noinspection SqlWithoutWhere */
        $sql = "DELETE FROM $table ";


        $sql .= " WHERE ";

        $sql .=  $this->getSQLFilter($condition,"AND", $noToshTag);

        //ejecuta
        return $this->execQuery($connectionName, $sql, false);
    }

    function getSQLFilter($filterArray, $join, $noToshTag)
    {
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
                        if(substr_count($value, $noToshTag) > 0){


                            //Elimina el tag <SQL>
                            $value = str_replace($noToshTag, "", $value);

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
        $campos = implode(" " . $join . " ", $campos);
        return " (" . $campos . ") ";
    }


    function valueNOW($noToshTag)
    {
        return $noToshTag . " NOW() ";
    }

    function valueISNULL($noToshTag)
    {
        return $noToshTag . " IS NULL ";
    }

    function disableForeignKeyCheck(Connection $connectionName)
    {
        $sql = "SET foreign_key_checks = 0";

        //ejecuta
        return $this->execQuery($connectionName, $sql, false);
    }

    function enableForeignKeyCheck(Connection $connectionName)
    {
        $sql = "SET foreign_key_checks = 1";

        //ejecuta
        return $this->execQuery($connectionName, $sql, false);
    }

    function resetPointer(QueryInfo &$summary, $pos = 0)
    {
        $status = false;

        if($summary->allRows > 0){
            $status = mysqli_data_seek( $summary->result , $pos);
        }
        return $status;
    }

    function getNumFields(QueryInfo &$sumary)
    {
        return mysqli_num_fields($sumary->result);
    }

    function getFieldInfo(QueryInfo &$sumary, $i)
    {
        return mysqli_fetch_field_direct($sumary->result, $i);
    }

    function addPagination($sql, QueryDynamicParams $params)
    {
        if($params != null) {
            $page = intval($params->getPage());

            //agrega limit si page es un numero mayor a cero
            if ($params->isEnablePaging() && $page >= 0) {
                //agrega SQL_CALC_FOUND_ROWS al query
                $sql = trim($sql);
                $sql = str_replace("\n", " ", $sql);
                $exploded = explode(" ", $sql);
                $exploded[0] .= " SQL_CALC_FOUND_ROWS ";
                $sql = implode(" ", $exploded);


                $desde = ($page) * $params->getCantByPage();
                $sql .= " LIMIT $desde, " .$params->getCantByPage();
            }
        }
        return $sql;
    }

    function addOrder($sql, QueryDynamicParams $params)
    {
        if($params) {
            $fields = $params->getOrderFields();

            $val = null;

            //agrega SQL_CALC_FOUND_ROWS al query
            $sql = trim($sql);

            if (!is_null($fields) && $fields != "") {
                if (is_array($fields) && count($fields) > 0) {
                    $all_orders = array();
                    foreach ($fields as $order_name => $order_type) {
                        if($order_type){
                            $order_type = "ASC";
                        }else{
                            $order_type = "DESC";
                        }

                        //if (self::validFieldExist($order_name, $sql)) {
                            $order_name = "`$order_name`";
                            $all_orders[] = $order_name . " " . $order_type;
                        //}
                    }
                    $val = " ORDER BY " . implode(",", $all_orders);
                }


            }

            $sql = SimpleDAO::embedParams($sql, "ORDER", $val);
        }
        return $sql;
    }

    function addFilters($sql, QueryDynamicParams $params,Connection $connectionName, $mergeTag)
    {
        if($params) {
            if(!$mergeTag){
                $mergeTag = self::MERGE_TYPE_HAVING;
            }

            $filters = $params->filters;
            $columns = $params->filter_keys;

            if ($filters) {

                $filters = explode(" ", $filters);
                $columns = explode(",", $columns);


                $sql_filter = array();

                for ($x = 0; $x < count($columns); $x++) {
                    //echo $x . " ";
                    if (empty($columns[$x]) || !strpos($sql, $columns[$x])) {
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
                    if (count($advance) == 3 && strpos($sql, $advance[0])) {

                        $advance[2] = str_replace(';;', ' ', $advance[2]);

                        $val_org = $advance[2];


                        $advance[2] = "'" . $this->escape($connectionName, $advance[2]) . "'";


                        switch ($advance[1]) {
                            case 'eq':
                                $all_filters[] = $advance[0] . " = " . $advance[2];
                                break;

                            case 'ne':
                                $all_filters[] = $advance[0] . " <> " . $advance[2];
                                break;

                            case 'lk':
                                $all_filters[] = $advance[0] . " LIKE '%" . $val_org . "%'";
                                break;

                            case 'gt':
                                $all_filters[] = $advance[0] . " > " . $advance[2];
                                break;

                            case 'ge':
                                $all_filters[] = $advance[0] . " >= " . $advance[2];
                                break;

                            case 'lt':
                                $all_filters[] = $advance[0] . " < " . $advance[2];
                                break;

                            case 'le':
                                $all_filters[] = $advance[0] . " <= " . $advance[2];
                                break;

                            case 'be':
                                $advance[2] = str_replace("'", "", $advance[2]);
                                $fx = explode(",", $advance[2]);
                                if (count($fx) >= 2) {
                                    $all_filters[] = $advance[0] . " BETWEEN '" . $fx[0] . "' AND '" . $fx[1] . "'";
                                }
                                break;
                        }
                    } else {
                        $all_filters[] = str_replace("%%", "%$text%", $sql_filter);
                    }
                }
                $all_filters = implode(" AND ", $all_filters);

                if( strlen(trim($all_filters)) > 2) {
                    $sql .= " " . $mergeTag . " " . $all_filters;
                }
            }
        }
        return $sql;
    }

    function existBy($table, $searchArray, Connection $connectionName, $noToshTag)
    {
        $sql = "SELECT COUNT(*) FROM " .
            $table .
            " WHERE " .
            $this->getSQLFilter($searchArray, self::MERGE_TYPE_AND, $noToshTag);

       $summary = $this->execQuery($connectionName,$sql,true);
        $row = $this->getNext($summary);

        if($row){
            $val = reset($row);
        }else{
            $val = 0;
        }


        return $val > 0;
    }
}