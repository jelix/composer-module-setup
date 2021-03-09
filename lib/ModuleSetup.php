<?php

namespace Jelix\ComposerPlugin;

use Composer\Composer;
use Composer\EventDispatcher\EventSubscriberInterface;
use Composer\Installer\PackageEvents;
use Composer\IO\IOInterface;
use Composer\Plugin\PluginInterface;
use Composer\Plugin\PluginEvents;
use Composer\Script\ScriptEvents;
use Composer\Installer\PackageEvent;

/**
 * Main class of the plugin for Compose.
 *
 * This class should load our classes only during onPost* methods,
 * to be sure to load latest version of the plugin.
 *
 * @package Jelix\ComposerPlugin
 */
class ModuleSetup  implements PluginInterface, EventSubscriberInterface {

    const VERSION = 1;
    protected $composer;
    protected $io;
    protected $vendorDir;
    protected $packages = array();


    public function activate(Composer $composer, IOInterface $io)
    {
        $this->composer = $composer;
        $this->io = $io;
        $this->vendorDir = $this->composer->getConfig()->get('vendor-dir').DIRECTORY_SEPARATOR;
    }

    public static function getSubscribedEvents()
    {
        return array(
            PackageEvents::POST_PACKAGE_INSTALL => array(
                array('onPackageInstalled', 0)
            ),
            PackageEvents::POST_PACKAGE_UPDATE => array(
                array('onPackageUpdated', 0)
            ),
            PackageEvents::PRE_PACKAGE_UNINSTALL => array(
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
            $installedPackage->getName() !== 'jelix/jelix-essential' &&
            $installedPackage->getName() !== 'jelix/for-classic-package' // deprecated
        ) {
            return;
        }
        $packagePath = $this->vendorDir.$installedPackage->getPrettyName();
        $this->packages[] = array('installed', $installedPackage->getName(), $installedPackage->getExtra(), $packagePath);
    }

    public function onPackageUpdated(PackageEvent $event)
    {
        $initialPackage = $event->getOperation()->getInitialPackage();
        $targetPackage = $event->getOperation()->getTargetPackage();
        //$this->io->write("=== ModuleSetup === updated package ".$targetPackage->getName()." (".$targetPackage->getType().")");
        if ($targetPackage->getType() !== 'jelix-module' &&
            $targetPackage->getName() !== 'jelix/jelix' &&
            $targetPackage->getName() !== 'jelix/jelix-essential' &&
            $targetPackage->getName() !== 'jelix/for-classic-package' // deprecated
        ) {
            return;
        }
        $packagePath = $this->vendorDir.$targetPackage->getPrettyName();
        $this->packages[] = array('updated', $targetPackage->getName(), $targetPackage->getExtra(), $packagePath);
    }

    public function onPackageUninstall(PackageEvent $event)
    {
        // note to myself: the package files are still there at this step
        $removedPackage = $event->getOperation()->getPackage();
        if ($removedPackage->getType() !== 'jelix-module' &&
            $removedPackage->getName() !== 'jelix/jelix' &&
            $removedPackage->getName() !== 'jelix/jelix-essential' &&
            $removedPackage->getName() !== 'jelix/for-classic-package' // deprecated
        ) {
            return;
        }
        $this->packages[] = array('removed', $removedPackage->getName(), $removedPackage->getExtra());

    }

    public function onPostInstall(\Composer\Script\Event $event)
    {
        $jelixParameters = new JelixParameters($this->vendorDir);
        $jsonInfosFile = $this->vendorDir.'jelix_modules_infos.json';
        if (file_exists($jsonInfosFile)) {
            $jelixParameters->loadFromFile($jsonInfosFile);
        }

        foreach($this->packages as $packageInfo) {
            $action = $packageInfo[0];
            if ($action == 'removed') {
                $jelixParameters->removePackage($packageInfo[1], $packageInfo[2]);
            }
            else {
                try {
                    list($action, $name, $extra, $path) = $packageInfo;
                    $jelixParameters->addPackage($name, $extra, $path, false);
                } catch (ReaderException $e) {
                    $this->io->writeError($e->getMessage());
                }
            }
        }

        try {
            $appPackage = $this->composer->getPackage();
            $jelixParameters->addPackage($appPackage->getName(), $appPackage->getExtra(), getcwd(), true);
        } catch (ReaderException $e) {
            $this->io->writeError($e->getMessage());
        }

        $jelixParameters->saveToFile($jsonInfosFile);

        if ($jelixParameters->getPackageParameters('jelix/jelix') ||
            $jelixParameters->getPackageParameters('jelix/jelix-essential') ||
            $jelixParameters->getPackageParameters('jelix/for-classic-package')  // deprecated
        ) {
            $setup = new SetupJelix17($jelixParameters);
            $setup->setup();
        } else {
            $setup = new SetupJelix16($jelixParameters);
            $setup->setup();
        }
    }

    public function onPostUpdate(\Composer\Script\Event $event)
    {
        $this->onPostInstall($event);
    }

    public function deactivate(Composer $composer, IOInterface $io)
    {

    }

    public function uninstall(Composer $composer, IOInterface $io)
    {

    }
}
