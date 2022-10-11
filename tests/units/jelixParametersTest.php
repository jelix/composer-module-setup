<?php

class jelixParametersTest extends \PHPUnit\Framework\TestCase
{
    function setUp() : void  {
        \Jelix\FileUtilities\Directory::copy(__DIR__.'/../assets/app2', __DIR__.'/../tmp/app2');
        parent::setUp();
    }

    function tearDown() : void  {
        parent::tearDown();
    }


    function testLoadEmptyInfos1()
    {
        $vendorDir = realpath(__DIR__.'/../tmp/app2/vendor/').'/';
        $p = new \Jelix\ComposerPlugin\JelixParameters($vendorDir);

        $p->loadFromFile($vendorDir.'jelix_modules_infos_empty.json');

        $this->assertEquals('', $p->getAppDir());
        $this->assertEquals('', $p->getVarConfigDir());
        $this->assertEquals($vendorDir, $p->getVendorDir());
        $this->assertEquals('', $p->getConfigFileName());

        $this->assertEquals(array(), $p->getAllModulesDirs());
        $this->assertEquals(array(), $p->getAllPluginsDirs());
        $this->assertEquals(array(), $p->getAllSingleModuleDirs());

        $this->assertEquals(array(), $p->getPackages());
        $this->assertEquals(null, $p->getApplicationPackage());
        $this->assertEquals(array(), $p->getRemovedPackages());
    }

    function testAddAppPackage()
    {
        $appDir = realpath(__DIR__.'/../tmp/app2/').'/';
        $vendorDir = $appDir.'vendor/';
        $p = new \Jelix\ComposerPlugin\JelixParameters($vendorDir);

        $p->loadFromFile($vendorDir.'jelix_modules_infos_empty.json');

        $p->addApplicationPackage('jelix/app2-tests', array(
            "jelix" => array (
                "app-dir" => "./",
                "var-config-dir" => "./var/config/",
                "modules-dir" => [
                    'modules2/'
                ]
            )
        ), $appDir);

        $this->assertEquals($appDir, $p->getAppDir());
        $this->assertEquals($appDir.'var/config/', $p->getVarConfigDir());
        $this->assertEquals($vendorDir, $p->getVendorDir());
        $this->assertEquals('', $p->getConfigFileName());

        $this->assertEquals(array('../modules2'), $p->getAllModulesDirs());
        $this->assertEquals(array(), $p->getAllPluginsDirs());
        $this->assertEquals(array(), $p->getAllSingleModuleDirs());

        $this->assertEquals(1, count($p->getPackages()));

        $this->assertEquals(array(), $p->getRemovedPackages());
        $packages = $p->getPackages();
        $appPackage = $packages['jelix/app2-tests'];
        $this->assertEquals($appPackage, $p->getApplicationPackage());

        $this->assertTrue($appPackage->isApp());
        $this->assertEquals('jelix/app2-tests', $appPackage->getPackageName());
        $this->assertEquals(array(), $appPackage->getPackageModulesAccess());
        $this->assertEquals(array(), $appPackage->getAppModulesAccess());
        $this->assertEquals(array('../modules2'), $appPackage->getModulesDirs());
        $this->assertEquals(array(), $appPackage->getPluginsDirs());
        $this->assertEquals(array(), $appPackage->getSingleModuleDirs());
        $this->assertFalse($p->isJelix16());

        $this->assertEquals(array(
            'main2' => $appDir.'vendor/../modules2/main2'
        ), $appPackage->getModules($vendorDir));

    }

    function testAddPackage()
    {
        $appDir = realpath(__DIR__.'/../tmp/app2/').'/';
        $vendorDir = $appDir.'vendor/';
        $p = new \Jelix\ComposerPlugin\JelixParameters($vendorDir);

        $p->loadFromFile($vendorDir.'jelix_modules_infos_empty.json');

        $p->addApplicationPackage('jelix/app2-tests', array(
            "jelix" => array (
                "app-dir" => "./"
            )
        ), $appDir);


        $p->addPackage(
            'jelix/test1-module',
            array(
                "jelix" => array (
                    "modules-dir" => [
                        'modules/'
                    ],
                    "autoconfig-access-16" => [
                        "jelix/app2-tests" => [
                            "test1" => [
                                "__global"=> 1,
                                "index" =>2,
                                "admin" => 1
                            ]
                        ]
                    ]
                )
            ), $vendorDir.'jelix/test1-module/');


        $this->assertEquals($appDir, $p->getAppDir());
        $this->assertEquals($appDir.'var/config/', $p->getVarConfigDir());
        $this->assertEquals($vendorDir, $p->getVendorDir());
        $this->assertEquals('', $p->getConfigFileName());

        $this->assertEquals(array('jelix/test1-module/modules'), $p->getAllModulesDirs());
        $this->assertEquals(array(), $p->getAllPluginsDirs());
        $this->assertEquals(array(), $p->getAllSingleModuleDirs());

        $this->assertEquals(2, count($p->getPackages()));

        $this->assertEquals(array(), $p->getRemovedPackages());
        $packages = $p->getPackages();
        $appPackage = $packages['jelix/app2-tests'];
        $this->assertEquals($appPackage, $p->getApplicationPackage());

        $this->assertTrue($appPackage->isApp());
        $this->assertEquals('jelix/app2-tests', $appPackage->getPackageName());
        $this->assertEquals(array(), $appPackage->getPackageModulesAccess());
        $this->assertEquals(array(), $appPackage->getAppModulesAccess());
        $this->assertEquals(array(), $appPackage->getModulesDirs());
        $this->assertEquals(array(), $appPackage->getPluginsDirs());
        $this->assertEquals(array(), $appPackage->getSingleModuleDirs());

        $this->assertEquals(array(), $appPackage->getModules($vendorDir));

        $package = $packages['jelix/test1-module'];
        $this->assertNotEquals($package, $p->getApplicationPackage());
        $this->assertFalse($package->isApp());
        $this->assertEquals('jelix/test1-module', $package->getPackageName());
        $this->assertEquals(array(), $package->getPackageModulesAccess());
        $this->assertEquals(array("jelix/app2-tests" => [
            "test1" => [
                "__global"=> 1,
                "index" =>2,
                "admin" => 1
            ]
        ]), $package->getAppModulesAccess());
        $this->assertEquals(array('jelix/test1-module/modules'), $package->getModulesDirs());
        $this->assertEquals(array(), $package->getPluginsDirs());
        $this->assertEquals(array(), $package->getSingleModuleDirs());
        $this->assertFalse($p->isJelix16());

        $this->assertEquals(array(
            'test1' => $appDir.'vendor/jelix/test1-module/modules/test1',
            'test2' => $appDir.'vendor/jelix/test1-module/modules/test2'
        ), $package->getModules($vendorDir));

        $p->saveToFile($vendorDir.'jelix_modules_infos_empty_result.json');
        $content = file_get_contents($vendorDir.'jelix_modules_infos_empty_result.json');
        $expected = file_get_contents($vendorDir.'jelix_modules_infos_empty_expected_result.json');
        $this->assertEquals($expected, $content);
    }

}
