<?php declare(strict_types=1);
use \PHPUnit\Framework\TestCase;
use \PHPUnit\Framework\Attributes\CoversClass;

use \Harmonia\Shutdown\ShutdownHandler;

use \Harmonia\Core\CSequentialArray;
use \Harmonia\Shutdown\IShutdownListener;
use \TestToolkit\AccessHelper;

#[CoversClass(ShutdownHandler::class)]
class ShutdownHandlerTest extends TestCase
{
    private ?ShutdownHandler $originalShutdownHandler = null;

    protected function setUp(): void
    {
        $this->originalShutdownHandler = ShutdownHandler::ReplaceInstance(
            $this->getMockBuilder(ShutdownHandler::class)
                ->onlyMethods(['_ini_set', '_register_shutdown_function',
                               '_error_get_last'])
                ->disableOriginalConstructor()
                ->getMock()
        );
    }

    protected function tearDown(): void
    {
        ShutdownHandler::ReplaceInstance($this->originalShutdownHandler);
    }

    #region __construct --------------------------------------------------------

    function testConstruct()
    {
        $shutdownHandler = ShutdownHandler::Instance();
        $shutdownHandler->expects($this->once())
            ->method('_ini_set')
            ->with('display_errors', 0);
        $shutdownHandler->expects($this->once())
            ->method('_register_shutdown_function')
            ->with([$shutdownHandler, 'OnShutdown']);

        AccessHelper::CallNonPublicConstructor($shutdownHandler);

        $listeners = AccessHelper::GetNonPublicMockProperty(
            ShutdownHandler::class,
            $shutdownHandler,
            'listeners'
        );
        $this->assertInstanceOf(CSequentialArray::class, $listeners);
        $this->assertCount(0, $listeners);
    }

    #endregion __construct

    #region AddListener --------------------------------------------------------

    function testAddListener()
    {
        $listener = $this->createStub(IShutdownListener::class);

        $shutdownHandler = ShutdownHandler::Instance();
        AccessHelper::SetNonPublicMockProperty(
            ShutdownHandler::class,
            $shutdownHandler,
            'listeners',
            new CSequentialArray()
        );
        $shutdownHandler->AddListener($listener);

        $listeners = AccessHelper::GetNonPublicMockProperty(
            ShutdownHandler::class,
            $shutdownHandler,
            'listeners'
        );
        $this->assertCount(1, $listeners);
        $this->assertSame($listener, $listeners[0]);
    }

    #endregion AddListener

    #region OnShutdown ---------------------------------------------------------

    function testOnShutdownWithNoError()
    {
        $listener = $this->createMock(IShutdownListener::class);
        $listener->expects($this->once())
            ->method('OnShutdown')
            ->with(null);

        $shutdownHandler = ShutdownHandler::Instance();
        AccessHelper::SetNonPublicMockProperty(
            ShutdownHandler::class,
            $shutdownHandler,
            'listeners',
            new CSequentialArray()
        );
        $shutdownHandler->AddListener($listener);
        $shutdownHandler->expects($this->once())
            ->method('_error_get_last')
            ->willReturn(null);

        $shutdownHandler->OnShutdown();
    }

    function testOnShutdownWithErrorHavingUnknownErrorType()
    {
        $listener = $this->createMock(IShutdownListener::class);
        $listener->expects($this->once())
            ->method('OnShutdown')
            ->with("Something went wrong in 'file.php' on line 123.");

        $shutdownHandler = ShutdownHandler::Instance();
        AccessHelper::SetNonPublicMockProperty(
            ShutdownHandler::class,
            $shutdownHandler,
            'listeners',
            new CSequentialArray()
        );
        $shutdownHandler->AddListener($listener);
        $shutdownHandler->expects($this->once())
            ->method('_error_get_last')
            ->willReturn(['type' => 999, 'message' => 'Something went wrong',
                          'file' => 'file.php', 'line' => 123]);

        $shutdownHandler->OnShutdown();
    }

    function testOnShutdownWithErrorHavingUnknownFile()
    {
        $listener = $this->createMock(IShutdownListener::class);
        $listener->expects($this->once())
            ->method('OnShutdown')
            ->with("E_NOTICE: Something went wrong on line 123.");

        $shutdownHandler = ShutdownHandler::Instance();
        AccessHelper::SetNonPublicMockProperty(
            ShutdownHandler::class,
            $shutdownHandler,
            'listeners',
            new CSequentialArray()
        );
        $shutdownHandler->AddListener($listener);
        $shutdownHandler->expects($this->once())
            ->method('_error_get_last')
            ->willReturn(['type' => 8, 'message' => 'Something went wrong',
                          'file' => 'Unknown', 'line' => 123]);

        $shutdownHandler->OnShutdown();
    }

    function testOnShutdownWithErrorHavingZeroLineNumber()
    {
        $listener = $this->createMock(IShutdownListener::class);
        $listener->expects($this->once())
            ->method('OnShutdown')
            ->with("E_NOTICE: Something went wrong in 'file.php'.");

        $shutdownHandler = ShutdownHandler::Instance();
        AccessHelper::SetNonPublicMockProperty(
            ShutdownHandler::class,
            $shutdownHandler,
            'listeners',
            new CSequentialArray()
        );
        $shutdownHandler->AddListener($listener);
        $shutdownHandler->expects($this->once())
            ->method('_error_get_last')
            ->willReturn(['type' => 8, 'message' => 'Something went wrong',
                          'file' => 'file.php', 'line' => 0]);

        $shutdownHandler->OnShutdown();
    }

    function testOnShutdownWithErrorHavingAllDetails()
    {
        $listener = $this->createMock(IShutdownListener::class);
        $listener->expects($this->once())
            ->method('OnShutdown')
            ->with("E_NOTICE: Something went wrong in 'file.php' on line 123.");

        $shutdownHandler = ShutdownHandler::Instance();
        AccessHelper::SetNonPublicMockProperty(
            ShutdownHandler::class,
            $shutdownHandler,
            'listeners',
            new CSequentialArray()
        );
        $shutdownHandler->AddListener($listener);
        $shutdownHandler->expects($this->once())
            ->method('_error_get_last')
            ->willReturn(['type' => 8, 'message' => 'Something went wrong',
                          'file' => 'file.php', 'line' => 123]);

        $shutdownHandler->OnShutdown();
    }

    #endregion OnShutdown
}
