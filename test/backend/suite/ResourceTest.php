<?php declare(strict_types=1);
use \PHPUnit\Framework\TestCase;
use \PHPUnit\Framework\Attributes\CoversClass;
use \PHPUnit\Framework\Attributes\RequiresOperatingSystem;

use \Harmonia\Resource;

use \Harmonia\Core\CArray;
use \Harmonia\Core\CPath;
use \Harmonia\Core\CUrl;
use \Harmonia\Server;
use \TestToolkit\AccessHelper;

#[CoversClass(Resource::class)]
class ResourceTest extends TestCase
{
    private ?Server $originalServer = null;

    protected function setUp(): void
    {
        $this->originalServer =
            Server::ReplaceInstance($this->createMock(Server::class));
    }

    protected function tearDown(): void
    {
        Server::ReplaceInstance($this->originalServer);
    }

    private function systemUnderTest(string ...$mockedMethods): Resource
    {
        $sut = $this->getMockBuilder(Resource::class)
            ->disableOriginalConstructor()
            ->onlyMethods($mockedMethods)
            ->getMock();
        return AccessHelper::CallConstructor($sut);
    }

    #region __construct --------------------------------------------------------

    function testConstructor()
    {
        $sut = $this->systemUnderTest();

        $this->assertNull(AccessHelper::GetProperty($sut, 'appPath'));
        $this->assertInstanceOf(CArray::class, AccessHelper::GetProperty($sut, 'cache'));
    }

    #endregion __construct

    #region Initialize ---------------------------------------------------------

    function testInitializeWhenAlreadyInitialized()
    {
        $sut = $this->systemUnderTest();

        $sut->Initialize(__DIR__);
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Resource is already initialized.');
        $sut->Initialize(__DIR__);
    }

    function testInitializeWithNonExistingPath()
    {
        $sut = $this->systemUnderTest();

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Failed to resolve application path.');
        $sut->Initialize(CPath::Join(__DIR__, 'non_existing_path'));
    }

    function testInitializeWithExistingPath()
    {
        $sut = $this->systemUnderTest();

        $sut->Initialize(__DIR__);
        $this->assertEquals(__DIR__, $sut->AppPath());
    }

    function testInitializeWithExistingRelativePath()
    {
        $sut = $this->systemUnderTest();

        $sut->Initialize(CPath::Join(__DIR__, '..', 'suite'));
        $this->assertEquals(__DIR__, $sut->AppPath());
    }

    function testInitializeDoesNotMutateCallerProvidedPathInstance()
    {
        $sut = $this->systemUnderTest();
        $appPath = CPath::Join(__DIR__, '.'); // Deliberately non-canonical
        $appPathClone = clone $appPath;

        $sut->Initialize($appPath);
        $this->assertEquals($appPath, $appPathClone);
    }

    #endregion Initialize

    #region AppPath ------------------------------------------------------------

    function testAppPathWhenNotInitialized()
    {
        $sut = $this->systemUnderTest();

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Resource is not initialized.');
        $sut->AppPath();
    }

    function testAppPathReturnsClone()
    {
        $sut = $this->systemUnderTest();

        $sut->Initialize(__DIR__);
        $path1 = $sut->AppPath();
        $path1->AppendInPlace('/oops');
        $path2 = $sut->AppPath();

        $this->assertNotSame($path1, $path2);
        $this->assertNotEquals($path1, $path2);
        $this->assertEquals(__DIR__, $path2);
    }

    #endregion AppPath

    #region AppRelativePath ----------------------------------------------------

    function testAppRelativePathWhenNotInitialized()
    {
        $sut = $this->systemUnderTest();

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Resource is not initialized.');
        $sut->AppRelativePath();
    }

    function testAppRelativePathWithNullServerPath()
    {
        $sut = $this->systemUnderTest();
        $server = Server::Instance();

        $server->method('Path')
            ->willReturn(null);

        $sut->Initialize(__DIR__);
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Server path not available.');
        $sut->AppRelativePath();
    }

    function testAppRelativePathWithNonExistingServerPath()
    {
        $sut = $this->systemUnderTest();
        $server = Server::Instance();

        $server->method('Path')
            ->willReturn(new CPath('non_existing_path'));

        $sut->Initialize(__DIR__);
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Failed to resolve server path.');
        $sut->AppRelativePath();
    }

    function testAppRelativePathWithAppPathNotUnderServerPath()
    {
        $sut = $this->systemUnderTest();
        $server = Server::Instance();

        $server->method('Path')
            ->willReturn(new CPath(__DIR__));

        $sut->Initialize(CPath::Join(__DIR__, '..'));
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Application path is not under server path.');
        $sut->AppRelativePath();
    }

