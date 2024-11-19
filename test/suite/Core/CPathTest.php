<?php declare(strict_types=1);
use \PHPUnit\Framework\TestCase;
use \PHPUnit\Framework\Attributes\CoversClass;
use \PHPUnit\Framework\Attributes\DataProvider;
use \PHPUnit\Framework\Attributes\DataProviderExternal;

use \Harmonia\Core\CPath;
use \Harmonia\Core\CString;

use \TestToolkit\AccessHelper;
use \TestToolkit\DataHelper;

#[CoversClass(CPath::class)]
class CPathTest extends TestCase
{
    #region __construct --------------------------------------------------------

    function testDefaultConstructor()
    {
        $path = new CPath();
        $this->assertSame('', (string)$path);
    }

    function testCopyConstructor()
    {
        $original = new CPath('/usr/local');
        $copy = new CPath($original);
        $this->assertSame((string)$original, (string)$copy);
        // Ensure modifying the original does not affect the copy.
        AccessHelper::GetNonPublicProperty($original, 'value')->Append('/bin');
        $this->assertSame('/usr/local/bin', (string)$original);
        $this->assertSame('/usr/local', (string)$copy);
    }

    public function testConstructWithCstring()
    {
        $cstr = new CString('/etc/config');
        $path = new CPath($cstr);
        $this->assertSame((string)$cstr, (string)$path);
        // Ensure modifying the original does not affect the copy.
        $cstr->Append('/extra');
        $this->assertSame('/etc/config/extra', (string)$cstr);
        $this->assertSame('/etc/config', (string)$path);
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

    #endregion __construct

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

    #region Interface: Stringable ----------------------------------------------

    function testToString()
    {
        $str = '/usr/bin';
        $path = new CPath($str);
        $this->assertSame($str, (string)$path);
    }

    #endregion Interface: Stringable

    #region Data Providers -----------------------------------------------------

    static function ensureLeadingSlashDataProvider()
    {
        if (PHP_OS_FAMILY === 'Windows') {
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
                ['/', '/'],
                ['/\\', '/\\'],
                ['/\\/', '/\\/'],
                ['/\\/\\', '/\\/\\'],
                ['\\', '\\'],
                ['\\/', '\\/'],
                ['\\/\\', '\\/\\'],
                ['\\/\\/', '\\/\\/'],
                ['\\', ''],
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
                ['/', '/'],
                ['//', '//'],
                ['///', '///'],
                ['////', '////'],
                ['/', ''],
            ];
        }
    }

    static function ensureTrailingSlashDataProvider()
    {
        if (PHP_OS_FAMILY === 'Windows') {
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
                ['/', '/'],
                ['/\\', '/\\'],
                ['/\\/', '/\\/'],
                ['/\\/\\', '/\\/\\'],
                ['\\', '\\'],
                ['\\/', '\\/'],
                ['\\/\\', '\\/\\'],
                ['\\/\\/', '\\/\\/'],
                ['\\', ''],
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
                ['/', '/'],
                ['//', '//'],
                ['///', '///'],
                ['////', '////'],
                ['/', ''],
            ];
        }
    }

    static function trimLeadingSlashesDataProvider()
    {
        if (PHP_OS_FAMILY === 'Windows') {
            return [
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
                ['', '/'],
                ['', '/\\'],
                ['', '/\\/'],
                ['', '/\\/\\'],
                ['', '\\'],
                ['', '\\/'],
                ['', '\\/\\'],
                ['', '\\/\\/'],
                ['', ''],
            ];
        } else {
            return [
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
                ['', '/'],
                ['', '//'],
                ['', '///'],
                ['', '////'],
                ['', ''],
            ];
        }
    }

    static function trimTrailingSlashesDataProvider()
    {
        if (PHP_OS_FAMILY === 'Windows') {
            return [
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
                ['', '/'],
                ['', '/\\'],
                ['', '/\\/'],
                ['', '/\\/\\'],
                ['', '\\'],
                ['', '\\/'],
                ['', '\\/\\'],
                ['', '\\/\\/'],
                ['', ''],
            ];
        } else {
            return [
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
                ['', '/'],
                ['', '//'],
                ['', '///'],
                ['', '////'],
                ['', ''],
            ];
        }
    }

    #endregion Data Providers
}
