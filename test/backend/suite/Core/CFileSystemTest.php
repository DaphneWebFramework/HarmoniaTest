<?php declare(strict_types=1);
use \PHPUnit\Framework\TestCase;
use \PHPUnit\Framework\Attributes\CoversClass;

use \Harmonia\Core\CFileSystem;

use \Harmonia\Core\CPath;

#[CoversClass(CFileSystem::class)]
class CFileSystemTest extends TestCase
{
    private CPath $testDirectoryPath;
    private CPath $testFilePath;

    protected function setUp(): void
    {
        $this->testDirectoryPath = CPath::Join(__DIR__, 'test-directory');
        $this->testFilePath = CPath::Join(__DIR__, 'test-file');
        $this->cleanUp();
    }

    protected function tearDown(): void
    {
        $this->cleanUp();
    }

    private function cleanUp()
    {
        $fs = CFileSystem::Instance();
        if ($this->testDirectoryPath->Call('\is_dir')) {
            $this->assertTrue($fs->DeleteDirectory($this->testDirectoryPath));
        }
        if ($this->testFilePath->Call('\is_file')) {
            $this->assertTrue($fs->DeleteFile($this->testFilePath));
        }
    }

    private static function createDirectoryStructure(
        CPath $parentDirectoryPath,
        array $structure
    ): void
    {
        @$parentDirectoryPath->Call('\mkdir', 0755, true);
        foreach ($structure as $key => $value) {
            $path = $parentDirectoryPath->Extend($key);
            if (\is_array($value)) {
                $path->Call('\mkdir', 0755, true);
                self::createDirectoryStructure($path, $value);
            } else {
                $path->Call('\file_put_contents', $value);
            }
        }
    }

    #region CreateDirectory ----------------------------------------------------

    function testCreateDirectoryWithExistingDirectory()
    {
        $this->assertTrue($this->testDirectoryPath->Call('\mkdir'));
        $this->assertDirectoryExists((string)$this->testDirectoryPath);
        $this->assertTrue(CFileSystem::Instance()->CreateDirectory($this->testDirectoryPath));
        $this->assertDirectoryExists((string)$this->testDirectoryPath);
    }

    function testCreateDirectoryWithNonExistingDirectory()
    {
        $this->assertDirectoryDoesNotExist((string)$this->testDirectoryPath);
        $this->assertTrue(CFileSystem::Instance()->CreateDirectory($this->testDirectoryPath));
        $this->assertDirectoryExists((string)$this->testDirectoryPath);
    }

    function testCreateDirectoryWithNestedPath()
    {
        $nestedPath = $this->testDirectoryPath->Extend('foo', 'bar');
        $this->assertDirectoryDoesNotExist((string)$nestedPath);
        $this->assertTrue(CFileSystem::Instance()->CreateDirectory($nestedPath));
        $this->assertDirectoryExists((string)$nestedPath);
    }

    #endregion CreateDirectory

    #region DeleteDirectory ----------------------------------------------------

    function testDeleteDirectoryWithNonExistingDirectory()
    {
        $this->assertDirectoryDoesNotExist((string)$this->testDirectoryPath);
        $this->assertFalse(CFileSystem::Instance()->DeleteDirectory($this->testDirectoryPath));
        $this->assertDirectoryDoesNotExist((string)$this->testDirectoryPath);
    }

    function testDeleteDirectoryWithExistingDirectory()
    {
        $this->assertTrue($this->testDirectoryPath->Call('\mkdir'));
        $this->assertDirectoryExists((string)$this->testDirectoryPath);
        $this->assertTrue(CFileSystem::Instance()->DeleteDirectory($this->testDirectoryPath));
        $this->assertDirectoryDoesNotExist((string)$this->testDirectoryPath);
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
        $this->assertDirectoryExists((string)$this->testDirectoryPath);
        $this->assertFileExists((string)$this->testDirectoryPath->Extend('file'));
        $this->assertDirectoryExists((string)$this->testDirectoryPath->Extend('subdir1'));
        $this->assertFileExists((string)$this->testDirectoryPath->Extend('subdir1', 'file1'));
        $this->assertFileExists((string)$this->testDirectoryPath->Extend('subdir1', 'file2'));
        $this->assertDirectoryExists((string)$this->testDirectoryPath->Extend('subdir2'));
        $this->assertFileExists((string)$this->testDirectoryPath->Extend('subdir3', 'subdir4', 'file3'));

        CFileSystem::Instance()->DeleteDirectory($this->testDirectoryPath);
        $this->assertDirectoryDoesNotExist((string)$this->testDirectoryPath);
    }

    #endregion DeleteDirectory

    #region DeleteFile ---------------------------------------------------------

    function testDeleteFileWithNonExistingFile()
    {
        $this->assertFileDoesNotExist((string)$this->testFilePath);
        $this->assertFalse(CFileSystem::Instance()->DeleteFile($this->testFilePath));
        $this->assertFileDoesNotExist((string)$this->testFilePath);
    }

    function testDeleteFileWithExistingFile()
    {
        $this->testFilePath->Call('\file_put_contents', 'content');
        $this->assertFileExists((string)$this->testFilePath);
        $this->assertTrue(CFileSystem::Instance()->DeleteFile($this->testFilePath));
        $this->assertFileDoesNotExist((string)$this->testFilePath);
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
        $this->assertTrue($this->testDirectoryPath->Call('\mkdir'));
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
            $this->testDirectoryPath->Extend('file1.txt'),
            $this->testDirectoryPath->Extend('subdir', 'file3.txt'),
            $this->testDirectoryPath->Extend('subdir', 'subdir2', 'file5.txt')
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
            $this->testDirectoryPath->Extend('file1.txt'),
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

    #region ModificationTime ---------------------------------------------------

    function testModificationTimeReturnsZeroForNonExistingFileOrDirectory()
    {
        $this->assertFileDoesNotExist((string)$this->testFilePath);
        $this->assertSame(
            0,
            CFileSystem::Instance()->ModificationTime($this->testFilePath)
        );
    }

    function testModificationTimeReturnsNonZeroTimestampForExistingFile()
    {
        $this->testFilePath->Call('\file_put_contents', 'content');
        $this->assertFileExists((string)$this->testFilePath);
        $this->assertGreaterThan(
            0,
            CFileSystem::Instance()->ModificationTime($this->testFilePath)
        );
    }

    function testModificationTimeReturnsNonZeroTimestampForExistingDirectory()
    {
        $this->testDirectoryPath->Call('\mkdir');
        $this->assertDirectoryExists((string)$this->testDirectoryPath);
        $this->assertGreaterThan(
            0,
            CFileSystem::Instance()->ModificationTime($this->testDirectoryPath)
        );
    }

    #endregion ModificationTime
}
