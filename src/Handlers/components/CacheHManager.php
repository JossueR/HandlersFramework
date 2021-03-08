<?php


namespace Handlers\components;


use Handlers\data_access\SimpleDAO;

abstract class CacheHManager extends HManager
{
    /**
     * @var string
     */
    private $session_conf_idx;

    /**
     * CacheHManager constructor.
     * @param string $session_conf_idx
     */
    protected  function __construct($session_conf_idx)
    {
        $this->session_conf_idx = $session_conf_idx;
    }

    /**
     * @return string
     */
    public function getSessionConfIdx()
    {
        return $this->session_conf_idx;
    }

    protected function isVarLoaded($var){
        $status = false;

        if(self::sessionEnabled()){
            if(isset($_SESSION[$this->session_conf_idx]) && isset($_SESSION[$this->session_conf_idx][$var])){
                $status = true;
            }
        }else{
            $status = $this->existVar($var);
        }

        return $status;
    }

    public function setVar($key, $value)
    {
        //var_dump("cargado $key");
        if(self::sessionEnabled()){
            $_SESSION[$this->session_conf_idx][$key] = $value;
        }else{
            parent::setVar($key, $value);
        }

    }

    public function getVar($key)
    {
        $value = null;

        //si no esta en cache o si se fuerza la recarga
        if(!$this->isVarLoaded($key) ){
            $value = $this->loadUnCachedVar($key);

            if($value){
                $this->setVar($key, $value);
            }
        }else{
            //var_dump("cache $key");
            if(self::sessionEnabled()){
                $value = $_SESSION[$this->session_conf_idx][$key];
            }else{
                return parent::getVar($key);
            }
        }

        return $value;
    }

    /**Obtiene una variable y la convierte en boleano si es igual a $accept
     * @param $key
     * @param string $accept
     * @return bool
     */
    public function getBooleanVar($key, $accept=null){
        $x = $this->getvar($key);
        if(!$accept){
            $accept = SimpleDAO::REG_ACTIVO_Y;
        }

        return ($x == $accept);
    }

    public function clearAllVars()
    {
        if(self::sessionEnabled()){
            unset($_SESSION[$this->session_conf_idx]);
        }else{
            parent::clearAllVars();
        }

    }

    public function getAllVars()
    {
        if(self::sessionEnabled()){
            return $_SESSION[$this->session_conf_idx];
        }else{
            return parent::getAllVars();
        }
    }


    /**
     * @param string $key
     * @return mixed
     */
    abstract protected function loadUnCachedVar($key);


}