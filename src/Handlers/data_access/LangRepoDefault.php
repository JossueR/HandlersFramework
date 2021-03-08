<?php


namespace Handlers\data_access;


use Handlers\models\LangDAO;

class LangRepoDefault extends LangRepo
{

    /**
     * LangRepo constructor.
     * @param $default_lang
     */
    public function __construct($default_lang)
    {

        if(self::sessionEnabled()){

            if(!isset($_SESSION["LANG"])){
                $_SESSION["LANG"] = $default_lang;
            }

        }

        $this->changeLang($default_lang);
    }

    function loadLang(){
        $dao = new LangDAO();
        $dao->getByLang($this->getLang());

        $this->removeAll();
        while($row = $dao->get() ){

            if(self::sessionEnabled()){
                $_SESSION['TAG'][strtolower($row['key'])] = $row[$this->getLang()];
            }else{
                $this->setVar(strtolower($row['key']),  $row[$this->getLang()]);
            }
        }
        $this->setLangLoaded();

    }

    function removeAll(){

        if(self::sessionEnabled()){
            unset($_SESSION['TAG']);
        }else{
            $this->clearAllVars();
        }
        $this->setLangLoaded(false);
    }

    function getString($tag){
        $string = null;

        if(!$this->isLangLoaded()){
            $this->loadLang();
        }

        if(self::sessionEnabled()){

            if(isset($_SESSION['TAG'][strtolower($tag)])){
                $string = $_SESSION['TAG'][strtolower($tag)] ;
            }

        }else{
            $string = $this->getVar($tag);
        }

        return $string;
    }
}