<?php declare(strict_types=1);
use \PHPUnit\Framework\TestCase;
use \PHPUnit\Framework\Attributes\CoversClass;

use \Harmonia\Resource;

use \TestToolkit\AccessHelper;
use \Harmonia\Core\CArray;
use \Harmonia\Core\CPath;
use \Harmonia\Core\CUrl;
use \Harmonia\Server;

#[CoversClass(Resource::class)]
class ResourceTest extends TestCase
{
    private ?Resource $originalResource = null;
    private ?Server $originalServer = null;

    protected function setUp(): void
    {
        $this->originalResource = Resource::ReplaceInstance(null);
        $this->originalServer = Server::ReplaceInstance($this->createMock(Server::class));
    }

    protected function tearDown(): void
    {
        Resource::ReplaceInstance($this->originalResource);
        Server::ReplaceInstance($this->originalServer);
    }

    #region __construct --------------------------------------------------------

    function testConstructor()
    {
        $resource = Resource::Instance();
        $this->assertNull(AccessHelper::GetNonPublicProperty($resource, 'appPath'));
        $this->assertInstanceOf(CArray::class, AccessHelper::GetNonPublicProperty($resource, 'cache'));
        $this->assertInstanceOf(Server::class, AccessHelper::GetNonPublicProperty($resource, 'server'));
    }

    #endregion __construct

    #region Initialize ---------------------------------------------------------

    function testInitializeWhenAlreadyInitialized()
    {
        $resource = Resource::Instance();
        $resource->Initialize(__DIR__);
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Resource is already initialized.');
        $resource->Initialize(__DIR__);
    }

    function testInitializeWithNonExistingPath()
    {
        $resource = Resource::Instance();
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Failed to resolve application path.');
        $resource->Initialize(CPath::Join(__DIR__, 'non_existing_path'));
    }

    function testInitializeWithExistingPath()
    {
        $resource = Resource::Instance();
        $resource->Initialize(__DIR__);
        $this->assertEquals(__DIR__, $resource->AppPath());
    }

    function testInitializeWithExistingRelativePath()
    {
        $resource = Resource::Instance();
        $resource->Initialize(CPath::Join(__DIR__, '..', 'suite'));
        $this->assertEquals(__DIR__, $resource->AppPath());
    }

    #endregion Initialize

    #region AppPath ------------------------------------------------------------

    function testAppPathWhenNotInitialized()
    {
        $resource = Resource::Instance();
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Resource is not initialized.');
        $resource->AppPath();
    }

    #endregion AppPath

    #region AppRelativePath ----------------------------------------------------

    function testAppRelativePathWhenNotInitialized()
    {
        $resource = Resource::Instance();
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Resource is not initialized.');
        $resource->AppRelativePath();
    }

    function testAppRelativePathWithNullServerPath()
    {
        $resource = Resource::Instance();
        $serverMock = Server::Instance();
        $serverMock->method('Path')
            ->willReturn(null);
        $resource->Initialize(__DIR__);
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Server path not available.');
        $resource->AppRelativePath();
    }

    function testAppRelativePathWithNonExistingServerPath()
    {
        $resource = Resource::Instance();
        $serverMock = Server::Instance();
        $serverMock->method('Path')
            ->willReturn(new CPath('non_existing_path'));
        $resource->Initialize(__DIR__);
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Failed to resolve server path.');
        $resource->AppRelativePath();
    }

    function testAppRelativePathWithAppPathNotUnderServerPath()
    {
        $resource = Resource::Instance();
        $serverMock = Server::Instance();
        $serverMock->method('Path')
            ->willReturn(new CPath(__DIR__));
        $resource->Initialize(CPath::Join(__DIR__, '..'));
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Application path is not under server path.');
        $resource->AppRelativePath();
    }

