<?php


namespace Handlers\components;


use Handlers\data_access\SimpleDAO;

use Handlers\models\TrackLogDAO;
use SimpleXMLElement;

class APIHandler extends XHandler
{
    const KEY_STATUS = "status";
    const KEY_STATUS_CODE = "status_code";
    const KEY_ACCESS_TOKEN = "token";
    const KEY_CLIENT_TOKEN = "client_id";
    const KEY_ERRORS = "errors";
    const KEY_WARNING = "warning";
    protected $warning;
    private $status_added;
    private static $log_id;
    private static $log_enabled;

    public static function setLogEnabled($mode){
        self::$log_enabled = $mode;
    }

    function __construct(){
        $this->status_added = false;
        SimpleDAO::escapeHTML_OFF();
        $this->configErrorHandler();

        //si no hay id de log, osea si es el primer llamado
        $this->storelog();
    }

    function toJSON($send = true, $headers = true){


        if($send && $headers){
            header('Cache-Control: no-cache, must-revalidate');
            header('Content-type: application/json');
        }

        $this->setGlobalWarning();
        $this->sendWarning();

        //si no se ha puesto el status
        if(!$this->status_added){
            $this->addStatus();
        }

        $json = json_encode($this->getAllVars());

        if($send){
            //$this->cleanSession();
            $this->storelog($json);
            echo $json;

            exit;
        }

        return $json;

    }

    function toXML($root = "<root/>", $send = true, $headers = true){


        $data = $this->getAllVars();
        $xml = new SimpleXMLElement($root);
        foreach ($data as $key => $value) {
            if(!is_array($value)){
                $xml->addChild($key, $value);
            }
        }


        $resmonse_xml = $xml->asXML();

        if($headers){
            header('Cache-Control: no-cache, must-revalidate');
            header("Content-type: text/xml");
        }

        if($send){
            $json = json_encode($this->getAllVars());
            $this->storelog($json);
            echo $resmonse_xml;
            exit;
        }
        return $resmonse_xml;
    }

    protected function success(){
        $this->setVar(APIHandler::KEY_STATUS, 'success');
        $this->setVar(APIHandler::KEY_STATUS_CODE, '100');
        $this->status_added = true;
    }

    protected function serverError($errorCode = '500'){
        $this->setVar(APIHandler::KEY_STATUS, 'server_error');
        $this->setVar(APIHandler::KEY_STATUS_CODE, $errorCode);

        $this->setVar(APIHandler::KEY_ERRORS,  $this->errors);
        $this->status_added = true;
    }

    private function sendWarning(){
        if($this->warning != null && count($this->warning) > 0){
            $this->setVar(APIHandler::KEY_WARNING,  $this->warning);
        }
    }

    public function addWarning($msg){
        $this->warning[] = $msg;
    }

    protected function addStatus(){
        //si hay errores
        if($this->haveErrors()){
            $this->serverError();
        }else{
            $this->success();
        }
    }

    protected function cleanSession(){
        if (ini_get("session.use_cookies")) {
            $params = session_get_cookie_params();
            setcookie(session_name(), '', time() - 42000,
                $params["path"], $params["domain"],
                $params["secure"], $params["httponly"]
            );
        }
        session_destroy();
        session_unset();
    }

    public function storelog($resp = null, $user=""){

        //si esta habilitado el modo log
        if(self::$log_enabled){
            $log = new TrackLogDAO();

            $log_record = array(
                "user"=>$user,
                "ip"=>self::getRealIpAddr(),
                "get"=>json_encode(self::getAllRequestData(false)),
                "post"=>json_encode(self::getAllRequestData()),
                "resp"=>$resp,
                "_handler" => self::$handler,
                "_do" => self::$do
            );

            //agrega id si es una edicion
            if($resp){
                $log_record["id"] = self::$log_id;
            }

            //guarda el log
            if($log->save($log_record)){

                //si hay un id nuevo
                if($resp){

                    //almacena
                    self::$log_id = $log->getNewID();
                }
            }
        }

    }

    protected function setStatus($status, $code){
        $this->setVar(APIHandler::KEY_STATUS, $status);
        $this->setVar(APIHandler::KEY_STATUS_CODE, $code);


        $this->status_added = true;
    }

    private function configErrorHandler(){
        set_error_handler(function($errno, $errstr, $errfile, $errline, $errcontext=null) {
            ErrorTracker::getInstance()->addError("$errno, $errstr, $errfile, $errline");

        });
    }

    private function setGlobalWarning(){
        $all_errors = ErrorTracker::getInstance()->getAllErrors();
        if(is_array($all_errors) && count($all_errors) > 0){
            foreach ($all_errors as $key => $msg) {
                $this->warning[] = $msg;
            }
        }
    }
}
