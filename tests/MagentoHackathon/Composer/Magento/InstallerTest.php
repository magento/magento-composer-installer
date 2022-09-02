<?php
namespace MagentoHackathon\Composer\Magento;

use Composer\Util\Filesystem;
use Composer\Test\TestCase;
use Composer\Composer;
use Composer\Config;

class InstallerTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var Installer
     */
    protected $object;

    /** @var  Composer */
    protected $composer;
    protected $config;
    protected $vendorDir;
    protected $binDir;
    protected $magentoDir;
    protected $dm;
    protected $repository;
    protected $io;
    /** @var Filesystem */
    protected $fs;

    protected function setUp(): void
    {
        $this->fs = new Filesystem;


        $this->vendorDir = realpath(sys_get_temp_dir()) . DIRECTORY_SEPARATOR . 'composer-test-vendor';
        $this->fs->ensureDirectoryExists($this->vendorDir);

        $this->binDir = realpath(sys_get_temp_dir()) . DIRECTORY_SEPARATOR . 'composer-test-bin';
        $this->fs->ensureDirectoryExists($this->binDir);

        $this->magentoDir = realpath(sys_get_temp_dir()) . DIRECTORY_SEPARATOR . 'composer-test-magento';
        $this->fs->ensureDirectoryExists($this->magentoDir);

        $this->composer = new Composer();
        $this->config = new Config();
        $this->composer->setConfig($this->config);
        $this->composer->setPackage($this->createPackageMock());

        $this->config->merge([
            'config' => [
                'vendor-dir' => $this->vendorDir,
                'bin-dir' => $this->binDir,
            ]
        ]);

        $this->dm = $this->getMockBuilder('Composer\Downloader\DownloadManager')
               ->disableOriginalConstructor()
               ->getMock();
        $this->composer->setDownloadManager($this->dm);

        $this->repository = $this->getMockBuilder('Composer\Repository\InstalledRepositoryInterface')
            ->getMock();
        $this->io = $this->getMockBuilder('Composer\IO\IOInterface')->getMock();

        $this->object = new Installer($this->io, $this->composer);
    }

    protected function tearDown(): void
    {
        $this->fs->removeDirectory($this->vendorDir);
        $this->fs->removeDirectory($this->binDir);
        $this->fs->removeDirectory($this->magentoDir);
    }

    protected function createPackageMock(array $extra = [], $name = 'example/test')
    {
        //$package= $this->getMockBuilder('Composer\Package\RootPackageInterface')
        $package = $this->getMockBuilder('Composer\Package\RootPackage')
            ->setConstructorArgs([$name, '1.0.0.0', '1.0.0'])
            ->onlyMethods(['getExtra', 'getName', 'getType'])
            ->getMock();

        $extraData = array_merge(['magento-root-dir' => $this->magentoDir], $extra);

        $package->method('getExtra')
            ->willReturn($extraData);
        
        $package->method('getName')
            ->willReturn($name);

        return $package;
    }

    /**
     * @dataProvider deployMethodProvider
     */
    public function testGetDeployStrategy($strategy, $expectedClass, $packageName, $composerExtra = [])
    {
        $extra = ['magento-deploystrategy' => $strategy];
        $extra = array_merge($composerExtra, $extra);
        $package = $this->createPackageMock($extra,$packageName);
        $this->composer->setPackage($package);
        $installer = new Installer($this->io, $this->composer);
        $this->assertInstanceOf($expectedClass, $installer->getDeployStrategy($package));
    }

    /**
     * @covers MagentoHackathon\Composer\Magento\Installer::supports
     */
    public function testSupports()
    {
        $this->assertTrue($this->object->supports('magento2-module'));
    }

    /**
     * @dataProvider parserTypeProvider
     */
    public function testGetParser( $packageExtra, $expectedClass, $composerExtra, $packageName, $prepareCallback )
    {
        $composerExtra = array_merge( $composerExtra, $this->composer->getPackage()->getExtra() );
        $this->composer->setPackage($this->createPackageMock($composerExtra));
        
        $package = $this->createPackageMock( $packageExtra, $packageName );
        $prepareCallback($this->vendorDir);
        $package->method('getType')->willReturn('magento2-library');

        $this->assertInstanceOf($expectedClass, $this->object->getParser($package));
    }
    
    public function deployMethodProvider()
    {
        $deployOverwrite = [
            'example/test2' => 'symlink',
            'example/test3' => 'none',
        ];
        
        return [
            [
                'method' => 'copy',
                'expectedClass' => 'MagentoHackathon\Composer\Magento\Deploystrategy\Copy',
                'packageName'   => 'example/test1',
            ],
            [
                'method' => 'symlink',
                'expectedClass' => 'MagentoHackathon\Composer\Magento\Deploystrategy\Symlink',
                'packageName'   => 'example/test1',
            ],
            [
                'method' => 'link',
                'expectedClass' => 'MagentoHackathon\Composer\Magento\Deploystrategy\Link',
                'packageName'   => 'example/test1',
            ],
            [
                'method' => 'none',
                'expectedClass' => 'MagentoHackathon\Composer\Magento\Deploystrategy\None',
                'packageName'   => 'example/test1',
            ],
            [
                'method' => 'symlink',
                'expectedClass' => 'MagentoHackathon\Composer\Magento\Deploystrategy\Symlink',
                'packageName'   => 'example/test2',
                'composerExtra' => ['magento-deploystrategy-overwrite' => $deployOverwrite],
            ],
            [
                'method' => 'symlink',
                'expectedClass' => 'MagentoHackathon\Composer\Magento\Deploystrategy\None',
                'packageName'   => 'example/test3',
                'composerExtra' => ['magento-deploystrategy-overwrite' => $deployOverwrite],
            ],
        ];
    }
    
    public function parserTypeProvider()
    {
        $mapOverwrite = [
            'example/test2' => [['test', 'test2']],
            'example/test3' => [['test', 'test3']],
        ];
        return [
            [
                'packageExtra'  => [
                    'map' => [['test', 'test']]
                ],
                'expectedClass' => 'MagentoHackathon\Composer\Magento\MapParser',
                'composerExtra' => ['magento-map-overwrite' => $mapOverwrite],
                'packageName'   => 'example/test1',
                'prepareCallback' => function($vendorDir){
                        
                    },
            ],
            [
                'packageExtra'  => ['map' => null],
                'expectedClass' => 'MagentoHackathon\Composer\Magento\ModmanParser',
                'composerExtra' => ['magento-map-overwrite' => $mapOverwrite],
                'packageName'   => 'example/test1',
                'prepareCallback' => function($vendorDir) {
                    $directory = $this->getPackageDir($vendorDir, 'example/test1');
                    mkdir($directory, 0755, true);
                    touch($directory . 'modman');
                }
            ],
            [
                'packageExtra'  => ['map' => null, 'package-xml' => 'package.xml'],
                'expectedClass' => 'MagentoHackathon\Composer\Magento\PackageXmlParser',
                'composerExtra' => ['magento-map-overwrite' => $mapOverwrite],
                'packageName'   => 'example/test1',
                'prepareCallback' => function($vendorDir) {
                    $directory = $this->getPackageDir($vendorDir, 'example/test1');
                    mkdir($directory, 0755, true);
                    touch($directory . 'package.xml');
                }
            ],
            [
                'packageExtra'  => [
                    'map' => [['test', 'test']]
                ],
                'expectedClass' => 'MagentoHackathon\Composer\Magento\MapParser',
                'composerExtra' => ['magento-map-overwrite' => $mapOverwrite],
                'packageName'   => 'example/test1',
                'prepareCallback' => function($vendorDir){

                    },
            ],
            [
                'packageExtra'  => ['map' => null],
                'expectedClass' => 'MagentoHackathon\Composer\Magento\ModmanParser',
                'composerExtra' => ['magento-map-overwrite' => $mapOverwrite],
                'packageName'   => 'example/test1',
                'prepareCallback' => function($vendorDir) {
                    $directory = $this->getPackageDir($vendorDir, 'example/test1');
                    mkdir($directory, 0755, true);
                    touch($directory . 'modman');
                }
            ],
            [
                'packageExtra'  => ['map' => null],
                'expectedClass' => 'MagentoHackathon\Composer\Magento\MapParser',
                'composerExtra' => ['magento-map-overwrite' => $mapOverwrite],
                'packageName'   => 'example/test2',
                'prepareCallback' => function($vendorDir) {
                    $directory = $this->getPackageDir($vendorDir, 'example/test2');
                    mkdir($directory, 0755, true);
                    touch($directory . 'modman');
                }
            ],
        ];
    }

    /**
     * Returns package directory path for Data provider.
     *
     * @param string $vendorDir
     * @param string $packageDir
     * @return string
     */
    private function getPackageDir(string $vendorDir, string $packageDir): string
    {
        return str_replace('/', DIRECTORY_SEPARATOR, "$vendorDir/$packageDir/");
    }

    /*
     * Test that path mapping translation code doesn't have any effect when no
     * translations are specified.
     */

    protected function createPathMappingTranslationMock()
    {
        return $this->createPackageMock(
            [
                'map' => [
                    ['src/app/etc/modules/Example_Name.xml',   'app/etc/modules/Example_Name.xml'],
                    ['src/app/code/community/Example/Name',    'app/code/community/Example/Name'],
                    ['src/skin',                               'skin/frontend/default/default/examplename'],
                    ['src/js',                                 'js/examplename'],
                    ['src/media/images',                       'media/examplename_images'],
                    ['src2/skin',                              './skin/frontend/default/default/examplename'],
                    ['src2/js',                                './js/examplename'],
                    ['src2/media/images',                      './media/examplename_images'],
                ]
            ]
        );
    }

    /**
     * @covers MagentoHackathon\Composer\Magento\Installer::getMappings
     */
    public function testEtcPathMappingTranslation()
    {
        $package = $this->createPathMappingTranslationMock();
        $mappings = $this->object->getParser($package)->getMappings();

        $this->assertContains(['src/app/etc/modules/Example_Name.xml', 'app/etc/modules/Example_Name.xml'], $mappings);
    }

    /**
     * @covers MagentoHackathon\Composer\Magento\Installer::getMappings
     */
    public function testCodePathMappingTranslation()
    {
        $package = $this->createPathMappingTranslationMock();
        $mappings = $this->object->getParser($package)->getMappings();

        $this->assertContains(['src/app/code/community/Example/Name', 'app/code/community/Example/Name'], $mappings);
    }

    /**
     * @covers MagentoHackathon\Composer\Magento\Installer::getMappings
     */
    public function testJSPathMappingTranslation()
    {
        $package = $this->createPathMappingTranslationMock();
        $mappings = $this->object->getParser($package)->getMappings();

        $this->assertContains(['src/js', 'js/examplename'], $mappings);
    }

    /**
     * @covers MagentoHackathon\Composer\Magento\Installer::getMappings
     */
    public function testSkinPathMappingTranslation()
    {
        $package = $this->createPathMappingTranslationMock();
        $mappings = $this->object->getParser($package)->getMappings();

        $this->assertContains(['src/skin', 'skin/frontend/default/default/examplename'], $mappings);
    }

    /**
     * @covers MagentoHackathon\Composer\Magento\Installer::getMappings
     */
    public function testMediaPathMappingTranslation()
    {
        $package = $this->createPathMappingTranslationMock();
        $mappings = $this->object->getParser($package)->getMappings();

        $this->assertContains(['src/media/images', 'media/examplename_images'], $mappings);
    }

    /**
     * @covers MagentoHackathon\Composer\Magento\Installer::getMappings
     */
    public function testJSPathMappingTranslation2()
    {
        $package = $this->createPathMappingTranslationMock();
        $mappings = $this->object->getParser($package)->getMappings();

        $this->assertContains(['src2/js', './js/examplename'],$mappings);
    }

    /**
     * @covers MagentoHackathon\Composer\Magento\Installer::getMappings
     */
    public function testSkinPathMappingTranslation2()
    {
        $package = $this->createPathMappingTranslationMock();
        $mappings = $this->object->getParser($package)->getMappings();

        $this->assertContains(['src2/skin', './skin/frontend/default/default/examplename'], $mappings);
    }

    /**
     * @covers MagentoHackathon\Composer\Magento\Installer::getMappings
     */
    public function testMediaPathMappingTranslation2()
    {
        $package = $this->createPathMappingTranslationMock();
        $mappings = $this->object->getParser($package)->getMappings();

        $this->assertContains(['src2/media/images', './media/examplename_images'], $mappings);
    }

}

