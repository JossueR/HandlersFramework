<?php


namespace Handlers\components;


use Handlers\data_access\SimpleDAO;

class ConfigParams
{
    public static $APP_CONTENT_MAIN;
    public static $APP_CONTENT_HIDDEN;
    public static $APP_DEFAULT_LIMIT_PER_PAGE;
    public static $APP_DEFAULT_MAX_LIMIT_PER_PAGE;
    public static $PATH_ROOT;
    public static $PATH_PRIVATE;
    public static $APP_LANG;
    public static $APP_MODAL_HANDLER;
    public static $APP_CONTENT_TITLE;
    public static $CONF_PERMISSION_CHECK;
    public static $APP_DEFAULT_HANDLER;
    public static $APP_DEFAULT_SECURE_HANDLER;
    public static $APP_DEFAULT_ACTION_PARAM="do";
    public static $APP_ENABLED_LANG = array(
        "en",
        "es"
    );

    private static $raw_config;

    public static $QUERY_PARAM_FILTERS;
    public static $QUERY_PARAM_FILTER_KEYS;
    public static $QUERY_PARAM_ORDER_FIELD;
    public static $QUERY_PARAM_ORDER_TYPE;
    public static $QUERY_PARAM_PAGE;
    public static $QUERY_PARAM_CANT_BY_PAGE;

