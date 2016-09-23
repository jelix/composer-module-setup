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
        $vendorDir = $this->parameters->getVendorDir();
        $fs = new Filesystem();
        $localinifile= $appDir.'/var/config/localconfig.ini.php';
        if (!file_exists($localinifile)) {
            if (!file_exists($appDir.'/var/config')) {
                throw new \Exception("Directory ".$appDir.'/var/config does not exist');
            }
            file_put_contents($localinifile, "<"."?php\n;die(''); ?".">\n\n");
        }
        $ini = new IniModifier($localinifile);

        $allModulesDir = $this->parameters->getAllModulesDirs();
        if (count($allModulesDir)) {
            $modulesPath = '';
            foreach($allModulesDir as $path) {
                $modulesPath .= ',app:'.$fs->findShortestPath($appDir, $vendorDir.$path);
            }
            $modulesPath = trim($modulesPath, ',');
            $ini->setValue('modulesPath', $modulesPath);
        }

        $allPluginsDir = $this->parameters->getAllPluginsDirs();
        if (count($allPluginsDir)) {
            $pluginsPath = '';
            foreach($allPluginsDir as $path) {
                $pluginsPath .= ',app:'.$fs->findShortestPath($appDir, $vendorDir.$path);
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
                $ini->setValue($moduleName.'.path', 'app:'.$fs->findShortestPath($appDir, $vendorDir.$path), 'modules');
            }
        }
        $ini->save();
    }
}