<?php declare(strict_types=1);
use \PHPUnit\Framework\TestCase;
use \PHPUnit\Framework\Attributes\CoversClass;
use \PHPUnit\Framework\Attributes\DataProvider;

use \Harmonia\Core\CUrl;
use \Harmonia\Core\CPath;
use \Harmonia\Core\CArray;

#[CoversClass(CUrl::class)]
class CUrlTest extends TestCase
{
    static ?string $originalWorkingDirectory = null;

    static function setUpBeforeClass(): void
    {
        // Ensure tests run within the "test" directory. The working directory
        // varies between environments: locally, it is often already "test",
        // but in GitHub Actions, it is typically the project root.
        $cwd = \getcwd();
        if (\basename($cwd) !== 'test') {
            \chdir('test');
            self::$originalWorkingDirectory = $cwd;
        }
    }

    static function tearDownAfterClass(): void
    {
        // Restore the original working directory after the test suite completes,
        // but only if it was changed during `setUpBeforeClass`.
        if (self::$originalWorkingDirectory !== null) {
            \chdir(self::$originalWorkingDirectory);
            self::$originalWorkingDirectory = null;
        }
    }

    #region __construct --------------------------------------------------------

    function testDefaultConstructor()
    {
        $url = new CUrl();
        $this->assertSame('', (string)$url);
    }

    function testConstructorWithStringable()
    {
        $stringable = new class() implements \Stringable {
            function __toString() {
                return 'ftp://fileserver.com/data';
            }
        };
        $url = new CUrl($stringable);
        $this->assertSame('ftp://fileserver.com/data', (string)$url);
    }

    function testConstructorWithNativeString()
    {
        $url = new CUrl('https://openai.com');
        $this->assertSame('https://openai.com', (string)$url);
    }

    function testConstructorTrimsWhitespace()
    {
        $url = new CUrl('   https://openai.com   ');
        $this->assertSame('https://openai.com', (string)$url);
    }

    #endregion __construct

    #region Join ---------------------------------------------------------------

    #[DataProvider('joinDataProvider')]
    function testJoin(string $expected, array $segments): void
    {
        $joined = CUrl::Join(...$segments);
        $this->assertInstanceOf(CUrl::class, $joined);
        $this->assertSame($expected, (string)$joined);
    }

    #endregion Join

    #region EnsureLeadingSlash -------------------------------------------------

    #[DataProvider('ensureLeadingSlashDataProvider')]
    function testEnsureLeadingSlash(string $expected, string $str)
    {
        $url = new CUrl($str);
        $this->assertSame($expected, (string)$url->EnsureLeadingSlash());
    }

    #endregion EnsureLeadingSlash

    #region EnsureTrailingSlash ------------------------------------------------

    #[DataProvider('ensureTrailingSlashDataProvider')]
    function testEnsureTrailingSlash(string $expected, string $str)
    {
        $url = new CUrl($str);
        $this->assertSame($expected, (string)$url->EnsureTrailingSlash());
    }

    #endregion EnsureTrailingSlash

    #region TrimLeadingSlashes -------------------------------------------------

    #[DataProvider('trimLeadingSlashesDataProvider')]
    function testTrimLeadingSlashes(string $expected, string $str)
    {
        $url = new CUrl($str);
        $this->assertSame($expected, (string)$url->TrimLeadingSlashes());
    }

    #endregion TrimLeadingSlashes

    #region TrimTrailingSlashes ------------------------------------------------

    #[DataProvider('trimTrailingSlashesDataProvider')]
    function testTrimTrailingSlashes(string $expected, string $str)
    {
        $url = new CUrl($str);
        $this->assertSame($expected, (string)$url->TrimTrailingSlashes());
    }

    #endregion TrimTrailingSlashes

    #region Components ---------------------------------------------------------

    function testComponentsWithFullUrl()
    {
        $url = new CUrl('http://username:password@hostname:9090/path?arg=value#anchor');
        $components = $url->Components();
        $this->assertInstanceOf(CArray::class, $components);
        $this->assertSame('http', $components->Get('scheme'));
        $this->assertSame('username', $components->Get('user'));
        $this->assertSame('password', $components->Get('pass'));
        $this->assertSame('hostname', $components->Get('host'));
        $this->assertSame(9090, $components->Get('port'));
        $this->assertSame('/path', $components->Get('path'));
        $this->assertSame('arg=value', $components->Get('query'));
        $this->assertSame('anchor', $components->Get('fragment'));
    }

    function testComponentsWithEmptyUrl()
    {
        $url = new CUrl('');
        $components = $url->Components();
        $this->assertInstanceOf(CArray::class, $components);
        $this->assertFalse($components->Has('scheme'));
        $this->assertFalse($components->Has('user'));
        $this->assertFalse($components->Has('pass'));
        $this->assertFalse($components->Has('host'));
        $this->assertFalse($components->Has('port'));
        $this->assertSame('', $components->Get('path'));
        $this->assertFalse($components->Has('query'));
        $this->assertFalse($components->Has('fragment'));
    }

