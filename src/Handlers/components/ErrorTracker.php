<?php


namespace Handlers\components;


class ErrorTracker extends HManager
{
    /**
     * @var ErrorTracker
     */
    private static $instance;

    /**
     * @return ErrorTracker
     */
    public static function getInstance(){
        if(self::$instance == null){
            self::$instance = new ErrorTracker();
        }

        return self::$instance;
    }


}