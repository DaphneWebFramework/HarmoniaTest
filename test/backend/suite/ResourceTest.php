<?php declare(strict_types=1);
use \PHPUnit\Framework\TestCase;
use \PHPUnit\Framework\Attributes\CoversClass;

use \Harmonia\Resource;

use \TestToolkit\AccessHelper;
use \Harmonia\Core\CPath;
use \Harmonia\Core\CUrl;
use \Harmonia\Server;

#[CoversClass(Resource::class)]
class ResourceTest extends TestCase
{
    private readonly ?Resource $originalResource;
    private readonly ?Server $originalServer;

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

    #region Initialize ---------------------------------------------------------

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
        $resource->Initialize(new CPath(__DIR__));
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

    function testAppRelativePathWithNonExistingServerPath()
    {
        $resource = Resource::Instance();
        $serverMock = Server::Instance();
        $serverMock->method('Path')
            ->willReturn(new CPath('non_existing_path'));
        $resource->Initialize(new CPath(__DIR__));
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
        $resource->Initialize(new CPath(__DIR__));
        $this->assertTrue($resource->AppRelativePath()->IsEmpty());
    }

    function testAppRelativePathWithAppPathUnderServerPath()
    {
        $resource = Resource::Instance();
        $serverMock = Server::Instance();
        $serverMock->expects($this->once())
            ->method('Path')
            ->willReturn(new CPath(__DIR__));
        $resource->Initialize(CPath::Join(__DIR__, 'Core'));
        $this->assertSame('Core', (string)$resource->AppRelativePath());
        // Cache hit:
        $this->assertSame('Core', (string)$resource->AppRelativePath());
    }

    #endregion AppRelativePath

    #region AppUrl -------------------------------------------------------------

    function testAppUrlWhenNotInitialized()
    {
        $resource = Resource::Instance();
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Resource is not initialized.');
        $resource->AppUrl();
    }

    function testAppUrlWithNonExistingServerPath()
    {
        $resource = Resource::Instance();
        $serverMock = Server::Instance();
        $serverMock->method('Path')
            ->willReturn(new CPath('non_existing_path'));
        $resource->Initialize(new CPath(__DIR__));
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Failed to resolve server path.');
        $resource->AppUrl();
    }

    function testAppUrlWithAppPathNotUnderServerPath()
    {
        $resource = Resource::Instance();
        $serverMock = Server::Instance();
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
        $serverMock->method('Path')
            ->willReturn(new CPath(__DIR__));
        $serverMock->method('Url')
            ->willReturn(new CUrl('http://localhost'));
        $resource->Initialize(new CPath(__DIR__));
        $this->assertSame('http://localhost/', (string)$resource->AppUrl());
    }

    function testAppUrlWithAppPathUnderServerPath()
    {
        $resource = Resource::Instance();
        $serverMock = Server::Instance();
        $serverMock->expects($this->once())
            ->method('Path')
            ->willReturn(new CPath(__DIR__));
        $serverMock->expects($this->once())
            ->method('Url')
            ->willReturn(new CUrl('http://localhost/'));
        $resource->Initialize(CPath::Join(__DIR__, 'Core'));
        $this->assertSame('http://localhost/Core/', (string)$resource->AppUrl());
        // Cache hit:
        $this->assertSame('http://localhost/Core/', (string)$resource->AppUrl());
    }

    #endregion AppUrl
}
