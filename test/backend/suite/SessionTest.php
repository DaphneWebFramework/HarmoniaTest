<?php declare(strict_types=1);
use \PHPUnit\Framework\TestCase;
use \PHPUnit\Framework\Attributes\CoversClass;
use \PHPUnit\Framework\Attributes\BackupGlobals;
use \PHPUnit\Framework\Attributes\DataProviderExternal;

use \Harmonia\Session;

use \Harmonia\Server;
use \Harmonia\Services\CookieService;
use \TestToolkit\AccessHelper;
use \TestToolkit\DataHelper;

#[CoversClass(Session::class)]
class SessionTest extends TestCase
{
    private ?Server $originalServer = null;
    private ?CookieService $originalCookieService = null;

    protected function setUp(): void
    {
        $this->originalServer = Server::ReplaceInstance(
            $this->createMock(Server::class));
        $this->originalCookieService = CookieService::ReplaceInstance(
            $this->createMock(CookieService::class));
    }

    protected function tearDown(): void
    {
        Server::ReplaceInstance($this->originalServer);
        CookieService::ReplaceInstance($this->originalCookieService);
    }

    private function systemUnderTest(): Session
    {
        return $this->getMockBuilder(Session::class)
            ->onlyMethods(['_ini_set', '_session_set_cookie_params',
                           '_session_status', '_session_name',
                           '_session_start', '_session_regenerate_id',
                           '_session_write_close', '_session_unset',
                           '_session_destroy'])
            ->disableOriginalConstructor()
            ->getMock();
    }

    #region __construct --------------------------------------------------------

    function testConstructWhenStatusIsDisabled()
    {
        $session = $this->systemUnderTest();

        $session->expects($this->once())
            ->method('_session_status')
            ->willReturn(\PHP_SESSION_DISABLED);

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Session support is disabled.');
        AccessHelper::CallConstructor($session);
    }

    function testConstructWhenStatusIsActive()
    {
        $session = $this->systemUnderTest();

        $session->expects($this->once())
            ->method('_session_status')
            ->willReturn(\PHP_SESSION_ACTIVE);

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Session is already active.');
        AccessHelper::CallConstructor($session);
    }

    function testConstructSetsIniOptions()
    {
        $session = $this->systemUnderTest();

        $session->expects($this->once())
            ->method('_session_status')
            ->willReturn(\PHP_SESSION_NONE);
        $session->expects($invokedCount = $this->exactly(5))
            ->method('_ini_set')
            ->willReturnCallback(function($option, $value) use($invokedCount) {
                switch ($invokedCount->numberOfInvocations()) {
                case 1:
                    $this->assertSame('session.use_strict_mode', $option);
                    $this->assertSame('1', $value);
                    break;
                case 2:
                    $this->assertSame('session.use_cookies', $option);
                    $this->assertSame('1', $value);
                    break;
                case 3:
                    $this->assertSame('session.use_only_cookies', $option);
                    $this->assertSame('1', $value);
                    break;
                case 4:
                    $this->assertSame('session.use_trans_sid', $option);
                    $this->assertSame('0', $value);
                    break;
                case 5:
                    $this->assertSame('session.cache_limiter', $option);
                    $this->assertSame('nocache', $value);
                    break;
                }
            });

        AccessHelper::CallConstructor($session);
    }

    #[DataProviderExternal(DataHelper::class, 'BooleanProvider')]
    function testConstructSetsCookieParamsWhenServerIsSecure($isSecure)
    {
        $session = $this->systemUnderTest();
        $server = Server::Instance();

        $session->expects($this->once())
            ->method('_session_status')
            ->willReturn(\PHP_SESSION_NONE);
        $server->expects($this->once())
            ->method('IsSecure')
            ->willReturn($isSecure);
        $session->expects($this->once())
            ->method('_session_set_cookie_params')
            ->with([
                'lifetime' => 0,
                'path'     => '/',
                'domain'   => '',
                'secure'   => $isSecure,
                'httponly' => true,
                'samesite' => 'Strict'
            ]);

        AccessHelper::CallConstructor($session);
    }

