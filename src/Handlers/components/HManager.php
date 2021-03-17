<?php


namespace Handlers\components;


class HManager
{
    /**
     * Almacena las variables que seran enviadas a las vistas
     */
    private $_vars = array();
    private static $LAST_UNIC;
    protected $errors = array();

    public function existVar($key){
        return isset($this->_vars[$key]);
    }

    /**
     *
     * Carga variable para ser accesada en las vistas
     * @param String $key
     * @param Mixed $value
     */
    public function setVar($key, $value){
        $this->_vars[$key] = $value;
    }

    /**
     *
     * Obtiene variable registrada con setVar.
     * retorna nulo si no existe
     * @param Mixed $key
     */
    public function getVar($key){
        return (isset($this->_vars[$key]))? $this->_vars[$key] : null;
    }

    public function clearVar($key){
        if(isset($this->_vars[$key])){
            unset($this->_vars[$key]);
        }
    }


    public function getAllVars(){
        return $this->_vars;
    }

    public function setALLVars($all){
        $this->_vars = $all;
    }

    public function clearAllVars(){
        unset($this->_vars);
        $this->_vars = array();
    }

    public function haveErrors(){
        return (count($this->errors) > 0);
    }

    public function addError($msg){
        $this->errors[] = $msg;
    }

    public function getAllErrors(){
        return $this->errors;
    }

    /**
     * @param array $errors
     */
    public function addErrors( $errors){
        $this->errors = array_merge($this->errors, $errors);
    }

    public function addDbErrors($col, $errors){

        if(is_array($errors) && count($errors)>0){
            foreach ($errors as $key => $value) {

                if(!isset($col[$key])){
                    $col[$key] = $key;
                }

                switch ($value) {
                    case 'required':
                        $msg = self::showMessage("field_required", array("field"=> $col[$key]));
                        break;

                    case 'too_long':
                        $msg = self::showMessage("field_too_long", array("field"=> $col[$key]));
                        break;

                    case 'no_int':
                        $msg = self::showMessage("field_no_int", array("field"=> $col[$key]));
                        break;

                    case 'no_decimal':
                        $msg = self::showMessage("field_no_decimal", array("field"=> $col[$key]));
                        break;

                    default:
                        $msg = $value;
                }

                $this->addError($msg);

            }
        }
    }

    public function sendErrors($show = true){
        $json = array("errors"=>$this->errors);

        if($show){
            header('Cache-Control: no-cache, must-revalidate');
            header('Content-type: application/json');
            echo json_encode($json);
            exit;
        }

        return json_encode($json);
    }



    /**
     * Obtiene un texto a partir de la llave y reemplaza los valores los key en $data por su valor
     * @param string $message
     * @param array $data
     * @return string|string[]
     */
    static function buildMessage($message, $data= array()){
        $pattern = "/\{([\w]+)\}/";

        if(count($data) > 0)
        {
            preg_match_all($pattern, $message, $matches, PREG_OFFSET_CAPTURE);

            for($i=0; $i < count($matches[0]); $i++){
                $foundKey = $matches[1][$i][0];

                if(!isset($data[$foundKey]) || $data[$foundKey] === null){
                    $replaceWith = "";
                }else{
                    $replaceWith = $data[$foundKey];
                }

                $message = str_replace("{".$foundKey."}", $replaceWith, $message);
            }
        }
        return $message;
    }

    public function getUnicName(){

        do{
            $sid = microtime(true);
            $sid = str_replace(".", "", $sid);
        }while ($sid == self::$LAST_UNIC);




        self::$LAST_UNIC = $sid;

        return $sid;
    }

    public static function trim_r($arr)
    {
        return is_array($arr) ? array_map('self::trim_r', $arr) : trim($arr);
    }

    /**
     * @param array $data
     * @param bool $autoEcho
     * @return string
     */
    static function genAttribs($data, $autoEcho = true){
        $msg = "";
        if(is_array($data) && count($data)> 0){

            foreach ($data as $att => $val) {
                if(is_array($val)){
                    $val = "'" . json_encode($val). "'";
                }else{
                    $val = "\"$val\"";
                }

                if($autoEcho){
                    echo " $att = $val ";
                }
                else{
                    $msg .= " $att = $val ";;
                }

            }
        }
        return $msg;
    }

    static function sessionEnabled(){
        return session_id() != "";
    }

    static function enableSession($session_params=array()){

        if(!self::sessionEnabled()){
            session_start($session_params);
        }
    }

    static function destroySession(){
        if(self::sessionEnabled()){
            session_destroy();
            session_unset();
        }
    }
}