<?php
namespace test_project;

use Handlers\components\APIHandler;
use Handlers\components\WebHandler;


use Handlers\data_access\LoggedUserRepo;
use Handlers\data_access\PermissionRepo;

class testHandler extends APIHandler
{
    function indexAction(){
        self::setVar("st","ok");

        $this->toJSON();
    }

    function resetAction(){
        self::destroySession();
        echo "reset ok";
    }
}