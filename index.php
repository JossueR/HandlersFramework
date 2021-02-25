<?php

require 'vendor/autoload.php';

use Handlers\components\ConfigParams;
use Handlers\components\Handler;


if(!Handler::excec()){
    header("location:" . ConfigParams::$APP_DEFAULT_HANDLER);
}