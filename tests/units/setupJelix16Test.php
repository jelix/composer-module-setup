<?php

class setupJelix16Test extends \PHPUnit\Framework\TestCase
{
    protected $setupJelix;

    protected $jelixParameters;

    function setUp(): void
    {
        \Jelix\FileUtilities\Directory::copy(
            __DIR__ . '/../assets/app1',
            __DIR__ . '/../tmp/app1'
        );
        $this->jelixParameters = new \Jelix\ComposerPlugin\JelixParameters(
            __DIR__ . '/../tmp/app1/vendor/'
        );
        parent::setUp();
    }

    function tearDown(): void
    {
        parent::tearDown();
    }

    function testSetupUrlEngineConfiguration()
    {
        echo "bla";
        $appDir = realpath(__DIR__.'/../tmp/app1/').'/';
        $vendorDir = $appDir.'vendor/';
        $p = new \Jelix\ComposerPlugin\JelixParameters($vendorDir);

        $p->loadFromFile($vendorDir.'jelix_modules_infos_empty.json');

        $p->addPackage('jelix/app1-tests', array(
            "jelix" => array (
                "app-dir" => "./"
            )
        ), $appDir, true);


        $p->addPackage(
            'jelix/test1-module',
            array(
                "jelix" => array (
                    "modules-dir" => [
                        'modules/'
                    ],
                    "autoconfig-access-16" => [
                        "jelix/app1-tests" => [
                            "test1" => [
                                "__global"=> 1,
                                "index" =>2,
                                "admin" => 1
                            ]
                        ]
                    ]
                )
        ), $vendorDir.'jelix/test1-module/', false);

        $setup = new \Jelix\ComposerPlugin\SetupJelix16($p);
        $setup->setup();

        $ini = new \Jelix\ComposerPlugin\Ini\IniReader($appDir.'var/config/localconfig.ini.php');
        $this->assertEquals('lib:jelix-admin-modules,lib:jelix-modules,app:modules,app:vendor/jelix/test1-module/modules', $ini->getValue('modulesPath'));
        $this->assertEquals('app:plugins,lib:jelix-plugins,module:jacl2db/plugins', $ini->getValue('pluginsPath'));
        $this->assertEquals(null, $ini->getValue('test1.path', 'modules'));
        $this->assertEquals(1, $ini->getValue('test1.access', 'modules'));

        $ini = new \Jelix\ComposerPlugin\Ini\IniReader($appDir.'var/config/mainconfig.ini.php');
        $this->assertEquals('lib:jelix-admin-modules,lib:jelix-modules,app:modules', $ini->getValue('modulesPath'));
        $this->assertEquals('app:plugins,lib:jelix-plugins,module:jacl2db/plugins', $ini->getValue('pluginsPath'));
        $this->assertEquals(null, $ini->getValue('test1.path', 'modules'));
        $this->assertEquals(null, $ini->getValue('test1.access', 'modules'));

        $ini = new \Jelix\ComposerPlugin\Ini\IniReader($appDir.'var/config/index/config.ini.php');
        $this->assertEquals(null, $ini->getValue('modulesPath'));
        $this->assertEquals(null, $ini->getValue('pluginsPath'));
        $this->assertEquals(null, $ini->getValue('test1.path', 'modules'));
        $this->assertEquals(2, $ini->getValue('test1.access', 'modules'));

        $ini = new \Jelix\ComposerPlugin\Ini\IniReader($appDir.'var/config/admin/config.ini.php');
        $this->assertEquals(null, $ini->getValue('modulesPath'));
        $this->assertEquals(null, $ini->getValue('pluginsPath'));
        $this->assertEquals(null, $ini->getValue('test1.path', 'modules'));
        $this->assertEquals(1, $ini->getValue('test1.access', 'modules'));

        $ini = new \Jelix\ComposerPlugin\Ini\IniReader($appDir.'var/config/cmdline/script.ini.php');
        $this->assertEquals(null, $ini->getValue('modulesPath'));
        $this->assertEquals(null, $ini->getValue('pluginsPath'));
        $this->assertEquals(null, $ini->getValue('test1.path', 'modules'));
        $this->assertEquals(null, $ini->getValue('test1.access', 'modules'));

    }

}
