<?php declare(strict_types=1);
use \PHPUnit\Framework\TestCase;
use \PHPUnit\Framework\Attributes\CoversClass;
use \PHPUnit\Framework\Attributes\DataProvider;
use \PHPUnit\Framework\Attributes\RequiresOperatingSystem;

use \Harmonia\Core\CPath;

#[CoversClass(CPath::class)]
class CPathTest extends TestCase
{
    static ?string $originalWorkingDirectory = null;

    /**
     * Ensures tests run within the "test/backend" directory. Locally, the
     * working directory is often already set correctly, but in CI environments
     * like GitHub Actions, it typically starts at the project root. This method
     * checks for the presence of "phpunit.xml" to confirm the correct directory.
     * If not found, it changes the working directory to "test/backend".
     */
    static function setUpBeforeClass(): void
    {
        $cwd = \getcwd();
        if (!CPath::Join($cwd, 'phpunit.xml')->IsFile()) {
            \chdir((string)CPath::Join(__DIR__, '..', '..'));
            self::$originalWorkingDirectory = $cwd;
        }
    }

    /**
     * Restore the original working directory after the test suite completes,
     * but only if it was changed during `setUpBeforeClass`.
     */
    static function tearDownAfterClass(): void
    {
        if (self::$originalWorkingDirectory !== null) {
            \chdir(self::$originalWorkingDirectory);
            self::$originalWorkingDirectory = null;
        }
    }

    #region __construct --------------------------------------------------------

    function testDefaultConstructor()
    {
        $path = new CPath();
        $this->assertSame('', (string)$path);
    }

    function testConstructorWithStringable()
    {
        $stringable = new class() implements \Stringable {
            function __toString() {
                return '/home/user';
            }
        };
        $path = new CPath($stringable);
        $this->assertSame('/home/user', (string)$path);
    }

    function testConstructorWithNativeString()
    {
        $path = new CPath('/var/www');
        $this->assertSame('/var/www', (string)$path);
    }

    function testConstructorTrimsWhitespace()
    {
        $path = new CPath('   /usr/local   ');
        $this->assertSame('/usr/local', (string)$path);
    }

    #endregion __construct

    #region Join ---------------------------------------------------------------

    #[DataProvider('joinDataProvider')]
    function testJoin(string $expected, array $segments)
    {
        $joined = CPath::Join(...$segments);
        $this->assertInstanceOf(CPath::class, $joined);
        $this->assertSame($expected, (string)$joined);
    }

    function testJoinWithMixedArguments()
    {
        if (\PHP_OS_FAMILY === 'Windows') {
            $joined = CPath::Join(
                'C:',
                new class() implements \Stringable {
                    function __toString() {
                        return 'xampp';
                    }
                },
                'htdocs',
                'index.html'
            );
            $this->assertSame('C:\\xampp\\htdocs\\index.html', (string)$joined);
        } else {
            $joined = CPath::Join(
                '/var',
                new class() implements \Stringable {
                    function __toString() {
                        return 'www';
                    }
                },
                'html',
                'index.html'
            );
            $this->assertSame('/var/www/html/index.html', (string)$joined);
        }
    }

    #endregion Join

    #region EnsureLeadingSlash -------------------------------------------------

    #[DataProvider('ensureLeadingSlashDataProvider')]
    function testEnsureLeadingSlash(string $expected, string $str)
    {
        $path = new CPath($str);
        $this->assertSame($expected, (string)$path->EnsureLeadingSlash());
    }

    #endregion EnsureLeadingSlash

    #region EnsureTrailingSlash ------------------------------------------------

    #[DataProvider('ensureTrailingSlashDataProvider')]
    function testEnsureTrailingSlash(string $expected, string $str)
    {
        $path = new CPath($str);
        $this->assertSame($expected, (string)$path->EnsureTrailingSlash());
    }

    #endregion EnsureTrailingSlash

    #region TrimLeadingSlashes -------------------------------------------------

    #[DataProvider('trimLeadingSlashesDataProvider')]
    function testTrimLeadingSlashes(string $expected, string $str)
    {
        $path = new CPath($str);
        $this->assertSame($expected, (string)$path->TrimLeadingSlashes());
    }

    #endregion TrimLeadingSlashes

    #region TrimTrailingSlashes ------------------------------------------------

    #[DataProvider('trimTrailingSlashesDataProvider')]
    function testTrimTrailingSlashes(string $expected, string $str)
    {
        $path = new CPath($str);
        $this->assertSame($expected, (string)$path->TrimTrailingSlashes());
    }

    #endregion TrimTrailingSlashes

    #region IsFile -------------------------------------------------------------

    #[DataProvider('isFileDataProvider')]
    function testIsFile($expected, $value)
    {
        $path = new CPath($value);
        $this->assertSame($expected, $path->IsFile());
    }

    #endregion IsFile

    #region IsDirectory --------------------------------------------------------

    #[DataProvider('isDirectoryDataProvider')]
    function testIsDirectory($expected, $value)
    {
        $path = new CPath($value);
        $this->assertSame($expected, $path->IsDirectory());
    }

    #endregion IsDirectory

    #region IsLink -------------------------------------------------------------

    #[RequiresOperatingSystem('Linux|Darwin')]
    function testIsLinkFailsWithAFile()
    {
        $path = new CPath(__FILE__);
        $this->assertFalse($path->IsLink());
    }

    #[RequiresOperatingSystem('Linux|Darwin')]
    function testIsLinkFailsWithADirectory()
    {
        $path = new CPath(__DIR__);
        $this->assertFalse($path->IsLink());
    }

