<?php declare(strict_types=1);
use \PHPUnit\Framework\TestCase;
use \PHPUnit\Framework\Attributes\CoversClass;
use \PHPUnit\Framework\Attributes\BackupGlobals;

use \Harmonia\Server;

#[CoversClass(Server::class)]
class ServerTest extends TestCase
{
    private ?Server $originalServer = null;

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
        $this->assertEquals('https://example.com', Server::Instance()->Url());
    }

    #[BackupGlobals(true)]
    function testUrlWithHttp()
    {
        unset($_SERVER['HTTPS']);
        unset($_SERVER['SERVER_PORT']);
        unset($_SERVER['REQUEST_SCHEME']);
        unset($_SERVER['HTTP_X_FORWARDED_PROTO']);
        $_SERVER['SERVER_NAME'] = 'example.com';
        $this->assertEquals('http://example.com', Server::Instance()->Url());
    }

    #[BackupGlobals(true)]
    function testUrlWithNoServerName()
    {
        unset($_SERVER['SERVER_NAME']);
        $this->assertNull(Server::Instance()->Url());
    }

    #[BackupGlobals(true)]
    function testUrlWithEmptyServerName()
    {
        $_SERVER['SERVER_NAME'] = '';
        $this->assertNull(Server::Instance()->Url());
    }

    #endregion Url

    #region Path ---------------------------------------------------------------

    #[BackupGlobals(true)]
    function testPathWithDocumentRoot()
    {
        $_SERVER['DOCUMENT_ROOT'] = 'path/to/root';
        $this->assertEquals('path/to/root', Server::Instance()->Path());
    }

    #[BackupGlobals(true)]
    function testPathWithNoDocumentRoot()
    {
        unset($_SERVER['DOCUMENT_ROOT']);
        $this->assertNull(Server::Instance()->Path());
    }

    #[BackupGlobals(true)]
    function testPathWithEmptyDocumentRoot()
    {
        $_SERVER['DOCUMENT_ROOT'] = '';
        $this->assertNull(Server::Instance()->Path());
    }

    #endregion Path

    #region RequestMethod ------------------------------------------------------

    #[BackupGlobals(true)]
    function testRequestMethodWithGet()
    {
        $_SERVER['REQUEST_METHOD'] = 'GET';
        $this->assertEquals('GET', Server::Instance()->RequestMethod());
    }

    #[BackupGlobals(true)]
    function testRequestMethodWithPost()
    {
        $_SERVER['REQUEST_METHOD'] = 'POST';
        $this->assertEquals('POST', Server::Instance()->RequestMethod());
    }

    #[BackupGlobals(true)]
    function testRequestMethodWithNoRequestMethod()
    {
        unset($_SERVER['REQUEST_METHOD']);
        $this->assertNull(Server::Instance()->RequestMethod());
    }

    #[BackupGlobals(true)]
    function testRequestMethodWithEmptyRequestMethod()
    {
        $_SERVER['REQUEST_METHOD'] = '';
        $this->assertNull(Server::Instance()->RequestMethod());
    }

    #endregion RequestMethod

    #region RequestUri ---------------------------------------------------------

    #[BackupGlobals(true)]
    function testRequestUriWithUri()
    {
        $_SERVER['REQUEST_URI'] = '/index.php?foo=bar#section';
        $this->assertEquals('/index.php?foo=bar#section',
                            Server::Instance()->RequestUri());
    }

    #[BackupGlobals(true)]
    function testRequestUriWithNoRequestUri()
    {
        unset($_SERVER['REQUEST_URI']);
        $this->assertNull(Server::Instance()->RequestUri());
    }

    #[BackupGlobals(true)]
    function testRequestUriWithEmptyRequestUri()
    {
        $_SERVER['REQUEST_URI'] = '';
        $this->assertNull(Server::Instance()->RequestUri());
    }

    #endregion RequestUri

    #region RequestHeaders -----------------------------------------------------

    #[BackupGlobals(true)]
    function testRequestHeadersWithNoHeaders()
    {
        $this->assertTrue(Server::Instance()->RequestHeaders()->IsEmpty());
    }

    #[BackupGlobals(true)]
    function testRequestHeadersWithHeaders()
    {
        $_SERVER['HTTP_FOO_BAR'] = 'baz';
        $_SERVER['HTTP_QUX_QUUX'] = 'corge';
        $this->assertEquals(
            [ 'foo-bar' => 'baz',
              'qux-quux' => 'corge' ],
            Server::Instance()->RequestHeaders()->ToArray()
        );
    }

    #[BackupGlobals(true)]
    function testRequestHeadersWithMixedCaseHeaders()
    {
        $_SERVER['http_foo_bar'] = 'baz'; // ignored due to lowercase "http_"
        $_SERVER['HTTP_qUX_qUUX'] = 'corge';
        $_SERVER['HTTP_Abc_Def'] = 'ghi';
        $this->assertEquals(
            [ 'qux-quux' => 'corge',
              'abc-def' => 'ghi' ],
            Server::Instance()->RequestHeaders()->ToArray()
        );
    }

    #[BackupGlobals(true)]
    function testRequestHeadersWithSpecialHeaders()
    {
        $_SERVER['CONTENT_TYPE'] = 'application/json';
        $_SERVER['CONTENT_LENGTH'] = '123';
        $this->assertEquals(
            [ 'content-type' => 'application/json',
              'content-length' => '123' ],
            Server::Instance()->RequestHeaders()->ToArray()
        );
    }

    #[BackupGlobals(true)]
    function testRequestHeadersWithSpecialHeadersButImproperCase()
    {
        $_SERVER['cONTENT_tYPE'] = 'application/json';
        $_SERVER['content_length'] = '123';
        $this->assertTrue(Server::Instance()->RequestHeaders()->IsEmpty());
    }

    #endregion RequestHeaders

    #region ClientAddress ------------------------------------------------------

    #[BackupGlobals(true)]
    function testClientAddressWithAddress()
    {
        $_SERVER['REMOTE_ADDR'] = '192.168.33.1';
        $this->assertSame('192.168.33.1', Server::Instance()->ClientAddress());
    }

    #[BackupGlobals(true)]
    function testClientAddressWithNoAddress()
    {
        unset($_SERVER['REMOTE_ADDR']);
        $this->assertSame('', Server::Instance()->ClientAddress());
    }

    #endregion ClientAddress
}
