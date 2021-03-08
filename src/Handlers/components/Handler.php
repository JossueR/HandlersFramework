<?php
    namespace Handlers\components;


    use Handlers\data_access\SimpleDAO;

    class Handler extends HManager {




        public static $SESSION;





        /**
         * Crea un objeto del tipo exctamente igual al nombre del script ejecutado
         * ejecuta el metodo con el nombre que se envie en la variable do
         * la variable do se buscara en POSt y si no se encuentra, en GET
         */
        public static function excec(){

            self::$do = self::getRequestAttr('do');
            if(!self::$do){
                self::$do = self::getRequestAttr('do',false);
            }


            self::$handler = (isset($_SERVER['REQUEST_URI']))? $_SERVER['REQUEST_URI'] : $_SERVER['PHP_SELF'];
            self::$handler = explode("?", self::$handler);
            self::$handler = self::$handler[0];
            $partes_ruta = pathinfo(self::$handler);

            $className = $partes_ruta["filename"] . self::$handlerSufix;


            if ($className != "Handler" && class_exists($className)) {
                self::$handler = $partes_ruta["filename"];

                $mi_clase = new $className();


                if(!($mi_clase instanceof ResponseHandler)){
                    //echo "aka";exit;
                    session_start();
                    $use_session=true;
                }else{
                    $use_session=false;
                }

                self::configSession($use_session);

                //si no es el login
                if(!($mi_clase instanceof UnsecureHandler) &&
                    !($mi_clase instanceof ResponseHandler)
                ){

                    if(!isset(self::$SESSION['USER_ID']) || self::$SESSION['USER_ID'] == ""){
                        self::windowReload("login");
                    }

                    SimpleDAO::setDataVar("USER_NAME", self::$SESSION['USER_NAME']);
                }

                if(method_exists($mi_clase, self::$do . self::$actionSufix)){
                    $method = self::$do . self::$actionSufix;

                    $sec = new DynamicSecurityAccess();
                    if($sec->checkHandlerActionAccess($className, self::$do . self::$actionSufix)){
                        $mi_clase->$method();
                    }else{
                        echo "no permiso: " . $sec->getFailPermission();
                    }

                }else{
                    $method = "index" . self::$actionSufix;

                    if(method_exists($mi_clase, $method)){
                        $mi_clase->$method();
                    }
                }

                exit;
            }else{
                return false;
            }
        }

        private static function configSession($use_session=true){
            self::$SESSION['USER_ID'] = "";
            self::$SESSION['USER_NAME'] ="";

            if(isset($_SESSION['USER_ID'])){
                self::$SESSION['USER_ID'] = $_SESSION['USER_ID'];
            }

            if(isset($_SESSION['USER_NAME'])){
                self::$SESSION['USER_NAME'] = $_SESSION['USER_NAME'];
            }

            if(isset($_SESSION['show_name'])){
                DynamicSecurityAccess::$show_names = $_SESSION['show_name'];
            }


            if($use_session){
                //si no se han cargado las sesiones, carga los mensajes basicos
                if(!isset($_SESSION['TAG'])){
                    $_SESSION['TAG']['login'] = "Login";
                    $_SESSION['TAG']['user'] = "User";
                    $_SESSION['TAG']['pass'] = "Pass";
                    $_SESSION['TAG']['bad_login'] = "Nombre de Usuario o Contraseña incorrecto";
                    $_SESSION['TAG']['bad_conection'] = "problemas de coneccion";
                }

                //inisializa la variable que almacenara las acciones pasadas del usuario
                if(!isset($_SESSION['HISTORY'])){
                    $_SESSION["HISTORY"] = array();
                }

                //Verifica si esta habilitado el modo depuracion, para habilitar
                if(!isset($_SESSION['SQL_SHOW'])){
                    $_SESSION['SQL_SHOW'] =  false;
                    SimpleDAO::setDataVar("SQL_SHOW", false);
                }else{
                    SimpleDAO::setDataVar("SQL_SHOW", $_SESSION['SQL_SHOW']);
                }

                if(!isset($_SESSION["fullcontrols"])){
                    $_SESSION["fullcontrols"] = false;
                }

                if(isset($_GET["fullcontrols"])){

                    switch ($_GET["fullcontrols"]) {
                        case "ON":
                            $_SESSION['fullcontrols'] =  true;
                            break;

                        default:
                            $_SESSION['fullcontrols'] =  false;
                    }
                }

                if(isset($_SESSION['LANG'])){
                    SimpleDAO::setDataVar("LANG", $_SESSION['LANG']);
                }
            }


            if(isset($_GET["sql_show"])){
                switch ($_GET["sql_show"]) {
                    case "ON":
                        $_SESSION['SQL_SHOW'] =  true;
                        SimpleDAO::setDataVar("SQL_SHOW", true);
                        break;

                    default:
                        $_SESSION['SQL_SHOW'] =  false;
                        SimpleDAO::setDataVar("SQL_SHOW", false);
                }
            }

            if(isset($_GET["show_name"])){
                switch ($_GET["show_name"]) {
                    case "ON":
                        $_SESSION['show_name'] =  true;

                        break;

                    default:
                        $_SESSION['show_name'] =  false;

                }

                DynamicSecurityAccess::$show_names = $_SESSION['show_name'];
            }

            //Carga las etiquetas de idioma
            self::loadLang(false, $use_session);
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

















    }
