<?php

namespace MagentoHackathon\Composer\Magento\FullStack;

use Composer\Util\Filesystem;
use Symfony\Component\Process\Process;

class GlobalPluginTest extends AbstractTest
{
    protected static $processLogCounter = 1;

    protected function setUp(): void
    {
        
    }
    
    protected function tearDown(): void
    {
        
    }

    protected function prepareCleanDirectories()
    {
        $fs = new Filesystem();
        $fs->removeDirectory( self::getBasePath().'/home/vendor' );
        $fs->removeDirectory( self::getBasePath().'/home/cache' );
        $fs->remove(          self::getBasePath().'/home/composer.lock' );
    }

    public function testGlobalInstall()
    {
        $process = Process::fromShellCommandline(
            './' . self::getComposerCommand() . ' global install',
            self::getProjectRoot(),
            ['COMPOSER_HOME' => self::getBasePath() . '/home']
        );
        $process->setTimeout(180);
        $process->run();
        $this->assertProcess($process);
    }

    public function testGlobalUpdate()
    {
        $process = Process::fromShellCommandline(
            './' . self::getComposerCommand() . ' global update',
            self::getProjectRoot(),
            ['COMPOSER_HOME' => self::getBasePath() . '/home']
        );
        $process->setTimeout(180);
        $process->run();
        $this->assertProcess($process);
    }
}
