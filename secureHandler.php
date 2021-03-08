<?php


namespace test_project;


use Handlers\components\SecureWebHandler;

class secureHandler extends SecureWebHandler
{
    function indexAction(){
        echo "ok sec";
        //self::destroySession();
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

                var_dump(LoggedUserRepo::getInstance()
                    ->loadUserInfo(2)
                    ->getUsername());
        */
    }
}