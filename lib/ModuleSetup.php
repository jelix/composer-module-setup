<?php

namespace Jelix\ComposerPlugin;

use Composer\Composer;
use Composer\EventDispatcher\EventSubscriberInterface;
use Composer\IO\IOInterface;
use Composer\Plugin\PluginInterface;
use Composer\Plugin\PluginEvents;
use Composer\Script\ScriptEvents;
use Composer\Installer\PackageEvent;
use Composer\Util\Filesystem;

class ModuleSetup  implements PluginInterface, EventSubscriberInterface {
    
    const VERSION = 1;
    protected $composer;
    protected $io;
    protected $vendorDir;
    protected $jsonInfosFile;
    protected $fs;

    protected $moduleInfos = array();

    public function activate(Composer $composer, IOInterface $io)
    {
        $this->composer = $composer;
        $this->io = $io;
        $this->vendorDir = $this->composer->getConfig()->get('vendor-dir').DIRECTORY_SEPARATOR;
        $this->fs = new Filesystem();
        $this->jsonInfosFile = $this->vendorDir.'jelix_modules_infos.json';
        if (file_exists($this->jsonInfosFile)) {
            $this->moduleInfos = json_decode(file_get_contents($this->jsonInfosFile), true);
        }
        else {
            $this->moduleInfos = array(
                 'version' => self::VERSION,
                 'packages' => array()
            );
        }
    }

    public static function getSubscribedEvents()
    {
        return array(
            ScriptEvents::POST_PACKAGE_INSTALL => array(
                array('onPackageInstalled', 0)
            ),
            ScriptEvents::POST_PACKAGE_UPDATE => array(
                array('onPackageUpdated', 0)
            ),
            ScriptEvents::PRE_PACKAGE_UNINSTALL => array(
                array('onPackageUninstall', 0)
            ),
            ScriptEvents::POST_INSTALL_CMD => array(
                array('onPostInstall', 0)
            ),
            ScriptEvents::POST_UPDATE_CMD => array(
                array('onPostUpdate', 0)
            ),
        );
    }

    public function onPackageInstalled(PackageEvent $event)
    {
        $installedPackage = $event->getOperation()->getPackage();
        //$this->io->write("=== ModuleSetup === installed package ".$installedPackage->getName()." (".$installedPackage->getType().")");
        if ($installedPackage->getType() !== 'jelix-module' &&
            $installedPackage->getName() !== 'jelix/jelix' &&
            $installedPackage->getName() !== 'jelix/for-classic-package') {
            return;
        }
        $packagePath = $this->vendorDir.$installedPackage->getName();
        $this->readModuleInfo($installedPackage, $packagePath);
    }

    public function onPackageUpdated(PackageEvent $event)
    {
        $initialPackage = $event->getOperation()->getInitialPackage();
        $targetPackage = $event->getOperation()->getTargetPackage();
        //$this->io->write("=== ModuleSetup === updated package ".$targetPackage->getName()." (".$targetPackage->getType().")");
        if ($targetPackage->getType() !== 'jelix-module' &&
            $targetPackage->getName() !== 'jelix/jelix' &&
            $targetPackage->getName() !== 'jelix/for-classic-package') {
            return;
        }
        $packagePath = $this->vendorDir.$targetPackage->getName();
        $this->readModuleInfo($targetPackage, $packagePath);
    }

    public function onPackageUninstall(PackageEvent $event)
    {
        $removedPackage = $event->getOperation()->getPackage();
        //$this->io->write("=== ModuleSetup === remove package ".$removedPackage->getName()." (".$removedPackage->getType().")");
        if ($removedPackage->getType() !== 'jelix-module' &&
            $removedPackage->getName() !== 'jelix/jelix' &&
            $removedPackage->getName() !== 'jelix/for-classic-package') {
            return;
        }
        $packagePath = $this->vendorDir.$removedPackage->getName();
        if (isset($this->moduleInfos['packages'][$removedPackage->getName()])) {
            unset($this->moduleInfos['packages'][$removedPackage->getName()]);
        }
        //$this->readModulesList($removedPackage, $packagePath, false);
    }

