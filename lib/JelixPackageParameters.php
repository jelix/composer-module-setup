<?php

namespace Jelix\ComposerPlugin;


class JelixPackageParameters {

    protected $modulesDirs = array();

    protected $pluginsDirs = array();

    protected $singleModuleDirs = array();

    protected $packageName = '';

    function __construct($packageName)
    {
        $this->packageName = $packageName;
    }

    function setModulesDirs($list)
    {
        $this->modulesDirs = $list;
    }

    function setPluginsDirs($list)
    {
        $this->pluginsDirs = $list;
    }

    function setSingleModuleDirs($list)
    {
        $this->singleModuleDirs = $list;
    }

    function getModulesDirs()
    {
        return $this->modulesDirs;
    }

    function getPluginsDirs()
    {
        return $this->pluginsDirs;
    }

    function getSingleModuleDirs()
    {
        return $this->singleModuleDirs;
    }
    function addModulesDir($path)
    {
        $this->modulesDirs[] = $path;
    }

    function addPluginsDir($path)
    {
        $this->pluginsDirs[] = $path;
    }

    function addSingleModuleDir($path)
    {
        $this->singleModuleDirs[] = $path;
    }

}