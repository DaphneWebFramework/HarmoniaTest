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
        $this->originalServer = Server::ReplaceInstance($this->createMock(Server::class));
        $this->originalConfig = Config::ReplaceInstance($this->createMock(Config::class));
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
        $cookieService = $this->systemUnderTest('_headers_sent', '_setcookie');

        $cookieService->expects($this->once())
            ->method('_headers_sent')
            ->willReturn(true);
        $cookieService->expects($this->never())
            ->method('_setcookie');

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('HTTP headers have already been sent.');
        $cookieService->SetCookie('', '');
    }

    #[DataProvider('setCookieDataProvider')]
    function testSetCookie($cookieValue, $isSecure, $returnValue)
    {
        $cookieService = $this->systemUnderTest('_headers_sent', '_setcookie');
        $server = Server::Instance();

        $server->expects($this->once())
            ->method('IsSecure')
            ->willReturn($isSecure);
        $cookieService->expects($this->once())
            ->method('_headers_sent')
            ->willReturn(false);
        $cookieService->expects($this->once())
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

        AccessHelper::CallConstructor($cookieService); // Initialize options

        if ($returnValue === false) {
            $this->expectException(\RuntimeException::class);
            $this->expectExceptionMessage('Failed to set or delete cookie.');
        }
        $cookieService->SetCookie('cookie-name', $cookieValue);
    }

    #endregion SetCookie

    #region DeleteCookie -------------------------------------------------------

    function testDeleteCookie()
    {
        $cookieService = $this->systemUnderTest('SetCookie');

        $cookieService->expects($this->once())
            ->method('SetCookie')
            ->with('cookie-name', '');

        $cookieService->DeleteCookie('cookie-name');
    }

    #endregion DeleteCookie

    #region AppSpecificCookieName ----------------------------------------------

    function testAppSpecificCookieNameWithEmptySuffix()
    {
        $cookieService = $this->systemUnderTest();
        $config = Config::Instance();

        $config->expects($this->never())
            ->method('OptionOrDefault');

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Suffix cannot be empty.');
        $cookieService->AppSpecificCookieName('');
    }

    function testAppSpecificCookieNameWhenConfigAppNameIsNotSetOrEmpty()
    {
        $cookieService = $this->systemUnderTest();
        $config = Config::Instance();

        $config->expects($this->once())
            ->method('OptionOrDefault')
            ->with('AppName', '')
            ->willReturn('');

        $this->assertSame(
            'HARMONIA_INTEGRITY_TOKEN',
            $cookieService->AppSpecificCookieName('INTEGRITY_TOKEN')
        );
    }

    function testAppSpecificCookieNameWhenConfigAppNameIsSetAndNotEmpty()
    {
        $cookieService = $this->systemUnderTest();
        $config = Config::Instance();

        $config->expects($this->once())
            ->method('OptionOrDefault')
            ->with('AppName', '')
            ->willReturn('MyApp');

        $this->assertSame(
            'MYAPP_INTEGRITY_TOKEN',
            $cookieService->AppSpecificCookieName('INTEGRITY_TOKEN')
        );
    }

    function testAppSpecificCookieNameWithLowerCaseSuffix()
    {
        $cookieService = $this->systemUnderTest();
        $config = Config::Instance();

        $config->expects($this->once())
            ->method('OptionOrDefault')
            ->with('AppName', '')
            ->willReturn('MyApp');

        $this->assertSame(
            'MYAPP_INTEGRITY_TOKEN',
            $cookieService->AppSpecificCookieName('integrity_token')
        );
    }

    #endregion AppSpecificCookieName

    #region SetCsrfCookie ------------------------------------------------------

    function testSetCsrfCookie()
    {
        $cookieService = $this->systemUnderTest('CsrfCookieName', 'SetCookie');

        $cookieService->expects($this->once())
            ->method('CsrfCookieName')
            ->willReturn('cookie-name');
        $cookieService->expects($this->once())
            ->method('SetCookie')
            ->with('cookie-name', 'cookie-value');

        $cookieService->SetCsrfCookie('cookie-value');
    }

    #endregion SetCsrfCookie

    #region DeleteCsrfCookie ---------------------------------------------------

    function testDeleteCsrfCookie()
    {
        $cookieService = $this->systemUnderTest('DeleteCookie', 'CsrfCookieName');

        $cookieService->expects($this->once())
            ->method('CsrfCookieName')
            ->willReturn('cookie-name');
        $cookieService->expects($this->once())
            ->method('DeleteCookie')
            ->with('cookie-name');

        $cookieService->DeleteCsrfCookie();
    }

    #endregion DeleteCsrfCookie

    #region CsrfCookieName -----------------------------------------------------

    function testCsrfCookieName()
    {
        $cookieService = $this->systemUnderTest('AppSpecificCookieName');

        $cookieService->expects($this->once())
            ->method('AppSpecificCookieName')
            ->with('CSRF')
            ->willReturn('cookie-name');

        $this->assertSame('cookie-name', $cookieService->CsrfCookieName());
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
