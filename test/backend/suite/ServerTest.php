<?php declare(strict_types=1);
use \PHPUnit\Framework\TestCase;
use \PHPUnit\Framework\Attributes\CoversClass;
use \PHPUnit\Framework\Attributes\BackupGlobals;

use \Harmonia\Server;

#[CoversClass(Server::class)]
class ServerTest extends TestCase
{
    private readonly ?Server $originalServer;

    protected function setUp(): void
    {
        $this->originalServer = Server::ReplaceInstance(null);
    }

    protected function tearDown(): void
    {
        Server::ReplaceInstance($this->originalServer);
    }

    #[BackupGlobals(true)]
    function testSuperglobalChangesDoNotAffectConstructedInstance()
    {
        // Set initial superglobal values.
        $_SERVER['HTTPS'] = 'on';
        $server = Server::Instance();
        // Modify the superglobal after instance construction.
        $_SERVER['HTTPS'] = 'off';
        $_SERVER['SERVER_PORT'] = '80';
        $_SERVER['REQUEST_SCHEME'] = 'http';
        $_SERVER['HTTP_X_FORWARDED_PROTO'] = 'http';
        // Assert that the instance retains the initial state.
        $this->assertTrue($server->IsSecure());
    }

    #region IsSecure -----------------------------------------------------------

    #[BackupGlobals(true)]
    function testIsSecureWithHttpsOn()
    {
        $_SERVER['HTTPS'] = 'on';
        $this->assertTrue(Server::Instance()->IsSecure());
    }

    #[BackupGlobals(true)]
    function testIsSecureWithHttpsOne()
    {
        $_SERVER['HTTPS'] = '1';
        $this->assertTrue(Server::Instance()->IsSecure());
    }

    #[BackupGlobals(true)]
    function testIsSecureWithServerPort443()
    {
        unset($_SERVER['HTTPS']);
        $_SERVER['SERVER_PORT'] = '443';
        $this->assertTrue(Server::Instance()->IsSecure());
    }

    #[BackupGlobals(true)]
    function testIsSecureWithRequestSchemeHttps()
    {
        unset($_SERVER['HTTPS']);
        unset($_SERVER['SERVER_PORT']);
        $_SERVER['REQUEST_SCHEME'] = 'https';
        $this->assertTrue(Server::Instance()->IsSecure());
    }

    #[BackupGlobals(true)]
    function testIsSecureWithHttpXForwardedProtoHttps()
    {
        unset($_SERVER['HTTPS']);
        unset($_SERVER['SERVER_PORT']);
        unset($_SERVER['REQUEST_SCHEME']);
        $_SERVER['HTTP_X_FORWARDED_PROTO'] = 'https';
        $this->assertTrue(Server::Instance()->IsSecure());
    }

    #[BackupGlobals(true)]
    function testIsSecureWithNoSecureIndicators()
    {
        unset($_SERVER['HTTPS']);
        unset($_SERVER['SERVER_PORT']);
        unset($_SERVER['REQUEST_SCHEME']);
        unset($_SERVER['HTTP_X_FORWARDED_PROTO']);
        $this->assertFalse(Server::Instance()->IsSecure());
    }

    #[BackupGlobals(true)]
    function testIsSecureWhenHttpsOverridesServerPort()
    {
        $_SERVER['HTTPS'] = 'on';
        $_SERVER['SERVER_PORT'] = '80';
        $this->assertTrue(Server::Instance()->IsSecure());
    }

    #[BackupGlobals(true)]
    function testIsSecureWhenServerPortOverridesRequestScheme()
    {
        unset($_SERVER['HTTPS']);
        $_SERVER['SERVER_PORT'] = '443';
        $_SERVER['REQUEST_SCHEME'] = 'http';
        $this->assertTrue(Server::Instance()->IsSecure());
    }

    #[BackupGlobals(true)]
    function testIsSecureWhenRequestSchemeOverridesHttpXForwardedProto()
    {
        unset($_SERVER['HTTPS']);
        unset($_SERVER['SERVER_PORT']);
        $_SERVER['REQUEST_SCHEME'] = 'https';
        $_SERVER['HTTP_X_FORWARDED_PROTO'] = 'http';
        $this->assertTrue(Server::Instance()->IsSecure());
    }

    #[BackupGlobals(true)]
    function testIsSecureWithCaseSensitiveHttps()
    {
        $_SERVER['HTTPS'] = 'On';
        $this->assertFalse(Server::Instance()->IsSecure());
    }

    #[BackupGlobals(true)]
    function testIsSecureWithCaseSensitiveRequestScheme()
    {
        $_SERVER['REQUEST_SCHEME'] = 'Https';
        $this->assertFalse(Server::Instance()->IsSecure());
    }

    #[BackupGlobals(true)]
    function testIsSecureWithCaseSensitiveHttpXForwardedProto()
    {
        $_SERVER['HTTP_X_FORWARDED_PROTO'] = 'Https';
        $this->assertFalse(Server::Instance()->IsSecure());
    }

    #endregion IsSecure

    #region Url ----------------------------------------------------------------

    #[BackupGlobals(true)]
    function testUrlWithHttps()
    {
        $_SERVER['HTTPS'] = 'on';
        $_SERVER['SERVER_NAME'] = 'example.com';
        $this->assertSame('https://example.com', (string)Server::Instance()->Url());
    }

    #[BackupGlobals(true)]
    function testUrlWithHttp()
    {
        unset($_SERVER['HTTPS']);
        unset($_SERVER['SERVER_PORT']);
        unset($_SERVER['REQUEST_SCHEME']);
        unset($_SERVER['HTTP_X_FORWARDED_PROTO']);
        $_SERVER['SERVER_NAME'] = 'example.com';
        $this->assertSame('http://example.com', (string)Server::Instance()->Url());
    }

    #[BackupGlobals(true)]
    function testUrlWithNoServerName()
    {
        unset($_SERVER['SERVER_NAME']);
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Server name is not set.');
        Server::Instance()->Url();
    }

    #endregion Url

    #region Path ---------------------------------------------------------------

    #[BackupGlobals(true)]
    function testPathWithDocumentRoot()
    {
        $_SERVER['DOCUMENT_ROOT'] = 'path/to/root';
        $this->assertSame('path/to/root', (string)Server::Instance()->Path());
    }

    #[BackupGlobals(true)]
    function testPathWithNoDocumentRoot()
    {
        unset($_SERVER['DOCUMENT_ROOT']);
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Document root is not set.');
        Server::Instance()->Path();
    }

    #endregion Path
}
