<?php
namespace MagentoHackathon\Composer\Magento;

use Composer\Installer\LibraryInstaller;
use Composer\Util\Filesystem;
use Composer\Test\TestCase;
use Composer\Composer;
use Composer\Config;

/**
 * Test that path mapping translations work correctly, including different
 * prefix types (i.e. 'js/...' vs './js/...').
 */
class PathMappingTranslationTest extends InstallerTest
{
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
        $this->composer->setPackage($this->createPackageMock(
            [
                'path-mapping-translations' => [
                    'js/'       =>  'public/js/',
                    'media/'    =>  'public/media/',
                    'skin/'     =>  'public/skin/',
                ]
            ]
        ));

        $this->config->merge([
            'config' => [
                'vendor-dir' => $this->vendorDir,
                'bin-dir' => $this->binDir,
            ],
        ]);

        $this->dm = $this->getMockBuilder('Composer\Downloader\DownloadManager')
                ->disableOriginalConstructor()
                ->getMock();
        $this->composer->setDownloadManager($this->dm);
        $this->getMockBuilder('Composer\Repository\InstalledRepositoryInterface')
            ->getMock();

        $this->io = $this->getMockBuilder('Composer\IO\IOInterface')
            ->getMock();

        $this->object = new Installer($this->io, $this->composer);
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

        $this->assertContains(['src/js', 'public/js/examplename'], $mappings);
    }

    /**
     * @covers MagentoHackathon\Composer\Magento\Installer::getMappings
     */
    public function testSkinPathMappingTranslation()
    {
        $package = $this->createPathMappingTranslationMock();
        $mappings = $this->object->getParser($package)->getMappings();

        $this->assertContains(['src/skin', 'public/skin/frontend/default/default/examplename'], $mappings);
    }

    /**
     * @covers MagentoHackathon\Composer\Magento\Installer::getMappings
     */
    public function testMediaPathMappingTranslation()
    {
        $package = $this->createPathMappingTranslationMock();
        $mappings = $this->object->getParser($package)->getMappings();

        $this->assertContains(['src/media/images', 'public/media/examplename_images'], $mappings);
    }

    /**
     * @covers MagentoHackathon\Composer\Magento\Installer::getMappings
     */
    public function testJSPathMappingTranslation2()
    {
        $package = $this->createPathMappingTranslationMock();
        $mappings = $this->object->getParser($package)->getMappings();

        $this->assertContains(['src2/js', 'public/js/examplename'],$mappings);
    }

    /**
     * @covers MagentoHackathon\Composer\Magento\Installer::getMappings
     */
    public function testSkinPathMappingTranslation2()
    {
        $package = $this->createPathMappingTranslationMock();
        $mappings = $this->object->getParser($package)->getMappings();

        $this->assertContains(['src2/skin', 'public/skin/frontend/default/default/examplename'], $mappings);
    }

    /**
     * @covers MagentoHackathon\Composer\Magento\Installer::getMappings
     */
    public function testMediaPathMappingTranslation2()
    {
        $package = $this->createPathMappingTranslationMock();
        $mappings = $this->object->getParser($package)->getMappings();

        $this->assertContains(['src2/media/images', 'public/media/examplename_images'], $mappings);
    }
}