    public function onPostInstall(\Composer\Script\Event $event)
    {
        $this->readModuleInfo($this->composer->getPackage(), getcwd());

        file_put_contents($this->jsonInfosFile, json_encode( $this->moduleInfos,JSON_PRETTY_PRINT));

        $allModulesDir = array();
        $allPluginsDir = array();
        $allModules = array();
        foreach( $this->moduleInfos['packages'] as $packageName => $package) {
            $allModulesDir = array_merge($allModulesDir, $package['modules-dir']);
            $allPluginsDir = array_merge($allPluginsDir, $package['plugins-dir']);
            $allModules = array_merge($allModules, $package['modules']);
        }

        $php = '<'.'?php'."\n";

        if (count($allModulesDir)) {
            $php .= <<<EOF
jApp::declareModulesDir(array(

EOF;
            foreach($allModulesDir as $dir) {
                $php .= <<<EOF
            __DIR__.'/$dir',

EOF;
            }
            $php .= "));\n";
        }

        if (count($allModules)) {
            $php .= <<<EOF
jApp::declareModule(array(

EOF;
            foreach($allModules as $dir) {
                $php .= <<<EOF
            __DIR__.'/$dir',

EOF;
            }
            $php .= "));\n";
        }

        if (count($allPluginsDir)) {
            $php .= <<<EOF
jApp::declarePluginsDir(array(

EOF;
            foreach($allPluginsDir as $dir) {
                $php .= <<<EOF
            __DIR__.'/$dir',

EOF;
            }
            $php .= "));\n";
        }
        file_put_contents($this->vendorDir.'jelix_app_path.php', $php);
    }

    public function onPostUpdate(\Composer\Script\Event $event)
    {
        $this->onPostInstall($event);
    }

    protected function readModuleInfo($package, $packagePath, $toAdd = true) {
        $this->moduleInfos['packages'][$package->getName()] = array(
            'modules-dir' => array(),
            'plugins-dir' => array(),
            'modules' => array(),
        );

        $extra = $package->getExtra();
        if (!isset($extra['jelix'])) {
            return;
        }

        if (isset($extra['jelix']['modules-dir'])) {
            if (!is_array($extra['jelix']['modules-dir'])) {
                $this->io->writeError("Error in composer.json of ".$package->getName().": extra/jelix/modules-dir is not an array");
                return;
            }
            $modulesDir = array();
            foreach($extra['jelix']['modules-dir'] as $path) {
                $path = realPath($packagePath.DIRECTORY_SEPARATOR.$path);
                if ($path != '') {
                    $modulesDir[] = $this->fs->findShortestPath($this->vendorDir, $path, true);
                }
            }
            $this->moduleInfos['packages'][$package->getName()]['modules-dir'] = $modulesDir;
        }
        if (isset($extra['jelix']['modules'])) {
            if (!is_array($extra['jelix']['modules'])) {
                $this->io->writeError("Error in composer.json of ".$package->getName().": extra/jelix/modules is not an array");
                return;
            }
            $modules = array();
            foreach($extra['jelix']['modules'] as $path) {
                $path = realPath($packagePath.DIRECTORY_SEPARATOR.$path);
                if ($path != '') {
                    $modules[] = $this->fs->findShortestPath($this->vendorDir, $path, true);
                }
            }
            $this->moduleInfos['packages'][$package->getName()]['modules'] = $modules;
        }
        if (isset($extra['jelix']['plugins-dir'])) {
            if (!is_array($extra['jelix']['plugins-dir'])) {
                $this->io->writeError("Error in composer.json of ".$package->getName().": extra/jelix/plugins-dir is not an array");
                return;
            }
            $pluginsDir = array();
            foreach($extra['jelix']['plugins-dir'] as $path) {
                $path = realPath($packagePath.DIRECTORY_SEPARATOR.$path);
                if ($path != '') {
                    $pluginsDir[] = $this->fs->findShortestPath($this->vendorDir, $path, true);
                }
            }
            $this->moduleInfos['packages'][$package->getName()]['plugins-dir'] = $pluginsDir;
        }
    }
}
