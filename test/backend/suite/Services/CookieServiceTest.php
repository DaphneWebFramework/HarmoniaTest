<?php declare(strict_types=1);
use \PHPUnit\Framework\TestCase;
use \PHPUnit\Framework\Attributes\CoversClass;
use \PHPUnit\Framework\Attributes\DataProvider;
use \PHPUnit\Framework\Attributes\DataProviderExternal;

use \Harmonia\Services\CookieService;

use \Harmonia\Config;
use \Harmonia\Server;
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

        $this->assertFalse($cookieService->SetCookie('', ''));
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

        $this->assertSame(
            $returnValue,
            $cookieService->SetCookie('cookie-name', $cookieValue)
        );
    }

    #endregion SetCookie

    #region DeleteCookie -------------------------------------------------------

    #[DataProviderExternal(DataHelper::class, 'BooleanProvider')]
    function testDeleteCookie($returnValue)
    {
        $cookieService = $this->systemUnderTest('SetCookie');

        $cookieService->expects($this->once())
            ->method('SetCookie')
            ->with('cookie-name', '')
            ->willReturn($returnValue);

        $this->assertSame($returnValue, $cookieService->DeleteCookie('cookie-name'));
    }

    #endregion DeleteCookie

    #region DeleteCsrfCookie ---------------------------------------------------

    #[DataProviderExternal(DataHelper::class, 'BooleanProvider')]
    function testDeleteCsrfCookie($returnValue)
    {
        $cookieService = $this->systemUnderTest('DeleteCookie', 'CsrfCookieName');

        $cookieService->expects($this->once())
            ->method('CsrfCookieName')
            ->willReturn('cookie-name');
        $cookieService->expects($this->once())
            ->method('DeleteCookie')
            ->with('cookie-name')
            ->willReturn($returnValue);

        $this->assertSame($returnValue, $cookieService->DeleteCsrfCookie());
    }

    #endregion DeleteCsrfCookie

    #region AppSpecificCookieName ----------------------------------------------

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

    #endregion AppSpecificCookieName

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
        return DataHelper::Cartesian(
            ['test_value', ''], // cookie value
            [false, true],      // is secure
            [false, true]       // return value
        );
    }

    #endregion Data Providers
}
