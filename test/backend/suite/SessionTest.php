<?php declare(strict_types=1);
use \PHPUnit\Framework\TestCase;
use \PHPUnit\Framework\Attributes\CoversClass;
use \PHPUnit\Framework\Attributes\TestWith;
use \PHPUnit\Framework\Attributes\BackupGlobals;

use \Harmonia\Session;

use \Harmonia\Server;
use \Harmonia\Services\CookieService;
use \TestToolkit\AccessHelper as AH;

#[CoversClass(Session::class)]
class SessionTest extends TestCase
{
    private ?Server $originalServer = null;
    private ?CookieService $originalCookieService = null;

    protected function setUp(): void
    {
        $this->originalServer =
            Server::ReplaceInstance($this->createMock(Server::class));
        $this->originalCookieService =
            CookieService::ReplaceInstance($this->createMock(CookieService::class));
    }

    protected function tearDown(): void
    {
        Server::ReplaceInstance($this->originalServer);
        CookieService::ReplaceInstance($this->originalCookieService);
    }

    private function systemUnderTest(string ...$mockedMethods): Session
    {
        return $this->getMockBuilder(Session::class)
            ->disableOriginalConstructor()
            ->onlyMethods($mockedMethods)
            ->getMock();
    }

    #region __construct --------------------------------------------------------

    function testConstructWhenStatusIsDisabled()
    {
        $sut = $this->systemUnderTest('_session_status');

        $sut->expects($this->once())
            ->method('_session_status')
            ->willReturn(\PHP_SESSION_DISABLED);

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Session support is disabled.');
        AH::CallConstructor($sut);
    }

    function testConstructWhenStatusIsActive()
    {
        $sut = $this->systemUnderTest('_session_status');

        $sut->expects($this->once())
            ->method('_session_status')
            ->willReturn(\PHP_SESSION_ACTIVE);

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Session is already active.');
        AH::CallConstructor($sut);
    }

    function testConstructSetsIniOptions()
    {
        $sut = $this->systemUnderTest('_session_status', '_ini_set',
            '_session_set_cookie_params', '_session_name');

        $sut->expects($this->once())
            ->method('_session_status')
            ->willReturn(\PHP_SESSION_NONE);
        $sut->expects($invokedCount = $this->exactly(5))
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

        AH::CallConstructor($sut);
    }

    #[TestWith([false])]
    #[TestWith([true])]
    function testConstructSetsCookieParamsWhenServerIsSecure($isSecure)
    {
        $sut = $this->systemUnderTest('_session_status', '_ini_set',
            '_session_set_cookie_params', '_session_name');
        $server = Server::Instance();

        $sut->expects($this->once())
            ->method('_session_status')
            ->willReturn(\PHP_SESSION_NONE);
        $server->expects($this->once())
            ->method('IsSecure')
            ->willReturn($isSecure);
        $sut->expects($this->once())
            ->method('_session_set_cookie_params')
            ->with([
                'lifetime' => 0,
                'path'     => '/',
                'domain'   => '',
                'secure'   => $isSecure,
                'httponly' => true,
                'samesite' => 'Lax'
            ]);

        AH::CallConstructor($sut);
    }

    function testConstructSetsSessionName()
    {
        $sut = $this->systemUnderTest('_session_status', '_ini_set',
            '_session_set_cookie_params', '_session_name');
        $cookieService = CookieService::Instance();

        $sut->expects($this->once())
            ->method('_session_status')
            ->willReturn(\PHP_SESSION_NONE);
        $cookieService->expects($this->once())
            ->method('AppSpecificCookieName')
            ->with('SID')
            ->willReturn('MYAPP_SID');
        $sut->expects($this->once())
            ->method('_session_name')
            ->with('MYAPP_SID');

        AH::CallConstructor($sut);
    }

    #endregion __construct

    #region Start --------------------------------------------------------------

    #[TestWith([\PHP_SESSION_DISABLED])]
    #[TestWith([\PHP_SESSION_ACTIVE])]
    function testStartWhenStatusIsNotNone($status)
    {
        $sut = $this->systemUnderTest('_session_status', '_session_start');

        $sut->expects($this->once())
            ->method('_session_status')
            ->willReturn($status);
        $sut->expects($this->never())
            ->method('_session_start');

        $this->assertSame($sut, $sut->Start());
    }

    function testStartWhenStatusIsNone()
    {
        $sut = $this->systemUnderTest('_session_status', '_session_start');

        $sut->expects($this->once())
            ->method('_session_status')
            ->willReturn(\PHP_SESSION_NONE);
        $sut->expects($this->once())
            ->method('_session_start');

        $this->assertSame($sut, $sut->Start());
    }

    #endregion Start

    #region RenewId ------------------------------------------------------------

