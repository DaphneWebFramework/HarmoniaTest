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

    #region Join ---------------------------------------------------------------

    #[DataProvider('joinDataProvider')]
    public function testJoin(string $expected, array $segments): void
    {
        $joined = CPath::Join(...$segments);
        $this->assertInstanceOf(CPath::class, $joined);
        $this->assertSame($expected, (string)$joined);
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

    #region Interface: Stringable ----------------------------------------------

    function testToString()
    {
        $str = '/usr/bin';
        $path = new CPath($str);
        $this->assertSame($str, (string)$path);
    }

    #endregion Interface: Stringable

    #region Data Providers -----------------------------------------------------

    static function joinDataProvider()
    {
        if (PHP_OS_FAMILY === 'Windows') {
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
        if (PHP_OS_FAMILY === 'Windows') {
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
        if (PHP_OS_FAMILY === 'Windows') {
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