    function testAppRelativePathWithAppPathEqualToServerPath()
    {
        $resource = Resource::Instance();
        $serverMock = Server::Instance();
        $serverMock->method('Path')
            ->willReturn(new CPath(__DIR__));
        $resource->Initialize(__DIR__);
        $this->assertTrue($resource->AppRelativePath()->IsEmpty());
    }

    function testAppRelativePathWithAppPathUnderServerPath()
    {
        $resource = Resource::Instance();
        $serverMock = Server::Instance();
        $serverMock->expects($this->once()) // once() is to ensure cache hit
            ->method('Path')
            ->willReturn(new CPath(__DIR__));
        $resource->Initialize(CPath::Join(__DIR__, 'Core'));
        $this->assertSame('Core', (string)$resource->AppRelativePath());
        // Cache hit:
        $this->assertSame('Core', (string)$resource->AppRelativePath());
    }

    #endregion AppRelativePath

    #region AppUrl -------------------------------------------------------------

    function testAppUrlWithNullServerUrl()
    {
        $resource = Resource::Instance();
        $serverMock = Server::Instance();
        $serverMock->method('Url')
            ->willReturn(null);
        $resource->Initialize(__DIR__);
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Server URL not available.');
        $resource->AppUrl();
    }

    function testAppUrlWhenNotInitialized()
    {
        $resource = Resource::Instance();
        $serverMock = Server::Instance();
        $serverMock->method('Url')
            ->willReturn(new CUrl()); // empty instance to pass the null check
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Resource is not initialized.');
        $resource->AppUrl();
    }

    function testAppUrlWithNullServerPath()
    {
        $resource = Resource::Instance();
        $serverMock = Server::Instance();
        $serverMock->method('Url')
            ->willReturn(new CUrl()); // empty instance to pass the null check
        $serverMock->method('Path')
            ->willReturn(null);
        $resource->Initialize(__DIR__);
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Server path not available.');
        $resource->AppUrl();
    }

    function testAppUrlWithNonExistingServerPath()
    {
        $resource = Resource::Instance();
        $serverMock = Server::Instance();
        $serverMock->method('Url')
            ->willReturn(new CUrl()); // empty instance to pass the null check
        $serverMock->method('Path')
            ->willReturn(new CPath('non_existing_path'));
        $resource->Initialize(__DIR__);
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Failed to resolve server path.');
        $resource->AppUrl();
    }

    function testAppUrlWithAppPathNotUnderServerPath()
    {
        $resource = Resource::Instance();
        $serverMock = Server::Instance();
        $serverMock->method('Url')
            ->willReturn(new CUrl()); // empty instance to pass the null check
        $serverMock->method('Path')
            ->willReturn(new CPath(__DIR__));
        $resource->Initialize(CPath::Join(__DIR__, '..'));
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Application path is not under server path.');
        $resource->AppUrl();
    }

    function testAppUrlWithAppPathEqualToServerPath()
    {
        $resource = Resource::Instance();
        $serverMock = Server::Instance();
        $serverMock->method('Url')
            ->willReturn(new CUrl('http://localhost'));
        $serverMock->method('Path')
            ->willReturn(new CPath(__DIR__));
        $resource->Initialize(__DIR__);
        $this->assertSame('http://localhost/', (string)$resource->AppUrl());
    }

    function testAppUrlWithAppPathUnderServerPath()
    {
        $resource = Resource::Instance();
        $serverMock = Server::Instance();
        $serverMock->expects($this->once()) // once() is to ensure cache hit
            ->method('Url')
            ->willReturn(new CUrl('http://localhost/'));
        $serverMock->expects($this->once()) // once() is to ensure cache hit
            ->method('Path')
            ->willReturn(new CPath(__DIR__));
        $resource->Initialize(CPath::Join(__DIR__, 'Core'));
        $this->assertSame('http://localhost/Core/', (string)$resource->AppUrl());
        // Cache hit:
        $this->assertSame('http://localhost/Core/', (string)$resource->AppUrl());
    }

    #endregion AppUrl
}
