<?php

namespace Jelix\ComposerPlugin;
use Composer\Util\Filesystem;
use Composer\Package\PackageInterface;

class JelixParameters {

    const VERSION = 1;
    protected $packagesInfos = array();

    protected $fs;

    /**
     * @var string
     */
    protected $vendorDir;

    /**
     * @var string
     */
    protected $varConfigDir;

    /**
     * @var string
     */
    protected $appDir = null;

    function __construct($vendorDir)
    {
        $this->fs = new Filesystem();
        $this->vendorDir = $vendorDir;
    }

    function loadFromFile($filepath)
    {
        $content = json_decode(file_get_contents($filepath), true);
        if (!isset($content['packages'])) {
            return;
        }
        foreach($content['packages'] as $package => $infos) {
            $content = array_merge_recursive(array('modules-dirs'=>array(),
                                        'plugins-dirs'=>array(),
                                        'modules'=>array()),
                                    $infos);
            $parameters = new JelixPackageParameters($package);
            $parameters->setModulesDirs($content['modules-dirs']);
            $parameters->setPluginsDirs($content['plugins-dirs']);
            $parameters->setSingleModuleDirs($content['modules']);
            $this->packagesInfos[$package] = $parameters;
        }
    }

    function saveToFile($filepath)
    {
        $content = array( 'version'=>1, 'packages'=>array());
        foreach($this->packagesInfos as $package => $parameters) {
            $content['packages'][$package] = array(
                'modules-dirs'=>$parameters->getModulesDirs(),
                'plugins-dirs'=>$parameters->getPluginsDirs(),
                'modules'=>$parameters->getSingleModuleDirs()
            );
        }
        file_put_contents($filepath, json_encode($content, JSON_PRETTY_PRINT));
    }

    function getAppDir() {
        return $this->appDir;
    }

    function getVarConfigDir() {
        return $this->varConfigDir;
    }

    function getVendorDir() {
        return $this->vendorDir;
    }

    /**
     * @param PackageInterface $package
     */
    function addPackage(PackageInterface $package, $packagePath, $appPackage=false)
    {
        $parameters = new JelixPackageParameters($package->getName());
        $this->packagesInfos[$package->getName()] = $parameters;

        $extra = $package->getExtra();
        if (!isset($extra['jelix'])) {
            if ($appPackage) {
                $this->appDir = $packagePath;
            }
            return;
        }

        if ($appPackage) {
            if (isset($extra['jelix']['app-dir'])) {
                if ($fs->isAbsolutePath($extra['jelix']['app-dir'])) {
                    $this->appDir = $extra['jelix']['app-dir'];
                }
                else {
                    $this->appDir = realPath($packagePath.DIRECTORY_SEPARATOR.$extra['jelix']['app-dir']);
                }
                if (!$this->appDir || !file_exists($this->appDir)) {
                    throw new ReaderException("given application dir is not set or does not exists");
                }
            }
            else {
                $this->appDir = $packagePath;
            }
            $this->appDir = rtrim($this->appDir, "/")."/";

            if (isset($extra['jelix']['var-config-dir'])) {
                if ($fs->isAbsolutePath($extra['jelix']['var-config-dir'])) {
                    $this->varConfigDir = $extra['jelix']['var-config-dir'];
                }
                else {
                    $this->varConfigDir = realPath($packagePath . DIRECTORY_SEPARATOR . $extra['jelix']['var-config-dir']);
                }
                if (!$this->varConfigDir || !file_exists($this->varConfigDir)) {
                    throw new ReaderException("given var config dir is not set or does not exists");
                }
            }
            else {
                $this->varConfigDir = $packagePath.'/var/config';
            }
            $this->varConfigDir = rtrim($this->varConfigDir, "/")."/";
        }

        if (isset($extra['jelix']['modules-dir'])) {
            if (!is_array($extra['jelix']['modules-dir'])) {
                throw new ReaderException("Error in composer.json of ".$package->getName().": extra/jelix/modules-dir is not an array");
            }
            foreach($extra['jelix']['modules-dir'] as $path) {
                $path = realPath($packagePath.DIRECTORY_SEPARATOR.$path);
                if ($path != '') {
                    $parameters->addModulesDir($this->fs->findShortestPath($this->vendorDir, $path, true));
                }
            }
        }

        if (isset($extra['jelix']['modules'])) {
            if (!is_array($extra['jelix']['modules'])) {
                throw new ReaderException("Error in composer.json of " . $package->getName() . ": extra/jelix/modules is not an array");
            }
            foreach($extra['jelix']['modules'] as $path) {
                $path = realPath($packagePath.DIRECTORY_SEPARATOR.$path);
                if ($path != '') {
                    $parameters->addSingleModuleDir($this->fs->findShortestPath($this->vendorDir, $path, true));
                }
            }
        }

        if (isset($extra['jelix']['plugins-dir'])) {
            if (!is_array($extra['jelix']['plugins-dir'])) {
                throw new ReaderException("Error in composer.json of ".$package->getName().": extra/jelix/plugins-dir is not an array");
            }
            foreach($extra['jelix']['plugins-dir'] as $path) {
                $path = realPath($packagePath.DIRECTORY_SEPARATOR.$path);
                if ($path != '') {
                    $parameters->addPluginsDir($this->fs->findShortestPath($this->vendorDir, $path, true));
                }
            }
        }
    }

    function removePackage($packageName) {
        if(isset($this->packagesInfos[$packageName]))
        {
            unset($this->packagesInfos[$packageName]);
        }
    }

    function getPackageParameters($packageName) {
        if(isset($this->packagesInfos[$packageName]))
        {
            return $this->packagesInfos[$packageName];
        }
        return null;
    }

    function getAllModulesDirs() {
        $allModulesDir = array();
        foreach( $this->packagesInfos as $packageName => $parameters) {
            $allModulesDir = array_merge($allModulesDir, $parameters->getModulesDirs());
        }
        return $allModulesDir;
    }

    function getAllPluginsDirs() {
        $allPluginsDir = array();
        foreach( $this->packagesInfos as $packageName => $parameters) {
            $allPluginsDir = array_merge($allPluginsDir, $parameters->getPluginsDirs());
        }
        return $allPluginsDir;
    }

    function getAllSingleModuleDirs() {
        $allModules = array();
        foreach( $this->packagesInfos as $packageName => $parameters) {
            $allModules = array_merge($allModules, $parameters->getSingleModuleDirs());
        }
        return $allModules;
    }
}