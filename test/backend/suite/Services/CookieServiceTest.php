<?php declare(strict_types=1);
use \PHPUnit\Framework\TestCase;
use \PHPUnit\Framework\Attributes\CoversClass;
use \PHPUnit\Framework\Attributes\DataProvider;

use \Harmonia\Services\CookieService;

use \Harmonia\Config;
use \Harmonia\Server;
use \TestToolkit\AccessHelper;
use \TestToolkit\DataHelper;

#[CoversClass(CookieService::class)]
class CookieServiceTest extends TestCase
{
    private ?Server $originalServer = null;
    private ?Config $originalConfig = null;

    protected function setUp(): void
    {
        $this->originalServer =
            Server::ReplaceInstance($this->createMock(Server::class));
        $this->originalConfig =
            Config::ReplaceInstance($this->createMock(Config::class));
    }

    protected function tearDown(): void
    {
        Server::ReplaceInstance($this->originalServer);
        Config::ReplaceInstance($this->originalConfig);
    }

    private function systemUnderTest(string ...$mockedMethods): CookieService
    {
        return $this->getMockBuilder(CookieService::class)
            ->disableOriginalConstructor()
            ->onlyMethods($mockedMethods)
            ->getMock();
    }

    #region SetCookie ----------------------------------------------------------

    function testSetCookieWhenHeadersSent()
    {
        $sut = $this->systemUnderTest('_headers_sent', '_setcookie');

        $sut->expects($this->once())
            ->method('_headers_sent')
            ->willReturn(true);
        $sut->expects($this->never())
            ->method('_setcookie');

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('HTTP headers have already been sent.');
        $sut->SetCookie('', '');
    }

    #[DataProvider('setCookieDataProvider')]
    function testSetCookie($cookieValue, $isSecure, $returnValue)
    {
        $sut = $this->systemUnderTest('_headers_sent', '_setcookie');
        $server = Server::Instance();

        $server->expects($this->once())
            ->method('IsSecure')
            ->willReturn($isSecure);
        $sut->expects($this->once())
            ->method('_headers_sent')
            ->willReturn(false);
        $sut->expects($this->once())
            ->method('_setcookie')
            ->with('cookie-name', $cookieValue, [
                'expires'  => 0,
                'path'     => '/',
                'domain'   => '',
                'secure'   => $isSecure,
                'httponly' => true,
                'samesite' => 'Strict'
            ])
            ->willReturn($returnValue);

        AccessHelper::CallConstructor($sut); // Initialize options

        if ($returnValue === false) {
            $this->expectException(\RuntimeException::class);
            $this->expectExceptionMessage('Failed to set or delete cookie.');
        }
        $sut->SetCookie('cookie-name', $cookieValue);
    }

    function testSetCookieWithExpiration()
    {
        $sut = $this->systemUnderTest('_headers_sent', '_setcookie');
        $server = Server::Instance();

        $server->expects($this->once())
            ->method('IsSecure')
            ->willReturn(false);
        $sut->expects($this->once())
            ->method('_headers_sent')
            ->willReturn(false);
        $sut->expects($this->once())
            ->method('_setcookie')
            ->with('cookie-name', 'cookie-value', [
                'expires'  => 1234567890,
                'path'     => '/',
                'domain'   => '',
                'secure'   => false,
                'httponly' => true,
                'samesite' => 'Strict'
            ])
            ->willReturn(true);

        AccessHelper::CallConstructor($sut); // Initialize options

        $sut->SetCookie('cookie-name', 'cookie-value', 1234567890);
    }

    #endregion SetCookie

    #region DeleteCookie -------------------------------------------------------

    function testDeleteCookie()
    {
        $sut = $this->systemUnderTest('SetCookie');

        $sut->expects($this->once())
            ->method('SetCookie')
            ->with('cookie-name', '');

        $sut->DeleteCookie('cookie-name');
    }

    #endregion DeleteCookie

    #region AppSpecificCookieName ----------------------------------------------

    function testAppSpecificCookieNameWithEmptySuffix()
    {
        $sut = $this->systemUnderTest();
        $config = Config::Instance();

        $config->expects($this->never())
            ->method('OptionOrDefault');

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Suffix cannot be empty.');
        $sut->AppSpecificCookieName('');
    }

    function testAppSpecificCookieNameWhenConfigAppNameIsNotSetOrEmpty()
    {
        $sut = $this->systemUnderTest();
        $config = Config::Instance();

        $config->expects($this->once())
            ->method('OptionOrDefault')
            ->with('AppName', '')
            ->willReturn('');

        $this->assertSame(
            'HARMONIA_INTEGRITY_TOKEN',
            $sut->AppSpecificCookieName('INTEGRITY_TOKEN')
        );
    }

    function testAppSpecificCookieNameWhenConfigAppNameIsSetAndNotEmpty()
    {
        $sut = $this->systemUnderTest();
        $config = Config::Instance();

        $config->expects($this->once())
            ->method('OptionOrDefault')
            ->with('AppName', '')
            ->willReturn('MyApp');

        $this->assertSame(
            'MYAPP_INTEGRITY_TOKEN',
            $sut->AppSpecificCookieName('INTEGRITY_TOKEN')
        );
    }

    function testAppSpecificCookieNameWithLowerCaseSuffix()
    {
        $sut = $this->systemUnderTest();
        $config = Config::Instance();

        $config->expects($this->once())
            ->method('OptionOrDefault')
            ->with('AppName', '')
            ->willReturn('MyApp');

        $this->assertSame(
            'MYAPP_INTEGRITY_TOKEN',
            $sut->AppSpecificCookieName('integrity_token')
        );
    }

    #endregion AppSpecificCookieName

    #region SetCsrfCookie ------------------------------------------------------

    function testSetCsrfCookie()
    {
        $sut = $this->systemUnderTest('CsrfCookieName', 'SetCookie');

        $sut->expects($this->once())
            ->method('CsrfCookieName')
            ->willReturn('cookie-name');
        $sut->expects($this->once())
            ->method('SetCookie')
            ->with('cookie-name', 'cookie-value');

        $sut->SetCsrfCookie('cookie-value');
    }

    #endregion SetCsrfCookie

    #region DeleteCsrfCookie ---------------------------------------------------

    function testDeleteCsrfCookie()
    {
        $sut = $this->systemUnderTest('DeleteCookie', 'CsrfCookieName');

        $sut->expects($this->once())
            ->method('CsrfCookieName')
            ->willReturn('cookie-name');
        $sut->expects($this->once())
            ->method('DeleteCookie')
            ->with('cookie-name');

        $sut->DeleteCsrfCookie();
    }

    #endregion DeleteCsrfCookie

    #region CsrfCookieName -----------------------------------------------------

    function testCsrfCookieName()
    {
        $sut = $this->systemUnderTest('AppSpecificCookieName');

        $sut->expects($this->once())
            ->method('AppSpecificCookieName')
            ->with('CSRF')
            ->willReturn('cookie-name');

        $this->assertSame('cookie-name', $sut->CsrfCookieName());
    }

    #endregion CsrfCookieName

    #region Data Providers -----------------------------------------------------

    static function setCookieDataProvider()
    {
        // cookieValue
        // isSecure
        // returnValue
        return DataHelper::Cartesian(
            ['cookie-value', ''],
            [false, true],
            [false, true]
        );
    }

    #endregion Data Providers
}
