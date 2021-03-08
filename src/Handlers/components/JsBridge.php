<?php


namespace Handlers\components;


interface JsBridge
{
    function windowReload($script = false, $auto=true);

    /**
     *
     * Genera el javascript nesesario para hacer una llamada asincronica
     * @param $action : script que sera ejecutado. Se le agregara el PATH_ROOT
     * @param $dest : contenedor DOM donse se insertara los datos
     * @param $param : arreglo asosiativo de parametros que se enviaran al script con el metodo POST
     * @param $noEcho : Si es true retorna un string solamente con la funcion de actualizacion, sin no lo imprime por echo.
     * @return string
     */
    function asyncLoad($action, $dest, $param, $noEcho = false, $escape = true, $msg = "");

    function asyncModal($action, $dest, $param, $noEcho=false, $escape=true, $msg="");

    function syncLoad($action, $dest, $param, $noEcho=false, $escape=true);

    function goAnchor($anchor, $autoshow=false);

    function asyncLoadInterval($action, $dest, $param, $noEcho=false, $escape=true, $interval=5);

    function showTableControls($name, $totalRows, $action, $param, $controls=null,$auto=true);


}