    #[RequiresOperatingSystem('Linux|Darwin')]
    function testIsLinkSucceedsWithALinkToAFile()
    {
        $path = CPath::Join(\sys_get_temp_dir(), \uniqid('test_'));
        $this->assertTrue(\symlink(__FILE__, (string)$path));
        $isLink = $path->IsLink();
        \unlink((string)$path);
        $this->assertTrue($isLink);
    }

    #[RequiresOperatingSystem('Linux|Darwin')]
    function testIsLinkSucceedsWithALinkToADirectory()
    {
        $path = CPath::Join(\sys_get_temp_dir(), \uniqid('test_'));
        $this->assertTrue(\symlink(__DIR__, (string)$path));
        $isLink = $path->IsLink();
        \unlink((string)$path);
        $this->assertTrue($isLink);
    }

    #endregion IsLink

    #region ReadLink -----------------------------------------------------------

    #[RequiresOperatingSystem('Linux|Darwin')]
    function testReadLinkFailsWithAFile()
    {
        $path = new CPath(__FILE__);
        $this->assertNull($path->ReadLink());
    }

    #[RequiresOperatingSystem('Linux|Darwin')]
    function testReadLinkFailsWithADirectory()
    {
        $path = new CPath(__DIR__);
        $this->assertNull($path->ReadLink());
    }

    #[RequiresOperatingSystem('Linux|Darwin')]
    function testReadLinkSucceedsWithALinkToAFile()
    {
        $path = CPath::Join(\sys_get_temp_dir(), \uniqid('test_'));
        $this->assertTrue(\symlink(__FILE__, (string)$path));
        $readLink = $path->ReadLink();
        \unlink((string)$path);
        $this->assertInstanceOf(CPath::class, $readLink);
        $this->assertEquals(__FILE__, $readLink);
    }

    #[RequiresOperatingSystem('Linux|Darwin')]
    function testReadLinkSucceedsWithALinkToADirectory()
    {
        $path = CPath::Join(\sys_get_temp_dir(), \uniqid('test_'));
        $this->assertTrue(\symlink(__DIR__, (string)$path));
        $readLink = $path->ReadLink();
        \unlink((string)$path);
        $this->assertInstanceOf(CPath::class, $readLink);
        $this->assertEquals(__DIR__, $readLink);
    }

    #endregion ReadLink

    #region BaseName -----------------------------------------------------------

    #[DataProvider('baseNameDataProvider')]
    function testBaseName($expected, $value)
    {
        $path = new CPath($value);
        $this->assertSame($expected, $path->BaseName());
    }

    #endregion BaseName

    #region ToAbsolute ---------------------------------------------------------

    #[DataProvider('toAbsoluteDataProvider')]
    function testToAbsolute($expected, $value)
    {
        $path = new CPath($value);
        $absolutePath = $path->ToAbsolute();
        if ($expected === null) {
            $this->assertNull($absolutePath);
        } else {
            $this->assertSame($expected, (string)$absolutePath);
        }
    }

    function testToAbsoluteWithBasePath()
    {
        $path = new CPath('.gitkeep');
        // First, call ToAbsolute without providing a base path. This should
        // fail because '.gitkeep' cannot be resolved in the current working
        // directory.
        $this->assertNull($path->ToAbsolute());
        // Then, call ToAbsolute with a valid base path (where '.gitkeep' is
        // located). This should resolve the path correctly relative to the
        // provided base path.
        $this->assertSame((string)CPath::Join(\getcwd(), 'suite', '.gitkeep'),
            (string)$path->ToAbsolute('suite'));
        // Finally, ensure the original working directory is restored after
        // calling ToAbsolute with a base path. This confirms that the base
        // path was only temporarily set and the current working directory
        // remains unchanged.
        $this->assertNull($path->ToAbsolute());
    }

    function testToAbsoluteWithStringableBasePath()
    {
        $basePath = new class implements \Stringable {
            function __toString(): string {
                return 'suite';
            }
        };
        $path = new CPath('.gitkeep');
        $this->assertSame((string)CPath::Join(\getcwd(), 'suite', '.gitkeep'),
            (string)$path->ToAbsolute($basePath));
    }

    function testToAbsoluteWithNonExistentBasePath()
    {
        $path = new CPath('phpunit.xml');
        $this->assertNull($path->ToAbsolute('non_existent_base_path'));
    }

    #endregion ToAbsolute

    #region Data Providers -----------------------------------------------------

