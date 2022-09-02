<?php

namespace MagentoHackathon\Composer\Magento;

use Composer\Util\Filesystem;
use Symfony\Component\Process\Process;

class FullStackTest extends FullStack\AbstractTest
{
    

    protected function setUp(): void
    {
        
    }
    
    protected function tearDown(): void
    {
        
    }

    public static function setUpBeforeClass(): void
    {
        parent::setUpBeforeClass();
        $packagesPath    = self::getProjectRoot() .'/tests/res/packages';
        $directory = new \DirectoryIterator($packagesPath);
        /** @var \DirectoryIterator $fileinfo */
        foreach($directory as $file){
            if (!$file->isDot() && $file->isDir()) {
                $composerPath = self::getProjectRoot() . DIRECTORY_SEPARATOR . self::getComposerCommand();
                $process = Process::fromShellCommandline(
                    $composerPath . ' archive --format=zip --dir="../../../../tests/FullStackTest/artifact" -vvv',
                    $file->getPathname()
                );
                $process->run();
                if( $process->getExitCode() !== 0){
                    $message = 'process for <code>'.$process->getCommandLine().'</code> exited with '.$process->getExitCode().': '.$process->getExitCodeText();
                    $message .= PHP_EOL.'Error Message:'.PHP_EOL.$process->getErrorOutput();
                    $message .= PHP_EOL.'Output:'.PHP_EOL.$process->getOutput();
                    echo $message;
                }
            }
        }
    }
    
    public static function tearDownAfterClass(): void
    {
        parent::tearDownAfterClass();
    }
    
    protected function prepareCleanDirectories()
    {
        $fs = new Filesystem();
        $fs->removeDirectory( self::getBasePath().'/htdocs' );
        $fs->ensureDirectoryExists( self::getBasePath().'/htdocs' );

        $fs->removeDirectory( self::getBasePath().'/magento/vendor' );
        $fs->remove( self::getBasePath().'/magento/composer.lock' );
        $fs->removeDirectory( self::getBasePath().'/magento-modules/vendor' );
        $fs->remove( self::getBasePath().'/magento-modules/composer.lock' );
    }
    
    protected function installBaseMagento()
    {
        $process = Process::fromShellCommandline(
            self::getProjectRoot() . DIRECTORY_SEPARATOR . self::getComposerCommand() . ' install ' .
            self::getComposerArgs() . ' --working-dir="./"',
            self::getBasePath() . '/magento'
        );
        $process->setTimeout(300);
        $process->run();
        self::logProcessOutput($process,'installBaseMagento');
        $this->assertProcess($process);
    }
    
    protected function getMethodRunConfigs()
    {
        $array = [
            'symlink' => [
                1 => [
                    'module_composer_json' => "composer_1.json",
                ],
                2 => [
                    'module_composer_json' => "composer_2.json",
                ],
                3 => [
                    'module_composer_json' => "composer_1.json",
                ],
            ],
            'copy' => [
                1 => [
                    'module_composer_json' => "composer_1_copy.json",
                ],
                2 => [
                    'module_composer_json' => "composer_2_copy.json",
                ],
                3 => [
                    'module_composer_json' => "composer_1_copy.json",
                ],
            ],
            'copy_force' => [
                1 => [
                    'module_composer_json' => "composer_1_copy_force.json",
                ],
                2 => [
                    'module_composer_json' => "composer_2_copy_force.json",
                ],
                3 => [
                    'module_composer_json' => "composer_1_copy_force.json",
                ],
            ],
        ];
        
        return $array;
    }
    
    public function methodProvider()
    {
        return [
            ['symlink'],
            ['copy'],
            ['copy_force'],
        ];
    }

