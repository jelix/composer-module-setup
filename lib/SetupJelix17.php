<?php

namespace Jelix\ComposerPlugin;



class SetupJelix17 {

    /**
     * @var JelixParameters
     */
    protected $parameters;

    function __construct(JelixParameters $parameters) {
        $this->parameters = $parameters;
    }

    function setup() {
        $allModulesDir = $this->parameters->getAllModulesDirs();
        $allPluginsDir = $this->parameters->getAllPluginsDirs();
        $allModules = $this->parameters->getAllSingleModuleDirs();

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
        file_put_contents($this->parameters->getVendorDir().'jelix_app_path.php', $php);
    }
}