<?php

namespace Jelix\ComposerPlugin;
use Composer\Util\Filesystem;

class SetupJelix16 {

    /**
     * @var JelixParameters
     */
    protected $parameters;

    /**
     * @var Filesystem
     */
    protected $fs;

    function __construct(JelixParameters $parameters) {
        $this->parameters = $parameters;
        $this->fs = new Filesystem();
    }

    function setup() {
        $allModulesDir = $this->parameters->getAllModulesDirs();
        $allPluginsDir = $this->parameters->getAllPluginsDirs();
        $allModules = $this->parameters->getAllSingleModuleDirs();

        $appDir = $this->parameters->getAppDir();
        if (!$appDir) {
            throw new \Exception("No application directory is set in JelixParameters");
        }
        $configDir = $this->parameters->getVarConfigDir();

        // open the configuration file
        $iniFileName = $this->parameters->getConfigFileName();
        if (!$iniFileName) {
            $iniFileName = 'localconfig.ini.php';
        }
        $iniFileName= $configDir.$iniFileName;
        if (!file_exists($iniFileName)) {
            if (!file_exists($configDir)) {
                throw new \Exception('Configuration directory "'.$configDir.'" for the app does not exist');
            }
            file_put_contents($iniFileName, "<"."?php\n;die(''); ?".">\n\n");
        }
        $ini = new IniModifier($iniFileName);


        $vendorPath = $this->getFinalPath('./');

        // retrieve the current modulesPath value
        $modulesPath = $this->getCurrentModulesPath($configDir, $ini, $vendorPath);
        if (count($allModulesDir)) {
            // add all declared modules directories
            foreach($allModulesDir as $path) {
                $modulesPath[] = $this->getFinalPath($path);
            }
        }
        $ini->setValue('modulesPath', implode(',', array_unique($modulesPath)));

        // retrieve the current pluginsPath value
        $pluginsPath = $this->getCurrentPluginsPath($configDir, $ini, $vendorPath);
        if (count($allPluginsDir)) {
            // add all declared plugins directories
            foreach($allPluginsDir as $path) {
                $pluginsPath[] = $this->getFinalPath($path);
            }
        }
        $ini->setValue('pluginsPath', implode(',', array_unique($pluginsPath)));

        // erase first all "<module>.path" keys of modules that are inside a package
        foreach($ini->getValues('modules') as $key => $val) {
            if (preg_match("/\\.path$/", $key) && strpos($val, $vendorPath) === 0) {
                $ini->removeValue($key, "modules");
            }
        }
        if (count($allModules)) {
            // declare path of single modules
            foreach($allModules as $path) {
                $path = $this->fs->normalizePath($path);
                $moduleName = basename($path);

                $path = $this->getFinalPath($path);

                $ini->setValue($moduleName.'.path', $path, 'modules');
            }
        }
        $ini->save();
    }

    protected function getCurrentModulesPath($configDir, $localIni, $vendorPath) {

        $modulesPath = $localIni->getValue('modulesPath');
        if ($modulesPath == '') {
            $mainConfigIni = new IniModifier($configDir.'mainconfig.ini.php');
            $modulesPath = $mainConfigIni->getValue('modulesPath');
            if ($modulesPath == '') {
                $modulesPath = 'lib:jelix-modules/,app:modules/';
            }
        }
        $pathList = preg_split('/ *, */', $modulesPath);
        return $this->removeVendorPath($pathList, $vendorPath);
    }

    protected function getCurrentPluginsPath($configDir, $localIni, $vendorPath) {

        $pluginsPath = $localIni->getValue('pluginsPath');
        if ($pluginsPath == '') {
            $mainConfigIni = new IniModifier($configDir.'mainconfig.ini.php');
            $pluginsPath = $mainConfigIni->getValue('pluginsPath');
            if ($pluginsPath == '') {
                $pluginsPath = 'app:plugins/';
            }
        }
        $pathList = preg_split('/ *, */', $pluginsPath);
        return $this->removeVendorPath($pathList, $vendorPath);
    }

    /**
     * Remove all path that are into the vendor directory, to be sure there will
     * not have anymore path from packages that are not existing anymore.
     *
     * @param string[] $pathList
     */
    protected function removeVendorPath($pathList, $vendorPath) {
        $list = [];

        foreach ($pathList as $path) {
            if (strpos($path, $vendorPath) !== 0) {
                $list[] = rtrim($path, '/');
            }
        }
        return $list;
    }


    protected function getFinalPath($path) {
        $appDir = $this->parameters->getAppDir();
        $vendorDir = $this->parameters->getVendorDir();
        $path = $this->fs->findShortestPath($appDir, $vendorDir.$path, true);
        if ($this->fs->isAbsolutePath($path)) {
            return $path;
        }
        if (substr($path, 0,2) == './') {
            $path = substr($path, 2);
        }
        return 'app:'.$path;
    }
}
