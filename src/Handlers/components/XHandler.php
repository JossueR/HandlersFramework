<?php


namespace Handlers\components;


use Exception;
use Handlers\data_access\LangRepo;
use Handlers\data_access\LangRepoDefault;

class XHandler extends HManager
{
    /**
     * @var LangRepo
     */
    private static $langREPO;

    /**
     * @var DisplayViewEngine
     */
    private static $defaultDisplayViewEngine;
    private static $actionSufix = "Action";
    private static $handlerSufix = "Handler";
    private static $request_json;
    private static $mode_raw_request = false;

    //Almacena la accion que sera ejecutada
    public static $do;

    //almacena el nombre del script Actual
    public static $handler;

    /**
     * @param LangRepo $langREPO
     */
    public static function setLangREPO(LangRepo $langREPO)
    {
        self::$langREPO = $langREPO;
    }

    /**
     * @return LangRepo
     */
    public static function getLangREPO()
    {
        if (!self::$langREPO) {
            self::$langREPO = new LangRepoDefault(ConfigParams::$APP_LANG);
        }
        return self::$langREPO;
    }

    /**
     * @return DisplayViewEngine
     */
    public  static function getDefaultDisplayViewEngine()
    {
        if(!self::$defaultDisplayViewEngine){

            self::$defaultDisplayViewEngine = new SimpleDisplayViewEngine();
        }

        return self::$defaultDisplayViewEngine;
    }

    /**
     * @param DisplayViewEngine $defaultDisplayViewEngine
     */
    public static function setDefaultDisplayViewEngine($defaultDisplayViewEngine)
    {
        self::$defaultDisplayViewEngine = $defaultDisplayViewEngine;
    }




    public function getHandlerSufix()
    {
        return self::$handlerSufix;
    }

    public function getActionSufix()
    {
        return self::$actionSufix;
    }