    function testConstructSetsSessionName()
    {
        $session = $this->systemUnderTest();
        $cookieService = CookieService::Instance();

        $session->expects($this->once())
            ->method('_session_status')
            ->willReturn(\PHP_SESSION_NONE);
        $cookieService->expects($this->once())
            ->method('AppSpecificCookieName')
            ->with('SID')
            ->willReturn('MYAPP_SID');
        $session->expects($this->once())
            ->method('_session_name')
            ->with('MYAPP_SID');

        AccessHelper::CallConstructor($session);
    }

    #endregion __construct

    #region Start --------------------------------------------------------------

    function testStartDoesNothingWhenStatusIsDisabled()
    {
        $session = $this->systemUnderTest();

        $session->expects($this->once())
            ->method('_session_status')
            ->willReturn(\PHP_SESSION_DISABLED);
        $session->expects($this->never())
            ->method('_session_start');

        $this->assertSame($session, $session->Start());
    }

    function testStartDoesNothingWhenStatusIsActive()
    {
        $session = $this->systemUnderTest();

        $session->expects($this->once())
            ->method('_session_status')
            ->willReturn(\PHP_SESSION_ACTIVE);
        $session->expects($this->never())
            ->method('_session_start');

        $this->assertSame($session, $session->Start());
    }

    function testStartWhenStatusIsNone()
    {
        $session = $this->systemUnderTest();

        $session->expects($this->once())
            ->method('_session_status')
            ->willReturn(\PHP_SESSION_NONE);
        $session->expects($this->once())
            ->method('_session_start');

        $this->assertSame($session, $session->Start());
    }

    #endregion Start

    #region RenewId ------------------------------------------------------------

    function testRenewIdDoesNothingWhenStatusIsDisabled()
    {
        $session = $this->systemUnderTest();

        $session->expects($this->once())
            ->method('_session_status')
            ->willReturn(\PHP_SESSION_DISABLED);
        $session->expects($this->never())
            ->method('_session_regenerate_id');

        $this->assertSame($session, $session->RenewId());
    }

    function testRenewIdDoesNothingWhenStatusIsNone()
    {
        $session = $this->systemUnderTest();

        $session->expects($this->once())
            ->method('_session_status')
            ->willReturn(\PHP_SESSION_NONE);
        $session->expects($this->never())
            ->method('_session_regenerate_id');

        $this->assertSame($session, $session->RenewId());
    }

    function testRenewIdWhenStatusIsActive()
    {
        $session = $this->systemUnderTest();

        $session->expects($this->once())
            ->method('_session_status')
            ->willReturn(\PHP_SESSION_ACTIVE);
        $session->expects($this->once())
            ->method('_session_regenerate_id');

        $this->assertSame($session, $session->RenewId());
    }

    #endregion RenewId

    #region Close --------------------------------------------------------------

    function testCloseDoesNothingWhenStatusIsDisabled()
    {
        $session = $this->systemUnderTest();

        $session->expects($this->once())
            ->method('_session_status')
            ->willReturn(\PHP_SESSION_DISABLED);
        $session->expects($this->never())
            ->method('_session_write_close');

        $this->assertSame($session, $session->Close());
    }

    function testCloseDoesNothingWhenStatusIsNone()
    {
        $session = $this->systemUnderTest();

        $session->expects($this->once())
            ->method('_session_status')
            ->willReturn(\PHP_SESSION_NONE);
        $session->expects($this->never())
            ->method('_session_write_close');

        $this->assertSame($session, $session->Close());
    }

    function testCloseWhenStatusIsActive()
    {
        $session = $this->systemUnderTest();

        $session->expects($this->once())
            ->method('_session_status')
            ->willReturn(\PHP_SESSION_ACTIVE);
        $session->expects($this->once())
            ->method('_session_write_close');

        $this->assertSame($session, $session->Close());
    }

    #endregion Close

    #region Set ----------------------------------------------------------------

