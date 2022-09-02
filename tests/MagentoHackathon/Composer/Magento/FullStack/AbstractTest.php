<?php
/**
 * 
 * 
 * 
 * 
 */

namespace MagentoHackathon\Composer\Magento\FullStack;

use Composer\Util\Filesystem;
use Symfony\Component\Process\Process;

abstract class AbstractTest extends \PHPUnit\Framework\TestCase
{

    protected static $processLogCounter = 1;

    /**
     * Store current module version for rollback
     *
     * @var string
     */
    protected static $currentModuleVersion = '';

    /**
     * Version to be set in root composer.json for tests
     *
     * @var string
     */
    protected static $testVersion = '100.100.100';

    public static function setUpBeforeClass(): void
    {
        $rootComposer = json_decode(file_get_contents(self::getProjectRoot() . '/composer.json'), true);
        self::$currentModuleVersion = $rootComposer['version'] ?? '';
        $rootComposer['version'] = self::$testVersion;
        file_put_contents(
            self::getProjectRoot() . '/composer.json',
            json_encode($rootComposer, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE)
        );

        @unlink(self::getProjectRoot().'/vendor/theseer/directoryscanner/tests/_data/linkdir');
        @unlink(self::getBasePath().'/magento/vendor/theseer/directoryscanner/tests/_data/linkdir');
        @unlink(self::getBasePath().'/magento-modules/vendor/theseer/directoryscanner/tests/_data/linkdir');
        @unlink(self::getProjectRoot().'/vendor/theseer/directoryscanner/tests/_data/nested/empty');
        @unlink(self::getBasePath().'/magento/vendor/theseer/directoryscanner/tests/_data/nested/empty');
        @unlink(self::getBasePath().'/magento-modules/vendor/theseer/directoryscanner/tests/_data/nested/empty');

        $process = Process::fromShellCommandline(
            './' . self::getComposerCommand().' archive --format=zip --dir="tests/FullStackTest/artifact" -vvv',
            self::getProjectRoot()
        );
        $process->run();
        if( $process->getExitCode() !== 0){
            $message = 'process for <code>'.$process->getCommandLine().'</code> exited with '.$process->getExitCode().': '.$process->getExitCodeText();
            $message .= PHP_EOL.'Error Message:'.PHP_EOL.$process->getErrorOutput();
            $message .= PHP_EOL.'Output:'.PHP_EOL.$process->getOutput();
            echo $message;
        }else{
            self::logProcessOutput($process,'createComposerArtifact');
        }

    }
    
    public static function tearDownAfterClass(): void
    {
        $rootComposer = json_decode(file_get_contents(self::getProjectRoot() . '/composer.json'), true);
        if (self::$currentModuleVersion === '') {
            unset($rootComposer['version']);
        } else {
            $rootComposer['version'] = self::$currentModuleVersion;
        }
        file_put_contents(
            self::getProjectRoot() . '/composer.json',
            json_encode($rootComposer, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE)
        );

        $process = Process::fromShellCommandline(
            'perl -pi -e \'s/"version"/"test_version"/g\' ./composer.json',
            self::getProjectRoot()
        );
        $process->run();
        if ($process->getExitCode() !== 0) {
            $message = 'process for <code>'.$process->getCommandLine().'</code> exited with '.$process->getExitCode().': '.$process->getExitCodeText();
            $message .= PHP_EOL.'Error Message:'.PHP_EOL.$process->getErrorOutput();
            $message .= PHP_EOL.'Output:'.PHP_EOL.$process->getOutput();
            echo $message;
        }
    }

    protected static function getBasePath(){
        return realpath(__DIR__.'/../../../../FullStackTest');
    }

    protected static function getProjectRoot(){
        return realpath(__DIR__.'/../../../../..');
    }

    protected static function getComposerCommand(){

        return 'composer.phar';
    }

    protected static function getComposerArgs(){
        return '--prefer-dist --no-dev --no-progress --no-interaction --profile -vvv';
    }

    protected static function logProcessOutput(Process $process, $name = null){
        if($name === null) {
            $name = self::$processLogCounter;
            self::$processLogCounter++;
        }
        $logPath = implode(DIRECTORY_SEPARATOR, [
            self::getBasePath(),
            'logs',
            str_replace('\\', '_', static::class),
            $name . '_Output.log'
        ]);
        if (!is_dir(dirname($logPath))) {
            mkdir(dirname($logPath), 0777, true);
        }
        file_put_contents(
            $logPath,
            $process->getCommandLine() . "\n\n" . $process->getOutput()
        );
    }

    public function assertProcess(Process $process)
    {
        $message = 'process for <code>'.$process->getCommandLine().
            '</code> exited with '.$process->getExitCode().': '.$process->getExitCodeText().
            'from class '.get_class($this);
        $message .= PHP_EOL.'Error Message:'.PHP_EOL.$process->getErrorOutput();
        $message .= PHP_EOL.'Output:'.PHP_EOL.$process->getOutput();
        $this->assertEquals(0, $process->getExitCode(), $message);
    }
}
