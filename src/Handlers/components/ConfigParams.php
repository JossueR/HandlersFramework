<?php


namespace Handlers\components;


use Handlers\data_access\SimpleDAO;

class ConfigParams
{
    public static $APP_CONTENT_BODY;
    public static $APP_DEFAULT_LIMIT_PER_PAGE;
    public static $PATH_ROOT;
    public static $PATH_PRIVATE;
    public static $APP_LANG;
    public static $APP_MODAL_HANDLER;
    public static $APP_CONTENT_TITLE;
    public static $CONF_PERMISSION_CHECK;
    public static $APP_DEFAULT_HANDLER;
    public static $APP_DEFAULT_ACTION_PARAM="do";
    public static $APP_ENABLED_LANG = array(
        "en",
        "es"
    );

    public static function loadConfigJson($path_to_json_file){
        $raw_file = file_get_contents($path_to_json_file);

        if($raw_file){
            $json_conf = json_decode($raw_file,true);

            if(isset($json_conf["APP_CONTENT_BODY"]) && $json_conf["APP_CONTENT_BODY"] != ""){
                self::$APP_CONTENT_BODY = $json_conf["APP_CONTENT_BODY"];
            }

            if(isset($json_conf["APP_DEFAULT_LIMIT_PER_PAGE"]) && $json_conf["APP_DEFAULT_LIMIT_PER_PAGE"] != ""){
                self::$APP_DEFAULT_LIMIT_PER_PAGE = $json_conf["APP_DEFAULT_LIMIT_PER_PAGE"];
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
}