    #[RequiresOperatingSystem('Linux|Darwin')]
    function testAppRelativePathWithServerDirectoryContainingLinkToAppPath()
    {
        $sut = $this->systemUnderTest();
        $server = Server::Instance();

        # /tmp
        $serverPath = new CPath(\sys_get_temp_dir());

        # /vagrant/test/backend/suite
        $appPath = new CPath(__DIR__);

        # suite
        $appBaseName = $appPath->Apply('\basename');

        # /tmp/suite
        $linkPath = $serverPath->Extend($appBaseName);

        # /tmp/suite -> /vagrant/test/backend/suite
        if ($linkPath->Call('\file_exists')) {
            $linkPath->Call('\unlink');
        }
        $this->assertTrue($appPath->Call('\symlink', (string)$linkPath));

        $server->method('Path')
            ->willReturn($serverPath);

        $sut->Initialize($appPath);
        $this->assertEquals($appBaseName, $sut->AppRelativePath());

        $linkPath->Call('\unlink');
    }

    function testAppRelativePathWithAppPathEqualToServerPath()
    {
        $sut = $this->systemUnderTest();
        $server = Server::Instance();

        $server->method('Path')
            ->willReturn(new CPath(__DIR__));

        $sut->Initialize(__DIR__);
        $this->assertTrue($sut->AppRelativePath()->IsEmpty());
    }

    function testAppRelativePathWithAppPathUnderServerPath()
    {
        $sut = $this->systemUnderTest();
        $server = Server::Instance();

        $server->expects($this->once()) // once() is to ensure cache hit
            ->method('Path')
            ->willReturn(new CPath(__DIR__));

        $sut->Initialize(CPath::Join(__DIR__, 'Core'));
        $this->assertEquals('Core', $sut->AppRelativePath());
        // Cache hit:
        $this->assertEquals('Core', $sut->AppRelativePath());
    }

    function testAppRelativePathReturnsClone()
    {
        $sut = $this->systemUnderTest('AppPath');
        $server = Server::Instance();
        $serverPath = new CPath(__DIR__);

        $sut->expects($this->once())
            ->method('AppPath')
            ->willReturn($serverPath->Extend('app'));
        $server->expects($this->once())
            ->method('Path')
            ->willReturn($serverPath);

        $path1 = $sut->AppRelativePath();
        $path1->ReplaceInPlace('app', 'oops1');
        $path2 = $sut->AppRelativePath();
        $path2->ReplaceInPlace('app', 'oops2');
        $path3 = $sut->AppRelativePath();

        $this->assertNotSame($path1, $path2);
        $this->assertNotSame($path2, $path3);
        $this->assertNotEquals($path1, $path2);
        $this->assertNotEquals($path2, $path3);
        $this->assertEquals('oops1', $path1);
        $this->assertEquals('oops2', $path2);
        $this->assertEquals('app', $path3);
    }

    #endregion AppRelativePath

    #region AppUrl -------------------------------------------------------------

    function testAppUrlWithNullServerUrl()
    {
        $sut = $this->systemUnderTest();
        $server = Server::Instance();

        $server->method('Url')
            ->willReturn(null);

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Server URL not available.');
        $sut->AppUrl();
    }

    function testAppUrlWhenNotInitialized()
    {
        $sut = $this->systemUnderTest();
        $server = Server::Instance();

        $server->method('Url')
            ->willReturn(new CUrl()); // empty instance to pass the null check

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Resource is not initialized.');
        $sut->AppUrl();
    }

    function testAppUrlWithNullServerPath()
    {
        $sut = $this->systemUnderTest();
        $server = Server::Instance();

        $server->method('Url')
            ->willReturn(new CUrl()); // empty instance to pass the null check
        $server->method('Path')
            ->willReturn(null);

        $sut->Initialize(__DIR__);
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Server path not available.');
        $sut->AppUrl();
    }

    function testAppUrlWithNonExistingServerPath()
    {
        $sut = $this->systemUnderTest();
        $server = Server::Instance();

        $server->method('Url')
            ->willReturn(new CUrl()); // empty instance to pass the null check
        $server->method('Path')
            ->willReturn(new CPath('non_existing_path'));

        $sut->Initialize(__DIR__);
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Failed to resolve server path.');
        $sut->AppUrl();
    }

    function testAppUrlWithAppPathNotUnderServerPath()
    {
        $sut = $this->systemUnderTest();
        $server = Server::Instance();

        $server->method('Url')
            ->willReturn(new CUrl()); // empty instance to pass the null check
        $server->method('Path')
            ->willReturn(new CPath(__DIR__));

        $sut->Initialize(CPath::Join(__DIR__, '..'));
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Application path is not under server path.');
        $sut->AppUrl();
    }

    function testAppUrlWithAppPathEqualToServerPath()
    {
        $sut = $this->systemUnderTest();
        $server = Server::Instance();

        $server->method('Url')
            ->willReturn(new CUrl('http://localhost'));
        $server->method('Path')
            ->willReturn(new CPath(__DIR__));

        $sut->Initialize(__DIR__);
        $this->assertEquals('http://localhost/', $sut->AppUrl());
    }

