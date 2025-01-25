<?php declare(strict_types=1);
use \PHPUnit\Framework\TestCase;
use \PHPUnit\Framework\Attributes\CoversClass;

use \Harmonia\Config;

use \Harmonia\Core\CPath;
use \Harmonia\Core\CFileSystem;

#[CoversClass(Config::class)]
class ConfigTest extends TestCase
{
    private readonly ?Config $originalInstance;
    private CPath $testFilePath;

    protected function setUp(): void
    {
        $this->originalInstance = Config::ReplaceInstance(null);
        $this->testFilePath = CPath::Join(__DIR__, 'config_test.inc.php');
    }

    protected function tearDown(): void
    {
        Config::ReplaceInstance($this->originalInstance);
        if ($this->testFilePath->IsFile()) {
            $this->assertTrue(CFileSystem::Instance()->DeleteFile($this->testFilePath));
        }
    }

    #region __construct --------------------------------------------------------

    function testConstructor()
    {
        $config = Config::Instance();
        $this->assertNull($config->GetOptionsFilePath());
        $this->assertNull($config->GetOptions());
    }

    #endregion __construct

    #region Load ---------------------------------------------------------------

    function testLoadWithNonExistingFile()
    {
        $this->assertFileDoesNotExist((string)$this->testFilePath);
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage(
            "Configuration options file not found: $this->testFilePath");
        Config::Instance()->Load($this->testFilePath);
    }

    function testLoadWithExistingFile()
    {
        $fileContent = <<<PHP
            <?php
            return [
                'key1' => 'value1',
                'key2' => 'value2'
            ];

        PHP;
        $fileContent = \preg_replace('/^[ ]{4}/m', '', $fileContent);
        \file_put_contents((string)$this->testFilePath, $fileContent);
        $config = Config::Instance();
        $config->Load($this->testFilePath);
        $this->assertSame($this->testFilePath, $config->GetOptionsFilePath());
        $this->assertSame(['key1' => 'value1', 'key2' => 'value2'],
                          $config->GetOptions()->ToArray());
    }

    #endregion Load

    #region Reload -------------------------------------------------------------

    function testReloadWithoutLoad()
    {
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('No configuration options file is loaded.');
        Config::Instance()->Reload();
    }

    function testReload()
    {
        $fileContent = <<<PHP
            <?php
            return [
                'key1' => 'value1',
                'key2' => 'value2'
            ];

        PHP;
        $fileContent = \preg_replace('/^[ ]{4}/m', '', $fileContent);
        \file_put_contents((string)$this->testFilePath, $fileContent);
        $config = Config::Instance();
        $config->Load($this->testFilePath);
        $this->assertSame(['key1' => 'value1', 'key2' => 'value2'],
                          $config->GetOptions()->ToArray());
        $fileContent = <<<PHP
            <?php
            return [
                'key1' => 'value1',
                'key2' => 'value2',
                'key3' => 'value3'
            ];

        PHP;
        $fileContent = \preg_replace('/^[ ]{4}/m', '', $fileContent);
        \file_put_contents((string)$this->testFilePath, $fileContent);
        $config->Reload();
        $this->assertSame(['key1' => 'value1', 'key2' => 'value2', 'key3' => 'value3'],
                          $config->GetOptions()->ToArray());
    }

    #endregion Reload
}