    static function joinDataProvider()
    {
        if (\PHP_OS_FAMILY === 'Windows') {
            return [
                ['C:', ['C:']],
                ['C:\\', ['C:\\']],
                ['C:\\foo', ['C:', 'foo']],
                ['C:\\foo', ['C:\\', 'foo']],
                ['C:\\foo\\', ['C:', 'foo\\']],
                ['C:\\foo\\', ['C:\\', 'foo\\']],
                ['C:\\foo\\bar', ['C:', 'foo', 'bar']],
                ['C:\\foo\\bar', ['C:\\', 'foo', 'bar']],
                ['C:\\foo\\bar', ['C:', 'foo\\', 'bar']],
                ['C:\\foo\\bar', ['C:\\', 'foo\\', 'bar']],
                ['C:\\foo\\bar\\', ['C:', 'foo', 'bar\\']],
                ['C:\\foo\\bar\\', ['C:\\', 'foo', 'bar\\']],
                ['C:\\foo\\bar\\', ['C:', 'foo\\', 'bar\\']],
                ['C:\\foo\\bar\\', ['C:\\', 'foo\\', 'bar\\']],
                ['C:\\foo\\bar\\fat.txt', ['C:', 'foo', 'bar', 'fat.txt']],
                ['C:\\foo\\bar\\fat.txt', ['C:\\', 'foo', 'bar', 'fat.txt']],
                ['C:\\foo\\bar\\fat.txt', ['C:', 'foo\\', 'bar', 'fat.txt']],
                ['C:\\foo\\bar\\fat.txt', ['C:\\', 'foo\\', 'bar', 'fat.txt']],
                ['C:\\foo\\bar\\fat.txt', ['C:', 'foo', 'bar\\', 'fat.txt']],
                ['C:\\foo\\bar\\fat.txt', ['C:\\', 'foo', 'bar\\', 'fat.txt']],
                ['C:\\foo\\bar\\fat.txt', ['C:', 'foo\\', 'bar\\', 'fat.txt']],
                ['C:\\foo\\bar\\fat.txt', ['C:\\', 'foo\\', 'bar\\', 'fat.txt']],

                ['C:', ['C:']],
                ['C:/', ['C:/']],
                ['C:\\foo', ['C:', 'foo']],
                ['C:/foo', ['C:/', 'foo']],
                ['C:\\foo/', ['C:', 'foo/']],
                ['C:/foo/', ['C:/', 'foo/']],
                ['C:\\foo\\bar', ['C:', 'foo', 'bar']],
                ['C:/foo\\bar', ['C:/', 'foo', 'bar']],
                ['C:\\foo/bar', ['C:', 'foo/', 'bar']],
                ['C:/foo/bar', ['C:/', 'foo/', 'bar']],
                ['C:\\foo\\bar/', ['C:', 'foo', 'bar/']],
                ['C:/foo\\bar/', ['C:/', 'foo', 'bar/']],
                ['C:\\foo/bar/', ['C:', 'foo/', 'bar/']],
                ['C:/foo/bar/', ['C:/', 'foo/', 'bar/']],
                ['C:\\foo\\bar\\fat.txt', ['C:', 'foo', 'bar', 'fat.txt']],
                ['C:/foo\\bar\\fat.txt', ['C:/', 'foo', 'bar', 'fat.txt']],
                ['C:\\foo/bar\\fat.txt', ['C:', 'foo/', 'bar', 'fat.txt']],
                ['C:/foo/bar\\fat.txt', ['C:/', 'foo/', 'bar', 'fat.txt']],
                ['C:\\foo\\bar/fat.txt', ['C:', 'foo', 'bar/', 'fat.txt']],
                ['C:/foo\\bar/fat.txt', ['C:/', 'foo', 'bar/', 'fat.txt']],
                ['C:\\foo/bar/fat.txt', ['C:', 'foo/', 'bar/', 'fat.txt']],
                ['C:/foo/bar/fat.txt', ['C:/', 'foo/', 'bar/', 'fat.txt']],

                ['\\foo\\bar\\fat', ['\\foo', 'bar', 'fat']],
                ['foo\\bar\\fat', ['foo\\', 'bar', 'fat']],
                ['\\foo\\bar\\fat', ['\\foo\\', 'bar', 'fat']],
                ['\\foo\\bar\\fat', ['\\foo', '\\bar', 'fat']],
                ['foo\\bar\\fat', ['foo\\', '\\bar', 'fat']],
                ['\\foo\\bar\\fat', ['\\foo\\', '\\bar', 'fat']],
                ['\\foo\\bar\\fat', ['\\foo', 'bar\\', 'fat']],
                ['foo\\bar\\fat', ['foo\\', 'bar\\', 'fat']],
                ['\\foo\\bar\\fat', ['\\foo\\', 'bar\\', 'fat']],
                ['\\foo\\bar\\fat', ['\\foo', '\\bar\\', 'fat']],
                ['foo\\bar\\fat', ['foo\\', '\\bar\\', 'fat']],
                ['\\foo\\bar\\fat', ['\\foo\\', '\\bar\\', 'fat']],
                ['\\foo\\bar\\fat', ['\\foo', '\\bar', '\\fat']],
                ['foo\\bar\\fat', ['foo\\', '\\bar', '\\fat']],
                ['\\foo\\bar\\fat', ['\\foo\\', '\\bar', '\\fat']],
                ['\\foo\\bar\\fat', ['\\foo', 'bar\\', '\\fat']],
                ['foo\\bar\\fat', ['foo\\', 'bar\\', '\\fat']],
                ['\\foo\\bar\\fat', ['\\foo\\', 'bar\\', '\\fat']],
                ['\\foo\\bar\\fat', ['\\foo', '\\bar\\', '\\fat']],
                ['foo\\bar\\fat', ['foo\\', '\\bar\\', '\\fat']],
                ['\\foo\\bar\\fat', ['\\foo\\', '\\bar\\', '\\fat']],
                ['\\foo\\bar\\fat\\', ['\\foo', '\\bar', 'fat\\']],
                ['foo\\bar\\fat\\', ['foo\\', '\\bar', 'fat\\']],
                ['\\foo\\bar\\fat\\', ['\\foo\\', '\\bar', 'fat\\']],
                ['\\foo\\bar\\fat\\', ['\\foo', 'bar\\', 'fat\\']],
                ['foo\\bar\\fat\\', ['foo\\', 'bar\\', 'fat\\']],
                ['\\foo\\bar\\fat\\', ['\\foo\\', 'bar\\', 'fat\\']],
                ['\\foo\\bar\\fat\\', ['\\foo', '\\bar\\', 'fat\\']],
                ['foo\\bar\\fat\\', ['foo\\', '\\bar\\', 'fat\\']],
                ['\\foo\\bar\\fat\\', ['\\foo\\', '\\bar\\', 'fat\\']],
                ['\\foo\\bar\\fat\\', ['\\foo', '\\bar', '\\fat\\']],
                ['foo\\bar\\fat\\', ['foo\\', '\\bar', '\\fat\\']],
                ['\\foo\\bar\\fat\\', ['\\foo\\', '\\bar', '\\fat\\']],
                ['\\foo\\bar\\fat\\', ['\\foo', 'bar\\', '\\fat\\']],
                ['foo\\bar\\fat\\', ['foo\\', 'bar\\', '\\fat\\']],
                ['\\foo\\bar\\fat\\', ['\\foo\\', 'bar\\', '\\fat\\']],
                ['\\foo\\bar\\fat\\', ['\\foo', '\\bar\\', '\\fat\\']],
                ['foo\\bar\\fat\\', ['foo\\', '\\bar\\', '\\fat\\']],
                ['\\foo\\bar\\fat\\', ['\\foo\\', '\\bar\\', '\\fat\\']],

                ['/foo\\bar\\fat', ['/foo', 'bar', 'fat']],
                ['foo/bar\\fat', ['foo/', 'bar', 'fat']],
                ['/foo/bar\\fat', ['/foo/', 'bar', 'fat']],
                ['/foo\\bar\\fat', ['/foo', '/bar', 'fat']],
                ['foo/bar\\fat', ['foo/', '/bar', 'fat']],
                ['/foo/bar\\fat', ['/foo/', '/bar', 'fat']],
                ['/foo\\bar/fat', ['/foo', 'bar/', 'fat']],
                ['foo/bar/fat', ['foo/', 'bar/', 'fat']],
                ['/foo/bar/fat', ['/foo/', 'bar/', 'fat']],
                ['/foo\\bar/fat', ['/foo', '/bar/', 'fat']],
                ['foo/bar/fat', ['foo/', '/bar/', 'fat']],
                ['/foo/bar/fat', ['/foo/', '/bar/', 'fat']],
                ['/foo\\bar\\fat', ['/foo', '/bar', '/fat']],
                ['foo/bar\\fat', ['foo/', '/bar', '/fat']],
                ['/foo/bar\\fat', ['/foo/', '/bar', '/fat']],
                ['/foo\\bar/fat', ['/foo', 'bar/', '/fat']],
                ['foo/bar/fat', ['foo/', 'bar/', '/fat']],
                ['/foo/bar/fat', ['/foo/', 'bar/', '/fat']],
                ['/foo\\bar/fat', ['/foo', '/bar/', '/fat']],
                ['foo/bar/fat', ['foo/', '/bar/', '/fat']],
                ['/foo/bar/fat', ['/foo/', '/bar/', '/fat']],
                ['/foo\\bar\\fat/', ['/foo', '/bar', 'fat/']],
                ['foo/bar\\fat/', ['foo/', '/bar', 'fat/']],
                ['/foo/bar\\fat/', ['/foo/', '/bar', 'fat/']],
                ['/foo\\bar/fat/', ['/foo', 'bar/', 'fat/']],
                ['foo/bar/fat/', ['foo/', 'bar/', 'fat/']],
                ['/foo/bar/fat/', ['/foo/', 'bar/', 'fat/']],
                ['/foo\\bar/fat/', ['/foo', '/bar/', 'fat/']],
                ['foo/bar/fat/', ['foo/', '/bar/', 'fat/']],
                ['/foo/bar/fat/', ['/foo/', '/bar/', 'fat/']],
                ['/foo\\bar\\fat/', ['/foo', '/bar', '/fat/']],
                ['foo/bar\\fat/', ['foo/', '/bar', '/fat/']],
                ['/foo/bar\\fat/', ['/foo/', '/bar', '/fat/']],
                ['/foo\\bar/fat/', ['/foo', 'bar/', '/fat/']],
                ['foo/bar/fat/', ['foo/', 'bar/', '/fat/']],
                ['/foo/bar/fat/', ['/foo/', 'bar/', '/fat/']],
                ['/foo\\bar/fat/', ['/foo', '/bar/', '/fat/']],
                ['foo/bar/fat/', ['foo/', '/bar/', '/fat/']],
                ['/foo/bar/fat/', ['/foo/', '/bar/', '/fat/']],

                ['foo\\bar', ['foo', '', 'bar']],
                ['foo\\bar', ['foo', '/', 'bar']],
                ['foo\\bar', ['foo', '/\\', 'bar']],
                ['foo\\bar', ['foo', '/\\/', 'bar']],
                ['foo\\bar', ['foo', '/\\/\\', 'bar']],
                ['foo\\bar', ['foo', '\\', 'bar']],
                ['foo\\bar', ['foo', '\\/', 'bar']],
                ['foo\\bar', ['foo', '\\/\\', 'bar']],
                ['foo\\bar', ['foo', '\\/\\/', 'bar']],

                ['C:\foo\bar/baz', ['   C:\\foo   ', 'bar/   ', 'baz   ']],
                ['C:\foo\bar   /', ['   C:\\foo   ', 'bar   /']],

                ['', ['']],
                ['', ['', '']],
                ['', ['', '', '']],

                ['', ['/']],
                ['', ['/', '/\\']],
                ['', ['/', '/\\', '/\\/']],
                ['', ['/', '/\\', '/\\/', '/\\/\\']],
                ['', ['/', '/\\', '/\\/', '/\\/\\', '\\']],
                ['', ['/', '/\\', '/\\/', '/\\/\\', '\\', '\\/']],
                ['', ['/', '/\\', '/\\/', '/\\/\\', '\\', '\\/', '\\/\\']],
                ['', ['/', '/\\', '/\\/', '/\\/\\', '\\', '\\/', '\\/\\', '\\/\\/']],

                ['', []],

                ['C:\\Documents\\Newsletters\\Summer2018.pdf', ['C:', 'Documents', 'Newsletters', 'Summer2018.pdf']],
                ['\\Program Files\\Custom Utilities\\StringFinder.exe', ['\\Program Files', 'Custom Utilities', 'StringFinder.exe']],
                ['2018\\January.xlsx', ['2018', 'January.xlsx']],
                ['..\\Publications\\TravelBrochure.pdf', ['..', 'Publications', 'TravelBrochure.pdf']],
                ['\\\\system07\\C$', ['\\\\system07', 'C$']],
                ['\\\\Server2\\Share\\Test\\Foo.txt', ['\\\\Server2', 'Share', 'Test', 'Foo.txt']],
                ['\\\\.\\C:\\Test\\Foo.txt', ['\\\\.', 'C:', 'Test', 'Foo.txt']],
                ['\\\\?\\Volume{b75e2c83-0000-0000-0000-602f00000000}\\Test', ['\\\\?', 'Volume{b75e2c83-0000-0000-0000-602f00000000}', 'Test']],
            ];
        } else {
            return [
                ['', ['/']],
                ['/foo', ['/foo']],
                ['/foo\\', ['/foo\\']],
                ['/foo/bar', ['/foo', 'bar']],
                ['/foo\\/bar', ['/foo\\', 'bar']],
                ['/foo/bar\\', ['/foo', 'bar\\']],
                ['/foo\\/bar\\', ['/foo\\', 'bar\\']],
                ['/foo/bar/fat.txt', ['/foo', 'bar', 'fat.txt']],
                ['/foo\\/bar/fat.txt', ['/foo\\', 'bar', 'fat.txt']],
                ['/foo/bar\\/fat.txt', ['/foo', 'bar\\', 'fat.txt']],
                ['/foo\\/bar\\/fat.txt', ['/foo\\', 'bar\\', 'fat.txt']],
                ['/foo/', ['/foo/']],
                ['/foo/bar', ['/foo', 'bar']],
                ['/foo/bar', ['/foo/', 'bar']],
                ['/foo/bar/', ['/foo', 'bar/']],
                ['/foo/bar/', ['/foo/', 'bar/']],
                ['/foo/bar/fat.txt', ['/foo', 'bar', 'fat.txt']],
                ['/foo/bar/fat.txt', ['/foo/', 'bar', 'fat.txt']],
                ['/foo/bar/fat.txt', ['/foo', 'bar/', 'fat.txt']],
                ['/foo/bar/fat.txt', ['/foo/', 'bar/', 'fat.txt']],

                ['\\foo/bar/fat', ['\\foo', 'bar', 'fat']],
                ['foo\\/bar/fat', ['foo\\', 'bar', 'fat']],
                ['\\foo\\/bar/fat', ['\\foo\\', 'bar', 'fat']],
                ['\\foo/\\bar/fat', ['\\foo', '\\bar', 'fat']],
                ['foo\\/\\bar/fat', ['foo\\', '\\bar', 'fat']],
                ['\\foo\\/\\bar/fat', ['\\foo\\', '\\bar', 'fat']],
                ['\\foo/bar\\/fat', ['\\foo', 'bar\\', 'fat']],
                ['foo\\/bar\\/fat', ['foo\\', 'bar\\', 'fat']],
                ['\\foo\\/bar\\/fat', ['\\foo\\', 'bar\\', 'fat']],
                ['\\foo/\\bar\\/fat', ['\\foo', '\\bar\\', 'fat']],
                ['foo\\/\\bar\\/fat', ['foo\\', '\\bar\\', 'fat']],
                ['\\foo\\/\\bar\\/fat', ['\\foo\\', '\\bar\\', 'fat']],
                ['\\foo/\\bar/\\fat', ['\\foo', '\\bar', '\\fat']],
                ['foo\\/\\bar/\\fat', ['foo\\', '\\bar', '\\fat']],
                ['\\foo\\/\\bar/\\fat', ['\\foo\\', '\\bar', '\\fat']],
                ['\\foo/bar\\/\\fat', ['\\foo', 'bar\\', '\\fat']],
                ['foo\\/bar\\/\\fat', ['foo\\', 'bar\\', '\\fat']],
                ['\\foo\\/bar\\/\\fat', ['\\foo\\', 'bar\\', '\\fat']],
                ['\\foo/\\bar\\/\\fat', ['\\foo', '\\bar\\', '\\fat']],
                ['foo\\/\\bar\\/\\fat', ['foo\\', '\\bar\\', '\\fat']],
                ['\\foo\\/\\bar\\/\\fat', ['\\foo\\', '\\bar\\', '\\fat']],
                ['\\foo/\\bar/fat\\', ['\\foo', '\\bar', 'fat\\']],
                ['foo\\/\\bar/fat\\', ['foo\\', '\\bar', 'fat\\']],
                ['\\foo\\/\\bar/fat\\', ['\\foo\\', '\\bar', 'fat\\']],
                ['\\foo/bar\\/fat\\', ['\\foo', 'bar\\', 'fat\\']],
                ['foo\\/bar\\/fat\\', ['foo\\', 'bar\\', 'fat\\']],
                ['\\foo\\/bar\\/fat\\', ['\\foo\\', 'bar\\', 'fat\\']],
                ['\\foo/\\bar\\/fat\\', ['\\foo', '\\bar\\', 'fat\\']],
                ['foo\\/\\bar\\/fat\\', ['foo\\', '\\bar\\', 'fat\\']],
                ['\\foo\\/\\bar\\/fat\\', ['\\foo\\', '\\bar\\', 'fat\\']],
                ['\\foo/\\bar/\\fat\\', ['\\foo', '\\bar', '\\fat\\']],
                ['foo\\/\\bar/\\fat\\', ['foo\\', '\\bar', '\\fat\\']],
                ['\\foo\\/\\bar/\\fat\\', ['\\foo\\', '\\bar', '\\fat\\']],
                ['\\foo/bar\\/\\fat\\', ['\\foo', 'bar\\', '\\fat\\']],
                ['foo\\/bar\\/\\fat\\', ['foo\\', 'bar\\', '\\fat\\']],
                ['\\foo\\/bar\\/\\fat\\', ['\\foo\\', 'bar\\', '\\fat\\']],
                ['\\foo/\\bar\\/\\fat\\', ['\\foo', '\\bar\\', '\\fat\\']],
                ['foo\\/\\bar\\/\\fat\\', ['foo\\', '\\bar\\', '\\fat\\']],
                ['\\foo\\/\\bar\\/\\fat\\', ['\\foo\\', '\\bar\\', '\\fat\\']],

                ['/foo/bar/fat', ['/foo', 'bar', 'fat']],
                ['foo/bar/fat', ['foo/', 'bar', 'fat']],
                ['/foo/bar/fat', ['/foo/', 'bar', 'fat']],
                ['/foo/bar/fat', ['/foo', '/bar', 'fat']],
                ['foo/bar/fat', ['foo/', '/bar', 'fat']],
                ['/foo/bar/fat', ['/foo/', '/bar', 'fat']],
                ['/foo/bar/fat', ['/foo', 'bar/', 'fat']],
                ['foo/bar/fat', ['foo/', 'bar/', 'fat']],
                ['/foo/bar/fat', ['/foo/', 'bar/', 'fat']],
                ['/foo/bar/fat', ['/foo', '/bar/', 'fat']],
                ['foo/bar/fat', ['foo/', '/bar/', 'fat']],
                ['/foo/bar/fat', ['/foo/', '/bar/', 'fat']],
                ['/foo/bar/fat', ['/foo', '/bar', '/fat']],
                ['foo/bar/fat', ['foo/', '/bar', '/fat']],
                ['/foo/bar/fat', ['/foo/', '/bar', '/fat']],
                ['/foo/bar/fat', ['/foo', 'bar/', '/fat']],
                ['foo/bar/fat', ['foo/', 'bar/', '/fat']],
                ['/foo/bar/fat', ['/foo/', 'bar/', '/fat']],
                ['/foo/bar/fat', ['/foo', '/bar/', '/fat']],
                ['foo/bar/fat', ['foo/', '/bar/', '/fat']],
                ['/foo/bar/fat', ['/foo/', '/bar/', '/fat']],
                ['/foo/bar/fat/', ['/foo', '/bar', 'fat/']],
                ['foo/bar/fat/', ['foo/', '/bar', 'fat/']],
                ['/foo/bar/fat/', ['/foo/', '/bar', 'fat/']],
                ['/foo/bar/fat/', ['/foo', 'bar/', 'fat/']],
                ['foo/bar/fat/', ['foo/', 'bar/', 'fat/']],
                ['/foo/bar/fat/', ['/foo/', 'bar/', 'fat/']],
                ['/foo/bar/fat/', ['/foo', '/bar/', 'fat/']],
                ['foo/bar/fat/', ['foo/', '/bar/', 'fat/']],
                ['/foo/bar/fat/', ['/foo/', '/bar/', 'fat/']],
                ['/foo/bar/fat/', ['/foo', '/bar', '/fat/']],
                ['foo/bar/fat/', ['foo/', '/bar', '/fat/']],
                ['/foo/bar/fat/', ['/foo/', '/bar', '/fat/']],
                ['/foo/bar/fat/', ['/foo', 'bar/', '/fat/']],
                ['foo/bar/fat/', ['foo/', 'bar/', '/fat/']],
                ['/foo/bar/fat/', ['/foo/', 'bar/', '/fat/']],
                ['/foo/bar/fat/', ['/foo', '/bar/', '/fat/']],
                ['foo/bar/fat/', ['foo/', '/bar/', '/fat/']],
                ['/foo/bar/fat/', ['/foo/', '/bar/', '/fat/']],

                ['foo/bar', ['foo', '', 'bar']],
                ['foo/bar', ['foo', '/', 'bar']],
                ['foo/bar', ['foo', '//', 'bar']],

                ['/foo/bar/baz', ['   /foo   ', 'bar/   ', 'baz   ']],
                ['/foo/bar   /', ['   /foo   ', 'bar   /']],

                ['', ['']],
                ['', ['', '']],
                ['', ['', '', '']],

                ['', ['/']],
                ['', ['/', '//']],
                ['', ['/', '//', '///']],
                ['', ['/', '//', '///', '////']],

                ['', []],

                ['/usr/Documents/Newsletters/Summer2018.pdf', ['/usr', 'Documents', 'Newsletters', 'Summer2018.pdf']],
                ['/usr/Custom\ Utilities/StringFinder.exe', ['/usr', 'Custom\ Utilities', 'StringFinder.exe']],
                ['2018/January.xlsx', ['2018', 'January.xlsx']],
                ['../Publications/TravelBrochure.pdf', ['..', 'Publications', 'TravelBrochure.pdf']],
                ['steve@192.168.1.151:/home', ['steve@192.168.1.151:', 'home']],
                ['/mnt/devboxhome/Test/Foo.txt', ['/mnt', 'devboxhome', 'Test', 'Foo.txt']],
                ['~/.ssh/id_rsa.pub', ['~', '.ssh', 'id_rsa.pub']],
                ['smb://ComputerName/ShareName', ['smb://ComputerName', 'ShareName']],
                ['/var/www/html/index.php', ['/var', 'www', 'html', 'index.php']],
            ];
        }
    }