    /**
     *
     *Obtiene un attributo enviado a traves de el post o el get y le aplica trim, bd_escape, htmlentities
     * @param $attr String del attributo
     * @param $post boolean true por defecto, false si se quiere buscar en GET
     * @return mixed
     */
    public static function getRequestAttr($attr, $post = true)
    {

        //si no esta habilitado el modo Raw
        if (!self::$mode_raw_request) {
            $attr = str_replace(".", "_", $attr);

            if ($post) {
                $var = $_POST;
            } else {
                $var = $_GET;
            }
        } else {
            //modo raw busca la data en el objeto ya serializado
            $var = self::$request_json;
        }


        if (isset($var[$attr])) {


            return self::trim_r($var[$attr]);

        } else {
            if ($post) {
                return self::getRequestAttr($attr, false);
            } else {
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
    public static function setRequestAttr($attr, $val, $post = true)
    {
        $attr = str_replace(".", "_", $attr);

        if (!is_array($val)) {
            $val = trim($val);
        }

        //si no esta habilitado el modo Raw
        if (!self::$mode_raw_request) {
            if ($post) {
                $_POST[$attr] = $val;
            } else {
                $_GET[$attr] = $val;
            }
        } else {
            self::$request_json[$attr] = $val;
        }
    }


    public static function enableRawRequest()
    {
        $raw = file_get_contents('php://input');
        self::$request_json = json_decode($raw, true);

        //si no puede decodificarlo
        if (!self::$request_json) {
            //usa el request
            self::$request_json = $_REQUEST;
        }

        self::$mode_raw_request = true;
    }

    public static function isRawEnabled()
    {
        return self::$mode_raw_request;
    }

    public static function getAllRequestData($post = true)
    {


        if (self::isRawEnabled()) {
            $data = self::$request_json;
        } else {
            if ($post) {
                $data = $_POST;
            } else {
                $data = $_GET;
            }
        }

        return $data;
    }

    /**
     * Obtiene el nombre del handler que se llamo en el request
     * @return string
     */
    public static function getRequestedHandlerName()
    {
        $h = (isset($_SERVER['REQUEST_URI'])) ? $_SERVER['REQUEST_URI'] : $_SERVER['PHP_SELF'];
        $h = explode("?", $h);
        $h = $h[0];
        $partes_ruta = pathinfo($h);

        return $partes_ruta["filename"];
    }

    /**
     * Obtiene el nombre del handler actual
     * @return string
     */
    public function getHandlerName()
    {
        $n = get_class($this);

        $i = strpos($n, $this->getHandlerSufix());

        if ($i !== false) {
            $n = substr($n, 0, $i);
        }

        return $n;
    }

    /***
     * Obtiene el ip desde el cual se esta ingresando
     */
    static function getRealIpAddr()
    {
        if (!empty($_SERVER['HTTP_CLIENT_IP']))   //check ip from share internet
        {
            $ip = $_SERVER['HTTP_CLIENT_IP'];
        } elseif (!empty($_SERVER['HTTP_X_FORWARDED_FOR']))   //to check ip is pass from proxy
        {
            $ip = $_SERVER['HTTP_X_FORWARDED_FOR'];
        } else {
            $ip = $_SERVER['REMOTE_ADDR'];
        }
        return $ip;
    }

    public static function sendJSON($data, $header = true, $show = true)
    {

        if ($header) {
            header('Cache-Control: no-cache, must-revalidate');
            header('Content-type: application/json');
        }


        $json = json_encode($data);

        if ($show) {
            echo $json;
        }
        return $json;
    }

    /**
     * Llena un prototipo con los valores q vienen de el post o get
     * @param array $prototype : arreglo con los datos a buscar
     * @param bool $post : indica si buscara los valores en post o get
     * @return array
     */
    public function fillPrototype($prototype, $post = true)
    {


        foreach ($prototype as $key => $default_value) {
            $prototype[$key] = $this->getRequestAttr($key, $post);

            if (is_null($prototype[$key]) && $default_value != null) {
                $prototype[$key] = $default_value;
            }
        }


        return $prototype;
    }


    /**
     * Obtiene un texto a partir de la llave y reemplaza los valores los key en $data por su valor
     * @param string $tagName
     * @param array $data
     * @return string
     */
    static function showMessage($tagName, $data= array()){
        $langRepo = self::getLangREPO();

        $pattern = "/\{([\w]+)\}/";
        $tagName = strtolower($tagName);

        $tag = $langRepo->getString($tagName);
        if($tag){


            if(count($data) > 0)
            {
                preg_match_all($pattern, $tag, $matches, PREG_OFFSET_CAPTURE);

                for($i=0; $i < count($matches[0]); $i++){
                    $foundKey = $matches[1][$i][0];

                    if(!isset($data[$foundKey]) || $data[$foundKey] === null){
                        $replaceWith = "";
                    }else{
                        $replaceWith = $data[$foundKey];
                    }

                    $tag = str_replace("{".$foundKey."}", $replaceWith, $tag);
                }
            }
            return $tag;

        }else{
            return "MISSING $tagName";
        }

    }

    /**
     * Recarga el idioma que se envivie por GET en la variable ln
     */
    public static function loadLang($force = false, $use_session=true)
    {
        $lang = self::getRequestAttr('ln',false);
        if($lang){

            if(!in_array($lang,ConfigParams::$APP_ENABLED_LANG )){
                $lang = ConfigParams::$APP_LANG;
            }

            self::getLangREPO()
                ->changeLang($lang,$force);
        }
    }

    public static function getLang(){
        return self::getLangREPO()->getLang();
    }


    public function display($script, $args=array(), $autoShow=true){
        $engine = self::getDefaultDisplayViewEngine();
        $engine->setALLVars($this->getAllVars());
        return $engine->display($script, $args, $autoShow);
    }

    public static function exec($namespace){

        $status = false;
        self::$do = self::getRequestAttr('do');
        if(!self::$do){
            self::$do = self::getRequestAttr('do',false);
        }

        self::$handler = (isset($_SERVER['REQUEST_URI']))? $_SERVER['REQUEST_URI'] : $_SERVER['PHP_SELF'];
        self::$handler = explode("?", self::$handler);
        self::$handler = self::$handler[0];
        $partes_ruta = pathinfo(self::$handler);

        $className = "\\" . $namespace . "\\" . $partes_ruta["filename"] . self::$handlerSufix;
        if ($className != "Handler" ) {
            self::$handler = $partes_ruta["filename"];

            try{
                $mi_clase = new $className();

                if(method_exists($mi_clase, self::$do . self::$actionSufix)){
                    $method = self::$do . self::$actionSufix;

                    $mi_clase->$method();
                    $status = true;
                }else{
                    $method = "index" . self::$actionSufix;

                    if(method_exists($mi_clase, $method)){
                        $mi_clase->$method();
                        $status = true;
                    }
                }
            }catch (Exception $e){
                //var_dump($e);
            }

        }

        return $status;
    }
}