    function testAppUrlWithAppPathUnderServerPath()
    {
        $sut = $this->systemUnderTest();
        $server = Server::Instance();

        $server->expects($this->once()) // once() is to ensure cache hit
            ->method('Url')
            ->willReturn(new CUrl('http://localhost/'));
        $server->expects($this->once()) // once() is to ensure cache hit
            ->method('Path')
            ->willReturn(new CPath(__DIR__));

        $sut->Initialize(CPath::Join(__DIR__, 'Core'));
        $this->assertEquals('http://localhost/Core/', $sut->AppUrl());
        // Cache hit:
        $this->assertEquals('http://localhost/Core/', $sut->AppUrl());
    }

    function testAppUrlReturnsClone()
    {
        $sut = $this->systemUnderTest('AppRelativePath');
        $server = Server::Instance();

        $server->expects($this->once())
            ->method('Url')
            ->willReturn(new CUrl('url/to/server'));
        $sut->expects($this->once())
            ->method('AppRelativePath')
            ->willReturn(new CPath('app'));

        $url1 = $sut->AppUrl();
        $url1->ReplaceInPlace('app', 'oops1');
        $url2 = $sut->AppUrl();
        $url2->ReplaceInPlace('app', 'oops2');
        $url3 = $sut->AppUrl();

        $this->assertNotSame($url1, $url2);
        $this->assertNotSame($url2, $url3);
        $this->assertNotEquals($url1, $url3);
        $this->assertNotEquals($url2, $url3);
        $this->assertEquals('url/to/server/oops1/', $url1);
        $this->assertEquals('url/to/server/oops2/', $url2);
        $this->assertEquals('url/to/server/app/', $url3);
    }

    #endregion AppUrl

    #region AppSubdirectoryPath ------------------------------------------------

    function testAppSubdirectoryPath()
    {
        $sut = $this->systemUnderTest('AppPath');

        $sut->expects($this->once()) // once() is to ensure cache hit
            ->method('AppPath')
            ->willReturn(new CPath('path/to/app'));

        $expected = 'path/to/app' . \DIRECTORY_SEPARATOR . 'subdir';
        $this->assertEquals(
            $expected,
            AccessHelper::CallMethod($sut, 'AppSubdirectoryPath', ['subdir'])
        );
        // Cache hit:
        $this->assertEquals(
            $expected,
            AccessHelper::CallMethod($sut, 'AppSubdirectoryPath', ['subdir'])
        );
    }

    function testAppSubdirectoryPathReturnsClone()
    {
        $sut = $this->systemUnderTest('AppPath');

        $sut->expects($this->once())
            ->method('AppPath')
            ->willReturn(new CPath('path/to/app/'));

        $path1 = $sut->AppSubdirectoryPath('subdir');
        $path1->ReplaceInPlace('subdir', 'oops1');
        $path2 = $sut->AppSubdirectoryPath('subdir');
        $path2->ReplaceInPlace('subdir', 'oops2');
        $path3 = $sut->AppSubdirectoryPath('subdir');

        $this->assertNotSame($path1, $path2);
        $this->assertNotSame($path2, $path3);
        $this->assertNotEquals($path1, $path2);
        $this->assertNotEquals($path2, $path3);
        $this->assertEquals('path/to/app/oops1', $path1);
        $this->assertEquals('path/to/app/oops2', $path2);
        $this->assertEquals('path/to/app/subdir', $path3);
    }

    #endregion AppSubdirectoryPath

    #region AppSubdirectoryUrl -------------------------------------------------

    function testAppSubdirectoryUrl()
    {
        $sut = $this->systemUnderTest('AppUrl');

        $sut->expects($this->once()) // once() is to ensure cache hit
            ->method('AppUrl')
            ->willReturn(new CUrl('http://localhost/app/'));

        $expected = 'http://localhost/app/subdir';
        $this->assertEquals(
            $expected,
            AccessHelper::CallMethod($sut, 'AppSubdirectoryUrl', ['subdir'])
        );
        // Cache hit:
        $this->assertEquals(
            $expected,
            AccessHelper::CallMethod($sut, 'AppSubdirectoryUrl', ['subdir'])
        );
    }

    function testAppSubdirectoryUrlReturnsClone()
    {
        $sut = $this->systemUnderTest('AppUrl');

        $sut->expects($this->once())
            ->method('AppUrl')
            ->willReturn(new CUrl('url/to/app/'));

        $url1 = $sut->AppSubdirectoryUrl('subdir');
        $url1->ReplaceInPlace('subdir', 'oops1');
        $url2 = $sut->AppSubdirectoryUrl('subdir');
        $url2->ReplaceInPlace('subdir', 'oops2');
        $url3 = $sut->AppSubdirectoryUrl('subdir');

        $this->assertNotSame($url1, $url2);
        $this->assertNotSame($url2, $url3);
        $this->assertNotEquals($url1, $url2);
        $this->assertNotEquals($url2, $url3);
        $this->assertEquals('url/to/app/oops1', $url1);
        $this->assertEquals('url/to/app/oops2', $url2);
        $this->assertEquals('url/to/app/subdir', $url3);
    }

    #endregion AppSubdirectoryUrl
}