    static function ensureLeadingSlashDataProvider()
    {
        if (\PHP_OS_FAMILY === 'Windows') {
            return [
                ['\\foo', 'foo'],
                ['/foo', '/foo'],
                ['\\foo/', 'foo/'],
                ['/foo/', '/foo/'],
                ['//foo', '//foo'],
                ['\\foo//', 'foo//'],
                ['//foo//', '//foo//'],
                ['\\foo', '\\foo'],
                ['\\foo\\', 'foo\\'],
                ['\\foo\\', '\\foo\\'],
                ['\\\\foo', '\\\\foo'],
                ['\\foo\\\\', 'foo\\\\'],
                ['\\\\foo\\\\', '\\\\foo\\\\'],
                ['\\', ''],
                ['/', '/'],
                ['/\\', '/\\'],
                ['/\\/', '/\\/'],
                ['/\\/\\', '/\\/\\'],
                ['\\', '\\'],
                ['\\/', '\\/'],
                ['\\/\\', '\\/\\'],
                ['\\/\\/', '\\/\\/'],
            ];
        } else {
            return [
                ['/foo', 'foo'],
                ['/foo', '/foo'],
                ['/foo/', 'foo/'],
                ['/foo/', '/foo/'],
                ['//foo', '//foo'],
                ['/foo//', 'foo//'],
                ['//foo//', '//foo//'],
                ['/\\foo', '\\foo'],
                ['/foo\\', 'foo\\'],
                ['/\\foo\\', '\\foo\\'],
                ['/\\\\foo', '\\\\foo'],
                ['/foo\\\\', 'foo\\\\'],
                ['/\\\\foo\\\\', '\\\\foo\\\\'],
                ['/', ''],
                ['/', '/'],
                ['//', '//'],
                ['///', '///'],
                ['////', '////'],
            ];
        }
    }

