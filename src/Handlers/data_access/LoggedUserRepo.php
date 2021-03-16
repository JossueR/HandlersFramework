<?php


namespace Handlers\data_access;


class LoggedUserRepo extends \Handlers\components\CacheHManager
{
    const IDX_USERNAME = "USERNAME";
    const IDX_FULL_NAME = "FULL_NAME";
    const IDX_USER_ID = "USER_ID";

    /**
     * @var LoggedUserRepo
     */
    private static $instance;


    /**
     * PermissionRepo constructor.
     */
    protected function __construct()
    {
        parent::__construct("USER_INFO");
    }

    public function setUserInfo($user_id,$username,$full_name){

        $this->setVar(self::IDX_USERNAME, $username);
        $this->setVar(self::IDX_FULL_NAME, $full_name);
        $this->setVar(self::IDX_USER_ID, $user_id);


        return self::$instance;
    }



    public function getUsername(){

        return $this->getVar(self::IDX_USERNAME);
    }

    public function getUserId(){

        return $this->getVar(self::IDX_USER_ID);
    }


    public function getUseFullName(){
        return $this->getVar(self::IDX_FULL_NAME);
    }

    public function isLogged(){
        return $this->isVarLoaded(self::IDX_USER_ID);
    }


    /**
     * @return LoggedUserRepo
     */
    public static function getInstance(){
        if(self::$instance == null){
            self::$instance = new LoggedUserRepo();
        }

        return self::$instance;
    }

    /**
     * @inheritDoc
     */
    protected function loadUnCachedVar($key)
    {

        return null;
    }
}