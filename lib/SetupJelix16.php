<?php

namespace Jelix\ComposerPlugin;
use Composer\Util\Filesystem;

class SetupJelix16 {

    /**
     * @var JelixParameters
     */
    protected $parameters;

    function __construct(JelixParameters $parameters) {
        $this->parameters = $parameters;
    }

    function setup() {
        $appDir = $this->parameters->getAppDir();
        if (!$appDir) {
            throw new \Exception("No application directory is set in JelixParameters");
        }
        $configDir = $this->parameters->getVarConfigDir();
        $vendorDir = $this->parameters->getVendorDir();
        $fs = new Filesystem();
        $localinifile= $configDir.'localconfig.ini.php';
        if (!file_exists($localinifile)) {
            if (!file_exists($configDir)) {
                throw new \Exception("Configuration directory ".$configDir.' for the app does not exist');
            }
            file_put_contents($localinifile, "<"."?php\n;die(''); ?".">\n\n");
        }
        $ini = new IniModifier($localinifile);

        $allModulesDir = $this->parameters->getAllModulesDirs();
        if (count($allModulesDir)) {
            $modulesPath = '';
            foreach($allModulesDir as $path) {
                $path = $fs->findShortestPath($appDir, $vendorDir.$path, true);
                if ($fs->isAbsolutePath($path)) {
                    $modulesPath .= ','.$path;
                } else {
                    $modulesPath .= ',app:'.$path;
                }
            }
            $modulesPath = trim($modulesPath, ',');
            $ini->setValue('modulesPath', $modulesPath);
        }

        $allPluginsDir = $this->parameters->getAllPluginsDirs();
        if (count($allPluginsDir)) {
            $pluginsPath = '';
            foreach($allPluginsDir as $path) {
                $path = $fs->findShortestPath($appDir, $vendorDir.$path, true);
                if ($fs->isAbsolutePath($path)) {
                    $pluginsPath .= ','.$path;
                } else {
                    $pluginsPath .= ',app:'.$path;
                }
            }
            $pluginsPath = trim($pluginsPath, ',');
            $ini->setValue('pluginsPath', $pluginsPath);
        }

        $allModules = $this->parameters->getAllSingleModuleDirs();
        if (count($allModules)) {
            // erase first all "<module>.path" keys
            foreach($ini->getValues('modules') as $key => $val) {
                if (preg_match("/\\.path$/", $key)) {
                    $ini->removeValue($key, "modules");
                }
            }
            foreach($allModules as $path) {
                $path = $fs->normalizePath($path);
                $moduleName = basename($path);

                $path = $fs->findShortestPath($appDir, $vendorDir.$path, true);
                if (!$fs->isAbsolutePath($path)) {
                    $path = 'app:'.$path;
                }

                $ini->setValue($moduleName.'.path', $path, 'modules');
            }
        }
        $ini->save();
    }
}