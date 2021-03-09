<?php


namespace Handlers\data_access;


use Handlers\components\XHandler;
use Handlers\models\SecAccessDAO;

class DynamicSecurityAccessRepo extends \Handlers\components\CacheHManager
{
    /**
     * @var DynamicSecurityAccessRepo
     */
    private static $instance;

    /**
     * @var string
     */
    private $lastFailedPermission;

    /**
     * @return DynamicSecurityAccessRepo
     */
    public static function getInstance(){
        if(self::$instance == null){
            self::$instance = new DynamicSecurityAccessRepo();
        }

        return self::$instance;
    }

    /**
     * @return string
     */
    public function getLastFailedPermission()
    {
        return $this->lastFailedPermission;
    }



    /**
     * DynamicSecurityAccessRepo constructor.
     */
    public function __construct()
    {
        parent::__construct("RULES");
    }


    /**
     * @inheritDoc
     */
    protected function loadUnCachedVar($key)
    {
        $dao = new SecAccessDAO();
        $dao->getById(array("invoker"=>$key));

        $r = $dao->get();

        return $r["permission"];
    }

    public function checkHandlerActionAccess(XHandler $handler){
        $status = true;

        $class_name_invoker = get_class($handler);
        $this->Record($class_name_invoker);

        //SI esta habilitado la revicion de permisos

        if($this->isEnableHandlerActionSecurity()){
            //var_dump("Verificacion habilitada");
            $permission = $this->getVar($class_name_invoker);

            if($permission){
                $status = PermissionRepo::getInstance()->havePermission($permission);

                if(!$status){
                    $this->lastFailedPermission = $permission;
                }
            }
        }


        return $status;
    }

    private function Record($invoker){

        if($this->isEnableRecordSecurity()) {
            $dao = new SecAccessDAO();

            $d = array(
                "invoker" => $invoker,
                "method" => ".",
            );


            //si no existe ya registrado
            if (!$dao->exist($d)) {
                $dao->save($d);
            }
        }
    }

    public function isEnableHandlerActionSecurity(){
        return ConfigVarRepo::getInstance()->getBooleanVar("ENABLE_HANDLER_ACTION_SECURITY");
    }

    function isEnableRecordSecurity(){
        return ConfigVarRepo::getInstance()->getBooleanVar("ENABLE_RECORD_SECURITY");
    }
}