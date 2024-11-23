<?php declare(strict_types=1);
use \PHPUnit\Framework\TestCase;
use \PHPUnit\Framework\Attributes\CoversClass;
use \PHPUnit\Framework\Attributes\DataProvider;

use \Harmonia\Core\CUrl;

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

    #region Interface: Stringable ----------------------------------------------

    function testToString()
    {
        $str = 'https://example.com';
        $url = new CUrl($str);
        $this->assertSame($str, (string)$url);
    }

    #endregion Interface: Stringable

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
