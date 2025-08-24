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
        $this->assertEquals('', $url);
    }

    function testConstructorWithStringable()
    {
        $stringable = new class() implements \Stringable {
            function __toString() {
                return 'ftp://fileserver.com/data';
            }
        };
        $url = new CUrl($stringable);
        $this->assertEquals('ftp://fileserver.com/data', $url);
    }

    function testConstructorWithNativeString()
    {
        $url = new CUrl('https://openai.com');
        $this->assertEquals('https://openai.com', $url);
    }

    function testConstructorRetainsWhitespace()
    {
        $url = new CUrl('   https://openai.com   ');
        $this->assertEquals('   https://openai.com   ', $url);
    }

    #endregion __construct

    #region Join ---------------------------------------------------------------

    #[DataProvider('joinDataProvider')]
    function testJoin(string $expected, array $segments)
    {
        $joined = CUrl::Join(...$segments);
        $this->assertInstanceOf(CUrl::class, $joined);
        $this->assertEquals($expected, $joined);
    }

    function testJoinWithMixedArguments()
    {
        $joined = CUrl::Join(
            'http://',
            new class() implements \Stringable {
                function __toString() {
                    return 'example.com';
                }
            },
            'path',
            'to',
            'file.html'
        );
        $this->assertEquals('http://example.com/path/to/file.html', $joined);
    }


    function testJoinDoesNotMutateCallerProvidedSegments()
    {
        $segment1 = new CUrl('url');
        $segment2 = new CUrl('/to');
        $segment3 = new CUrl('/file');
        $segment1Clone = clone $segment1;
        $segment2Clone = clone $segment2;
        $segment3Clone = clone $segment3;

        $joined = CUrl::Join($segment1, $segment2, $segment3);

        $this->assertEquals($segment1Clone, $segment1);
        $this->assertEquals($segment2Clone, $segment2);
        $this->assertEquals($segment3Clone, $segment3);
        $this->assertEquals('url/to/file', $joined);
    }

    #endregion Join

    #region Extend ----------------------------------------------------------------

    function testExtend()
    {
        $url = new CUrl('url');
        $extended = $url->Extend('to', 'file');
        $this->assertNotSame($url, $extended);
        $this->assertEquals('url', $url);
        $this->assertEquals('url/to/file', $extended);
    }

    function testExtendFromEmpty()
    {
        $url = new CUrl();
        $extended = $url->Extend('to', 'file');
        $this->assertEquals('to/file', $extended);
    }

    #endregion Extend

    #region EnsureLeadingSlash -------------------------------------------------

    #[DataProvider('ensureLeadingSlashDataProvider')]
    function testEnsureLeadingSlash(string $expected, string $str)
    {
        $url = new CUrl($str);
        $this->assertEquals($expected, $url->EnsureLeadingSlash());
    }

    #endregion EnsureLeadingSlash

    #region EnsureTrailingSlash ------------------------------------------------

    #[DataProvider('ensureTrailingSlashDataProvider')]
    function testEnsureTrailingSlash(string $expected, string $str)
    {
        $url = new CUrl($str);
        $this->assertEquals($expected, $url->EnsureTrailingSlash());
    }

    #endregion EnsureTrailingSlash

    #region TrimLeadingSlashes -------------------------------------------------

    #[DataProvider('trimLeadingSlashesDataProvider')]
    function testTrimLeadingSlashes(string $expected, string $str)
    {
        $url = new CUrl($str);
        $this->assertEquals($expected, $url->TrimLeadingSlashes());
    }

    #endregion TrimLeadingSlashes

    #region TrimTrailingSlashes ------------------------------------------------

    #[DataProvider('trimTrailingSlashesDataProvider')]
    function testTrimTrailingSlashes(string $expected, string $str)
    {
        $url = new CUrl($str);
        $this->assertEquals($expected, $url->TrimTrailingSlashes());
    }

    #endregion TrimTrailingSlashes

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

            ['   /foo   /bar/   /baz   ', ['   /foo   ', 'bar/   ', 'baz   ']],
            ['   /foo   /bar   /', ['   /foo   ', 'bar   /']],

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