    static function ensureTrailingSlashDataProvider()
    {
        if (\PHP_OS_FAMILY === 'Windows') {
            return [
                ['foo\\', 'foo'],
                ['/foo\\', '/foo'],
                ['foo/', 'foo/'],
                ['/foo/', '/foo/'],
                ['//foo\\', '//foo'],
                ['foo//', 'foo//'],
                ['//foo//', '//foo//'],
                ['\\foo\\', '\\foo'],
                ['foo\\', 'foo\\'],
                ['\\foo\\', '\\foo\\'],
                ['\\\\foo\\', '\\\\foo'],
                ['foo\\\\', 'foo\\\\'],
                ['\\\\foo\\\\', '\\\\foo\\\\'],
                ['\\', ''],
                ['/', '/'],
                ['/\\', '/\\'],
                ['/\\/', '/\\/'],
                ['/\\/\\', '/\\/\\'],
                ['\\', '\\'],
                ['\\/', '\\/'],
                ['\\/\\', '\\/\\'],
                ['\\/\\/', '\\/\\/'],
            ];
        } else {
            return [
                ['foo/', 'foo'],
                ['/foo/', '/foo'],
                ['foo/', 'foo/'],
                ['/foo/', '/foo/'],
                ['//foo/', '//foo'],
                ['foo//', 'foo//'],
                ['//foo//', '//foo//'],
                ['\\foo/', '\\foo'],
                ['foo\\/', 'foo\\'],
                ['\\foo\\/', '\\foo\\'],
                ['\\\\foo/', '\\\\foo'],
                ['foo\\\\/', 'foo\\\\'],
                ['\\\\foo\\\\/', '\\\\foo\\\\'],
                ['/', ''],
                ['/', '/'],
                ['//', '//'],
                ['///', '///'],
                ['////', '////'],
            ];
        }
    }

