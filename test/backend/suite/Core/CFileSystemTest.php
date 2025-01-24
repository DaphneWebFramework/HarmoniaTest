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
    ): void
    {
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
        $this->assertDirectoryExists($this->testDirectoryPath);
        $this->assertTrue(CFileSystem::Instance()->CreateDirectory($this->testDirectoryPath));
        $this->assertDirectoryExists($this->testDirectoryPath);
    }

    function testCreateDirectoryWithNonExistingDirectory()
    {
        $this->assertDirectoryDoesNotExist($this->testDirectoryPath);
        $this->assertTrue(CFileSystem::Instance()->CreateDirectory($this->testDirectoryPath));
        $this->assertDirectoryExists($this->testDirectoryPath);
    }

    function testCreateDirectoryWithNestedPath()
    {
        $nestedPath = (string)CPath::Join($this->testDirectoryPath, 'foo', 'bar');
        $this->assertDirectoryDoesNotExist($nestedPath);
        $this->assertTrue(CFileSystem::Instance()->CreateDirectory($nestedPath));
        $this->assertDirectoryExists($nestedPath);
    }

    #endregion CreateDirectory

    #region DeleteDirectory ----------------------------------------------------

    function testDeleteDirectoryWithNonExistingDirectory()
    {
        $this->assertDirectoryDoesNotExist($this->testDirectoryPath);
        $this->assertFalse(CFileSystem::Instance()->DeleteDirectory($this->testDirectoryPath));
        $this->assertDirectoryDoesNotExist($this->testDirectoryPath);
    }

    function testDeleteDirectoryWithExistingDirectory()
    {
        $this->assertTrue(\mkdir($this->testDirectoryPath));
        $this->assertDirectoryExists($this->testDirectoryPath);
        $this->assertTrue(CFileSystem::Instance()->DeleteDirectory($this->testDirectoryPath));
        $this->assertDirectoryDoesNotExist($this->testDirectoryPath);
    }

    function testDeleteDirectoryWithRecursion()
    {
        self::createDirectoryStructure($this->testDirectoryPath, [
            'file' => 'content',
            'subdir1' => [
                'file1' => 'content1',
                'file2' => 'content2'
            ],
            'subdir2' => [],
            'subdir3' => [
                'subdir4' => [
                    'file3' => 'content3'
                ]
            ]
        ]);
        // Optional: Verify the directory structure before deletion.
        $this->assertDirectoryExists($this->testDirectoryPath);
        $this->assertFileExists((string)CPath::Join($this->testDirectoryPath, 'file'));
        $this->assertDirectoryExists((string)CPath::Join($this->testDirectoryPath, 'subdir1'));
        $this->assertFileExists((string)CPath::Join($this->testDirectoryPath, 'subdir1', 'file1'));
        $this->assertFileExists((string)CPath::Join($this->testDirectoryPath, 'subdir1', 'file2'));
        $this->assertDirectoryExists((string)CPath::Join($this->testDirectoryPath, 'subdir2'));
        $this->assertFileExists((string)CPath::Join($this->testDirectoryPath, 'subdir3', 'subdir4', 'file3'));

        CFileSystem::Instance()->DeleteDirectory($this->testDirectoryPath);
        $this->assertDirectoryDoesNotExist($this->testDirectoryPath);
    }

    #endregion DeleteDirectory

    #region DeleteFile ---------------------------------------------------------

    function testDeleteFileWithNonExistingFile()
    {
        $this->assertFileDoesNotExist($this->testFilePath);
        $this->assertFalse(CFileSystem::Instance()->DeleteFile($this->testFilePath));
        $this->assertFileDoesNotExist($this->testFilePath);
    }

    function testDeleteFileWithExistingFile()
    {
        $this->assertTrue(\file_put_contents($this->testFilePath, 'content') !== false);
        $this->assertFileExists($this->testFilePath);
        $this->assertTrue(CFileSystem::Instance()->DeleteFile($this->testFilePath));
        $this->assertFileDoesNotExist($this->testFilePath);
    }

    #endregion DeleteFile

    #region FindFiles ----------------------------------------------------------

    function testFindFilesWithNonExistingDirectory()
    {
        $actualPaths = [];
        $generator = CFileSystem::Instance()->FindFiles(
            $this->testDirectoryPath,
            '*.txt'
        );
        foreach ($generator as $filePath) {
            $actualPaths[] = $filePath;
        }
        $this->assertEmpty($actualPaths);
    }

    function testFindFilesWithEmptyDirectory()
    {
        $this->assertTrue(\mkdir($this->testDirectoryPath));
        $actualPaths = [];
        $generator = CFileSystem::Instance()->FindFiles(
            $this->testDirectoryPath,
            '*.txt'
        );
        foreach ($generator as $filePath) {
            $actualPaths[] = $filePath;
        }
        $this->assertEmpty($actualPaths);
    }

    function testFindFilesRecursive()
    {
        self::createDirectoryStructure($this->testDirectoryPath, [
            'file1.txt' => 'content1',
            'file2.log' => 'content2',
            'subdir' => [
                'file3.txt' => 'content3',
                'file4.log' => 'content4',
                'subdir2' => [
                    'file5.txt' => 'content5',
                    'file6.log' => 'content6'
                ]
            ]
        ]);
        $expectedPaths = [
            CPath::Join($this->testDirectoryPath, 'file1.txt'),
            CPath::Join($this->testDirectoryPath, 'subdir', 'file3.txt'),
            CPath::Join($this->testDirectoryPath, 'subdir', 'subdir2', 'file5.txt')
        ];
        $actualPaths = [];
        $generator = CFileSystem::Instance()->FindFiles(
            $this->testDirectoryPath,
            '*.txt',
            true
        );
        foreach ($generator as $filePath) {
            $actualPaths[] = $filePath;
        }
        \sort($expectedPaths);
        \sort($actualPaths);
        $this->assertEquals($expectedPaths, $actualPaths);
    }

    function testFindFilesNonRecursive()
    {
        self::createDirectoryStructure($this->testDirectoryPath, [
            'file1.txt' => 'content1',
            'file2.log' => 'content2',
            'subdir' => [
                'file3.txt' => 'content3',
                'file4.log' => 'content4',
                'subdir2' => [
                    'file5.txt' => 'content5',
                    'file6.log' => 'content6'
                ]
            ]
        ]);
        $expectedPaths = [
            CPath::Join($this->testDirectoryPath, 'file1.txt'),
        ];
        $actualPaths = [];
        $generator = CFileSystem::Instance()->FindFiles(
            $this->testDirectoryPath,
            '*.txt',
            false
        );
        foreach ($generator as $filePath) {
            $actualPaths[] = $filePath;
        }
        \sort($expectedPaths);
        \sort($actualPaths);
        $this->assertEquals($expectedPaths, $actualPaths);
    }

    #endregion FindFiles
}
