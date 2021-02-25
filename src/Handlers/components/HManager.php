<?php


namespace Handlers\components;


class HManager
{
    protected $errors = array();

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
     * @param string $tagName
     * @param array $data
     * @return string
     */
    static function showMessage($tagName, $data= array()){
        $pattern = "/\{([\w]+)\}/";
        $tagName = strtolower($tagName);
        if(isset($_SESSION['TAG'][$tagName])){
            $tag = $_SESSION['TAG'][$tagName];

            if(count($data) > 0)
            {
                preg_match_all($pattern, $tag, $matches, PREG_OFFSET_CAPTURE);

                for($i=0; $i < count($matches[0]); $i++){
                    $foundKey = $matches[1][$i][0];

                    if(!isset($data[$foundKey]) || $data[$foundKey] === null){
                        $replaceWith = "";
                    }else{
                        $replaceWith = $data[$foundKey];
                    }

                    $tag = str_replace("{".$foundKey."}", $replaceWith, $tag);
                }
            }
            return $tag;

        }else{
            return "MISSING $tagName";
        }

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


}