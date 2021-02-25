<?php


namespace Handlers\components;


use Handlers\models\SecAccessDAO;

class DynamicSecurityAccess
{
    const RULES = "RULES";
    private static $separator = "::";
    private $dao;
    private $permission;

    public static $show_names;

    function __construct() {
        if(!isset($_SESSION[self::RULES])){
            $_SESSION[self::RULES] = array();
        }

        $this->dao = new SecAccessDAO();
    }

    public static function cleanRules(){
        if(isset($_SESSION[self::RULES])){
            unset($_SESSION[self::RULES]);
        }
        $_SESSION[self::RULES] = array();
    }

    public static function havePermission($permission){
        $check = true;

        //si esta habilitada la validacion de permisos
        if(self::getPermissionCheck()){
            $check = in_array($permission, $_SESSION['USER_PERMISSIONS']);

            if(!$check){
                #para imprecion de mensajes de permiso faltante

                //echo "#####################$permission";
            }
        }


        return $check;
    }

    public static function getPermissionCheck(){
        return $_SESSION["CONF"][ConfigVarDAO::VAR_PERMISSION_CHECK];
    }

    public static function getEnableRecordSecurity(){
        $r = false;
        if(isset($_SESSION["CONF"][ConfigVarDAO::VAR_ENABLE_RECORD_SECURITY])){
            $r = $_SESSION["CONF"][ConfigVarDAO::VAR_ENABLE_RECORD_SECURITY];
        }
        return $r;
    }

    public static function getEnableHandlerActionSecurity(){
        $r = false;
        if(isset($_SESSION["CONF"][ConfigVarDAO::VAR_ENABLE_HANDLER_ACTION_SECURITY])){
            $r = $_SESSION["CONF"][ConfigVarDAO::VAR_ENABLE_HANDLER_ACTION_SECURITY];
        }
        return $r;
    }

    public static function getEnableDashSecurity(){
        return $_SESSION["CONF"][ConfigVarDAO::VAR_ENABLE_DASH_SECURITY];
    }

    public static function getEnableDashButtonSecurity(){
        return $_SESSION["CONF"][ConfigVarDAO::VAR_ENABLE_DASH_BUTTON_SECURITY];
    }



    private function loadRules($invoker, $method = null){
        //si no esta en memoria la regla
        if(!isset($_SESSION[self::RULES][$invoker])){
            //si no se espesofoco validar todo el dash
            if(!$method){
                //carga la regla a memoria
                $this->dao->getById(array("invoker"=>$invoker));
                $r = $this->dao->get();


                $this->setPermission($invoker, $r["permission"]);
            }else{
                //carga todas las reglas del dash
                $this->dao->getMethodRules($method);
                //var_dump($this->dao->getSumary()->sql);
                while ($r = $this->dao->get()) {

                    $this->setPermission($r["invoker"], $r["permission"]);
                }
            }

        }
    }

    private function check($permission){
        //siempre hay acceso por defecto
        $access = true;

        //si tiene permiso configurado
        if($permission != ""){
            //valida el permiso
            $access = self::havePermission($permission);
        }

        //si no tiene el permiso
        if(!$access){
            //registra el permiso fallido
            $this->permission = $permission;
        }else{
            //limpia el permiso
            $this->permission = null;
        }

        return $access;
    }

    public function checkHandlerActionAccess($handler, $action){
        //siempre hay acceso por defecto
        $access = true;

        //obtiene el nombre del invoker
        $invoker = $handler . self::$separator . $action;

        //si esta habilitado el registro
        if(self::getEnableRecordSecurity()){
            $this->Record($invoker);
        }

        //si esta habilitada la revision de permisos de las acciones
        if(self::getEnableHandlerActionSecurity()){

            //carga las reglas si no estan
            $this->loadRules($invoker);

            $permission = $this->getPermission($invoker);


            //valida
            $access = $this->check($permission);
        }


        return $access;
    }

    public function checkDash($method, $name){

        //siempre hay acceso por defecto
        $access = true;
        //si se envio el method
        if($method && $method != ""){
            //obtiene el nombre completo del objeto
            $invoker = $method . self::$separator . $name;

            //si esta habilitado el registro
            if(self::getEnableRecordSecurity()){
                $this->Record($invoker, $method);
            }




            //si esta activa la revision dinamica de permisos
            if(self::getEnableDashSecurity()){
                //carga las reglas si no estan
                $this->loadRules($invoker,$method);

                //obtiene el permiso
                $permission = $this->getPermission($invoker);

                $this->showInvokerName($invoker, $permission);

                //valida
                $access = $this->check($permission);
            }
        }



        return $access;
    }

    public function checkDashButton($method, $name, $btn){
        //siempre hay acceso por defecto
        $access = true;
        //si se envio el method
        if($method && $method != ""){
            //obtiene el nombre completo del objeto
            $invoker = $method . self::$separator . $name . self::$separator . $btn;

            //si esta habilitado el registro
            if(self::getEnableRecordSecurity()){
                $this->Record($invoker, $method);
            }

            //si esta activa la revision dinamica de permisos
            if(self::getEnableDashButtonSecurity()){
                //carga las reglas si no estan
                $this->loadRules($invoker,$method);

                //obtiene el permiso
                $permission = $this->getPermission($invoker);

                //valida
                $access = $this->check($permission);
            }
        }



        return $access;
    }

    private function Record($invoker, $method= null){
        if(!$method){
            $method = $invoker;
        }

        $d = array(
            "invoker" => $invoker,
            "method" => $method
        );
        //si no existe ya registrado
        if(!$this->dao->exist($this->dao->putQuoteAndNull($d))){
            $this->dao->save($d);
        }
    }

    public function getFailPermission(){
        return $this->permission;
    }

    public function getPermission($invoker){
        $permisssion = "";

        if(isset($_SESSION[self::RULES]) && isset($_SESSION[self::RULES][$invoker])){
            $permisssion = $_SESSION[self::RULES][$invoker];
        }
        return $permisssion;
    }

    private function setPermission($invoker, $permission){
        $_SESSION[self::RULES][$invoker] = $permission;
    }

    private function showInvokerName($invoker, $permission){

        if(self::$show_names == null){
            self::$show_names = false;

        }

        if(self::$show_names){

            $link = Handler::make_link("<h5><i class='fas fa-lock'></i> invoker: $invoker</h5>",
                Handler::asyncLoad("SecAccess", APP_CONTENT_BODY, array(
                    "do"=>"form",
                    "invoker"=>$invoker
                ),true),
                false
            );
            echo "<div class='col-12'>
					<div class='callout callout-danger'>
						$link
						$permission
					</div>
				</div>";
        }

    }

    /**
     * @param array $all_permissions
     */
    static function loadPermissions( $all_permissions){
        $_SESSION['USER_PERMISSIONS'] = $all_permissions;
    }
}