<?php declare(strict_types=1);
use \PHPUnit\Framework\TestCase;
use \PHPUnit\Framework\Attributes\CoversClass;
use \PHPUnit\Framework\Attributes\DataProvider;

use \Harmonia\Systems\DatabaseSystem\Database;

use \Harmonia\Config;
use \Harmonia\Logger;
use \Harmonia\Systems\DatabaseSystem\Connection;
use \Harmonia\Systems\DatabaseSystem\Proxies\MySQLiHandle;
use \Harmonia\Systems\DatabaseSystem\Proxies\MySQLiResult;
use \Harmonia\Systems\DatabaseSystem\Queries\Query;
use \Harmonia\Systems\DatabaseSystem\ResultSet;
use \TestToolkit\AccessHelper;

#[CoversClass(Database::class)]
class DatabaseTest extends TestCase
{
    private ?Config $originalConfig = null;
    private ?Logger $originalLogger = null;

    protected function setUp(): void
    {
        $this->originalConfig =
            Config::ReplaceInstance($this->createConfig());
        $this->originalLogger =
            Logger::ReplaceInstance($this->createStub(Logger::class));
    }

    protected function tearDown(): void
    {
        Config::ReplaceInstance($this->originalConfig);
        Logger::ReplaceInstance($this->originalLogger);
    }

    private function systemUnderTest(string ...$mockedMethods): Database
    {
        return $this->getMockBuilder(Database::class)
            ->disableOriginalConstructor()
            ->onlyMethods($mockedMethods)
            ->getMock();
    }

    private function createConfig(
        string $host = 'localhost',
        string $username = 'root',
        string $password = 'pass1234',
        string $name = 'test_db',
        ?string $charset = 'utf8mb4'
    ): Config
    {
        $mock = $this->createMock(Config::class);
        $mock->expects($this->any())
            ->method('OptionOrDefault')
            ->willReturnMap([
                ['DatabaseHost', '', $host],
                ['DatabaseUsername', '', $username],
                ['DatabasePassword', '', $password],
                ['DatabaseName', '', $name]
            ]);
        $mock->expects($this->any())
            ->method('Option')
            ->with('DatabaseCharset')
            ->willReturn($charset);
        return $mock;
    }

    #region Execute ------------------------------------------------------------

    function testExecuteWhenConnectionReturnsNull()
    {
        $sut = $this->systemUnderTest('connection');
        $query = $this->createStub(Query::class);

        $sut->expects($this->once())
            ->method('connection')
            ->willReturn(null);

        $this->assertNull($sut->Execute($query));
    }

    function testExecuteWhenConnectionExecuteThrows()
    {
        $sut = $this->systemUnderTest('connection');
        $connection = $this->createMock(Connection::class);
        $query = $this->createStub(Query::class);

        $sut->expects($this->once())
            ->method('connection')
            ->willReturn($connection);
        $connection->expects($this->once())
            ->method('Execute')
            ->with($query)
            ->willThrowException(new \RuntimeException());

        $this->assertNull($sut->Execute($query));
    }

    #[DataProvider('nullOrMySQLiResultProvider')]
    function testExecuteWhenSucceeds($result)
    {
        $sut = $this->systemUnderTest('connection');
        $connection = $this->createMock(Connection::class);
        $query = $this->createStub(Query::class);

        $sut->expects($this->once())
            ->method('connection')
            ->willReturn($connection);
        $connection->expects($this->once())
            ->method('Execute')
            ->with($query)
            ->willReturn($result);

        $this->assertInstanceOf(ResultSet::class, $sut->Execute($query));
    }

    #endregion Execute

    #region LastInsertId -------------------------------------------------------

    function testLastInsertIdWhenConnectionReturnsNull()
    {
        $sut = $this->systemUnderTest('connection');

        $sut->expects($this->once())
            ->method('connection')
            ->willReturn(null);

        $this->assertSame(0, $sut->LastInsertId());
    }

    function testLastInsertIdWhenSucceeds()
    {
        $sut = $this->systemUnderTest('connection');
        $connection = $this->createMock(Connection::class);

        $sut->expects($this->once())
            ->method('connection')
            ->willReturn($connection);
        $connection->expects($this->once())
            ->method('LastInsertId')
            ->willReturn(123);

        $this->assertSame(123, $sut->LastInsertId());
    }

    #endregion LastInsertId

    #region LastAffectedRowCount -----------------------------------------------

    function testLastAffectedRowCountWhenConnectionReturnsNull()
    {
        $sut = $this->systemUnderTest('connection');

        $sut->expects($this->once())
            ->method('connection')
            ->willReturn(null);

        $this->assertSame(-1, $sut->LastAffectedRowCount());
    }

    function testLastAffectedRowCountWhenSucceeds()
    {
        $sut = $this->systemUnderTest('connection');
        $connection = $this->createMock(Connection::class);

        $sut->expects($this->once())
            ->method('connection')
            ->willReturn($connection);
        $connection->expects($this->once())
            ->method('LastAffectedRowCount')
            ->willReturn(3);

        $this->assertSame(3, $sut->LastAffectedRowCount());
    }

    #endregion LastAffectedRowCount

    #region WithTransaction ----------------------------------------------------

    function testWithTransactionWhenConnectionReturnsNull()
    {
        $sut = $this->systemUnderTest('connection');
        $callback = function() {
            $this->fail('Callback should not be executed');
        };

        $sut->expects($this->once())
            ->method('connection')
            ->willReturn(null);

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage("No database connection available.");
        $sut->WithTransaction($callback);
    }

