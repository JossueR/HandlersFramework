<?php
namespace test_project;

use Handlers\components\WebHandler;


use Handlers\data_access\LoggedUserRepo;
use Handlers\data_access\PermissionRepo;

class testHandler extends WebHandler
{
    function indexAction(){
        echo "ok";
/*
        var_dump( PermissionRepo::getInstance()
            ->setUserId(2)
            ->havePermission("CONF-02") );

        var_dump( PermissionRepo::getInstance()
            ->setUserId(2)
            ->havePermission("CONF-03") );

        var_dump( PermissionRepo::getInstance()
            ->setUserId(2)
            ->havePermission("CONF-04") );
*/
        var_dump(LoggedUserRepo::getInstance()
            ->loadUserInfo(2)
            ->getUsername());

        var_dump(LoggedUserRepo::getInstance()
            ->isLogged());
    }
}