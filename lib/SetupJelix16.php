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


    /**
     * list of entrypoint configuration
     *
     * @var IniModifier[]
     */
    protected $entryPoints = array();

    /**
     * @var string
     */
    protected $appId = '';

    function __construct(JelixParameters $parameters) {
        $this->parameters = $parameters;
        $this->fs = new Filesystem();
    }

    /**
     * Update the configuration of the application according to informations
     * readed from all composer.json
     *
     * Warning: during upgrade of composer-module-setup, it seems Composer load some classes
     * of the previous version (here ModuleSetup + JelixParameters), and load
     * other classes (here SetupJelix16) after the upgrade. so API is not the one we expected.
     * so we should check if the new methods of JelixParameters are there before
     * using them.
     *
     * @throws \Exception
     */
    function setup() {
        $allModulesDir = $this->parameters->getAllModulesDirs();
        $allPluginsDir = $this->parameters->getAllPluginsDirs();
        $allModules = $this->parameters->getAllSingleModuleDirs();

        $appDir = $this->parameters->getAppDir();
        if (!$appDir) {
            throw new \Exception("No application directory is set in JelixParameters");
        }

        $this->readProjectXml();
        $ini = $this->loadConfigFile();
        $configDir = $this->parameters->getVarConfigDir();

        $vendorPath = $this->getFinalPath('./');

        // retrieve the current modulesPath value
        $modulesPath = $this->getCurrentModulesPath($configDir, $ini, $vendorPath);
        if (count($allModulesDir)) {
            // add all declared modules directories
            foreach($allModulesDir as $path) {
                $modulesPath[] = $this->getFinalPath($path);
            }
        }
        $modulesPath =  implode(',', array_unique($modulesPath));
        if ($ini->getValue('modulesPath') != $modulesPath) {
            $ini->setValue('modulesPath', $modulesPath);
        }

        // retrieve the current pluginsPath value
        $pluginsPath = $this->getCurrentPluginsPath($configDir, $ini, $vendorPath);
        if (count($allPluginsDir)) {
            // add all declared plugins directories
            foreach($allPluginsDir as $path) {
                $pluginsPath[] = $this->getFinalPath($path);
            }
        }
        $pluginsPath = implode(',', array_unique($pluginsPath));
        if ($ini->getValue('pluginsPath') != $pluginsPath) {
            $ini->setValue('pluginsPath', $pluginsPath);
        }

        $modulePathToRemove = array();
        foreach($ini->getValues('modules') as $key => $val) {
            if (preg_match("/\\.path$/", $key) && strpos($val, $vendorPath) === 0) {
                $modulePathToRemove[$key] = $val;
            }
        }

        if (count($allModules)) {
            // declare path of single modules
            foreach($allModules as $path) {
                $path = $this->fs->normalizePath($path);
                $moduleName = basename($path);

                $path = $this->getFinalPath($path);

                if ($ini->getValue($moduleName.'.path', 'modules') != $path) {
                    $ini->setValue($moduleName.'.path', $path, 'modules');
                }

                if (isset($modulePathToRemove[$moduleName.'.path'])) {
                    unset($modulePathToRemove[$moduleName.'.path']);
                }
            }
        }

        // erase all "<module>.path" keys of modules that are not inside a package anymore
        foreach ($modulePathToRemove as $key => $path) {
            $ini->removeValue($key, 'modules');
        }

        $this->setupModuleAccess($ini);

        $ini->save();
        foreach($this->entryPoints as $epIni) {
            $epIni->save();
        }
    }

    /**
     * @return IniModifier
     * @throws \Exception
     */
    protected function loadConfigFile()
    {
        $configDir = $this->parameters->getVarConfigDir();

        // open the configuration file
        if (method_exists($this->parameters, 'getConfigFileName')) {
            $iniFileName = $this->parameters->getConfigFileName();
        }
        else {
            $iniFileName = 'localconfig.ini.php';
        }
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
        return $ini;
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

    protected function readProjectXml()
    {
        $appDir = $this->parameters->getAppDir();
        $configDir = $this->parameters->getVarConfigDir();

        $this->entryPoints = array();
        $xml = simplexml_load_file($appDir.'/project.xml');
        // read all entry points data
        foreach ($xml->entrypoints->entry as $entrypoint) {
            $file                     = (string)$entrypoint['file'];
            $configFile               = (string)$entrypoint['config'];
            $file                     = str_replace('.php', '', $file);
            $this->entryPoints[$file] = new IniModifier(
                $configDir . $configFile
            );
        }
        $this->appId = (string) $xml->info['id'];
    }

    protected function setupModuleAccess(IniModifier $localIni)
    {
        if (!method_exists($this->parameters, 'getPackages')) {
            return;
        }

        $appPackage = $this->parameters->getApplicationPackage();

        foreach($this->parameters->getPackages() as $packageName => $package)
        {
            if (!method_exists($package, 'isApp')) {
                continue;
            }
            if ($package->isApp()) {
                continue;
            }
            // let's see if the application defines configuration of entrypoint
            // for the package
            $modulesAccess = $appPackage->getModulesAccessForPackage($packageName);
            if (count($modulesAccess) == 0) {
                // no, so let's retrieve entrypoint configuration from the
                // package
                $modulesAccess = $package->getModulesAccessForApp($this->appId);
            }
            if (count($modulesAccess) == 0) {
                // no entrypoint configuration for the package, let's ignore it
                continue;
            }

            foreach ($modulesAccess as $module=>$access) {
                foreach($access as $ep => $accessValue) {
                    if ($ep == '__global') {
                        $localIni->setValue($module.'.access', $accessValue, 'modules');
                    }
                    else {
                        $ep = str_replace('.php', '', $ep);
                        if (isset($this->entryPoints[$ep])) {
                            $this->entryPoints[$ep]->setValue($module.'.access', $accessValue, 'modules');
                        }
                    }
                }
            }
        }
    }
}