    static function trimLeadingSlashesDataProvider()
    {
        if (\PHP_OS_FAMILY === 'Windows') {
            return [
                ['foo', 'foo'],
                ['foo', '/foo'],
                ['foo/', 'foo/'],
                ['foo/', '/foo/'],
                ['foo', '//foo'],
                ['foo//', 'foo//'],
                ['foo//', '//foo//'],
                ['foo', '\\foo'],
                ['foo\\', 'foo\\'],
                ['foo\\', '\\foo\\'],
                ['foo', '\\\\foo'],
                ['foo\\\\', 'foo\\\\'],
                ['foo\\\\', '\\\\foo\\\\'],
                ['foo', '\\/\\/foo'],
                ['foo\\/\\/', 'foo\\/\\/'],
                ['foo\\/\\/', '\\/\\/foo\\/\\/'],
                ['foo', '/\\/\\foo'],
                ['foo/\\/\\', 'foo/\\/\\'],
                ['foo/\\/\\', '/\\/\\foo/\\/\\'],
                ['', ''],
                ['', '/'],
                ['', '/\\'],
                ['', '/\\/'],
                ['', '/\\/\\'],
                ['', '\\'],
                ['', '\\/'],
                ['', '\\/\\'],
                ['', '\\/\\/'],
            ];
        } else {
            return [
                ['foo', 'foo'],
                ['foo', '/foo'],
                ['foo/', 'foo/'],
                ['foo/', '/foo/'],
                ['foo', '//foo'],
                ['foo//', 'foo//'],
                ['foo//', '//foo//'],
                ['\\foo', '\\foo'],
                ['foo\\', 'foo\\'],
                ['\\foo\\', '\\foo\\'],
                ['\\\\foo', '\\\\foo'],
                ['foo\\\\', 'foo\\\\'],
                ['\\\\foo\\\\', '\\\\foo\\\\'],
                ['\\/\\/foo', '\\/\\/foo'],
                ['foo\\/\\/', 'foo\\/\\/'],
                ['\\/\\/foo\\/\\/', '\\/\\/foo\\/\\/'],
                ['\\/\\foo', '/\\/\\foo'],
                ['foo/\\/\\', 'foo/\\/\\'],
                ['\\/\\foo/\\/\\', '/\\/\\foo/\\/\\'],
                ['', ''],
                ['', '/'],
                ['', '//'],
                ['', '///'],
                ['', '////'],
            ];
        }
    }

