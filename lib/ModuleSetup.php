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
    
    protected $composer;
    protected $io;
    protected $vendorDir;

    protected $modulesDirToAdd = array();
    protected $modulesDirToRemove = array();
    protected $pluginsDirToAdd = array();
    protected $pluginsDirToRemove = array();
    protected $modules = array();
    
    public function activate(Composer $composer, IOInterface $io)
    {
        $this->composer = $composer;
        $this->io = $io;
        $this->vendorDir = $this->composer->getConfig()->get('vendor-dir').DIRECTORY_SEPARATOR;
    }

    public static function getSubscribedEvents()
    {
        return array(
            ScriptEvents::POST_PACKAGE_INSTALL => array(
                array('onPackageInstalled', 0)
            ),
            ScriptEvents::PRE_PACKAGE_UPDATE => array(
                array('onPackageUpdate', 0)
            ),
            ScriptEvents::POST_PACKAGE_UPDATE => array(
                array('onPackageUpdated', 0)
            ),
            ScriptEvents::PRE_PACKAGE_UNINSTALL => array(
                array('onPackageUninstall', 0)
            ),
            ScriptEvents::POST_ROOT_PACKAGE_INSTALL => array(
                array('onRootPackageInstalled', 0)
            ),
            ScriptEvents::POST_INSTALL_CMD => array(
                array('onPostInstall', 0)
            ),
            ScriptEvents::POST_UPDATE_CMD => array(
                array('onPostUpdate', 0)
            ),
        );
    }

    public function onRootPackageInstalled(PackageEvent $event)
    {
        $installedPackage = $event->getOperation()->getPackage();
        $this->io->write("=== ModuleSetup === installed root package ".$installedPackage->getName()." (".$installedPackage->getType().")");
        
    }

    public function onPackageInstalled(PackageEvent $event)
    {
        $installedPackage = $event->getOperation()->getPackage();
        $this->io->write("=== ModuleSetup === installed package ".$installedPackage->getName()." (".$installedPackage->getType().")");
        if ($installedPackage->getType() !== 'jelix-module') {
            return;
        }
        $packagePath = $this->vendorDir.$installedPackage->getName();
        $this->readModulesList($installedPackage, $packagePath);
    }

    public function onPackageUpdate(PackageEvent $event)
    {
        $initialPackage = $event->getOperation()->getInitialPackage();
        $targetPackage = $event->getOperation()->getTargetPackage();
        $this->io->write("=== ModuleSetup === update package ".$initialPackage->getName()." (".$initialPackage->getType().")");
        if ($targetPackage->getType() !== 'jelix-module') {
            return;
        }
        $packagePath = $this->vendorDir.$initialPackage->getName();
        $this->readModulesList($initialPackage, $packagePath, false);
    }

    public function onPackageUpdated(PackageEvent $event)
    {
        $initialPackage = $event->getOperation()->getInitialPackage();
        $targetPackage = $event->getOperation()->getTargetPackage();
        $this->io->write("=== ModuleSetup === updated package ".$targetPackage->getName()." (".$targetPackage->getType().")");
        if ($targetPackage->getType() !== 'jelix-module') {
            return;
        }
        $packagePath = $this->vendorDir.$targetPackage->getName();
        $this->readModulesList($targetPackage, $packagePath);
    }

    public function onPackageUninstall(PackageEvent $event)
    {
        $removedPackage = $event->getOperation()->getPackage();
        $this->io->write("=== ModuleSetup === remove package ".$removedPackage->getName()." (".$removedPackage->getType().")");
        if ($removedPackage->getType() !== 'jelix-module') {
            return;
        }
        $packagePath = $this->vendorDir.$removedPackage->getName();
        $this->readModulesList($removedPackage, $packagePath, false);
    }

    public function onPostInstall(\Composer\Script\Event $event)
    {
        $this->readModulesList($this->composer->getPackage(), getcwd());
        if (file_exists($this->vendorDir.'jelix_app_path.json')) {
            $dirs = json_decode(file_get_contents($this->vendorDir.'jelix_app_path.json'), true);
        }
        else {
            $dirs = array(
                 'modulesDir' => array(),
                 'pluginsDir'=> array()
            );
        }

        $fs = new Filesystem();

        foreach($this->modulesDirToAdd as $path) {
            $path = $fs->findShortestPath($this->vendorDir, $path, true);
            if (!in_array($path, $dirs['modulesDir'])) {
                $dirs['modulesDir'][] = $path;
            }
        }

        foreach($this->modulesDirToRemove as $path) {
            $path = $fs->findShortestPath($this->vendorDir, $path, true);
            $key = array_search($path, $dirs['modulesDir']);
            if ($key !== false) {
                unset($dirs['modulesDir'][$key]);
            }
        }

        foreach($this->pluginsDirToAdd as $path) {
            $path = $fs->findShortestPath($this->vendorDir, $path, true);
            if (!in_array($path, $dirs['pluginsDir'])) {
                $dirs['pluginsDir'][] = $path;
            }
        }

        foreach($this->pluginsDirToRemove as $path) {
            $path = $fs->findShortestPath($this->vendorDir, $path, true);
            $key = array_search($path, $dirs['pluginsDir']);
            if ($key !== false) {
                unset($dirs['pluginsDir'][$key]);
            }
        }
        file_put_contents($this->vendorDir.'jelix_app_path.json', json_encode($dirs,JSON_PRETTY_PRINT));

        $php = '<'.'?php'."\n";

        if (count($dirs['modulesDir'])) {
            $php .= <<<EOF
jApp::declareModulesDir(array(

EOF;
            foreach($dirs['modulesDir'] as $dir) {
                $php .= <<<EOF
            __DIR__.'/$dir',
EOF;
            }
            $php .= '));';
        }

        if (count($dirs['pluginsDir'])) {
            $php .= <<<EOF
jApp::declarePluginsDir(array(

EOF;
            foreach($dirs['pluginsDir'] as $dir) {
                $php .= <<<EOF
            __DIR__.'/$dir',
EOF;
            }
            $php .= '));';
        }
        file_put_contents($this->vendorDir.'jelix_app_path.php', $php);
    }

    public function onPostUpdate(\Composer\Script\Event $event)
    {
        $this->onPostInstall($event);
    }

    protected function readModulesList($package, $packagePath, $toAdd = true) {
        $extra = $package->getExtra();
        
        if (!isset($extra['jelix'])) {
            return;
        }
        if (isset($extra['jelix']['modules-dir'])) {
            if (!is_array($extra['jelix']['modules-dir'])) {
                $this->io->writeError("Error in composer.json of ".$package->getName().": extra/jelix/modules-dir is not an array");
                return;
            }
            foreach($extra['jelix']['modules-dir'] as $path) {
                if ($toAdd) {
                    $this->modulesDirToAdd[] = realPath($packagePath.DIRECTORY_SEPARATOR.$path);
                }  else {
                    $this->modulesDirToRemove[] = realPath($packagePath.DIRECTORY_SEPARATOR.$path);
                }
            }
        }
        if (isset($extra['jelix']['modules'])) {
            
        }
        if (isset($extra['jelix']['plugins-dir'])) {
            if (!is_array($extra['jelix']['plugins-dir'])) {
                $this->io->writeError("Error in composer.json of ".$package->getName().": extra/jelix/plugins-dir is not an array");
                return;
            }
            foreach($extra['jelix']['plugins-dir'] as $path) {
                if ($toAdd) {
                    $this->pluginsDirToAdd[] = realPath($packagePath.DIRECTORY_SEPARATOR.$path);
                } else {
                    $this->pluginsDirToRemove[] = realPath($packagePath.DIRECTORY_SEPARATOR.$path);
                }
            }
        }
    }
}
