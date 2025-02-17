<?php declare(strict_types=1);
use \PHPUnit\Framework\TestCase;
use \PHPUnit\Framework\Attributes\CoversClass;

use \Harmonia\Database\Database;

use \Harmonia\Config;
use \Harmonia\Database\Connection;
use \Harmonia\Database\Proxies\MySQLiHandle;
use \Harmonia\Database\Proxies\MySQLiResult;
use \Harmonia\Database\Queries\Query;
use \TestToolkit\AccessHelper;

#[CoversClass(Database::class)]
class DatabaseTest extends TestCase
{
    private ?Database $originalDatabase = null;
    private ?Config $originalConfig = null;

    protected function setUp(): void
    {
        $this->originalDatabase = Database::ReplaceInstance(
            $this->createDatabasePartialMock());
        $this->originalConfig = Config::ReplaceInstance(
            $this->createConfigMock());
    }

    protected function tearDown(): void
    {
        Database::ReplaceInstance($this->originalDatabase);
        Config::ReplaceInstance($this->originalConfig);
    }

    private function createDatabasePartialMock(): Database
    {
        return $this->getMockBuilder(Database::class)
            ->onlyMethods(['connect'])
            ->disableOriginalConstructor()
            ->getMock();
    }

    private function createConfigMock(): Config
    {
        $config = $this->createMock(Config::class);
        $config->expects($this->any())
            ->method('Option')
            ->willReturnMap([
                ['DatabaseHostname', 'localhost'],
                ['DatabaseUsername', 'root'],
                ['DatabasePassword', 'pass123'],
                ['DatabaseCharset', 'utf8mb4'],
                ['DatabaseName', 'test_db']
            ]);
        return $config;
    }

    private function createConnectionMock(): Connection
    {
        $connection = $this->createMock(Connection::class);
        // Since Connection's `__construct()` wasn't called during mock creation,
        // its `$this->handle` will be null when `__destruct()` is invoked. This
        // fix prevents calling `close()` on a null handle.
        AccessHelper::SetNonPublicMockProperty(
            Connection::class,
            $connection,
            'handle',
            $this->createMock(MySQLiHandle::class)
        );
        return $connection;
    }

    #region Execute ------------------------------------------------------------

    function testExecuteReturnsNullWhenConnectionFails()
    {
        $database = Database::Instance();
        $database->expects($this->once())
            ->method('connect')
            ->with('localhost', 'root', 'pass123', 'utf8mb4')
            ->willThrowException(new \RuntimeException('Access denied', 1045));

        $query = $this->createStub(Query::class);

        $this->assertNull($database->Execute($query));
    }

    function testExecuteReturnsNullWhenDatabaseSelectionFails()
    {
        $connection = $this->createConnectionMock();
        $connection->expects($this->once())
            ->method('SelectDatabase')
            ->with('test_db')
            ->willThrowException(new \RuntimeException('Unknown database', 1049));

        $database = Database::Instance();
        $database->expects($this->once())
            ->method('connect')
            ->with('localhost', 'root', 'pass123', 'utf8mb4')
            ->willReturn($connection);

        $query = $this->createStub(Query::class);

        $this->assertNull($database->Execute($query));
    }

    function testExecuteReturnsNullWhenConnectionExecuteFails()
    {
        $query = $this->createStub(Query::class);

        $connection = $this->createConnectionMock();
        $connection->expects($this->once())
            ->method('Execute')
            ->with($query)
            ->willThrowException(new \RuntimeException('Syntax error', 1064));

        $database = Database::Instance();
        $database->expects($this->once())
            ->method('connect')
            ->willReturn($connection);

        $this->assertNull($database->Execute($query));
    }

    function testExecuteReturnsNullWhenConnectionExecuteSucceedWithoutResultSet()
    {
        $query = $this->createStub(Query::class);

        $connection = $this->createConnectionMock();
        $connection->expects($this->once())
            ->method('Execute')
            ->with($query)
            ->willReturn(null);

        $database = Database::Instance();
        $database->expects($this->once())
            ->method('connect')
            ->willReturn($connection);

        $this->assertNull($database->Execute($query));
    }

    function testExecuteReturnsResultWhenConnectionExecuteSucceedWithResultSet()
    {
        $query = $this->createStub(Query::class);

        $connection = $this->createConnectionMock();
        $connection->expects($this->once())
            ->method('Execute')
            ->with($query)
            ->willReturn($this->createMock(MySQLiResult::class));

        $database = Database::Instance();
        $database->expects($this->once())
            ->method('connect')
            ->willReturn($connection);

        $result = $database->Execute($query);
        $this->assertInstanceOf(MySQLiResult::class, $result);
    }

    #endregion Execute
}
