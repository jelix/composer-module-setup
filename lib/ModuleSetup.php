<?php

namespace Jelix\ComposerPlugin;

use Composer\Composer;
use Composer\EventDispatcher\EventSubscriberInterface;
use Composer\IO\IOInterface;
use Composer\Plugin\PluginInterface;
use Composer\Plugin\PluginEvents;
use Composer\Script\ScriptEvents;


class ModuleSetup  implements PluginInterface, EventSubscriberInterface {
    
    protected $composer;
    protected $io;

    public function activate(Composer $composer, IOInterface $io)
    {
        $this->composer = $composer;
        $this->io = $io;
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
        );
    }
    
    public function onPackageInstalled(PackageEvent $event)
    {
        $installedPackage = $event->getOperation()->getPackage();
        if ($installedPackage->getType() !== 'jelix-module') {
            return;
        }
    }

    public function onPackageUpdate(PackageEvent $event)
    {
        $initialPackage = $event->getOperation()->getInitialPackage();
        $targetPackage = $event->getOperation()->getTargetPackage();
        if ($targetPackage->getType() !== 'jelix-module') {
            return;
        }
    }

    public function onPackageUpdated(PackageEvent $event)
    {
        $initialPackage = $event->getOperation()->getInitialPackage();
        $targetPackage = $event->getOperation()->getTargetPackage();
        if ($targetPackage->getType() !== 'jelix-module') {
            return;
        }
    }

    public function onPackageUninstall(PackageEvent $event)
    {
        $removedPackage = $event->getOperation()->getPackage();
        if ($removedPackage->getType() !== 'jelix-module') {
            return;
        }
    }
}
