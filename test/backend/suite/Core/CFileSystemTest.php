<?php declare(strict_types=1);
use \PHPUnit\Framework\TestCase;
use \PHPUnit\Framework\Attributes\CoversClass;

use \Harmonia\Core\CFileSystem;

use \Harmonia\Core\CPath;

#[CoversClass(CFileSystem::class)]
class CFileSystemTest extends TestCase
{
    private string $testDirectoryPath;
    private string $testFilePath;

    protected function setUp(): void
    {
        $this->testDirectoryPath = (string)CPath::Join(__DIR__, 'test-directory');
        $this->testFilePath = (string)CPath::Join(__DIR__, 'test-file');
        $this->cleanUp();
    }

    protected function tearDown(): void
    {
        $this->cleanUp();
    }

    private function cleanUp()
    {
        if (\is_dir($this->testDirectoryPath)) {
            $this->assertTrue(CFileSystem::Instance()->DeleteDirectory(
                $this->testDirectoryPath));
        }
        if (\is_file($this->testFilePath)) {
            $this->assertTrue(CFileSystem::Instance()->DeleteFile(
                $this->testFilePath));
        }
    }

    private static function createDirectoryStructure(
        string $parentDirectoryPath,
        array $structure
    ) {
        @\mkdir($parentDirectoryPath, 0755, true);
        foreach ($structure as $key => $value) {
            $path = (string)CPath::Join($parentDirectoryPath, $key);
            if (\is_array($value)) {
                \mkdir($path, 0755, true);
                self::createDirectoryStructure($path, $value);
            } else {
                \file_put_contents($path, $value);
            }
        }
    }

    #region CreateDirectory ----------------------------------------------------

    function testCreateDirectoryWithExistingDirectory()
    {
        $this->assertTrue(\mkdir($this->testDirectoryPath));
        $this->assertTrue(\is_dir($this->testDirectoryPath));
        $this->assertTrue(CFileSystem::Instance()->CreateDirectory($this->testDirectoryPath));
        $this->assertTrue(\is_dir($this->testDirectoryPath));
    }

    function testCreateDirectoryWithNonExistingDirectory()
    {
        $this->assertFalse(\is_dir($this->testDirectoryPath));
        $this->assertTrue(CFileSystem::Instance()->CreateDirectory($this->testDirectoryPath));
        $this->assertTrue(\is_dir($this->testDirectoryPath));
    }

    function testCreateDirectoryWithNestedPath()
    {
        $nestedPath = (string)CPath::Join($this->testDirectoryPath, 'foo', 'bar');
        $this->assertFalse(\is_dir($nestedPath));
        $this->assertTrue(CFileSystem::Instance()->CreateDirectory($nestedPath));
        $this->assertTrue(\is_dir($nestedPath));
    }

    #endregion CreateDirectory

    #region DeleteDirectory ----------------------------------------------------

    function testDeleteDirectoryWithNonExistingDirectory()
    {
        $this->assertFalse(\is_dir($this->testDirectoryPath));
        $this->assertFalse(CFileSystem::Instance()->DeleteDirectory($this->testDirectoryPath));
        $this->assertFalse(\is_dir($this->testDirectoryPath));
    }

    function testDeleteDirectoryWithExistingDirectory()
    {
        $this->assertTrue(\mkdir($this->testDirectoryPath));
        $this->assertTrue(\is_dir($this->testDirectoryPath));
        $this->assertTrue(CFileSystem::Instance()->DeleteDirectory($this->testDirectoryPath));
        $this->assertFalse(\is_dir($this->testDirectoryPath));
    }

    function testDeleteDirectoryWithRecursion()
    {
        self::createDirectoryStructure($this->testDirectoryPath, [
            'file' => 'file content',
            'subdir1' => [
                'file1' => 'file1 content',
                'file2' => 'file2 content'
            ],
            'subdir2' => [],
            'subdir3' => [
                'subdir4' => [
                    'file3' => 'file3 content'
                ]
            ]
        ]);
        // Optional: Verify the directory structure before deletion.
        $this->assertTrue(\is_dir($this->testDirectoryPath));
        $this->assertTrue(\is_file((string)CPath::Join($this->testDirectoryPath, 'file')));
        $this->assertTrue(\is_dir((string)CPath::Join($this->testDirectoryPath, 'subdir1')));
        $this->assertTrue(\is_file((string)CPath::Join($this->testDirectoryPath, 'subdir1', 'file1')));
        $this->assertTrue(\is_file((string)CPath::Join($this->testDirectoryPath, 'subdir1', 'file2')));
        $this->assertTrue(\is_dir((string)CPath::Join($this->testDirectoryPath, 'subdir2')));
        $this->assertTrue(\is_file((string)CPath::Join($this->testDirectoryPath, 'subdir3', 'subdir4', 'file3')));

        CFileSystem::Instance()->DeleteDirectory($this->testDirectoryPath);
        $this->assertFalse(\is_dir($this->testDirectoryPath));
    }

    #endregion DeleteDirectory

    #region DeleteFile ---------------------------------------------------------

    function testDeleteFileWithNonExistingFile()
    {
        $this->assertFalse(\is_file($this->testFilePath));
        $this->assertFalse(CFileSystem::Instance()->DeleteFile($this->testFilePath));
        $this->assertFalse(\is_file($this->testFilePath));
    }

    function testDeleteFileWithExistingFile()
    {
        $this->assertTrue(\file_put_contents($this->testFilePath, 'file content') !== false);
        $this->assertTrue(\is_file($this->testFilePath));
        $this->assertTrue(CFileSystem::Instance()->DeleteFile($this->testFilePath));
        $this->assertFalse(\is_file($this->testFilePath));
    }

    #endregion DeleteFile
}
