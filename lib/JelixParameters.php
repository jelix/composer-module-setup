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


    /**
     * name of the configuration file taht will be modified to declare
     * module paths. Only for Jelix 1.6
     * @var string $configurationFileName
     */
    protected $configurationFileName = '';

    function __construct($vendorDir)
    {
        $this->fs = new Filesystem();
        $this->vendorDir = rtrim($vendorDir, '/').'/';
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

    function getConfigFileName() {
        return $this->configurationFileName;
    }

    function getVendorDir() {
        return $this->vendorDir;
    }

    /**
     * @param PackageInterface $package
     * @param bool $appPackage indicate if the package is a package loaded by composer (false)
     *             or if it is the application itself (true)
     *
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
                if ($this->fs->isAbsolutePath($extra['jelix']['app-dir'])) {
                    $this->appDir = $extra['jelix']['app-dir'];
                }
                else {
                    $this->appDir = realPath($packagePath.DIRECTORY_SEPARATOR.$extra['jelix']['app-dir']);
                }
                if (!$this->appDir || !file_exists($this->appDir)) {
                    throw new ReaderException("Error in composer.json of ".$package->getName().": extra/jelix/app-dir is not set or does not contain a valid path");
                }
                if (!file_exists($this->appDir.'/project.xml')) {
                    throw new ReaderException("Error in composer.json of ".$package->getName().": extra/jelix/app-dir is not a path to a Jelix application");
                }

            }
            else {
                $this->appDir = $packagePath;
                if (!file_exists($this->appDir.'/project.xml')) {
                    throw new ReaderException("The directory of the jelix application cannot be found. Indicate its path into the composer.json of the application, into an extra/jelix/app-dir parameter");
                }
            }
            $this->appDir = rtrim($this->appDir, "/")."/";

            $this->varConfigDir = $this->appDir.'var/config/';

            if (isset($extra['jelix']['var-config-dir'])) {
                if ($this->fs->isAbsolutePath($extra['jelix']['var-config-dir'])) {
                    $this->varConfigDir = $extra['jelix']['var-config-dir'];
                }
                else {
                    $this->varConfigDir = realPath($packagePath . DIRECTORY_SEPARATOR . $extra['jelix']['var-config-dir']);
                }
                if (!$this->varConfigDir || !file_exists($this->varConfigDir)) {
                    throw new ReaderException("Error in composer.json of ".$package->getName().": extra/jelix/var-config-dir is not set or does not contain a valid path");
                }
                $this->varConfigDir = rtrim($this->varConfigDir, "/")."/";
            }
            else if (!file_exists($this->varConfigDir)) {
                throw new ReaderException("The var/config directory of the jelix application cannot be found. Indicate its path into the composer.json of the application, into an extra/jelix/var-config-dir parameter");
            }

            if (isset($extra['jelix']['config-file-16'])) {
                $this->configurationFileName = $extra['jelix']['config-file-16'];
                if ($this->configurationFileName && !file_exists($this->varConfigDir.$this->configurationFileName)) {
                    throw new ReaderException("The configuration file name indicated into extra/jelix/config-file-16 does not exists into the var/config/ directory of the application");
                }
            }
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
