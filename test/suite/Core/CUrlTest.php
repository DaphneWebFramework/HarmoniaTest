<?php declare(strict_types=1);
use \PHPUnit\Framework\TestCase;
use \PHPUnit\Framework\Attributes\CoversClass;
use \PHPUnit\Framework\Attributes\DataProvider;

use \Harmonia\Core\CUrl;
use \Harmonia\Core\CArray;

#[CoversClass(CUrl::class)]
class CUrlTest extends TestCase
{
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
    public function testJoin(string $expected, array $segments): void
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

    #endregion Data Providers
}
