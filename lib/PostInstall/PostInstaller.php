<?php

namespace Jelix\ComposerPlugin\PostInstall;


use Jelix\ComposerPlugin\DummyLogger;

class PostInstaller
{

    protected $vendorDir;

    /**
     * @var \Composer\IO\IOInterface
     */
    protected $io;

    protected $debugLogger = null;

    /**
     * @param $vendorDir
     * @param \Composer\IO\IOInterface $io
     */
    public function __construct($vendorDir, $io = null, $debugLogger = null)
    {
        $this->vendorDir = $vendorDir;
        $this->io = $io;
        if ($debugLogger === null) {
            $debugLogger = new DummyLogger();
        }
        $this->debugLogger = $debugLogger;
    }

    protected function writeError($error)
    {
        if ($this->io) {
            $this->io->writeError($error);
        }
        else {
            echo $error."\n";
        }
    }

    public function process($packages, $appPackage)
    {
        $jelixParameters = new JelixParameters($this->vendorDir);
        $jelixParameters->loadFromFile();

        // let's add all packages
        foreach($packages as $packageInfo) {
            list($action, $packageName, $extra, $packagePath) = $packageInfo;

            if ($action == 'removed') {
                $jelixParameters->removePackage($packageName, $extra);
            }
            else {
                try {
                    $jelixParameters->addPackage($packageName, $extra, $packagePath);
                } catch (ReaderException $e) {
                    $this->writeError($e->getMessage());
                }
            }
        }

        // let's add the app package
        try {
            $jelixParameters->addApplicationPackage($appPackage->getName(), $appPackage->getExtra(), getcwd());
        } catch (ReaderException $e) {
            $this->writeError($e->getMessage());
        }

        $jelixParameters->saveToFile();

        // launch the setup of the application
        $setup = new SetupJelix20($jelixParameters, $this->debugLogger);
        $setup->setup();

    }
}