    static function trimTrailingSlashesDataProvider()
    {
        if (\PHP_OS_FAMILY === 'Windows') {
            return [
                ['foo', 'foo'],
                ['/foo', '/foo'],
                ['foo', 'foo/'],
                ['/foo', '/foo/'],
                ['//foo', '//foo'],
                ['foo', 'foo//'],
                ['//foo', '//foo//'],
                ['\\foo', '\\foo'],
                ['foo', 'foo\\'],
                ['\\foo', '\\foo\\'],
                ['\\\\foo', '\\\\foo'],
                ['foo', 'foo\\\\'],
                ['\\\\foo', '\\\\foo\\\\'],
                ['\\/\\/foo', '\\/\\/foo'],
                ['foo', 'foo\\/\\/'],
                ['\\/\\/foo', '\\/\\/foo\\/\\/'],
                ['/\\/\\foo', '/\\/\\foo'],
                ['foo', 'foo/\\/\\'],
                ['/\\/\\foo', '/\\/\\foo/\\/\\'],
                ['', ''],
                ['', '/'],
                ['', '/\\'],
                ['', '/\\/'],
                ['', '/\\/\\'],
                ['', '\\'],
                ['', '\\/'],
                ['', '\\/\\'],
                ['', '\\/\\/'],
            ];
        } else {
            return [
                ['foo', 'foo'],
                ['/foo', '/foo'],
                ['foo', 'foo/'],
                ['/foo', '/foo/'],
                ['//foo', '//foo'],
                ['foo', 'foo//'],
                ['//foo', '//foo//'],
                ['\\foo', '\\foo'],
                ['foo\\', 'foo\\'],
                ['\\foo\\', '\\foo\\'],
                ['\\\\foo', '\\\\foo'],
                ['foo\\\\', 'foo\\\\'],
                ['\\\\foo\\\\', '\\\\foo\\\\'],
                ['\\/\\/foo', '\\/\\/foo'],
                ['foo\\/\\', 'foo\\/\\/'],
                ['\\/\\/foo\\/\\', '\\/\\/foo\\/\\/'],
                ['/\\/\\foo', '/\\/\\foo'],
                ['foo/\\/\\', 'foo/\\/\\'],
                ['/\\/\\foo/\\/\\', '/\\/\\foo/\\/\\'],
                ['', ''],
                ['', '/'],
                ['', '//'],
                ['', '///'],
                ['', '////'],
            ];
        }
    }