    #[BackupGlobals(true)]
    function testSetDoesNothingWhenStatusIsDisabled()
    {
        $session = $this->systemUnderTest();

        $session->expects($this->once())
            ->method('_session_status')
            ->willReturn(\PHP_SESSION_DISABLED);

        $this->assertSame($session, $session->Set('key1', 'value1'));
        $this->assertFalse(isset($_SESSION));
    }

    #[BackupGlobals(true)]
    function testSetDoesNothingWhenStatusIsNone()
    {
        $session = $this->systemUnderTest();

        $session->expects($this->once())
            ->method('_session_status')
            ->willReturn(\PHP_SESSION_NONE);

        $this->assertSame($session, $session->Set('key1', 'value1'));
        $this->assertFalse(isset($_SESSION));
    }

    #[BackupGlobals(true)]
    function testSetWhenStatusIsActive()
    {
        $session = $this->systemUnderTest();

        $session->expects($this->once())
            ->method('_session_status')
            ->willReturn(\PHP_SESSION_ACTIVE);

        $this->assertSame($session, $session->Set('key1', 'value1'));
        $this->assertSame('value1', $_SESSION['key1']);
    }

    #endregion Set

    #region Get ----------------------------------------------------------------

    #[BackupGlobals(true)]
    function testGetReturnsDefaultValueWhenSessionSuperglobalIsMissing()
    {
        $session = $this->systemUnderTest();

        $session->expects($this->never())
            ->method('_session_status');

        unset($_SESSION);

        $this->assertSame('default1', $session->Get('key1', 'default1'));
    }

    #[BackupGlobals(true)]
    function testGetReturnsDefaultValueWhenKeyIsMissing()
    {
        $session = $this->systemUnderTest();

        $session->expects($this->never())
            ->method('_session_status');

        $_SESSION = [];

        $this->assertSame('default1', $session->Get('key1', 'default1'));
    }

    #[BackupGlobals(true)]
    function testGetReturnsValueWhenKeyExists(): void
    {
        $session = $this->systemUnderTest();

        $session->expects($this->never())
            ->method('_session_status');

        $_SESSION['key1'] = 'value1';

        $this->assertSame('value1', $session->Get('key1'));
    }

    #endregion Get

    #region Remove -------------------------------------------------------------

    #[BackupGlobals(true)]
    function testRemoveDoesNothingWhenStatusIsDisabled()
    {
        $session = $this->systemUnderTest();

        $session->expects($this->once())
            ->method('_session_status')
            ->willReturn(\PHP_SESSION_DISABLED);

        $_SESSION['key1'] = 'value1';
        $this->assertSame($session, $session->Remove('key1'));
        $this->assertSame('value1', $_SESSION['key1']);
    }

    #[BackupGlobals(true)]
    function testRemoveDoesNothingWhenStatusIsNone()
    {
        $session = $this->systemUnderTest();

        $session->expects($this->once())
            ->method('_session_status')
            ->willReturn(\PHP_SESSION_NONE);

        $_SESSION['key1'] = 'value1';
        $this->assertSame($session, $session->Remove('key1'));
        $this->assertSame('value1', $_SESSION['key1']);
    }

    #[BackupGlobals(true)]
    function testRemoveWhenStatusIsActiveButSuperglobalDoesNotExist()
    {
        $session = $this->systemUnderTest();

        $session->expects($this->once())
            ->method('_session_status')
            ->willReturn(\PHP_SESSION_ACTIVE);

        $this->assertSame($session, $session->Remove('key1'));
    }

    #[BackupGlobals(true)]
    function testRemoveWhenStatusIsActiveButKeyDoesNotExist()
    {
        $session = $this->systemUnderTest();

        $session->expects($this->once())
            ->method('_session_status')
            ->willReturn(\PHP_SESSION_ACTIVE);

        $_SESSION = [];
        $this->assertSame($session, $session->Remove('key1'));
        $this->assertArrayNotHasKey('key1', $_SESSION);
    }

    #[BackupGlobals(true)]
    function testRemoveWhenStatusIsActiveAndKeyExists()
    {
        $session = $this->systemUnderTest();

        $session->expects($this->once())
            ->method('_session_status')
            ->willReturn(\PHP_SESSION_ACTIVE);

        $_SESSION['key1'] = 'value1';
        $this->assertSame($session, $session->Remove('key1'));
        $this->assertArrayNotHasKey('key1', $_SESSION);
    }

