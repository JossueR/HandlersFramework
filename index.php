<?php
namespace test_project;



require 'vendor/autoload.php';

use Handlers\components\ConfigParams;
use Handlers\components\Handler;

ConfigParams::$APP_LANG = "es";
ConfigParams::$APP_DEFAULT_HANDLER = "test";

\Handlers\data_access\SimpleDAO::connect("localhost","sys_warzone","root","");

if(!\Handlers\components\XHandler::exec(__NAMESPACE__)){
    header("location:" . ConfigParams::$APP_DEFAULT_HANDLER);

}