    function testWithTransactionWhenBeginTransactionThrows()
    {
        $sut = $this->systemUnderTest('connection');
        $callback = function() {
            $this->fail('Callback should not be executed');
        };
        $connection = $this->createMock(Connection::class);

        $sut->expects($this->once())
            ->method('connection')
            ->willReturn($connection);
        $connection->expects($this->once())
            ->method('BeginTransaction')
            ->willThrowException(new \RuntimeException("Failed to begin transaction."));
        $connection->expects($this->never())
            ->method('CommitTransaction');
        $connection->expects($this->once())
            ->method('RollbackTransaction');

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage("Failed to begin transaction.");
        $sut->WithTransaction($callback);
    }

    function testWithTransactionWhenCallbackThrows()
    {
        $sut = $this->systemUnderTest('connection');
        $callback = function() {
            throw new \RuntimeException("Failed to execute callback.");
        };
        $connection = $this->createMock(Connection::class);

        $sut->expects($this->once())
            ->method('connection')
            ->willReturn($connection);
        $connection->expects($this->once())
            ->method('BeginTransaction');
        $connection->expects($this->never())
            ->method('CommitTransaction');
        $connection->expects($this->once())
            ->method('RollbackTransaction');

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage("Failed to execute callback.");
        $sut->WithTransaction($callback);
    }

    function testWithTransactionWhenCommitTransactionThrows()
    {
        $sut = $this->systemUnderTest('connection');
        $callback = function() {};
        $connection = $this->createMock(Connection::class);

        $sut->expects($this->once())
            ->method('connection')
            ->willReturn($connection);
        $connection->expects($this->once())
            ->method('BeginTransaction');
        $connection->expects($this->once())
            ->method('CommitTransaction')
            ->willThrowException(new \RuntimeException("Failed to commit transaction."));
        $connection->expects($this->once())
            ->method('RollbackTransaction');

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage("Failed to commit transaction.");
        $sut->WithTransaction($callback);
    }

    function testWithTransactionWhenRollbackThrows()
    {
        $sut = $this->systemUnderTest('connection');
        $callback = function() {
            throw new \RuntimeException("Failed to execute callback.");
        };
        $connection = $this->createMock(Connection::class);

        $sut->expects($this->once())
            ->method('connection')
            ->willReturn($connection);
        $connection->expects($this->once())
            ->method('BeginTransaction');
        $connection->expects($this->never())
            ->method('CommitTransaction');
        $connection->expects($this->once())
            ->method('RollbackTransaction')
            ->willThrowException(new \RuntimeException("Failed to rollback transaction."));

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage("Failed to rollback transaction.");
        $sut->WithTransaction($callback);
    }

    function testWithTransactionWhenSucceeds()
    {
        $sut = $this->systemUnderTest('connection');
        $callback = function() {};
        $connection = $this->createMock(Connection::class);

        $sut->expects($this->once())
            ->method('connection')
            ->willReturn($connection);
        $connection->expects($this->once())
            ->method('BeginTransaction');
        $connection->expects($this->once())
            ->method('CommitTransaction');
        $connection->expects($this->never())
            ->method('RollbackTransaction');

        $sut->WithTransaction($callback);
    }

    #endregion WithTransaction

    #region EscapeString -------------------------------------------------------

    function testEscapeStringWhenConnectionReturnsNull()
    {
        $sut = $this->systemUnderTest('connection');

        $sut->expects($this->once())
            ->method('connection')
            ->willReturn(null);

        $this->assertSame('', $sut->EscapeString('input-string'));
    }

    function testEscapeStringWhenSucceeds()
    {
        $sut = $this->systemUnderTest('connection');
        $connection = $this->createMock(Connection::class);

        $sut->expects($this->once())
            ->method('connection')
            ->willReturn($connection);
        $connection->expects($this->once())
            ->method('EscapeString')
            ->with('input-string')
            ->willReturn('escaped-string');

        $this->assertSame('escaped-string', $sut->EscapeString('input-string'));
    }

    #endregion EscapeString

    #region connection ---------------------------------------------------------

    function testConnectionWhenNewConnectionThrows()
    {
        $sut = $this->systemUnderTest('_new_Connection');

        $sut->expects($this->once())
            ->method('_new_Connection')
            ->willThrowException(new \RuntimeException());

        $this->assertNull(AccessHelper::CallMethod($sut, 'connection'));
    }

    function testConnectionWhenSelectDatabaseThrows()
    {
        $sut = $this->systemUnderTest('_new_Connection');
        $connection = $this->createMock(Connection::class);

        $sut->expects($this->once())
            ->method('_new_Connection')
            ->willReturn($connection);
        $connection->expects($this->once())
            ->method('SelectDatabase')
            ->willThrowException(new \RuntimeException());

        $this->assertNull(AccessHelper::CallMethod($sut, 'connection'));
    }

    function testConnectionWhenSucceeds()
    {
        $sut = $this->systemUnderTest('_new_Connection');
        $connection = $this->createMock(Connection::class);

        $sut->expects($this->once())
            ->method('_new_Connection')
            ->willReturn($connection);
        $connection->expects($this->once())
            ->method('SelectDatabase');

        $this->assertSame($connection, AccessHelper::CallMethod($sut, 'connection'));
    }

    #endregion connection

    #region Data Providers -----------------------------------------------------

    static function nullOrMySQLiResultProvider()
    {
        return [
            'null' => [null],
            'MySQLiResult' => [self::createStub(MySQLiResult::class)]
        ];
    }

    #endregion Data Providers
}
