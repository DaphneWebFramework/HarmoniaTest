<?php declare(strict_types=1);
use \PHPUnit\Framework\TestCase;
use \PHPUnit\Framework\Attributes\CoversClass;

use \Harmonia\Config;

use \Harmonia\Core\CPath;
use \Harmonia\Core\CFileSystem;
use \TestToolkit\AccessHelper;

#[CoversClass(Config::class)]
class ConfigTest extends TestCase
{
    private ?Config $originalConfig = null;
    private CPath $testFilePath;

    protected function setUp(): void
    {
        $this->originalConfig = Config::ReplaceInstance(null);
        $this->testFilePath = CPath::Join(__DIR__, 'config_test.inc.php');
    }

    protected function tearDown(): void
    {
        Config::ReplaceInstance($this->originalConfig);
        if ($this->testFilePath->Call('\is_file')) {
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
        $this->testFilePath->Call(
            '\file_put_contents',
            \preg_replace('/^[ ]{4}/m', '', $fileContent)
        );
    }

    #region __construct --------------------------------------------------------

    function testConstructor()
    {
        $config = Config::Instance();
        $this->assertCount(0, AccessHelper::GetProperty($config, 'options'));
        $this->assertNull($config->OptionsFilePath());
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
        $this->assertSame($this->testFilePath, $config->OptionsFilePath());
        $this->assertSame(
            ['key1' => 'value1', 'key2' => 'value2'],
            AccessHelper::GetProperty($config, 'options')->ToArray()
        );
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
        $this->assertSame(
            ['key1' => 'value1', 'key2' => 'value2'],
            AccessHelper::GetProperty($config, 'options')->ToArray()
        );
        $this->createTestFile(<<<PHP
            <?php
            return [
                'key1' => 'value1',
                'key2' => 'value2',
                'key3' => 'value3'
            ];

        PHP);
        $config->Reload();
        $this->assertSame(
            ['key1' => 'value1', 'key2' => 'value2', 'key3' => 'value3'],
            AccessHelper::GetProperty($config, 'options')->ToArray()
        );
    }

    #endregion Reload

    #region Option -------------------------------------------------------------

    function testOptionWithoutLoad()
    {
        $config = Config::Instance();
        $this->assertNull($config->Option('key'));
    }

    function testOptionWithLoad()
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
        $this->assertSame('value1', $config->Option('key1'));
        $this->assertSame('value2', $config->Option('key2'));
        $this->assertNull($config->Option('key3'));
    }

    #endregion Option

    #region OptionOrDefault ----------------------------------------------------

    function testOptionOrDefaultWithoutLoad()
    {
        $config = Config::Instance();
        $this->assertSame('default', $config->OptionOrDefault('key', 'default'));
    }

    function testOptionOrDefaultWithLoad()
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
        $this->assertSame('value1', $config->OptionOrDefault('key1', 'default'));
        $this->assertSame('value2', $config->OptionOrDefault('key2', 'default'));
        $this->assertSame('default', $config->OptionOrDefault('key3', 'default'));
    }

    #endregion OptionOrDefault

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
        $this->assertSame('new_value1', $config->Option('key1'));
    }

    #endregion SetOption
}
