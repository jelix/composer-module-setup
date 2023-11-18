<?php

namespace Jelix\ComposerPlugin\PostInstall;

/**
 * Information about a package
 *
 * @internal Warning: this class should not rely on Composer classes, to be able to use
 * by other packages that need to have information about a package.
 */
class JelixPackageParameters {

    protected $modulesDirs = array();

    protected $pluginsDirs = array();

    protected $singleModuleDirs = array();

    protected $packageName = '';

    protected $isAppPackage = false;

    function __construct($packageName, $isAppPackage)
    {
        $this->packageName = $packageName;
        $this->isAppPackage = $isAppPackage;
    }

    function getPackageName()
    {
        return $this->packageName;
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
     * list of path to modules directories
     *
     * @return string[] list of path
     */
    function getModulesDirs()
    {
        return $this->modulesDirs;
    }

    /**
     * list of path to plugins directories
     *
     * @return string[] list of path
     */
    function getPluginsDirs()
    {
        return $this->pluginsDirs;
    }

    /**
     * list of path to single module directories
     *
     * @return string[] list of path
     */
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

    /**
     * @param string $vendorPath  path to the vendor directory
     * @return string[]  list of modules. key=module name, value = absolute path
     */
    function getModules($vendorPath)
    {
        $modules = array();

        foreach ($this->getModulesDirs() as $modulesDir) {
            $dir = new \DirectoryIterator($vendorPath.$modulesDir);
            foreach ($dir as $dirContent) {
                if (!$dirContent->isDot() && $dirContent->isDir() && file_exists($dirContent->getPathName().'/module.xml')) {
                    $modules[$dirContent->getFilename()] = $dirContent->getPathName();
                }
            }
        }
        foreach ($this->getSingleModuleDirs()  as $moduleDir) {
            $moduleDir = $vendorPath.$moduleDir;
            if (file_exists($moduleDir.'/module.xml')) {
                $modules[basename(rtrim($moduleDir, '/'))] = $moduleDir;
            }
        }
        return $modules;
    }
}
