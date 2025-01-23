<?php declare(strict_types=1);
use \PHPUnit\Framework\TestCase;
use \PHPUnit\Framework\Attributes\CoversClass;

use \Harmonia\Core\CFileSystem;

use \Harmonia\Core\CPath;

#[CoversClass(CFileSystem::class)]
class CFileSystemTest extends TestCase
{
    static function setUpBeforeClass(): void
    {
        self::assertTrue(\basename(__DIR__) === 'Core');
    }

    #region CreateDirectory ----------------------------------------------------

    function testCreateDirectoryWithExistingDirectory()
    {
        $directoryPath = CPath::Join(__DIR__, 'test-directory');
        if (!\is_dir((string)$directoryPath)) {
            $this->assertTrue(\mkdir((string)$directoryPath));
        }
        $this->assertTrue(CFileSystem::Instance()->CreateDirectory($directoryPath));
        $this->assertTrue(\is_dir((string)$directoryPath));
        $this->assertTrue(\rmdir((string)$directoryPath));
    }

    function testCreateDirectoryWithNonExistingDirectory()
    {
        $directoryPath = CPath::Join(__DIR__, 'test-directory');
        if (\is_dir((string)$directoryPath)) {
            $this->assertTrue(\rmdir((string)$directoryPath));
        }
        $this->assertTrue(CFileSystem::Instance()->CreateDirectory($directoryPath));
        $this->assertTrue(\is_dir((string)$directoryPath));
        $this->assertTrue(\rmdir((string)$directoryPath));
    }

    #endregion CreateDirectory
}
