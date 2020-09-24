<?php

namespace Jelix\ComposerPlugin;


class JelixModuleAccess {

    protected $moduleName;

    protected $access = array();

    /**
     * JelixModuleAccess constructor.
     *
     * @param string $moduleName
     * @param array $access array("__global"=> 1, "index" => 2,...)
     */
    function __construct($moduleName, $access)
    {
        $this->moduleName = $moduleName;
        $this->access = $access;
    }

    public function getModuleName()
    {
        return $this->moduleName;
    }

    public function getAccess()
    {
        return $this->access;
    }

}