    function testComponentsWithRelativeUrl()
    {
        $url = new CUrl('/index.php');
        $components = $url->Components();
        $this->assertInstanceOf(CArray::class, $components);
        $this->assertFalse($components->Has('scheme'));
        $this->assertFalse($components->Has('user'));
        $this->assertFalse($components->Has('pass'));
        $this->assertFalse($components->Has('host'));
        $this->assertFalse($components->Has('port'));
        $this->assertSame('/index.php', $components->Get('path'));
        $this->assertFalse($components->Has('query'));
        $this->assertFalse($components->Has('fragment'));
    }

    function testComponentsWithInvalidUrl()
    {
        $url = new CUrl('http://:-123456');
        $components = $url->Components();
        $this->assertNull($components);
    }

    #endregion Components

    #region ToAbsolute ---------------------------------------------------------

    #[DataProvider('toAbsoluteDataProvider')]
    function testToAbsolute($expected, $value, $baseUrl, $basePath)
    {
        $url = new CUrl($value);
        $absoluteUrl = $url->ToAbsolute($baseUrl, $basePath);
        $this->assertSame($expected, (string)$absoluteUrl);
    }

    function testToAbsoluteWithRelativeBasePath()
    {
        $path = new CUrl('phpunit.xml');
        $this->assertSame('https://example.com/phpunit.xml',
            (string)$path->ToAbsolute('https://example.com', 'suite/..'));
    }

    function testToAbsoluteWithNonExistentBasePath()
    {
        $path = new CUrl('phpunit.xml');
        $this->assertSame('phpunit.xml',
            (string)$path->ToAbsolute('https://example.com', 'non_existent_base_path'));
    }

    #endregion ToAbsolute

    #region Data Providers -----------------------------------------------------

    static function joinDataProvider()
    {
        return [
            ['http:', ['http:']],
            ['http://', ['http://']],
            ['http://foo', ['http://', 'foo']],
            ['http://foo/', ['http://', 'foo/']],
            ['http://foo/bar', ['http://', 'foo', 'bar']],
            ['http://foo/bar', ['http://', 'foo/', 'bar']],
            ['http://foo/bar/', ['http://', 'foo', 'bar/']],
            ['http://foo/bar/', ['http://', 'foo/', 'bar/']],
            ['http://foo/bar/fat.html', ['http://', 'foo', 'bar', 'fat.html']],
            ['http://foo/bar/fat.html', ['http://', 'foo/', 'bar', 'fat.html']],
            ['http://foo/bar/fat.html', ['http://', 'foo', 'bar/', 'fat.html']],
            ['http://foo/bar/fat.html', ['http://', 'foo/', 'bar/', 'fat.html']],

            ['http:/foo', ['http:', 'foo']],
            ['http:/foo/', ['http:', 'foo/']],
            ['http:/foo/bar', ['http:', 'foo', 'bar']],
            ['http:/foo/bar', ['http:', 'foo/', 'bar']],
            ['http:/foo/bar/', ['http:', 'foo', 'bar/']],
            ['http:/foo/bar/', ['http:', 'foo/', 'bar/']],
            ['http:/foo/bar/fat.html', ['http:', 'foo', 'bar', 'fat.html']],
            ['http:/foo/bar/fat.html', ['http:', 'foo/', 'bar', 'fat.html']],
            ['http:/foo/bar/fat.html', ['http:', 'foo', 'bar/', 'fat.html']],
            ['http:/foo/bar/fat.html', ['http:', 'foo/', 'bar/', 'fat.html']],

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

            ['\\foo/bar', ['\\foo', 'bar']],
            ['foo\\/bar', ['foo\\', 'bar']],
            ['\\foo\\/bar', ['\\foo\\', 'bar']],
            ['\\foo/\\bar', ['\\foo', '\\bar']],
            ['foo\\/\\bar', ['foo\\', '\\bar']],
            ['\\foo\\/\\bar', ['\\foo\\', '\\bar']],
            ['\\foo/bar\\', ['\\foo', 'bar\\']],
            ['foo\\/bar\\', ['foo\\', 'bar\\']],
            ['\\foo\\/bar\\', ['\\foo\\', 'bar\\']],
            ['\\foo/\\bar\\', ['\\foo', '\\bar\\']],
            ['foo\\/\\bar\\', ['foo\\', '\\bar\\']],
            ['\\foo\\/\\bar\\', ['\\foo\\', '\\bar\\']],

            ['foo/bar', ['foo', '', 'bar']],
            ['foo/bar', ['foo', '/', 'bar']],
            ['foo/bar', ['foo', '//', 'bar']],
            ['foo/bar', ['foo', '///', 'bar']],
            ['foo/bar', ['foo', '////', 'bar']],

            ['/foo/bar/baz', ['   /foo   ', 'bar/   ', 'baz   ']],
            ['/foo/bar   /', ['   /foo   ', 'bar   /']],

            ['', ['']],
            ['', ['', '']],
            ['', ['', '', '']],

            ['', ['/']],
            ['', ['/', '//']],
            ['', ['/', '//', '///']],

            ['', []],

            ['http://www.example.com/home', ['http://www.example.com', 'home']],
            ['http://www.example.com/home/index.php', ['http://', 'www.example.com', 'home', 'index.php']],
            ['../../app.css', ['..', '..', 'app.css']],
            ['/stylesheets/screen.css', ['/stylesheets', 'screen.css']],
            ['/favicon.png', ['/favicon.png']],
            ['reset/index.html', ['reset', 'index.html']],
            ['//www.google.com/js/gweb/analytics/autotrack.js', ['//www.google.com', 'js', 'gweb', 'analytics', 'autotrack.js']],
        ];
    }

