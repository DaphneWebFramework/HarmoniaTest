<?php declare(strict_types=1);
use \PHPUnit\Framework\TestCase;
use \PHPUnit\Framework\Attributes\CoversClass;

use \Harmonia\Config;

use \Harmonia\Core\CFileSystem;
use \TestToolkit\AccessHelper as ah;

#[CoversClass(Config::class)]
class ConfigTest extends TestCase
{
    private string $testFilePath;

    protected function setUp(): void
    {
        $this->testFilePath = __DIR__ . '/config_test.inc.php';
    }

    protected function tearDown(): void
    {
        if (\is_file($this->testFilePath)) {
            $fileSystem = CFileSystem::Instance();
            $this->assertTrue($fileSystem->DeleteFile($this->testFilePath));
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
            $this->testFilePath,
            \preg_replace('/^[ ]{4}/m', '', $fileContent)
        );
    }

    private function systemUnderTest(string ...$mockedMethods): Config
    {
        $mock = $this->getMockBuilder(Config::class)
            ->disableOriginalConstructor()
            ->onlyMethods($mockedMethods)
            ->getMock();
        return ah::CallConstructor($mock);
    }

    #region __construct --------------------------------------------------------

    function testConstructor()
    {
        $config = $this->systemUnderTest();
        $this->assertCount(0, ah::GetProperty($config, 'options'));
        $this->assertNull(ah::GetProperty($config, 'optionsFilePath'));
    }

    #endregion __construct

    #region Load ---------------------------------------------------------------

    function testLoadWithNonExistingFile()
    {
        $config = $this->systemUnderTest();
        $this->assertFileDoesNotExist($this->testFilePath);
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage(
            "Configuration options file not found: $this->testFilePath");
        $config->Load($this->testFilePath);
    }

    function testLoadWithExistingFile()
    {
        $config = $this->systemUnderTest();
        $this->createTestFile(<<<PHP
            <?php
            return [
                'key1' => 'value1',
                'key2' => 'value2'
            ];

        PHP);
        $config->Load($this->testFilePath);
        $this->assertSame(
            $this->testFilePath,
            ah::GetProperty($config, 'optionsFilePath')
        );
        $this->assertSame(
            ['key1' => 'value1', 'key2' => 'value2'],
            ah::GetProperty($config, 'options')->ToArray()
        );
    }

    #endregion Load

    #region Reload -------------------------------------------------------------

    function testReloadWithoutLoad()
    {
        $config = $this->systemUnderTest();
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('No configuration options file is loaded.');
        $config->Reload();
    }

    function testReload()
    {
        $config = $this->systemUnderTest();
        $this->createTestFile(<<<PHP
            <?php
            return [
                'key1' => 'value1',
                'key2' => 'value2'
            ];

        PHP);
        $config->Load($this->testFilePath);
        $this->assertSame(
            ['key1' => 'value1', 'key2' => 'value2'],
            ah::GetProperty($config, 'options')->ToArray()
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
            ah::GetProperty($config, 'options')->ToArray()
        );
    }

    #endregion Reload

    #region Option -------------------------------------------------------------

    function testOptionWithoutLoad()
    {
        $config = $this->systemUnderTest();
        $this->assertNull($config->Option('key'));
    }

    function testOptionWithLoad()
    {
        $config = $this->systemUnderTest();
        $this->createTestFile(<<<PHP
            <?php
            return [
                'key1' => 'value1',
                'key2' => 'value2'
            ];

        PHP);
        $config->Load($this->testFilePath);
        $this->assertSame('value1', $config->Option('key1'));
        $this->assertSame('value2', $config->Option('key2'));
        $this->assertNull($config->Option('key3'));
    }

    #endregion Option

    #region OptionOrDefault ----------------------------------------------------

    function testOptionOrDefaultWithoutLoad()
    {
        $config = $this->systemUnderTest();
        $this->assertSame('default', $config->OptionOrDefault('key', 'default'));
    }

    function testOptionOrDefaultWithLoad()
    {
        $config = $this->systemUnderTest();
        $this->createTestFile(<<<PHP
            <?php
            return [
                'key1' => 'value1',
                'key2' => 'value2'
            ];

        PHP);
        $config->Load($this->testFilePath);
        $this->assertSame('value1', $config->OptionOrDefault('key1', 'default'));
        $this->assertSame('value2', $config->OptionOrDefault('key2', 'default'));
        $this->assertSame('default', $config->OptionOrDefault('key3', 'default'));
    }

    #endregion OptionOrDefault

    #region SetOption ----------------------------------------------------------

    function testSetOptionWithoutLoad()
    {
        $config = $this->systemUnderTest();
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Configuration option not found: key1');
        $config->SetOption('key1', 'value1');
    }

    function testSetOptionWithNonExistingKey()
    {
        $config = $this->systemUnderTest();
        $this->createTestFile(<<<PHP
            <?php
            return [
                'key1' => 'value1',
                'key2' => 'value2'
            ];

        PHP);
        $config->Load($this->testFilePath);
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Configuration option not found: key3');
        $config->SetOption('key3', 'value3');
    }

    function testSetOptionWithTypeMismatch()
    {
        $config = $this->systemUnderTest();
        $this->createTestFile(<<<PHP
            <?php
            return [
                'key1' => 'value1',
                'key2' => 'value2'
            ];

        PHP);
        $config->Load($this->testFilePath);
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Configuration option type mismatch: key1');
        $config->SetOption('key1', 123);
    }

    function testSetOption()
    {
        $config = $this->systemUnderTest();
        $this->createTestFile(<<<PHP
            <?php
            return [
                'key1' => 'value1',
                'key2' => 'value2'
            ];

        PHP);
        $config->Load($this->testFilePath);
        $config->SetOption('key1', 'new_value1');
        $this->assertSame('new_value1', $config->Option('key1'));
    }

    #endregion SetOption
}
