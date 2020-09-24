<?php

namespace Jelix\ComposerPlugin;


class JelixPackageParameters {

    protected $modulesDirs = array();

    protected $pluginsDirs = array();

    protected $singleModuleDirs = array();

    protected $packageName = '';

    /**
     * access to setup for modules that are in vendor modules
     *
     * Only for composer.json of an application
     *
     * @var array 'package_name'=>array('module_name'=>array("__global"=> 1, "index" => 2,...))
     */
    protected $packageModulesAccess = array();

    /**
     * access to setup for modules for each known applications
     *
     * Only for composer.json of packages of modules
     * @var array 'application_name'=>array('module_name'=>array("__global"=> 1, "index" => 2,...))
     */
    protected $appModulesAccess = array();

    protected $isAppPackage = false;

    function __construct($packageName, $isAppPackage)
    {
        $this->packageName = $packageName;
        $this->isAppPackage = $isAppPackage;
    }

    function isApp()
    {
        return $this->isAppPackage;
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

    /**
     * @param  array  $list 'application_name'=>array('module_name'=>array("__global"=> 1, "index" => 2,...))
     */
    function setAppModulesAccess(array $list)
    {
        if (!$this->isAppPackage) {
            $this->appModulesAccess = $list;
        }
    }

    /**
     * @param  array  $list 'package_name'=>array('module_name'=>array("__global"=> 1, "index" => 2,...))
     */
    function setPackageModulesAccess(array $list)
    {
        if ($this->isAppPackage) {
            $this->packageModulesAccess = $list;
        }
    }

    function getPackageModulesAccess()
    {
        return $this->packageModulesAccess;
    }

    function getAppModulesAccess()
    {
        return $this->appModulesAccess;
    }

    /**
     * @param string $packageName the id of the package
     *
     * @return JelixModuleAccess[]
     */
    function getModulesAccessForPackage($packageName)
    {
        if (isset($this->packageModulesAccess[$packageName])) {
            $list = array();
            foreach($this->packageModulesAccess[$packageName] as $module => $access) {
                $list[] = new JelixModuleAccess($module, $access);
            }
            return $list;
        }
        return array();
    }

    /**
     * @param string $appName the id of the app
     *
     * @return JelixModuleAccess[]
     */
    function getModulesAccessForApp($appName)
    {
        if (!isset($this->appModulesAccess[$appName])) {
            $appName = '__any_app';
        }

        if (isset($this->appModulesAccess[$appName])) {
            $list = array();
            foreach($this->appModulesAccess[$appName] as $module => $access) {
                $list[] = new JelixModuleAccess($module, $access);
            }
            return $list;
        }
        return array();
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