    #endregion Remove

    #region Clear --------------------------------------------------------------

    function testClearDoesNothingWhenStatusIsDisabled()
    {
        $session = $this->systemUnderTest();

        $session->expects($this->once())
            ->method('_session_status')
            ->willReturn(\PHP_SESSION_DISABLED);
        $session->expects($this->never())
            ->method('_session_unset');

        $this->assertSame($session, $session->Clear());
    }

    function testClearDoesNothingWhenStatusIsNone()
    {
        $session = $this->systemUnderTest();

        $session->expects($this->once())
            ->method('_session_status')
            ->willReturn(\PHP_SESSION_NONE);
        $session->expects($this->never())
            ->method('_session_unset');

        $this->assertSame($session, $session->Clear());
    }

    function testClearWhenStatusIsActive()
    {
        $session = $this->systemUnderTest();

        $session->expects($this->once())
            ->method('_session_status')
            ->willReturn(\PHP_SESSION_ACTIVE);
        $session->expects($this->once())
            ->method('_session_unset');

        $this->assertSame($session, $session->Clear());
    }

    #endregion Clear

    #region Destroy ------------------------------------------------------------

    function testDestroyDoesNothingWhenStatusIsDisabled()
    {
        $session = $this->systemUnderTest();
        $cookieService = CookieService::Instance();

        $session->expects($this->once())
            ->method('_session_status')
            ->willReturn(\PHP_SESSION_DISABLED);
        $session->expects($this->never())
            ->method('_session_name');
        $cookieService->expects($this->never())
            ->method('DeleteCookie');
        $session->expects($this->never())
            ->method('_session_unset');
        $session->expects($this->never())
            ->method('_session_destroy');

        $this->assertSame($session, $session->Destroy());
    }

    function testDestroyDoesNothingWhenStatusIsNone()
    {
        $session = $this->systemUnderTest();
        $cookieService = CookieService::Instance();

        $session->expects($this->once())
            ->method('_session_status')
            ->willReturn(\PHP_SESSION_NONE);
        $session->expects($this->never())
            ->method('_session_name');
        $cookieService->expects($this->never())
            ->method('DeleteCookie');
        $session->expects($this->never())
            ->method('_session_unset');
        $session->expects($this->never())
            ->method('_session_destroy');

        $this->assertSame($session, $session->Destroy());
    }

    function testDestroyWhenStatusIsActiveButCookieCannotBeDeleted()
    {
        $session = $this->systemUnderTest();
        $cookieService = CookieService::Instance();

        $session->expects($this->once())
            ->method('_session_status')
            ->willReturn(\PHP_SESSION_ACTIVE);
        $session->expects($this->once())
            ->method('_session_name')
            ->willReturn('HARMONIA_SID');
        $cookieService->expects($this->once())
            ->method('DeleteCookie')
            ->with('HARMONIA_SID')
            ->willThrowException(new \RuntimeException('Failed to set or delete cookie.'));
        $session->expects($this->never())
            ->method('_session_unset');
        $session->expects($this->never())
            ->method('_session_destroy');

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Failed to set or delete cookie.');
        $this->assertSame($session, $session->Destroy());
    }

    function testDestroyWhenStatusIsActiveAndCookieIsDeleted()
    {
        $session = $this->systemUnderTest();
        $cookieService = CookieService::Instance();

        $session->expects($this->once())
            ->method('_session_status')
            ->willReturn(\PHP_SESSION_ACTIVE);
        $session->expects($this->once())
            ->method('_session_name')
            ->willReturn('HARMONIA_SID');
        $cookieService->expects($this->once())
            ->method('DeleteCookie')
            ->with('HARMONIA_SID');
        $session->expects($this->once())
            ->method('_session_unset');
        $session->expects($this->once())
            ->method('_session_destroy');

        $this->assertSame($session, $session->Destroy());
    }

    #endregion Destroy
}
