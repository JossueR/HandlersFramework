<?php


namespace Handlers\components;


class WebHandler extends XHandler
{

    /**
     * @var JsBridge
     */
    private static $jsBride;

    /**
     * WebHandler constructor.
     */
    public function __construct()
    {
        self::enableSession();
    }


    /**
     * @param JsBridge $jsBride
     */
    public static function setJsBride(JsBridge $jsBride)
    {
        self::$jsBride = $jsBride;
    }


    /**
     * @return JsBridge
     */
    private  static function getJsBride()
    {
        if(is_null(self::$jsBride)){
            self::$jsBride = new DefaultJsBride();
        }
        return self::$jsBride;
    }


    /**
     * @param false $script
     * @param bool $auto
     * @return string
     */
    public static function windowReload($script=false, $auto=true){
        $bridge = self::getJsBride();

        return $bridge->windowReload($script, $auto);
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
        $bridge = self::getJsBride();

        return $bridge->asyncLoad($action, $dest, $param, $noEcho, $escape, $msg);
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
        $bridge = self::getJsBride();

        return $bridge->asyncModal($action, $dest, $param, $noEcho, $escape, $msg);
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

        $bridge = self::getJsBride();

        return $bridge->syncLoad($action, $dest, $param, $noEcho, $escape);

    }

    public static function goAnchor($anchor, $autoshow=false){

        $bridge = self::getJsBride();

        return $bridge->goAnchor($anchor, $autoshow);


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

        $bridge = self::getJsBride();

        return $bridge->asyncLoadInterval($action, $dest, $param, $noEcho, $escape, $interval);

    }

    /**
     * Genera script para imprimir  js que genera los controles de una tabla
     */
    public static function showTableControls($name, $totalRows, $action, $param, $controls=null){

        $bridge = self::getJsBride();

        return $bridge->showTableControls($name, $totalRows, $action, $param, $controls);

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
            return $script . "dom_update('$action','$post','".ConfigParams::$APP_CONTENT_MAIN."')" . $script_end;
        }else{
            return false;
        }
    }

    /**
     * Genera cabezeras para imprimir en formato excel
     * @$filename Es el nombre que tendra el archivo
     */
    public function outputExcel($filename = "excel.xls"){
        header ('Content-Type: application/vnd.ms-excel');

        header ('Content-Transfer-Encoding: binary');
        header('Content-Disposition: attachment; filename='.$filename );


    }

    public static function make_link($text, $link, $show = true, $href=null, $html_params = null){
        $onclick = "";

        if(!$href){

            if($link) {
                $onclick = 'onclick="' . $link . '"';
            }

            $href = 'javascript: void(0)';
        }

        if($html_params) {
            $attrs = self::genAttribs($html_params, false);
            $link = "<a href='$href' $onclick $attrs >$text</a>";
        }

        if($show){
            echo $link;
        }
        return $link;
    }

    public static function reloadLast($auto=false){
        $total = count($_SESSION["HISTORY"]) - 1;

        if($total >= 0){

            $action = $_SESSION["HISTORY"][$total]["ACTION"] . "?" . html_entity_decode($_SESSION["HISTORY"][$total]["GET"]);

            $post = html_entity_decode($_SESSION["HISTORY"][$total]["POST"]);



            $command =   self::getJsBride()->asyncLoad($action,$post,ConfigParams::$APP_CONTENT_MAIN,!$auto);

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
     * Genera una url hacia un Handler
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

}