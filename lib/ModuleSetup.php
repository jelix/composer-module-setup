<?php

namespace Jelix\ComposerPlugin;

use Composer\Composer;
use Composer\EventDispatcher\EventSubscriberInterface;
use Composer\IO\IOInterface;
use Composer\Plugin\PluginInterface;
use Composer\Plugin\PluginEvents;
use Composer\Script\ScriptEvents;
use Composer\Installer\PackageEvent;


class ModuleSetup  implements PluginInterface, EventSubscriberInterface {
    
    const VERSION = 1;
    protected $composer;
    protected $io;
    protected $vendorDir;
    protected $jsonInfosFile;
    protected $fs;

    protected $moduleInfos = array();

    /**
     * @var JelixParameters
     */
    protected $jelixParameters = null;

    public function activate(Composer $composer, IOInterface $io)
    {
        $this->composer = $composer;
        $this->io = $io;
        $this->vendorDir = $this->composer->getConfig()->get('vendor-dir').DIRECTORY_SEPARATOR;

        $this->jelixParameters = new JelixParameters($this->vendorDir);

        $this->jsonInfosFile = $this->vendorDir.'jelix_modules_infos.json';
        if (file_exists($this->jsonInfosFile)) {
            $this->jelixParameters->loadFromFile($this->jsonInfosFile);
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
        try {
            $this->jelixParameters->addPackage($installedPackage, $packagePath);
        }
        catch(ReaderException $e) {
            $this->io->writeError($e->getMessage());
        }
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
        try {
            $this->jelixParameters->addPackage($targetPackage, $packagePath);
        }
        catch(ReaderException $e) {
            $this->io->writeError($e->getMessage());
        }
    }

    public function onPackageUninstall(PackageEvent $event)
    {
        // note to myself: the package files are still there at this step
        $removedPackage = $event->getOperation()->getPackage();
        if ($removedPackage->getType() !== 'jelix-module' &&
            $removedPackage->getName() !== 'jelix/jelix' &&
            $removedPackage->getName() !== 'jelix/for-classic-package') {
            return;
        }
        $this->jelixParameters->removePackage($removedPackage->getName());
    }

    public function onPostInstall(\Composer\Script\Event $event)
    {
        try {
            $this->jelixParameters->addPackage($this->composer->getPackage(), getcwd(), true);
        } catch (ReaderException $e) {
            $this->io->writeError($e->getMessage());
        }

        $this->jelixParameters->saveToFile($this->jsonInfosFile);

        if ($this->jelixParameters->getPackageParameters('jelix/jelix') ||
            $this->jelixParameters->getPackageParameters('jelix/for-classic-package')) {
            $setup = new SetupJelix17($this->jelixParameters);
            $setup->setup();
        } else {
            $setup = new SetupJelix16($this->jelixParameters);
            $setup->setup();
        }
    }

    public function onPostUpdate(\Composer\Script\Event $event)
    {
        $this->onPostInstall($event);
    }
}