    static function ensureLeadingSlashDataProvider()
    {
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

    static function ensureTrailingSlashDataProvider()
    {
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

    static function trimLeadingSlashesDataProvider()
    {
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

    static function trimTrailingSlashesDataProvider()
    {
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

    static function toAbsoluteDataProvider()
    {
        // Data providers are executed before any test setup logic and rely on
        // the initial working directory. This adjustment ensures paths are
        // consistent with the "test" directory, which will later be set as the
        // current directory during `setUpBeforeClass`.
        $cwd = \getcwd();
        if (\basename($cwd) !== 'test') {
            $cwd = (string)CPath::Join($cwd, 'test');
        }

        $data = [
            'existing file' => [
                'https://example.com/phpunit.xml',
                'phpunit.xml',
                'https://example.com',
                $cwd
            ],
            'existing file in subdirectory' => [
                'https://example.com/suite/Core/CUrlTest.php',
                'suite/Core/CUrlTest.php',
                'https://example.com',
                $cwd
            ],
            'existing directory' => [
                'https://example.com/suite/',
                'suite',
                'https://example.com',
                $cwd
            ],
            'existing directory in subdirectory' => [
                'https://example.com/suite/Core/',
                'suite/Core',
                'https://example.com',
                $cwd
            ],
            'non existing url' => [
                'non_existing',
                'non_existing',
                'https://example.com',
                $cwd
            ],
            'file url with dotted segments' => [
                'https://example.com/phpunit.xml',
                './suite/../phpunit.xml',
                'https://example.com',
                $cwd
            ],
            'directory url with dotted segments' => [
                'https://example.com/suite/',
                './suite/../suite',
                'https://example.com',
                $cwd
            ],
            'file url with extra separators' => [
                'https://example.com/suite/Core/CUrlTest.php',
                'suite//Core///CUrlTest.php',
                'https://example.com',
                $cwd
            ],
            'directory url with extra separators' => [
                'https://example.com/suite/Core/',
                'suite//Core///',
                'https://example.com',
                $cwd
            ],
            'file url with percent-encoded characters' => [
                'https://example.com/phpunit.xml',
                'phpunit%2Exml',
                'https://example.com',
                $cwd
            ],
            'directory url with percent-encoded characters' => [
                'https://example.com/suite/Core/',
                'suite%2FCore',
                'https://example.com',
                $cwd
            ],
            'file url with query and fragment' => [
                'https://example.com/phpunit.xml?version=17#section',
                'phpunit.xml?version=17#section',
                'https://example.com',
                $cwd
            ],
            'directory url with query and fragment' => [
                'https://example.com/suite/?version=17&lang=tr#section',
                'suite?version=17&lang=tr#section',
                'https://example.com',
                $cwd
            ],
            'already absolute url' => [
                'https://example.com/suite/Core',
                'https://example.com/suite/Core',
                'https://example.com',
                $cwd
            ],
            'absolute path diverges from base path' => [
                'phpunit.xml',
                'phpunit.xml',
                'https://example.com',
                (string)CPath::Join($cwd, 'suite')
            ],
        ];
        if (\PHP_OS_FAMILY === 'Windows') {
            $data += [
                'case-insensitive file url (windows)' => [
                    'https://example.com/phpunit.xml',
                    'PHPUnit.Xml',
                    'https://example.com',
                    $cwd
                ],
                'case-insensitive directory url (windows)' => [
                    'https://example.com/suite/Core/',
                    'Suite/cORE',
                    'https://example.com',
                    $cwd
                ],
                'case-insensitive base path (windows)' => [
                    'https://example.com/phpunit.xml',
                    'phpunit.xml',
                    'https://example.com',
                    strtoupper($cwd)
                ],
            ];
        }
        return $data;
    }

    #endregion Data Providers
}
