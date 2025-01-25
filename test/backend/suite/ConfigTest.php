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

    /**
     * Writes the specified content to the test file.
     *
     * Leading 4-space indentation is removed from each line.
     *
     * @param string $fileContent
     *   The content to write to the test file.
     */
    private function createTestFile(string $fileContent): void
    {
        \file_put_contents(
            (string)$this->testFilePath,
            \preg_replace('/^[ ]{4}/m', '', $fileContent)
        );
    }

    #region __construct --------------------------------------------------------

    function testConstructor()
    {
        $config = Config::Instance();
        $this->assertCount(0, $config->GetOptions());
        $this->assertNull($config->GetOptionsFilePath());
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
        $this->createTestFile(<<<PHP
            <?php
            return [
                'key1' => 'value1',
                'key2' => 'value2'
            ];

        PHP);
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
        $this->createTestFile(<<<PHP
            <?php
            return [
                'key1' => 'value1',
                'key2' => 'value2'
            ];

        PHP);
        $config = Config::Instance();
        $config->Load($this->testFilePath);
        $this->assertSame(['key1' => 'value1', 'key2' => 'value2'],
                          $config->GetOptions()->ToArray());
        $this->createTestFile(<<<PHP
            <?php
            return [
                'key1' => 'value1',
                'key2' => 'value2',
                'key3' => 'value3'
            ];

        PHP);
        $config->Reload();
        $this->assertSame(['key1' => 'value1', 'key2' => 'value2', 'key3' => 'value3'],
                          $config->GetOptions()->ToArray());
    }

    #endregion Reload

    #region GetOption ----------------------------------------------------------

    function testGetOptionWithoutLoad()
    {
        $config = Config::Instance();
        $this->assertNull($config->GetOption('key'));
    }

    function testGetOptionWithLoad()
    {
        $this->createTestFile(<<<PHP
            <?php
            return [
                'key1' => 'value1',
                'key2' => 'value2'
            ];

        PHP);
        $config = Config::Instance();
        $config->Load($this->testFilePath);
        $this->assertSame('value1', $config->GetOption('key1'));
        $this->assertSame('value2', $config->GetOption('key2'));
        $this->assertNull($config->GetOption('key3'));
    }

    #endregion GetOption

    #region SetOption ----------------------------------------------------------

    function testSetOptionWithoutLoad()
    {
        $config = Config::Instance();
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Configuration option not found: key1');
        $config->SetOption('key1', 'value1');
    }

    function testSetOptionWithNonExistingKey()
    {
        $this->createTestFile(<<<PHP
            <?php
            return [
                'key1' => 'value1',
                'key2' => 'value2'
            ];

        PHP);
        $config = Config::Instance();
        $config->Load($this->testFilePath);
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Configuration option not found: key3');
        $config->SetOption('key3', 'value3');
    }

    function testSetOptionWithTypeMismatch()
    {
        $this->createTestFile(<<<PHP
            <?php
            return [
                'key1' => 'value1',
                'key2' => 'value2'
            ];

        PHP);
        $config = Config::Instance();
        $config->Load($this->testFilePath);
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Configuration option type mismatch: key1');
        $config->SetOption('key1', 123);
    }

    function testSetOption()
    {
        $this->createTestFile(<<<PHP
            <?php
            return [
                'key1' => 'value1',
                'key2' => 'value2'
            ];

        PHP);
        $config = Config::Instance();
        $config->Load($this->testFilePath);
        $config->SetOption('key1', 'new_value1');
        $this->assertSame('new_value1', $config->GetOption('key1'));
    }

    #endregion SetOption
}
