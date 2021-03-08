<?php


namespace Handlers\data_access;


use Handlers\components\HManager;

abstract class LangRepo extends HManager
{
    private $lang;
    private $langLoaded = false;

    /**
     * @return string
     */
    public function getLang()
    {
        return $this->lang;
    }






    abstract function loadLang();

    /**
     * @param string $tag
     * @return string|null
     */
    abstract function getString($tag);

    /**
     * @param $lang
     * @param false $force
     */
    function changeLang($lang, $force = false)
    {
        if($this->lang != $lang || $force){
            $this->lang = $lang;
            $this->loadLang();
        }

    }

    /**
     * @return bool
     */
    public function isLangLoaded()
    {
        return $this->langLoaded;
    }

    /**
     * @param bool $langLoaded
     */
    protected function setLangLoaded($langLoaded=true)
    {
        $this->langLoaded = $langLoaded;
    }



}