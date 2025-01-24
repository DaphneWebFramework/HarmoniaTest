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
    private readonly ?Config $previousInstance;
    private CPath $testFilePath;

    protected function setUp(): void
    {
        $this->previousInstance = Config::ReplaceInstance(null);
        $this->testFilePath = CPath::Join(__DIR__, 'config_test.inc.php');
    }

    protected function tearDown(): void
    {
        Config::ReplaceInstance($this->previousInstance);
        if ($this->testFilePath->IsFile()) {
            $this->assertTrue(CFileSystem::Instance()->DeleteFile($this->testFilePath));
        }
    }

    #region __construct --------------------------------------------------------

    function testConstructor()
    {
        $config = Config::Instance();
        $this->assertNull(AccessHelper::GetNonPublicProperty($config, 'optionsFilePath'));
        $this->assertNull(AccessHelper::GetNonPublicProperty($config, 'options'));
    }

    #endregion __construct

    #region Load ---------------------------------------------------------------

    function testLoadWithNonExistingFile()
    {
        $this->assertFileDoesNotExist((string)$this->testFilePath);
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage("Configuration options file not found: $this->testFilePath");
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
        $this->assertSame($this->testFilePath,
            AccessHelper::GetNonPublicProperty($config, 'optionsFilePath'));
        $this->assertSame(['key1' => 'value1', 'key2' => 'value2'],
            AccessHelper::GetNonPublicProperty($config, 'options')->ToArray());
    }

    #endregion Load
}