    #[TestWith([\PHP_SESSION_DISABLED])]
    #[TestWith([\PHP_SESSION_NONE])]
    function testRenewIdWhenStatusIsNotActive($status)
    {
        $sut = $this->systemUnderTest('_session_status', '_session_regenerate_id');

        $sut->expects($this->once())
            ->method('_session_status')
            ->willReturn($status);
        $sut->expects($this->never())
            ->method('_session_regenerate_id');

        $this->assertSame($sut, $sut->RenewId());
    }

    function testRenewIdWhenStatusIsActive()
    {
        $sut = $this->systemUnderTest('_session_status', '_session_regenerate_id');

        $sut->expects($this->once())
            ->method('_session_status')
            ->willReturn(\PHP_SESSION_ACTIVE);
        $sut->expects($this->once())
            ->method('_session_regenerate_id');

        $this->assertSame($sut, $sut->RenewId());
    }

    #endregion RenewId

    #region Close --------------------------------------------------------------

    #[TestWith([\PHP_SESSION_DISABLED])]
    #[TestWith([\PHP_SESSION_NONE])]
    function testCloseWhenStatusIsNotActive($status)
    {
        $sut = $this->systemUnderTest('_session_status', '_session_write_close');

        $sut->expects($this->once())
            ->method('_session_status')
            ->willReturn($status);
        $sut->expects($this->never())
            ->method('_session_write_close');

        $this->assertSame($sut, $sut->Close());
    }

    function testCloseWhenStatusIsActive()
    {
        $sut = $this->systemUnderTest('_session_status', '_session_write_close');

        $sut->expects($this->once())
            ->method('_session_status')
            ->willReturn(\PHP_SESSION_ACTIVE);
        $sut->expects($this->once())
            ->method('_session_write_close');

        $this->assertSame($sut, $sut->Close());
    }

    #endregion Close

    #region Has ----------------------------------------------------------------

    #[BackupGlobals(true)]
    function testHasReturnsFalseWhenSessionSuperglobalIsMissing()
    {
        $sut = $this->systemUnderTest();

        unset($_SESSION);

        $this->assertFalse($sut->Has('key1'));
    }

    #[BackupGlobals(true)]
    function testHasReturnsFalseWhenKeyIsMissing()
    {
        $sut = $this->systemUnderTest();

        $_SESSION = [];

        $this->assertFalse($sut->Has('key1'));
    }

    #[BackupGlobals(true)]
    function testHasReturnsTrueWhenKeyExists(): void
    {
        $sut = $this->systemUnderTest();

        $_SESSION['key1'] = 'value1';

        $this->assertTrue($sut->Has('key1'));
    }

    #endregion Has

    #region Get ----------------------------------------------------------------

    #[BackupGlobals(false)]
    function testGetReturnsNullIfHasReturnsFalse()
    {
        $sut = $this->systemUnderTest('Has');

        $sut->expects($this->once())
            ->method('Has')
            ->with('key1')
            ->willReturn(false);

        $this->assertNull($sut->Get('key1'));
    }

    #[BackupGlobals(false)]
    function testGetReturnsDefaultValueIfHasReturnsFalse()
    {
        $sut = $this->systemUnderTest('Has');

        $sut->expects($this->once())
            ->method('Has')
            ->with('key1')
            ->willReturn(false);

        $this->assertSame('default1', $sut->Get('key1', 'default1'));
    }

    #[BackupGlobals(true)]
    function testGetReturnsValueIfHasReturnsTrueAndKeyExists()
    {
        $sut = $this->systemUnderTest('Has');

        $sut->expects($this->once())
            ->method('Has')
            ->with('key1')
            ->willReturn(true);

        $_SESSION['key1'] = 'value1';

        $this->assertSame('value1', $sut->Get('key1'));
    }

    #endregion Get

    #region Set ----------------------------------------------------------------

    #[BackupGlobals(true)]
    #[TestWith([\PHP_SESSION_DISABLED])]
    #[TestWith([\PHP_SESSION_NONE])]
    function testSetWhenStatusIsNotActive($status)
    {
        $sut = $this->systemUnderTest('_session_status');

        $sut->expects($this->once())
            ->method('_session_status')
            ->willReturn($status);

        $this->assertSame($sut, $sut->Set('key1', 'value1'));
        $this->assertFalse(isset($_SESSION));
    }

    #[BackupGlobals(true)]
    function testSetWhenStatusIsActive()
    {
        $sut = $this->systemUnderTest('_session_status');

        $sut->expects($this->once())
            ->method('_session_status')
            ->willReturn(\PHP_SESSION_ACTIVE);

        $this->assertSame($sut, $sut->Set('key1', 'value1'));
        $this->assertSame('value1', $_SESSION['key1']);
    }

    #endregion Set

    #region Remove -------------------------------------------------------------

    #[BackupGlobals(true)]
    #[TestWith([\PHP_SESSION_DISABLED])]
    #[TestWith([\PHP_SESSION_NONE])]
    function testRemoveWhenStatusIsNotActive($status)
    {
        $sut = $this->systemUnderTest('_session_status');

        $sut->expects($this->once())
            ->method('_session_status')
            ->willReturn($status);

        $_SESSION['key1'] = 'value1';
        $this->assertSame($sut, $sut->Remove('key1'));
        $this->assertSame('value1', $_SESSION['key1']);
    }

