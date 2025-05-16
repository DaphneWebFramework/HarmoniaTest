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
    private ?Database $originalDatabase = null;
    private ?Config $originalConfig = null;
    private ?Logger $originalLogger = null;

    protected function setUp(): void
    {
        $this->originalDatabase =
            Database::ReplaceInstance($this->createDatabasePartialMock());
        $this->originalConfig =
            Config::ReplaceInstance($this->createConfigMock());
        $this->originalLogger =
            Logger::ReplaceInstance($this->createStub(Logger::class));
    }

    protected function tearDown(): void
    {
        Database::ReplaceInstance($this->originalDatabase);
        Config::ReplaceInstance($this->originalConfig);
        Logger::ReplaceInstance($this->originalLogger);
    }

    private function createDatabasePartialMock(): Database
    {
        return $this->getMockBuilder(Database::class)
            ->onlyMethods(['_new_Connection'])
            ->disableOriginalConstructor()
            ->getMock();
    }

    private function createConfigMock(): Config
    {
        $config = $this->createMock(Config::class);
        $config->expects($this->any())
            ->method('OptionOrDefault')
            ->willReturnMap([
                ['DatabaseHost', '', 'localhost'],
                ['DatabaseUsername', '', 'root'],
                ['DatabasePassword', '', 'pass123'],
                ['DatabaseName', '', 'test_db']
            ]);
        $config->expects($this->any())
            ->method('Option')
            ->with('DatabaseCharset')
            ->willReturn('utf8mb4');
        return $config;
    }

    #region Execute ------------------------------------------------------------

    function testExecuteReturnsNullWhenConfigOptionsAreMissing()
    {
        $config = $this->createMock(Config::class); // doesn't use the singleton
        $config->expects($this->any())
            ->method('OptionOrDefault')
            ->willReturnMap([
                ['DatabaseHost', '', ''],
                ['DatabaseUsername', '', ''],
                ['DatabasePassword', '', ''],
                ['DatabaseName', '', '']
            ]);
        $config->expects($this->any())
            ->method('Option')
            ->with('DatabaseCharset')
            ->willReturn(null);
        Config::ReplaceInstance($config); // tearDown will restore the original

        $database = Database::Instance();
        $database->expects($this->once())
            ->method('_new_Connection')
            ->with('', '', '', null)
            ->willThrowException(new \RuntimeException('Database not found', 1049));

        $query = $this->createStub(Query::class);
        $this->assertNull($database->Execute($query));
    }

    function testExecuteReturnsNullWhenNewConnectionThrows()
    {
        $database = Database::Instance();
        $database->expects($this->once())
            ->method('_new_Connection')
            ->with('localhost', 'root', 'pass123', 'utf8mb4')
            ->willThrowException(new \RuntimeException('Access denied', 1045));

        $query = $this->createStub(Query::class);
        $this->assertNull($database->Execute($query));
    }

    function testExecuteReturnsNullWhenConnectionSelectDatabaseThrows()
    {
        $connection = $this->createMock(Connection::class);
        $connection->expects($this->once())
            ->method('SelectDatabase')
            ->with('test_db')
            ->willThrowException(new \RuntimeException('Unknown database', 1049));

        $database = Database::Instance();
        $database->expects($this->once())
            ->method('_new_Connection')
            ->with('localhost', 'root', 'pass123', 'utf8mb4')
            ->willReturn($connection);

        $query = $this->createStub(Query::class);
        $this->assertNull($database->Execute($query));
    }

    function testExecuteReturnsNullWhenConnectionExecuteThrows()
    {
        $query = $this->createStub(Query::class);

        $connection = $this->createMock(Connection::class);
        $connection->expects($this->once())
            ->method('Execute')
            ->with($query)
            ->willThrowException(new \RuntimeException('Syntax error', 1064));

        $database = Database::Instance();
        $database->expects($this->once())
            ->method('_new_Connection')
            ->willReturn($connection);

        $this->assertNull($database->Execute($query));
    }

    #[DataProvider('nullOrResultDataProvider')]
    function testExecuteReturnsResultSetWhenConnectionExecuteSucceeds($result)
    {
        $query = $this->createStub(Query::class);

        $connection = $this->createMock(Connection::class);
        $connection->expects($this->once())
            ->method('Execute')
            ->with($query)
            ->willReturn($result);

        $database = Database::Instance();
        $database->expects($this->once())
            ->method('_new_Connection')
            ->willReturn($connection);

        $this->assertInstanceOf(ResultSet::class, $database->Execute($query));
    }

    #endregion Execute

    #region LastInsertId -------------------------------------------------------

    function testLastInsertIdReturnsZeroWhenNewConnectionThrows()
    {
        $database = Database::Instance();
        $database->expects($this->once())
            ->method('_new_Connection')
            ->willThrowException(new \RuntimeException());

        $this->assertSame(0, $database->LastInsertId());
    }

    function testLastInsertIdReturnsWhateverTheConnectionMethodReturns()
    {
        $connection = $this->createMock(Connection::class);
        $connection->expects($this->once())
            ->method('LastInsertId')
            ->willReturn(123);

        $database = Database::Instance();
        $database->expects($this->once())
            ->method('_new_Connection')
            ->willReturn($connection);

        $this->assertSame(123, $database->LastInsertId());
    }

    #endregion LastInsertId

    #region LastAffectedRowCount -----------------------------------------------

    function testLastAffectedRowCountReturnsMinusOneWhenNewConnectionThrows()
    {
        $database = Database::Instance();
        $database->expects($this->once())
            ->method('_new_Connection')
            ->willThrowException(new \RuntimeException());

        $this->assertSame(-1, $database->LastAffectedRowCount());
    }

    function testLastAffectedRowCountReturnsWhateverTheConnectionMethodReturns()
    {
        $connection = $this->createMock(Connection::class);
        $connection->expects($this->once())
            ->method('LastAffectedRowCount')
            ->willReturn(3);

        $database = Database::Instance();
        $database->expects($this->once())
            ->method('_new_Connection')
            ->willReturn($connection);

        $this->assertSame(3, $database->LastAffectedRowCount());
    }

    #endregion LastAffectedRowCount

    #region WithTransaction ----------------------------------------------------

    function testWithTransactionReturnsFalseWhenNewConnectionThrows()
    {
        $database = Database::Instance();
        $database->expects($this->once())
            ->method('_new_Connection')
            ->willThrowException(new \RuntimeException());

        $result = $database->WithTransaction(function() {
            return 'any result';
        });
        $this->assertFalse($result);
    }

    function testWithTransactionReturnsFalseWhenBeginTransactionThrows()
    {
        $connection = $this->createMock(Connection::class);
        $connection->expects($this->once())
            ->method('BeginTransaction')
            ->willThrowException(new \RuntimeException());
        $connection->expects($this->once())
            ->method('RollbackTransaction');

        $database = Database::Instance();
        $database->expects($this->once())
            ->method('_new_Connection')
            ->willReturn($connection);

        $result = $database->WithTransaction(function() {
            $this->fail('Callback should not be executed');
        });
        $this->assertFalse($result);
    }

    function testWithTransactionReturnsFalseWhenCallbackThrows()
    {
        $connection = $this->createMock(Connection::class);
        $connection->expects($this->once())
            ->method('BeginTransaction');
        $connection->expects($this->never())
            ->method('CommitTransaction');
        $connection->expects($this->once())
            ->method('RollbackTransaction');

        $database = Database::Instance();
        $database->expects($this->once())
            ->method('_new_Connection')
            ->willReturn($connection);

        $result = $database->WithTransaction(function() {
            throw new \RuntimeException();
        });
        $this->assertFalse($result);
    }

    function testWithTransactionReturnsFalseWhenCommitTransactionThrows(): void
    {
        $connection = $this->createMock(Connection::class);
        $connection->expects($this->once())
            ->method('BeginTransaction');
        $connection->expects($this->once())
            ->method('CommitTransaction')
            ->willThrowException(new \RuntimeException());
        $connection->expects($this->once())
            ->method('RollbackTransaction');

        $database = Database::Instance();
        $database->expects($this->once())
            ->method('_new_Connection')
            ->willReturn($connection);

        $result = $database->WithTransaction(function() {
            return 42;
        });
        $this->assertFalse($result);
    }

    function testWithTransactionReturnsFalseWhenRollbackThrows()
    {
        $connection = $this->createMock(Connection::class);
        $connection->expects($this->once())
            ->method('BeginTransaction');
        $connection->expects($this->never())
            ->method('CommitTransaction');
        $connection->expects($this->once())
            ->method('RollbackTransaction')
            ->willThrowException(new \RuntimeException());

        $database = Database::Instance();
        $database->expects($this->once())
            ->method('_new_Connection')
            ->willReturn($connection);

        $result = $database->WithTransaction(function() {
            throw new \RuntimeException('Error in callback');
        });
        $this->assertFalse($result);
    }

    function testWithTransactionReturnsCallbackResultOfNullOnSuccess(): void
    {
        $connection = $this->createMock(Connection::class);
        $connection->expects($this->once())
            ->method('BeginTransaction');
        $connection->expects($this->once())
            ->method('CommitTransaction');
        $connection->expects($this->never())
            ->method('RollbackTransaction');

        $database = Database::Instance();
        $database->expects($this->once())
            ->method('_new_Connection')
            ->willReturn($connection);

        $result = $database->WithTransaction(function() {
            // no return statement
        });
        $this->assertNull($result);
    }

    function testWithTransactionReturnsCallbackResultOfFalseOnSuccess(): void
    {
        $connection = $this->createMock(Connection::class);
        $connection->expects($this->once())
            ->method('BeginTransaction');
        $connection->expects($this->once())
            ->method('CommitTransaction');
        $connection->expects($this->never())
            ->method('RollbackTransaction');

        $database = Database::Instance();
        $database->expects($this->once())
            ->method('_new_Connection')
            ->willReturn($connection);

        $result = $database->WithTransaction(function() {
            return false;
        });
        $this->assertFalse($result);
    }

    function testWithTransactionReturnsCallbackResultOnSuccess()
    {
        $connection = $this->createMock(Connection::class);
        $connection->expects($this->once())
            ->method('BeginTransaction');
        $connection->expects($this->once())
            ->method('CommitTransaction');
        $connection->expects($this->never())
            ->method('RollbackTransaction');

        $database = Database::Instance();
        $database->expects($this->once())
            ->method('_new_Connection')
            ->willReturn($connection);

        $result = $database->WithTransaction(function() {
            return 'any result';
        });
        $this->assertSame('any result', $result);
    }

    #endregion WithTransaction

    #region Data Providers -----------------------------------------------------

    static function nullOrResultDataProvider()
    {
        return [
            'null' => [null],
            'result' => [self::createStub(MySQLiResult::class)]
        ];
    }

    #endregion Data Providers
}
