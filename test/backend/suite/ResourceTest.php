<?php declare(strict_types=1);
use \PHPUnit\Framework\TestCase;
use \PHPUnit\Framework\Attributes\CoversClass;
use \PHPUnit\Framework\Attributes\RequiresOperatingSystem;

use \Harmonia\Resource;

use \Harmonia\Core\CArray;
use \Harmonia\Core\CPath;
use \Harmonia\Core\CUrl;
use \Harmonia\Server;
use \TestToolkit\AccessHelper as ah;

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
        return ah::CallConstructor($sut);
    }

    #region __construct --------------------------------------------------------

    function testConstructor()
    {
        $sut = $this->systemUnderTest();

        $this->assertNull(ah::GetProperty($sut, 'appPath'));
        $this->assertInstanceOf(CArray::class, ah::GetProperty($sut, 'cache'));
        $this->assertInstanceOf(Server::class, ah::GetProperty($sut, 'server'));
    }

    #endregion __construct

    #region Initialize ---------------------------------------------------------

    function testInitializeThrowsIfAlreadyInitialized()
    {
        $sut = $this->systemUnderTest();

        $sut->Initialize(__DIR__);
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage("Resource is already initialized.");
        $sut->Initialize(__DIR__);
    }

    function testInitializeThrowsIfPathCannotBeResolved()
    {
        $sut = $this->systemUnderTest();

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage("Failed to resolve application path.");
        $sut->Initialize(CPath::Join(__DIR__, 'non-existing-path'));
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

    function testAppPathThrowsIfNotInitialized()
    {
        $sut = $this->systemUnderTest();

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage("Resource is not initialized.");
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

    function testAppRelativePathThrowsIfServerPathNotAvailable()
    {
        $sut = $this->systemUnderTest();
        $server = Server::Instance();

        $server->expects($this->once())
            ->method('Path')
            ->willReturn(null);

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage("Server path not available.");
        $sut->AppRelativePath();
    }

    function testAppRelativePathThrowsIfServerPathCannotBeResolved()
    {
        $sut = $this->systemUnderTest();
        $server = Server::Instance();

        $server->expects($this->once())
            ->method('Path')
            ->willReturn(new CPath('non-existing-path'));

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage("Failed to resolve server path.");
        $sut->AppRelativePath();
    }

    function testAppRelativePathThrowsIfAppPathIsNotUnderServerPath()
    {
        $sut = $this->systemUnderTest('AppPath');
        $server = Server::Instance();

        $server->expects($this->once())
            ->method('Path')
            ->willReturn(new CPath(__DIR__));
        $sut->expects($this->once())
            ->method('AppPath')
            ->willReturn(CPath::Join(__DIR__, '..')->Apply('\realpath'));

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage("Application path is not under server path.");
        $sut->AppRelativePath();
    }

    #[RequiresOperatingSystem('Linux|Darwin')]
    function testAppRelativePathWithServerDirectoryContainingLinkToAppPath()
    {
        $sut = $this->systemUnderTest('AppPath');
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

        $server->expects($this->once())
            ->method('Path')
            ->willReturn($serverPath);
        $sut->expects($this->once())
            ->method('AppPath')
            ->willReturn($appPath);

        $this->assertEquals($appBaseName, $sut->AppRelativePath());

        $linkPath->Call('\unlink');
    }

    function testAppRelativePathWithAppPathEqualToServerPath()
    {
        $sut = $this->systemUnderTest('AppPath');
        $server = Server::Instance();

        $server->expects($this->once())
            ->method('Path')
            ->willReturn(new CPath(__DIR__));
        $sut->expects($this->once())
            ->method('AppPath')
            ->willReturn(new CPath(__DIR__));

        $this->assertTrue($sut->AppRelativePath()->IsEmpty());
    }

    function testAppRelativePathWithAppPathUnderServerPath()
    {
        $sut = $this->systemUnderTest('AppPath');
        $server = Server::Instance();

        $server->expects($this->once())
            ->method('Path')
            ->willReturn(new CPath(__DIR__));
        $sut->expects($this->once())
            ->method('AppPath')
            ->willReturn(CPath::Join(__DIR__, 'Core'));

        $this->assertEquals('Core', $sut->AppRelativePath());
        $this->assertEquals('Core', $sut->AppRelativePath()); // cache hit
    }

    function testAppRelativePathReturnsClone()
    {
        $sut = $this->systemUnderTest('AppPath');
        $server = Server::Instance();
        $serverPath = new CPath(__DIR__);

        $server->expects($this->once())
            ->method('Path')
            ->willReturn($serverPath);
        $sut->expects($this->once())
            ->method('AppPath')
            ->willReturn($serverPath->Extend('app'));

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

    function testAppUrlThrowsIfServerUrlNotAvailable()
    {
        $sut = $this->systemUnderTest();
        $server = Server::Instance();

        $server->expects($this->once())
            ->method('Url')
            ->willReturn(null);

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage("Server URL not available.");
        $sut->AppUrl();
    }

    function testAppUrlWithAppPathEqualToServerPath()
    {
        $sut = $this->systemUnderTest('AppRelativePath');
        $server = Server::Instance();

        $server->expects($this->once())
            ->method('Url')
            ->willReturn(new CUrl('http://localhost'));
        $sut->expects($this->once())
            ->method('AppRelativePath')
            ->willReturn(new CPath(''));

        $this->assertEquals('http://localhost/', $sut->AppUrl());
    }

    function testAppUrlWithAppPathUnderServerPath()
    {
        $sut = $this->systemUnderTest('AppRelativePath');
        $server = Server::Instance();

        $server->expects($this->once())
            ->method('Url')
            ->willReturn(new CUrl('http://localhost/'));
        $sut->expects($this->once())
            ->method('AppRelativePath')
            ->willReturn(new CPath('Core'));

        $this->assertEquals('http://localhost/Core/', $sut->AppUrl());
        $this->assertEquals('http://localhost/Core/', $sut->AppUrl()); // cache hit
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
        $expected = 'path/to/app' . \DIRECTORY_SEPARATOR . 'subdir';

        $sut->expects($this->once())
            ->method('AppPath')
            ->willReturn(new CPath('path/to/app'));

        $this->assertEquals($expected, $sut->AppSubdirectoryPath('subdir'));
        $this->assertEquals($expected, $sut->AppSubdirectoryPath('subdir')); // cache hit
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
        $expected = 'http://localhost/app/subdir';

        $sut->expects($this->once())
            ->method('AppUrl')
            ->willReturn(new CUrl('http://localhost/app/'));

        $this->assertEquals($expected, $sut->AppSubdirectoryUrl('subdir'));
        $this->assertEquals($expected, $sut->AppSubdirectoryUrl('subdir')); // cache hit
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