    public static function loadConfigJson($path_to_json_file){
        $raw_file = file_get_contents($path_to_json_file);

        if($raw_file){
            $json_conf = json_decode($raw_file,true);

            if(isset($json_conf["QUERY_PARAM_FILTERS"]) && $json_conf["QUERY_PARAM_FILTERS"] != ""){
                self::$QUERY_PARAM_FILTERS = $json_conf["QUERY_PARAM_FILTERS"];
            }

            if(isset($json_conf["QUERY_PARAM_FILTER_KEYS"]) && $json_conf["QUERY_PARAM_FILTER_KEYS"] != ""){
                self::$QUERY_PARAM_FILTER_KEYS = $json_conf["QUERY_PARAM_FILTER_KEYS"];
            }

            if(isset($json_conf["QUERY_PARAM_ORDER_FIELD"]) && $json_conf["QUERY_PARAM_ORDER_FIELD"] != ""){
                self::$QUERY_PARAM_ORDER_FIELD = $json_conf["QUERY_PARAM_ORDER_FIELD"];
            }

            if(isset($json_conf["QUERY_PARAM_ORDER_TYPE"]) && $json_conf["QUERY_PARAM_ORDER_TYPE"] != ""){
                self::$QUERY_PARAM_ORDER_TYPE = $json_conf["QUERY_PARAM_ORDER_TYPE"];
            }

            if(isset($json_conf["QUERY_PARAM_PAGE"]) && $json_conf["QUERY_PARAM_PAGE"] != ""){
                self::$QUERY_PARAM_PAGE = $json_conf["QUERY_PARAM_PAGE"];
            }

            if(isset($json_conf["QUERY_PARAM_CANT_BY_PAGE"]) && $json_conf["QUERY_PARAM_CANT_BY_PAGE"] != ""){
                self::$QUERY_PARAM_CANT_BY_PAGE = $json_conf["QUERY_PARAM_CANT_BY_PAGE"];
            }

            if(isset($json_conf["APP_CONTENT_BODY"]) && $json_conf["APP_CONTENT_BODY"] != ""){
                self::$APP_CONTENT_MAIN = $json_conf["APP_CONTENT_BODY"];
            }

            if(isset($json_conf["APP_DEFAULT_LIMIT_PER_PAGE"]) && $json_conf["APP_DEFAULT_LIMIT_PER_PAGE"] != ""){
                self::$APP_DEFAULT_LIMIT_PER_PAGE = $json_conf["APP_DEFAULT_LIMIT_PER_PAGE"];
            }

            if(isset($json_conf["APP_DEFAULT_MAX_LIMIT_PER_PAGE"]) && $json_conf["APP_DEFAULT_MAX_LIMIT_PER_PAGE"] != ""){
                self::$APP_DEFAULT_MAX_LIMIT_PER_PAGE = $json_conf["APP_DEFAULT_MAX_LIMIT_PER_PAGE"];
            }

            if(isset($json_conf["PATH_ROOT"]) && $json_conf["PATH_ROOT"] != ""){
                self::$PATH_ROOT = $json_conf["PATH_ROOT"];
            }

            if(isset($json_conf["PATH_PRIVATE"]) && $json_conf["PATH_PRIVATE"] != ""){
                self::$PATH_PRIVATE = $json_conf["PATH_PRIVATE"];
            }

            if(isset($json_conf["APP_LANG"]) && $json_conf["APP_LANG"] != ""){
                self::$APP_LANG = $json_conf["APP_LANG"];
            }

            if(isset($json_conf["APP_MODAL_HANDLER"]) && $json_conf["APP_MODAL_HANDLER"] != ""){
                self::$APP_MODAL_HANDLER = $json_conf["APP_MODAL_HANDLER"];
            }

            if(isset($json_conf["APP_CONTENT_TITLE"]) && $json_conf["APP_CONTENT_TITLE"] != ""){
                self::$APP_CONTENT_TITLE = $json_conf["APP_CONTENT_TITLE"];
            }

            if(isset($json_conf["CONF_PERMISSION_CHECK"]) && $json_conf["CONF_PERMISSION_CHECK"] != ""){
                self::$CONF_PERMISSION_CHECK = $json_conf["CONF_PERMISSION_CHECK"];
            }

            if(isset($json_conf["APP_DEFAULT_HANDLER"]) && $json_conf["APP_DEFAULT_HANDLER"] != ""){
                self::$APP_DEFAULT_HANDLER = $json_conf["APP_DEFAULT_HANDLER"];
            }

            if(isset($json_conf["APP_DEFAULT_ACTION_PARAM"]) && $json_conf["APP_DEFAULT_ACTION_PARAM"] != ""){
                self::$APP_DEFAULT_ACTION_PARAM = $json_conf["APP_DEFAULT_ACTION_PARAM"];
            }

            if(isset($json_conf["APP_ENABLED_LANG"]) && $json_conf["APP_ENABLED_LANG"] != ""){
                self::$APP_ENABLED_LANG = $json_conf["APP_ENABLED_LANG"];
            }

            if(isset($json_conf["APP_CONTENT_HIDDEN"]) && $json_conf["APP_CONTENT_HIDDEN"] != ""){
                self::$APP_CONTENT_HIDDEN = $json_conf["APP_CONTENT_HIDDEN"];
            }

            if(isset($json_conf["APP_DEFAULT_SECURE_HANDLER"]) && $json_conf["APP_DEFAULT_SECURE_HANDLER"] != ""){
                self::$APP_DEFAULT_SECURE_HANDLER = $json_conf["APP_DEFAULT_SECURE_HANDLER"];
            }

            if(isset($json_conf["DB"]) &&
                isset($json_conf["DB"]["server"]) &&
                isset($json_conf["DB"]["bd_name"]) &&
                isset($json_conf["DB"]["user"]) &&
                isset($json_conf["DB"]["pass"])
            ){
                SimpleDAO::connect($json_conf["DB"]["server"],$json_conf["DB"]["bd_name"],$json_conf["DB"]["user"],$json_conf["DB"]["pass"]);
            }
        }

    }

    /** Busca en un array $search_obj el valor que se encuentra en la ruta $config_path
     *
     * @param string $config_path indica la ruta para llegar al valor, debe estar separada por puntos
     * @param array $search_obj arreglo asociativo como los que devuelve json_decode(..., true)
     * @return mixed|null
     */
    public static function getConfig($config_path, $search_obj=null){
        $path_slices = explode(".", $config_path);
        $value = null;

        if($search_obj == null){
            $search_obj = self::$raw_config;
        }

        foreach ($path_slices as $path) {
            if(isset($search_obj) && isset($search_obj[$path])){
                $search_obj = $search_obj[$path];
            }else{
                $search_obj = null;
                break;
            }
        }

        if($search_obj){
            $value = $search_obj;
        }

        return $value;
    }
}