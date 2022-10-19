<?php


namespace Handlers\components;


use Exception;
use Handlers\data_access\ConfigVarRepo;
use Handlers\data_access\DynamicSecurityAccessRepo;
use Handlers\data_access\LoggedUserRepo;
use Handlers\data_access\PermissionRepo;

class SecureWebHandler extends WebHandler
{

    //tiene un motor de permisos

    /**
     * SecureWebHandler constructor.
     * @throws Exception
     */
    public function __construct()
    {
        parent::__construct();


        //requiere un usuario
        $logged = LoggedUserRepo::getInstance()->isLogged();

        if(!$logged){
            throw new Exception('no logeado');
        }else{
            //TODO si esta logeado, valida si requere permiso
            ConfigVarRepo::getInstance()->clearAllVars();
            PermissionRepo::getInstance()->setUserId( LoggedUserRepo::getInstance()->getUserId());
           if(! DynamicSecurityAccessRepo::getInstance()->checkHandlerActionAccess($this) ){
               throw new Exception('requiere permiso:' . DynamicSecurityAccessRepo::getInstance()->getLastFailedPermission());
           }
        }
    }
}
