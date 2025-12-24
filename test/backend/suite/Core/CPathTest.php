<?php declare(strict_types=1);
use \PHPUnit\Framework\TestCase;
use \PHPUnit\Framework\Attributes\CoversClass;
use \PHPUnit\Framework\Attributes\DataProvider;

use \Harmonia\Core\CPath;

#[CoversClass(CPath::class)]
class CPathTest extends TestCase
{
    #region __construct --------------------------------------------------------

    function testDefaultConstructor()
    {
        $path = new CPath();
        $this->assertEquals('', $path);
    }

    function testConstructorWithStringable()
    {
        $stringable = new class() implements \Stringable {
            function __toString() {
                return '/home/user';
            }
        };
        $path = new CPath($stringable);
        $this->assertEquals('/home/user', $path);
    }

    function testConstructorWithNativeString()
    {
        $path = new CPath('/var/www');
        $this->assertEquals('/var/www', $path);
    }

    function testConstructorRetainsWhitespace()
    {
        $path = new CPath('   /usr/local   ');
        $this->assertEquals('   /usr/local   ', $path);
    }

    #endregion __construct

    #region Join ---------------------------------------------------------------

    #[DataProvider('joinDataProvider')]
    function testJoin(string $expected, array $segments)
    {
        $joined = CPath::Join(...$segments);
        $this->assertInstanceOf(CPath::class, $joined);
        $this->assertEquals($expected, $joined);
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
            $this->assertEquals('C:\\xampp\\htdocs\\index.html', $joined);
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
            $this->assertEquals('/var/www/html/index.html', $joined);
        }
    }

    function testJoinDoesNotMutateCallerProvidedSegments()
    {
        $segment1 = new CPath('path');
        $segment2 = new CPath('/to');
        $segment3 = new CPath('/file');
        $segment1Clone = clone $segment1;
        $segment2Clone = clone $segment2;
        $segment3Clone = clone $segment3;

        $joined = CPath::Join($segment1, $segment2, $segment3);

        $this->assertEquals($segment1Clone, $segment1);
        $this->assertEquals($segment2Clone, $segment2);
        $this->assertEquals($segment3Clone, $segment3);
        if (\PHP_OS_FAMILY === 'Windows') {
            $this->assertEquals('path\\to\\file', $joined);
        } else {
            $this->assertEquals('path/to/file', $joined);
        }
    }

    #endregion Join

    #region Extend -------------------------------------------------------------

    function testExtend()
    {
        $path = new CPath('path');
        $extended = $path->Extend('to', 'file');
        $this->assertNotSame($path, $extended);
        $this->assertEquals('path', $path);
        if (\PHP_OS_FAMILY === 'Windows') {
            $this->assertEquals('path\\to\\file', $extended);
        } else {
            $this->assertEquals('path/to/file', $extended);
        }
    }

    function testExtendFromEmpty()
    {
        $path = new CPath();
        $extended = $path->Extend('to', 'file');
        if (\PHP_OS_FAMILY === 'Windows') {
            $this->assertEquals('to\\file', $extended);
        } else {
            $this->assertEquals('to/file', $extended);
        }
    }

    #endregion Extend

    #region EnsureLeadingSlash -------------------------------------------------

    #[DataProvider('ensureLeadingSlashDataProvider')]
    function testEnsureLeadingSlash(string $expected, string $str)
    {
        $path = new CPath($str);
        $this->assertEquals($expected, $path->EnsureLeadingSlash());
    }

    #endregion EnsureLeadingSlash

    #region EnsureTrailingSlash ------------------------------------------------

    #[DataProvider('ensureTrailingSlashDataProvider')]
    function testEnsureTrailingSlash(string $expected, string $str)
    {
        $path = new CPath($str);
        $this->assertEquals($expected, $path->EnsureTrailingSlash());
    }

    #endregion EnsureTrailingSlash

    #region TrimLeadingSlashes -------------------------------------------------

    #[DataProvider('trimLeadingSlashesDataProvider')]
    function testTrimLeadingSlashes(string $expected, string $str)
    {
        $path = new CPath($str);
        $this->assertEquals($expected, $path->TrimLeadingSlashes());
    }

    #endregion TrimLeadingSlashes

    #region TrimTrailingSlashes ------------------------------------------------

    #[DataProvider('trimTrailingSlashesDataProvider')]
    function testTrimTrailingSlashes(string $expected, string $str)
    {
        $path = new CPath($str);
        $this->assertEquals($expected, $path->TrimTrailingSlashes());
    }

    #endregion TrimTrailingSlashes

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

                ['   C:\foo   \bar/   \baz   ', ['   C:\\foo   ', 'bar/   ', 'baz   ']],
                ['   C:\foo   \bar   /', ['   C:\\foo   ', 'bar   /']],

                ['', ['']],
                ['', ['', '']],
                ['', ['', '', '']],

                ['/', ['/']],
                ['/', ['/', '/\\']],
                ['/', ['/', '/\\', '/\\/']],
                ['/', ['/', '/\\', '/\\/', '/\\/\\']],
                ['/', ['/', '/\\', '/\\/', '/\\/\\', '\\']],
                ['/', ['/', '/\\', '/\\/', '/\\/\\', '\\', '\\/']],
                ['/', ['/', '/\\', '/\\/', '/\\/\\', '\\', '\\/', '\\/\\']],
                ['/', ['/', '/\\', '/\\/', '/\\/\\', '\\', '\\/', '\\/\\', '\\/\\/']],

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
                ['/', ['/']],
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

                ['   /foo   /bar/   /baz   ', ['   /foo   ', 'bar/   ', 'baz   ']],
                ['   /foo   /bar   /', ['   /foo   ', 'bar   /']],

                ['', ['']],
                ['', ['', '']],
                ['', ['', '', '']],

                ['/', ['/']],
                ['/', ['/', '//']],
                ['/', ['/', '//', '///']],
                ['/', ['/', '//', '///', '////']],

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

    #endregion Data Providers
}
