<?php


namespace Handlers\data_access;


use Handlers\components\CacheHManager;
use Handlers\models\PermissionsDAO;

class PermissionRepo extends CacheHManager
{
    /**
     * @var PermissionRepo
     */
    private static $instance;

    /**
     * @var string
     */
    private $user_id;


    /**
     * PermissionRepo constructor.
     */
    protected function __construct()
    {
        parent::__construct("USER_PERMISSIONS");
    }


    /**
     * @return PermissionRepo
     */
    public static function getInstance(): PermissionRepo
    {
        if(self::$instance == null){
            self::$instance = new PermissionRepo();
        }

        return self::$instance;
    }

    /**
     * @param string $user_id
     */
    public function setUserId( $user_id): PermissionRepo
    {
        $this->user_id = $user_id;

        $this->clearAllVars();


        return self::$instance;
    }



    public function havePermission($permission): bool
    {
        $permissionCheck = ConfigVarRepo::getInstance()->getBooleanVar("PERMISSION_CHECK");



        if($permissionCheck){
            $this->getVar($permission);
            return $this->isVarLoaded($permission);
        }else{
            return true;
        }
    }

    protected function loadUnCachedVar($key)
    {
        $dao = new PermissionsDAO();
        $all = $dao->loadPermissions($this->user_id, $key);

        return count($all) > 0;
    }
}