    static function isFileDataProvider()
    {
        return [
            'existing file' => [true, 'phpunit.xml'],
            'existing directory' => [false, 'suite'],
            'non existing path' => [false, 'non_existing'],
            'path with invalid characters' => [false, '<>:"|?*'],
            'empty path' => [false, ''],
        ];
    }

    static function isDirectoryDataProvider()
    {
        return [
            'existing directory' => [true, 'suite'],
            'existing file' => [false, 'phpunit.xml'],
            'non existing path' => [false, 'non_existing'],
            'path with invalid characters' => [false, '<>:"|?*'],
            'empty path' => [false, ''],
        ];
    }

    static function baseNameDataProvider()
    {
        return [
            ['sudoers.d', '/etc/sudoers.d/'],
            ['sudoers.d', '/etc/sudoers.d'],
            ['passwd', '/etc/passwd'],
            ['etc', '/etc/'],
            ['etc', 'etc/'],
            ['etc', '/etc'],
            ['.', '.'],
            ['', '/'],
            ['', '']
        ];
    }

    static function toAbsoluteDataProvider()
    {
        // Data providers run before any test setup and depend on the initial
        // working directory. To ensure the base path is "test/backend" (set
        // later by `setUpBeforeClass`, if needed), we check for a specific file
        // (e.g., "phpunit.xml"). If it's not found, we adjust the path.
        $cwd = \getcwd();
        if (!CPath::Join($cwd, 'phpunit.xml')->IsFile()) {
            $cwd = \realpath((string)CPath::Join(__DIR__, '..', '..'));
        }

        return [
            'existing file' => [
                (string)CPath::Join($cwd, 'phpunit.xml'),
                'phpunit.xml'
            ],
            'existing directory' => [
                (string)CPath::Join($cwd, 'suite'),
                'suite'
            ],
            'path with dotted segments' => [
                (string)CPath::Join($cwd, 'phpunit.xml'),
                './suite/../phpunit.xml'
            ],
            'path with extra separator' => [
                (string)CPath::Join($cwd, 'suite', '.gitkeep'),
                'suite//.gitkeep'
            ],
            'already absolute path' => [
                (string)CPath::Join($cwd, 'suite', '.gitkeep'),
                (string)CPath::Join($cwd, 'suite', '.gitkeep'),
            ],
            'non existing path' => [
                null,
                'non_existing'
            ],
            'path with invalid characters' => [
                null,
                '<>:"|?*'
            ],
            'empty path' => [
                $cwd,
                ''
            ],
        ];
    }

    #endregion Data Providers
}
