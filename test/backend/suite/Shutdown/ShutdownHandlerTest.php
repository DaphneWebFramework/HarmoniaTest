<?php declare(strict_types=1);
use \PHPUnit\Framework\TestCase;
use \PHPUnit\Framework\Attributes\CoversClass;

use \Harmonia\Shutdown\ShutdownHandler;

use \Harmonia\Shutdown\IShutdownListener;
use \TestToolkit\AccessHelper;

#[CoversClass(ShutdownHandler::class)]
class ShutdownHandlerTest extends TestCase
{
    private ?ShutdownHandler $originalShutdownHandler = null;
    private static int $phpUnitMajorVersion = 0;

    public static function setUpBeforeClass(): void
    {
        $phpUnitVersion = \PHPUnit\Runner\Version::id(); // e.g., "11.5.6"
        self::$phpUnitMajorVersion = (int)\explode('.', $phpUnitVersion)[0];
    }

    protected function setUp(): void
    {
        $this->originalShutdownHandler =
            ShutdownHandler::ReplaceInstance($this->createShutdownHandlerMock());
    }

    protected function tearDown(): void
    {
        ShutdownHandler::ReplaceInstance($this->originalShutdownHandler);
    }

    private function createShutdownHandlerMock(): ShutdownHandler
    {
        $mockBuilder = $this->getMockBuilder(ShutdownHandler::class)
            ->onlyMethods(['_ini_set', '_register_shutdown_function',
                           '_error_get_last']);
        if (self::$phpUnitMajorVersion < 11) {
            $mockBuilder->disableOriginalConstructor();
        }
        $shutdownHandler = $mockBuilder->getMock();
        if (self::$phpUnitMajorVersion < 11) {
            AccessHelper::CallNonPublicConstructor($shutdownHandler);
        }
        return $shutdownHandler;
    }

    #region AddListener --------------------------------------------------------

    function testAddListener()
    {
        $shutdownHandler = ShutdownHandler::Instance();
        $listener = $this->createMock(IShutdownListener::class);
        $shutdownHandler->AddListener($listener);
        $listeners = AccessHelper::GetNonPublicMockProperty(
            ShutdownHandler::class, $shutdownHandler, 'listeners');
        $this->assertCount(1, $listeners);
        $this->assertSame($listener, $listeners[0]);
    }

    #endregion AddListener

    #region OnShutdown ---------------------------------------------------------

    function testOnShutdownWithNoError()
    {
        $shutdownHandler = ShutdownHandler::Instance();
        $listener = $this->createMock(IShutdownListener::class);
        $shutdownHandler->AddListener($listener);
        $shutdownHandler->expects($this->once())
            ->method('_error_get_last')
            ->willReturn(null);
        $listener->expects($this->once())
            ->method('OnShutdown')
            ->with(null);
        $shutdownHandler->OnShutdown();
    }

    function testOnShutdownWithErrorHavingUnknownErrorType()
    {
        $shutdownHandler = ShutdownHandler::Instance();
        $listener = $this->createMock(IShutdownListener::class);
        $shutdownHandler->AddListener($listener);
        $shutdownHandler->expects($this->once())
            ->method('_error_get_last')
            ->willReturn(['type' => 999, 'message' => 'Something went wrong',
                          'file' => 'file.php', 'line' => 123]);
        $listener->expects($this->once())
            ->method('OnShutdown')
            ->with("Something went wrong in 'file.php' on line 123.");
        $shutdownHandler->OnShutdown();
    }

    function testOnShutdownWithErrorHavingUnknownFile()
    {
        $shutdownHandler = ShutdownHandler::Instance();
        $listener = $this->createMock(IShutdownListener::class);
        $shutdownHandler->AddListener($listener);
        $shutdownHandler->expects($this->once())
            ->method('_error_get_last')
            ->willReturn(['type' => 8, 'message' => 'Something went wrong',
                          'file' => 'Unknown', 'line' => 123]);
        $listener->expects($this->once())
            ->method('OnShutdown')
            ->with("E_NOTICE: Something went wrong on line 123.");
        $shutdownHandler->OnShutdown();
    }

    function testOnShutdownWithErrorHavingZeroLineNumber()
    {
        $shutdownHandler = ShutdownHandler::Instance();
        $listener = $this->createMock(IShutdownListener::class);
        $shutdownHandler->AddListener($listener);
        $shutdownHandler->expects($this->once())
            ->method('_error_get_last')
            ->willReturn(['type' => 8, 'message' => 'Something went wrong',
                          'file' => 'file.php', 'line' => 0]);
        $listener->expects($this->once())
            ->method('OnShutdown')
            ->with("E_NOTICE: Something went wrong in 'file.php'.");
        $shutdownHandler->OnShutdown();
    }

    function testOnShutdownWithErrorHavingAllDetails()
    {
        $shutdownHandler = ShutdownHandler::Instance();
        $listener = $this->createMock(IShutdownListener::class);
        $shutdownHandler->AddListener($listener);
        $shutdownHandler->expects($this->once())
            ->method('_error_get_last')
            ->willReturn(['type' => 8, 'message' => 'Something went wrong',
                          'file' => 'file.php', 'line' => 123]);
        $listener->expects($this->once())
            ->method('OnShutdown')
            ->with("E_NOTICE: Something went wrong in 'file.php' on line 123.");
        $shutdownHandler->OnShutdown();
    }

    #endregion OnShutdown
}
