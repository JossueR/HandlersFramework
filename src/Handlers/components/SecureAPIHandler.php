<?php


namespace Handlers\components;



use Handlers\data_access\LangRepo;
use Handlers\data_access\LoggedUserRepo;
use Handlers\data_access\SimpleDAO;
use Handlers\models\ConnectionFromDAO;
use Handlers\models\PermissionsDAO;

class SecureAPIHandler extends APIHandler
{
    protected $access_token;
    protected $user;
    protected $username;
    protected $connection_data;
    private static $ip_check_mode;

    public static function setIPcheckMode($mode){
        self::$ip_check_mode = $mode;
    }


    function __construct(){
        parent::__construct();
        $this->getAccess();




        //si hay errores
        if($this->haveErrors()){
            //envia errores y termina
            $this->toJSON();
        }else{
            $this->loadSession();
            $this->setUsername();
            $this->updateLast();

        }
    }

    function getAccess(){
        $uname = $this->getRequestAttr("user");
        $token = $this->getRequestAttr(self::KEY_ACCESS_TOKEN);

        //$ip = null;
        $conn = new ConnectionFromDAO();

        //si esta habilitado el verificar ip
        if(self::$ip_check_mode){
            $ip = self::getRealIpAddr();
        }else{
            $ip = null;
        }

        //busca un token valido
        $conn->getValidToken($uname, $ip);
        $conection_data = $conn->get();

        //si hay token
        if(!$token || $conection_data["token"] != $token ||  $token == ""){

            $this->addError(self::showMessage("error_invalid_token"));

            $this->setStatus('access_denied', '400');
            //$this->addWarning($conn->getSumary()->sql);
        }else{


            $this->access_token = $conection_data["token"];
            $this->username = $conection_data["user"];
            $this->connection_data = $conection_data;

            SimpleDAO::setDataVar("USER_NAME", $this->username );
        }

    }

    private function loadSession(){




    }

    private function  setUsername(){
        LoggedUserRepo::getInstance()->setUserInfo(
            $this->connection_data["user"],
            $this->connection_data["user"],
            $this->connection_data["user"]
        );
    }

    private function updateLast(){

        //actualiza last en la coneccion
        $conn = new ConnectionFromDAO();
        $conn->updateLast(array("id" => $this->connection_data["id"]));
    }

    public function storelog($resp = null, $user=""){
        parent::storelog($resp, $this->username);
    }
}