    /**
     * @dataProvider methodProvider
     */
    public function testEverything( $method )
    {
        $this->assertFileExists(
            self::getBasePath() .
            '/artifact/magento-magento-composer-installer-' .
            self::$testVersion . '.zip'
        );

        $this->prepareCleanDirectories();
        $this->installBaseMagento();

        $methods = $this->getMethodRunConfigs();
        $runs = $methods[$method];
        foreach( $runs as $run => $value){
            $this->changeModuleComposerFileAndUpdate(
                $value['module_composer_json'],
                ($run === 1) ? 'install' : 'update'
            );

            switch($run){
                case 1:
                case 3:
                    foreach($this->getFirstOnlyFileTestSet() + $this->getFirstExistTestSet() as $file){
                        $this->assertFileExists(self::getBasePath() . '/htdocs/'. $file );
                    }
                    foreach($this->getFirstNotExistTestSet() as $file){
                        $this->assertFileDoesNotExist( self::getBasePath() .'/htdocs/'. $file );
                    }
                    if($method==="copy_force"){
                        $this->assertStringEqualsFile(
                            self::getBasePath().'/htdocs/' . 'app/design/frontend/Magento/luma/Magento_SortThemeMock/design/test1.phtml',
                            'testcontent1'
                        );
                        $this->assertStringEqualsFile(
                            self::getBasePath().'/htdocs/' . 'app/design/frontend/Magento/luma/Magento_SortThemeMock/design/test2.phtml',
                            'testcontent4'
                        );
                    }
                    break;
                case 2:
                    if($method==="symlink"){
                        foreach($this->getFirstOnlyFileTestSet() as $file){
                            $this->assertFileDoesNotExist( self::getBasePath() .'/htdocs/'. $file );
                        }
                    }
                    foreach($this->getSecondExistTestSet() as $file){
                        $this->assertFileExists( self::getBasePath() .'/htdocs/'. $file );
                    }
                    break;
            }
        }
    }

    protected function changeModuleComposerFileAndUpdate($file, $command = "update")
    {
        $magentoModuleComposerFile = self::getBasePath() . '/magento-modules/composer.json';
        if(file_exists($magentoModuleComposerFile)){
            unlink($magentoModuleComposerFile);
        }
        copy(
            self::getBasePath().'/magento-modules/' . $file,
            $magentoModuleComposerFile
        );

        $composerPath = '../../.././composer.phar';
        $process = Process::fromShellCommandline(
            $composerPath . ' ' . $command. ' '. self::getComposerArgs().' --optimize-autoloader --working-dir="./"',
            self::getBasePath() . '/magento-modules'
        );
        $process->setTimeout(300);
        $process->run();
        self::logProcessOutput($process);
        $this->assertProcess($process);
    }
    
    protected function getFirstOnlyFileTestSet()
    {
        return [
            'app/code/Magento/ModuleMock/etc/module.xml',
            'app/design/frontend/Magento/luma/Magento_ThemeMock/Foobar/issue76.phtml',
            'app/design/adminhtml/Magento/luma/Magento_AdminThemeMock/wildcard/wildcard.phtml',
            'composer_lib/autoload.php',
            'composer_lib/magento/magento-composer-installer-test-library/composer.json',
//            'app/design/frontend/test/default/updateFileRemove/design/test2.phtml',
        ];
    }

    protected function getFirstNotExistTestSet()
    {
        return [
            'app/design/frontend/test/default/issue76/Foobar/Foobar/issue76.phtml',
            'app/design/frontend/frontend/wildcard/wildcard.phtml',
            'app/app/code/test.php',
            'index.php',
            'shell/compiler.php',
//            'app/design/frontend/test/default/updateFileRemove/design/test2.phtml',
//            'app/design/frontend/test/default/updateFileRemove/test2.phtml',
        ];
    }

    protected function getFirstExistTestSet()
    {
        return [
//            'app/design/frontend/test/default/updateFileRemove/design/test1.phtml',
//            'app/design/frontend/test/default/updateFileRemove/design/test2.phtml',
              'shell/log.php',
        ];
    }

    protected function getSecondExistTestSet()
    {
        return [
//            'app/design/frontend/test/default/updateFileRemove/design/test1.phtml',
        ];
    }
}