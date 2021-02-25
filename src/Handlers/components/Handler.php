<?php
    namespace Handlers\components;


    use Handlers\models\SimpleDAO;

    class Handler extends HManager {



        /**
         * Almacena las variables que seran enviadas a las vistas
         */
        private $_vars;
        public static $SESSION;

        private static $actionSufix = "Action";
        private static $handlerSufix = "Handler";

        const OUTPUT_FORMAT = "OUTPUT_FORMAT";
        const FORMAT_EXCEL = "EXCEL";

        //Almacena la accion que sera ejecutada
        public static $do;

        //almacena el nombre del script Actual
        public static $handler;

        private static $LAST_UNIC;

        private static $request_json;
        private static $mode_raw_request = false;

        public function getHandlerSufix(){
            return self::$handlerSufix;
        }

        public function getActionSufix(){
            return self::$actionSufix;
        }

        /**
         *
         *Obtiene un attributo enviado a traves de el post o el get y le aplica trim, bd_escape, htmlentities
         * @param $attr String del attributo
         * @param $post boolean true por defecto, false si se quiere buscar en GET
         * @return mixed
         */
        public static function getRequestAttr($attr, $post = true){

            //si no esta habilitado el modo Raw
            if(!self::$mode_raw_request){
                $attr = str_replace(".", "_", $attr);

                if($post){
                    $var = $_POST;
                }else{
                    $var = $_GET;
                }
            }else{
                //modo raw busca la data en el objeto ya serializado
                $var = self::$request_json;
            }



            if(isset($var[$attr])){


                return self::trim_r($var[$attr]);

            }else{
                if($post){
                    return self::getRequestAttr($attr, false);
                }else{
                    return null;
                }

            }
        }

        /**
         *
         *Asigna un attributo enviado a traves de el post o el get y le aplica trim, bd_escape, htmlentities
         * @param $attr string del attributo
         * @param $val string
         * @param $post true por defecto, false si se quiere buscar en GET
         */
        public static function setRequestAttr($attr, $val, $post = true){
            $attr = str_replace(".", "_", $attr);

            if(!is_array($val)){
                $val = trim($val);
            }

            //si no esta habilitado el modo Raw
            if(!self::$mode_raw_request){
                if($post){
                    $_POST[$attr] = $val;
                }else{
                    $_GET[$attr] = $val;
                }
            }else{
                self::$request_json[$attr] = $val;
            }
        }


        public function display($script, $args=array(), $autoshoy=true){
            extract($args);

            if(!$autoshoy){
                ob_start();
            }
            /** @noinspection PhpIncludeInspection */
            include($script);

            if(!$autoshoy){
                return ob_get_clean();
            }else{
                return true;
            }
        }

        /**
         *
         * Carga variable para ser accesada en las vistas
         * @param String $key
         * @param Mixed $value
         */
        public function setVar($key, $value){
            $this->_vars[$key] = $value;
        }

        /**
         *
         * Obtiene variable registrada con setVar.
         * retorna nulo si no existe
         * @param Mixed $key
         */
        public function getVar($key){
            return (isset($this->_vars[$key]))? $this->_vars[$key] : null;
        }


        public function getAllVars(){
            return $this->_vars;
        }

        public function setALLVars($all){
            $this->_vars = $all;
        }

        /**
         * Genera cabezeras para imprimir en formato excel
         * @$filename Es el nombre que tendra el archivo
         */
        public function outputExcel($filename = "excel.xls", $html = true){
            header ('Content-Type: application/vnd.ms-excel');

            header ('Content-Transfer-Encoding: binary');
            header('Content-Disposition: attachment; filename='.$filename );

            if($html){
                echo '<html><head><meta http-equiv="content-type" content="application/vnd.ms-excel;" charset="UTF-8">
						<meta charset="UTF-8"></head>';
            }
        }

        /**
         *
         *Pone puntos suspensivos al final de cadenas cuya longitud sea mayor a $desde
         * @param $str
         * @param $desde
         * @return string
         */
        public static function resumeDesde($str, $desde=25){
            if(strlen($str) > $desde){
                $str = substr($str, 0, $desde);
                $str.= "...";
            }
            return $str;
        }

        /**
         * Envia a recrgar la pantalla por javascript
         * @$script url de la nueva pagina
         */
        public static function windowReload($script=false){
            echo "<script>";
            if($script){
                echo "window.location='$script'";
            }else{
                echo "location.reload(true)";
            }

            echo "</script>";
            exit;
        }

        /**
         *
         * Genera el javascript nesesario para hacer una llamada asincronica
         * @param $action : script que sera ejecutado. Se le agregara el PATH_ROOT
         * @param $dest : contenedor DOM donse se insertara los datos
         * @param $param : arreglo asosiativo de parametros que se enviaran al script con el metodo POST
         * @param $noEcho : Si es true retorna un string solamente con la funcion de actualizacion, sin no lo imprime por echo.
         * @return string
         */
        public static function asyncLoad($action, $dest, $param, $noEcho=false, $escape=true, $msg=""){


            //muestra el sql si se habilita el modo depuracion
            if($_SESSION['SQL_SHOW']){
                var_dump($param);
            }

            if($escape){
                $param = http_build_query($param, '', '&');
            }else{
                $p= "";
                foreach ($param as $key => $value) {
                    $p .= "$key=$value&";
                }
                $param = substr($p, 0, -1);
            }

            $msg = addslashes($msg);

            if(trim($msg) == ""){
                $comand = "dom_update('$action', '$param', '$dest')";
            }else{
                $comand = "dom_confirm('$action', '$param', '$dest', '$msg')";
            }



            if(!$noEcho){
                echo "<script>";
                echo $comand;
                echo "</script>";
                return true;
            }else{
                return $comand;
            }
        }

        /**
         *
         * Genera el javascript nesesario para hacer una llamada asincronica y cargarlo en una ventana modal
         * @param $action : script que sera ejecutado. Se le agregara el PATH_ROOT
         * @param $dest : contenedor DOM donse se insertara los datos
         * @param $param : arreglo asosiativo de parametros que se enviaran al script con el metodo POST
         * @param $noEcho : Si es true retorna un string solamente con la funcion de actualizacion, sin no lo imprime por echo.
         * @return string
         */
        public static function asyncModal($action, $dest, $param, $noEcho=false, $escape=true, $msg=""){

            $command = " $('#$dest').empty();  ";
            $command .= self::asyncLoad($action, $dest, $param,$noEcho,$escape, $msg);
            $command .= "; " . self::goAnchor(ConfigParams::$APP_MODAL_HANDLER);

            //si es false, si se quiere que se muestre automaticamente
            if(!$noEcho){
                echo "<script>" . $command . "</script>";
            }


            return $command;
        }

        /**
         *
         * Genera el javascript nesesario para hacer una llamada asincronica
         * @param $action : script que sera ejecutado. Se le agregara el PATH_ROOT
         * @param $dest : contenedor DOM donse se insertara los datos
         * @param $param : arreglo asosiativo de parametros que se enviaran al script con el metodo POST
         * @param $noEcho : Si es true retorna un string solamente con la funcion de actualizacion, sin no lo imprime por echo.
         * @return bool|string
         */
        public static function syncLoad($action, $dest, $param, $noEcho=false, $escape=true){


            //muestra el sql si se habilita el modo depuracion
            if($_SESSION['SQL_SHOW']){
                var_dump($param);
            }

            if($escape){
                $param = http_build_query($param, '', '&');
            }else{
                $p= "";
                foreach ($param as $key => $value) {
                    $p .= "$key=$value&";
                }
                $param = substr($p, 0, -1);
            }

            if($dest==""){
                $comand = "window.location.href='".ConfigParams::$PATH_ROOT."$action?$param'";
            }else{
                $comand = "window.open('".ConfigParams::$PATH_ROOT."$action?$param')";
            }




            if(!$noEcho){
                echo "<script>";
                echo $comand;
                echo "</script>";
                return true;
            }else{
                return $comand;
            }
        }

        public static function goAnchor($anchor, $autoshow=false){


            //muestra el sql si se habilita el modo depuracion
            if($_SESSION['SQL_SHOW']){
                var_dump($anchor);
            }



            $comand = "location.hash = '#$anchor'";

            if($autoshow){
                echo "<script>$comand</script>";
            }


            return $comand;

        }

        /**
         *
         * Genera el javascript nesesario para hacer una llamada asincronica
         * @param $action : script que sera ejecutado. Se le agregara el PATH_ROOT
         * @param $dest : contenedor DOM donse se insertara los datos
         * @param $param : arreglo asosiativo de parametros que se enviaran al script con el metodo POST
         * @param $noEcho : Si es true retorna un string solamente con la funcion de actualizacion, sin no lo imprime por echo.
         * @return bool|string
         */
        public static function asyncLoadInterval($action, $dest, $param, $noEcho=false, $escape=true, $interval=5){


            //muestra el sql si se habilita el modo depuracion
            if($_SESSION['SQL_SHOW']){
                var_dump($param);
            }

            if($escape){
                $param = http_build_query($param, '', '&');
            }else{
                $p= "";
                foreach ($param as $key => $value) {
                    $p .= "$key=$value&";
                }
                $param = substr($p, 0, -1);
            }

            $comand = "dom_update_refresh('$action', '$param', '$dest', '$interval')";



            if(!$noEcho){
                echo "<script>";
                echo $comand;
                echo "</script>";

                return true;
            }else{
                return $comand;
            }
        }

        /**
         * Carga el idioma
         * @param string $lang
         * @param bool $force
         * @param bool $use_session
         */
        private static function changeLang($lang, $force=false, $use_session=true)
        {
            self::$SESSION["LANG"] = $lang;
            SimpleDAO::setDataVar("LANG", $lang);
            if(!isset($_SESSION["LANG"]) || $_SESSION["LANG"] != $lang || $force){
                $_SESSION["LANG"] = $lang;

                //ejecuta el query
                $sql = "SELECT `key`, " . $lang . " FROM i18n";

                $sumary = SimpleDAO::execQuery($sql);

                unset($_SESSION['TAG']);
                //carga los datos del query
                while($bdData = SimpleDAO::getNext($sumary) ){

                    self::$SESSION['TAG'][strtolower($bdData['key'])] = $bdData[$lang];
                    $_SESSION['TAG'][strtolower($bdData['key'])] = $bdData[$lang];
                }
            }
        }

        /**
         * Recarga el idioma que se envivie por GET en la variable ln
         */
        public static function loadLang($force = false, $use_session=true)
        {
            $lang = self::getRequestAttr('ln',false);
            if($lang){



                switch ($lang) {
                    case "es":
                    case "en":

                        break;

                    default:
                        $lang = ConfigParams::$APP_LANG;
                        break;
                }

                self::changeLang($lang);
            }else{

                if(!isset($_SESSION["LANG"]) ){

                    $lang = ConfigParams::$APP_LANG;
                }else{
                    $lang = $_SESSION["LANG"];
                }

                if(!isset($_SESSION["LANG"]) || $force){

                    self::changeLang($lang, $force, $use_session);
                }
            }
        }

        public static function getRequestedHandlerName(){
            $h = (isset($_SERVER['REQUEST_URI']))? $_SERVER['REQUEST_URI'] : $_SERVER['PHP_SELF'];
            $h = explode("?", $h);
            $h = $h[0];
            $partes_ruta = pathinfo($h);

            return $partes_ruta["filename"];
        }


        /**
         * Crea un objeto del tipo exctamente igual al nombre del script ejecutado
         * ejecuta el metodo con el nombre que se envie en la variable do
         * la variable do se buscara en POSt y si no se encuentra, en GET
         */
        public static function excec(){

            self::$do = self::getRequestAttr('do');
            if(!self::$do){
                self::$do = self::getRequestAttr('do',false);
            }


            self::$handler = (isset($_SERVER['REQUEST_URI']))? $_SERVER['REQUEST_URI'] : $_SERVER['PHP_SELF'];
            self::$handler = explode("?", self::$handler);
            self::$handler = self::$handler[0];
            $partes_ruta = pathinfo(self::$handler);

            $className = $partes_ruta["filename"] . self::$handlerSufix;


            if ($className != "Handler" && class_exists($className)) {
                self::$handler = $partes_ruta["filename"];

                $mi_clase = new $className();


                if(!($mi_clase instanceof ResponseHandler)){
                    //echo "aka";exit;
                    session_start();
                    $use_session=true;
                }else{
                    $use_session=false;
                }

                self::configSession($use_session);

                //si no es el login
                if(!($mi_clase instanceof UnsecureHandler) &&
                    !($mi_clase instanceof ResponseHandler)
                ){

                    if(!isset(self::$SESSION['USER_ID']) || self::$SESSION['USER_ID'] == ""){
                        self::windowReload("login");
                    }

                    SimpleDAO::setDataVar("USER_NAME", self::$SESSION['USER_NAME']);
                }

                if(method_exists($mi_clase, self::$do . self::$actionSufix)){
                    $method = self::$do . self::$actionSufix;

                    $sec = new DynamicSecurityAccess();
                    if($sec->checkHandlerActionAccess($className, self::$do . self::$actionSufix)){
                        $mi_clase->$method();
                    }else{
                        echo "no permiso: " . $sec->getFailPermission();
                    }

                }else{
                    $method = "index" . self::$actionSufix;

                    if(method_exists($mi_clase, $method)){
                        $mi_clase->$method();
                    }
                }

                exit;
            }else{
                return false;
            }
        }

        private static function configSession($use_session=true){
            self::$SESSION['USER_ID'] = "";
            self::$SESSION['USER_NAME'] ="";

            if(isset($_SESSION['USER_ID'])){
                self::$SESSION['USER_ID'] = $_SESSION['USER_ID'];
            }

            if(isset($_SESSION['USER_NAME'])){
                self::$SESSION['USER_NAME'] = $_SESSION['USER_NAME'];
            }

            if(isset($_SESSION['show_name'])){
                DynamicSecurityAccess::$show_names = $_SESSION['show_name'];
            }


            if($use_session){
                //si no se han cargado las sesiones, carga los mensajes basicos
                if(!isset($_SESSION['TAG'])){
                    $_SESSION['TAG']['login'] = "Login";
                    $_SESSION['TAG']['user'] = "User";
                    $_SESSION['TAG']['pass'] = "Pass";
                    $_SESSION['TAG']['bad_login'] = "Nombre de Usuario o Contraseña incorrecto";
                    $_SESSION['TAG']['bad_conection'] = "problemas de coneccion";
                }

                //inisializa la variable que almacenara las acciones pasadas del usuario
                if(!isset($_SESSION['HISTORY'])){
                    $_SESSION["HISTORY"] = array();
                }

                //Verifica si esta habilitado el modo depuracion, para habilitar
                if(!isset($_SESSION['SQL_SHOW'])){
                    $_SESSION['SQL_SHOW'] =  false;
                    SimpleDAO::setDataVar("SQL_SHOW", false);
                }else{
                    SimpleDAO::setDataVar("SQL_SHOW", $_SESSION['SQL_SHOW']);
                }

                if(!isset($_SESSION["fullcontrols"])){
                    $_SESSION["fullcontrols"] = false;
                }

                if(isset($_GET["fullcontrols"])){

                    switch ($_GET["fullcontrols"]) {
                        case "ON":
                            $_SESSION['fullcontrols'] =  true;
                            break;

                        default:
                            $_SESSION['fullcontrols'] =  false;
                    }
                }

                if(isset($_SESSION['LANG'])){
                    SimpleDAO::setDataVar("LANG", $_SESSION['LANG']);
                }
            }


            if(isset($_GET["sql_show"])){
                switch ($_GET["sql_show"]) {
                    case "ON":
                        $_SESSION['SQL_SHOW'] =  true;
                        SimpleDAO::setDataVar("SQL_SHOW", true);
                        break;

                    default:
                        $_SESSION['SQL_SHOW'] =  false;
                        SimpleDAO::setDataVar("SQL_SHOW", false);
                }
            }

            if(isset($_GET["show_name"])){
                switch ($_GET["show_name"]) {
                    case "ON":
                        $_SESSION['show_name'] =  true;

                        break;

                    default:
                        $_SESSION['show_name'] =  false;

                }

                DynamicSecurityAccess::$show_names = $_SESSION['show_name'];
            }

            //Carga las etiquetas de idioma
            self::loadLang(false, $use_session);
        }

        /**
         * Genera script para imprimir funcion js que genera la pgaginacion de una tabla
         */
        public static function showPagination($name, $totalRows, $action, $param, $controls=null){

            $param = http_build_query($param, '', '&');
            $action = ConfigParams::$PATH_ROOT . $action;

            $show = array();

            if($controls){

                foreach($controls as $control){
                    $show[$control]=true;
                }
            }
            $show = json_encode($show);

            echo "<script>";
            //showPagination(totalRows,dest,accion,params, maxPerPage)
            echo "showPagination($totalRows,'$name','$action','$param', '" . ConfigParams::$APP_DEFAULT_LIMIT_PER_PAGE . "', $show) ";
            echo "</script>";

        }

        /**
         * Llena un prototipo con los valores q vienen de el post o get
         * @param $prototype: arreglo con los datos a buscar
         * @param $post: indica si buscara los valores en post o get
         */
        public function fillPrototype($prototype , $post=true){


            foreach ($prototype as $key => $default_value) {
                $prototype[$key] = $this->getRequestAttr($key, $post);

                if(is_null($prototype[$key]) && $default_value != null){
                    $prototype[$key] = $default_value;
                }
            }


            return $prototype;
        }

        static function genAttribs($data, $autoEcho = true){
            $msg = "";
            if(count($data)> 0){

                foreach ($data as $att => $val) {
                    if(is_array($val)){
                        $val = "'" . json_encode($val). "'";
                    }else{
                        $val = "\"$val\"";
                    }

                    if($autoEcho){
                        echo " $att = $val ";
                    }
                    else{
                        $msg .= " $att = $val ";;
                    }

                }
            }
            return $msg;
        }



        /**
         *
         * Hace un snapshot de la llamada del script actual
         * @param String $scriptKey
         * @param String $showText
         */
        public function registerAction($scriptKey, $showText){
            $total =count($_SESSION["HISTORY"]);
            for($i=0; $i < $total; $i++){
                if($_SESSION["HISTORY"][$i]["KEY"] == $scriptKey){
                    break;
                }
            }

            //si encuentra ya ejecutada esa accion
            if($i < $total){

                //elimina las acciones posteriores
                for($j = $total; $j > $i; $j--){
                    unset($_SESSION["HISTORY"][$j]);
                }
            }


            if($i == $total){
                $his = array();
                $his["KEY"]    = $scriptKey;
                $his["TEXT"]   = $showText;
                $his["TIME"]   = date("c");
                $his["GET"]    = http_build_query($_GET, '', '&amp;');
                $his["POST"]   = http_build_query($_POST, '', '&amp;');
                $his["ACTION"] = (isset($_SERVER['REQUEST_URI']))? $_SERVER['REQUEST_URI'] : $_SERVER['PHP_SELF'];
                $his["ACTION"] = explode("?", $his["ACTION"]);
                $his["ACTION"] = $his["ACTION"][0];
                /*
                 * self::$handler = (isset($_SERVER['REQUEST_URI']))? $_SERVER['REQUEST_URI'] : $_SERVER['PHP_SELF'];
                self::$handler = explode("?", self::$handler);
                self::$handler = self::$handler[0];
                 * */

                $_SESSION["HISTORY"][] = $his;
            }

        }

        public function clearSteps(){
            $_SESSION["HISTORY"] = array();
        }

        function historyBack($auto=false, $indexStep=1){
            $indexStep = intval($indexStep);
            $total = count($_SESSION["HISTORY"]);

            if($indexStep < $total){
                //eliminamos 1 para movernos por los indices del arreglo
                $total--;

                //si es 0 entonces regresa al inicio (indice 0)
                if($indexStep == 0){
                    $indexStep = $total;
                }

                $action = $_SESSION["HISTORY"][$total - $indexStep]["ACTION"] . "?" . $_SESSION["HISTORY"][$total - $indexStep]["GET"];
                $post = $_SESSION["HISTORY"][$total - $indexStep]["POST"];

                if($auto){
                    $script = "<script>";
                    $script_end = "</script>";
                }else{
                    $script = "";
                    $script_end = "";
                }
                return $script . "dom_update('$action','$post','".ConfigParams::$APP_CONTENT_BODY."')" . $script_end;
            }else{
                return false;
            }
        }

        public static function reloadLast($auto=false){
            $total = count($_SESSION["HISTORY"]) - 1;

            if($total >= 0){

                $action = $_SESSION["HISTORY"][$total]["ACTION"] . "?" . html_entity_decode($_SESSION["HISTORY"][$total]["GET"]);

                $post = html_entity_decode($_SESSION["HISTORY"][$total]["POST"]);

                if($auto){
                    $script = "<script>";
                    $script_end = "</script>";
                }else{
                    $script = "";
                    $script_end = "";
                }
                $command =  $script . "dom_update('$action','$post','".ConfigParams::$APP_CONTENT_BODY."')" . $script_end;

                if($auto){
                    echo $command;
                }

                return $command;
            }else{
                return false;
            }
        }

        /**
         *
         *Muestra $title en APP_CONTENT_TITLE
         * @param  $title
         */
        public function showTitle($title){
            echo "<script>";
            echo "$('#".ConfigParams::$APP_CONTENT_TITLE."').html('$title');";
            echo "</script>";
        }

        /**
         *
         * Genera el javascript nesesario para hacer una llamada asincronica
         * @param $action : script que sera ejecutado. Se le agregara el PATH_ROOT
         * @param $param : arreglo asosiativo de parametros que se enviaran al script con el metodo POST
         * @param $noEcho : Si es true retorna un string solamente con la funcion de actualizacion, sin no lo imprime por echo.
         * @return bool|string
         */
        public static function makeURL($action, $param, $noEcho=false, $escape=true){


            //muestra el sql si se habilita el modo depuracion
            if($_SESSION['SQL_SHOW']){
                var_dump($param);
            }

            if($escape){
                $param = http_build_query($param, '', '&');
            }else{
                $p= "";
                foreach ($param as $key => $value) {
                    $p .= "$key=$value&";
                }
                $param = substr($p, 0, -1);
            }


            $comand = ConfigParams::$PATH_ROOT."$action?$param";


            if(!$noEcho){
                echo $comand;
                return true;
            }else{
                return $comand;
            }
        }

        public static function havePermission($permission){
            $check = true;

            //si esta habilitada la validacion de permisos
            if(self::getPermissionCheck()){
                $check = in_array($permission, $_SESSION['USER_PERMISSIONS']);

                if(!$check){
                    #para imprecion de mensajes de permiso faltante
                    //echo "#####################$permission";
                }
            }


            return $check;
        }



        public static function getUsename(){

            return self::$SESSION["USER_NAME"];
        }

        public static function getUseFullname(){
            return $_SESSION["usuario_nombre"];
        }

        public function getUnicName(){

            do{
                $sid = microtime(true);
                $sid = str_replace(".", "", $sid);
            }while ($sid == self::$LAST_UNIC);




            self::$LAST_UNIC = $sid;

            return $sid;
        }

        public static function sendJSON($data, $header = true, $show= true){

            if($header){
                header('Cache-Control: no-cache, must-revalidate');
                header('Content-type: application/json');
            }



            $json = json_encode($data);

            if($show){
                echo $json;
            }
            return $json;
        }

        public function getHandlerName(){
            $n =	get_class($this);

            $i = strpos($n, $this->getHandlerSufix());

            if($i !== false){
                $n = substr($n, 0, $i);
            }

            return $n;
        }

        public static function getPermissionCheck(){
            return $_SESSION["CONF"][ConfigParams::$CONF_PERMISSION_CHECK];
        }

        public static function getLang(){
            return $_SESSION["LANG"];
        }

        public static function trim_r($arr)
        {
            return is_array($arr) ? array_map('self::trim_r', $arr) : trim($arr);
        }

        public static function make_link($text, $link, $show = true, $href=null, $html_params = null){
            $onclick = "";

            if(!$href){

                $onclick = 'onclick="'.$link.'"';
                $href = 'javascript: void(0)';
            }

            $attrs = self::genAttribs($html_params, false);
            $link = "<a href='$href' $onclick $attrs >$text</a>";

            if($show){
                echo $link;
            }
            return $link;
        }

        public static function enableRawRequest(){
            $raw = file_get_contents('php://input');
            self::$request_json = json_decode($raw,true);

            //si no puede decodificarlo
            if(!self::$request_json){
                //usa el request
                self::$request_json = $_REQUEST;
            }

            self::$mode_raw_request = true;
        }

        public static function isRawEnabled(){
            return self::$mode_raw_request;
        }

        public static function getAllRequestData($post = true){


            if(self::isRawEnabled()){
                $data = self::$request_json;
            }else{
                if($post){
                    $data = $_POST;
                }else{
                    $data = $_GET;
                }
            }

            return $data;
        }

        /***
         * Obtiene el ip desde el cual se esta accesando
         */
        static function getRealIpAddr()
        {
            if (!empty($_SERVER['HTTP_CLIENT_IP']))   //check ip from share internet
            {
                $ip=$_SERVER['HTTP_CLIENT_IP'];
            }
            elseif (!empty($_SERVER['HTTP_X_FORWARDED_FOR']))   //to check ip is pass from proxy
            {
                $ip=$_SERVER['HTTP_X_FORWARDED_FOR'];
            }
            else
            {
                $ip=$_SERVER['REMOTE_ADDR'];
            }
            return $ip;
        }
    }