    #[BackupGlobals(true)]
    function testRemoveWhenStatusIsActiveButSuperglobalDoesNotExist()
    {
        $sut = $this->systemUnderTest('_session_status');

        $sut->expects($this->once())
            ->method('_session_status')
            ->willReturn(\PHP_SESSION_ACTIVE);

        $this->assertSame($sut, $sut->Remove('key1'));
    }

    #[BackupGlobals(true)]
    function testRemoveWhenStatusIsActiveButKeyDoesNotExist()
    {
        $sut = $this->systemUnderTest('_session_status');

        $sut->expects($this->once())
            ->method('_session_status')
            ->willReturn(\PHP_SESSION_ACTIVE);

        $_SESSION = [];
        $this->assertSame($sut, $sut->Remove('key1'));
        $this->assertArrayNotHasKey('key1', $_SESSION);
    }

    #[BackupGlobals(true)]
    function testRemoveWhenStatusIsActiveAndKeyExists()
    {
        $sut = $this->systemUnderTest('_session_status');

        $sut->expects($this->once())
            ->method('_session_status')
            ->willReturn(\PHP_SESSION_ACTIVE);

        $_SESSION['key1'] = 'value1';
        $this->assertSame($sut, $sut->Remove('key1'));
        $this->assertArrayNotHasKey('key1', $_SESSION);
    }

    #endregion Remove

    #region Clear --------------------------------------------------------------

    #[TestWith([\PHP_SESSION_DISABLED])]
    #[TestWith([\PHP_SESSION_NONE])]
    function testClearWhenStatusIsNotActive($status)
    {
        $sut = $this->systemUnderTest('_session_status', '_session_unset');

        $sut->expects($this->once())
            ->method('_session_status')
            ->willReturn($status);
        $sut->expects($this->never())
            ->method('_session_unset');

        $this->assertSame($sut, $sut->Clear());
    }

    function testClearWhenStatusIsActive()
    {
        $sut = $this->systemUnderTest('_session_status', '_session_unset');

        $sut->expects($this->once())
            ->method('_session_status')
            ->willReturn(\PHP_SESSION_ACTIVE);
        $sut->expects($this->once())
            ->method('_session_unset');

        $this->assertSame($sut, $sut->Clear());
    }

    #endregion Clear

    #region Destroy ------------------------------------------------------------

    #[TestWith([\PHP_SESSION_DISABLED])]
    #[TestWith([\PHP_SESSION_NONE])]
    function testDestroyWhenStatusIsNotActive($status)
    {
        $sut = $this->systemUnderTest('_session_status', '_session_name',
            '_session_unset', '_session_destroy');
        $cookieService = CookieService::Instance();

        $sut->expects($this->once())
            ->method('_session_status')
            ->willReturn($status);
        $sut->expects($this->never())
            ->method('_session_name');
        $cookieService->expects($this->never())
            ->method('DeleteCookie');
        $sut->expects($this->never())
            ->method('_session_unset');
        $sut->expects($this->never())
            ->method('_session_destroy');

        $this->assertSame($sut, $sut->Destroy());
    }

    function testDestroyWhenStatusIsActiveButCookieCannotBeDeleted()
    {
        $sut = $this->systemUnderTest('_session_status', '_session_name',
            '_session_unset', '_session_destroy');
        $cookieService = CookieService::Instance();

        $sut->expects($this->once())
            ->method('_session_status')
            ->willReturn(\PHP_SESSION_ACTIVE);
        $sut->expects($this->once())
            ->method('_session_name')
            ->willReturn('HARMONIA_SID');
        $cookieService->expects($this->once())
            ->method('DeleteCookie')
            ->with('HARMONIA_SID')
            ->willThrowException(new \RuntimeException('Expected message.'));
        $sut->expects($this->never())
            ->method('_session_unset');
        $sut->expects($this->never())
            ->method('_session_destroy');

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Expected message.');
        $this->assertSame($sut, $sut->Destroy());
    }

    function testDestroyWhenStatusIsActiveAndCookieIsDeleted()
    {
        $sut = $this->systemUnderTest('_session_status', '_session_name',
            '_session_unset', '_session_destroy');
        $cookieService = CookieService::Instance();

        $sut->expects($this->once())
            ->method('_session_status')
            ->willReturn(\PHP_SESSION_ACTIVE);
        $sut->expects($this->once())
            ->method('_session_name')
            ->willReturn('HARMONIA_SID');
        $cookieService->expects($this->once())
            ->method('DeleteCookie')
            ->with('HARMONIA_SID');
        $sut->expects($this->once())
            ->method('_session_unset');
        $sut->expects($this->once())
            ->method('_session_destroy');

        $this->assertSame($sut, $sut->Destroy());
    }

    #endregion Destroy
}
