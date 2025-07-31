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

    function testExecuteReturnsNullWhenConfigOptionsAreMissing()
    {
        $sut = $this->systemUnderTest('_new_Connection');
        $query = $this->createStub(Query::class);

        $sut->expects($this->once())
            ->method('_new_Connection')
            ->with('', '', '', null)
            ->willThrowException(new \RuntimeException('Database not found', 1049));

        // Overwrite the singleton; tearDown() will restore it.
        Config::ReplaceInstance($this->createConfig('', '', '', '', null));

        $this->assertNull($sut->Execute($query));
    }

    function testExecuteReturnsNullWhenNewConnectionThrows()
    {
        $sut = $this->systemUnderTest('_new_Connection');
        $query = $this->createStub(Query::class);

        $sut->expects($this->once())
            ->method('_new_Connection')
            ->with('localhost', 'root', 'pass1234', 'utf8mb4')
            ->willThrowException(new \RuntimeException('Access denied', 1045));

        $this->assertNull($sut->Execute($query));
    }

    function testExecuteReturnsNullWhenConnectionSelectDatabaseThrows()
    {
        $sut = $this->systemUnderTest('_new_Connection');
        $connection = $this->createMock(Connection::class);
        $query = $this->createStub(Query::class);

        $sut->expects($this->once())
            ->method('_new_Connection')
            ->with('localhost', 'root', 'pass1234', 'utf8mb4')
            ->willReturn($connection);
        $connection->expects($this->once())
            ->method('SelectDatabase')
            ->with('test_db')
            ->willThrowException(new \RuntimeException('Unknown database', 1049));

        $this->assertNull($sut->Execute($query));
    }

    function testExecuteReturnsNullWhenConnectionExecuteThrows()
    {
        $sut = $this->systemUnderTest('_new_Connection');
        $connection = $this->createMock(Connection::class);
        $query = $this->createStub(Query::class);

        $sut->expects($this->once())
            ->method('_new_Connection')
            ->willReturn($connection);
        $connection->expects($this->once())
            ->method('Execute')
            ->with($query)
            ->willThrowException(new \RuntimeException('Syntax error', 1064));

        $this->assertNull($sut->Execute($query));
    }

    #[DataProvider('nullOrResultDataProvider')]
    function testExecuteReturnsResultSetWhenConnectionExecuteSucceeds($result)
    {
        $sut = $this->systemUnderTest('_new_Connection');
        $connection = $this->createMock(Connection::class);
        $query = $this->createStub(Query::class);

        $sut->expects($this->once())
            ->method('_new_Connection')
            ->willReturn($connection);
        $connection->expects($this->once())
            ->method('Execute')
            ->with($query)
            ->willReturn($result);

        $this->assertInstanceOf(ResultSet::class, $sut->Execute($query));
    }

    #endregion Execute

    #region LastInsertId -------------------------------------------------------

    function testLastInsertIdReturnsZeroWhenNewConnectionThrows()
    {
        $sut = $this->systemUnderTest('_new_Connection');

        $sut->expects($this->once())
            ->method('_new_Connection')
            ->willThrowException(new \RuntimeException());

        $this->assertSame(0, $sut->LastInsertId());
    }

    function testLastInsertIdReturnsWhateverTheConnectionMethodReturns()
    {
        $sut = $this->systemUnderTest('_new_Connection');
        $connection = $this->createMock(Connection::class);

        $sut->expects($this->once())
            ->method('_new_Connection')
            ->willReturn($connection);
        $connection->expects($this->once())
            ->method('LastInsertId')
            ->willReturn(123);

        $this->assertSame(123, $sut->LastInsertId());
    }

    #endregion LastInsertId

    #region LastAffectedRowCount -----------------------------------------------

    function testLastAffectedRowCountReturnsMinusOneWhenNewConnectionThrows()
    {
        $sut = $this->systemUnderTest('_new_Connection');

        $sut->expects($this->once())
            ->method('_new_Connection')
            ->willThrowException(new \RuntimeException());

        $this->assertSame(-1, $sut->LastAffectedRowCount());
    }

    function testLastAffectedRowCountReturnsWhateverTheConnectionMethodReturns()
    {
        $sut = $this->systemUnderTest('_new_Connection');
        $connection = $this->createMock(Connection::class);

        $sut->expects($this->once())
            ->method('_new_Connection')
            ->willReturn($connection);
        $connection->expects($this->once())
            ->method('LastAffectedRowCount')
            ->willReturn(3);

        $this->assertSame(3, $sut->LastAffectedRowCount());
    }

    #endregion LastAffectedRowCount

    #region WithTransaction ----------------------------------------------------

    function testWithTransactionReturnsFalseWhenNewConnectionThrows()
    {
        $sut = $this->systemUnderTest('_new_Connection');

        $sut->expects($this->once())
            ->method('_new_Connection')
            ->willThrowException(new \RuntimeException());

        $result = $sut->WithTransaction(function() {
            return 'any result';
        });
        $this->assertFalse($result);
    }

    function testWithTransactionReturnsFalseWhenBeginTransactionThrows()
    {
        $sut = $this->systemUnderTest('_new_Connection');
        $connection = $this->createMock(Connection::class);

        $sut->expects($this->once())
            ->method('_new_Connection')
            ->willReturn($connection);
        $connection->expects($this->once())
            ->method('BeginTransaction')
            ->willThrowException(new \RuntimeException());
        $connection->expects($this->once())
            ->method('RollbackTransaction');

        $result = $sut->WithTransaction(function() {
            $this->fail('Callback should not be executed');
        });
        $this->assertFalse($result);
    }

    function testWithTransactionReturnsFalseWhenCallbackThrows()
    {
        $sut = $this->systemUnderTest('_new_Connection');
        $connection = $this->createMock(Connection::class);

        $sut->expects($this->once())
            ->method('_new_Connection')
            ->willReturn($connection);
        $connection->expects($this->once())
            ->method('BeginTransaction');
        $connection->expects($this->never())
            ->method('CommitTransaction');
        $connection->expects($this->once())
            ->method('RollbackTransaction');

        $result = $sut->WithTransaction(function() {
            throw new \RuntimeException();
        });
        $this->assertFalse($result);
    }

    function testWithTransactionReturnsFalseWhenCommitTransactionThrows()
    {
        $sut = $this->systemUnderTest('_new_Connection');
        $connection = $this->createMock(Connection::class);

        $sut->expects($this->once())
            ->method('_new_Connection')
            ->willReturn($connection);
        $connection->expects($this->once())
            ->method('BeginTransaction');
        $connection->expects($this->once())
            ->method('CommitTransaction')
            ->willThrowException(new \RuntimeException());
        $connection->expects($this->once())
            ->method('RollbackTransaction');

        $result = $sut->WithTransaction(function() {
            return 42;
        });
        $this->assertFalse($result);
    }

    function testWithTransactionReturnsFalseWhenRollbackThrows()
    {
        $sut = $this->systemUnderTest('_new_Connection');
        $connection = $this->createMock(Connection::class);

        $sut->expects($this->once())
            ->method('_new_Connection')
            ->willReturn($connection);
        $connection->expects($this->once())
            ->method('BeginTransaction');
        $connection->expects($this->never())
            ->method('CommitTransaction');
        $connection->expects($this->once())
            ->method('RollbackTransaction')
            ->willThrowException(new \RuntimeException());

        $result = $sut->WithTransaction(function() {
            throw new \RuntimeException('Error in callback');
        });
        $this->assertFalse($result);
    }

    function testWithTransactionReturnsCallbackResultOfNullOnSuccess()
    {
        $sut = $this->systemUnderTest('_new_Connection');
        $connection = $this->createMock(Connection::class);

        $sut->expects($this->once())
            ->method('_new_Connection')
            ->willReturn($connection);
        $connection->expects($this->once())
            ->method('BeginTransaction');
        $connection->expects($this->once())
            ->method('CommitTransaction');
        $connection->expects($this->never())
            ->method('RollbackTransaction');

        $result = $sut->WithTransaction(function() {
            // no return statement
        });
        $this->assertNull($result);
    }

    function testWithTransactionReturnsCallbackResultOfFalseOnSuccess()
    {
        $sut = $this->systemUnderTest('_new_Connection');
        $connection = $this->createMock(Connection::class);

        $sut->expects($this->once())
            ->method('_new_Connection')
            ->willReturn($connection);
        $connection->expects($this->once())
            ->method('BeginTransaction');
        $connection->expects($this->once())
            ->method('CommitTransaction');
        $connection->expects($this->never())
            ->method('RollbackTransaction');

        $result = $sut->WithTransaction(function() {
            return false;
        });
        $this->assertFalse($result);
    }

    function testWithTransactionReturnsCallbackResultOnSuccess()
    {
        $sut = $this->systemUnderTest('_new_Connection');
        $connection = $this->createMock(Connection::class);

        $sut->expects($this->once())
            ->method('_new_Connection')
            ->willReturn($connection);
        $connection->expects($this->once())
            ->method('BeginTransaction');
        $connection->expects($this->once())
            ->method('CommitTransaction');
        $connection->expects($this->never())
            ->method('RollbackTransaction');

        $result = $sut->WithTransaction(function() {
            return 'any result';
        });
        $this->assertSame('any result', $result);
    }

    #endregion WithTransaction

    #region EscapeString -------------------------------------------------------

    function testEscapeStringReturnsEmptyStringWhenNewConnectionThrows()
    {
        $sut = $this->systemUnderTest('_new_Connection');

        $sut->expects($this->once())
            ->method('_new_Connection')
            ->willThrowException(new \RuntimeException());

        $this->assertSame('', $sut->EscapeString('input-string'));
    }

    function testEscapeStringReturnsWhateverTheConnectionMethodReturns()
    {
        $sut = $this->systemUnderTest('_new_Connection');
        $connection = $this->createMock(Connection::class);

        $sut->expects($this->once())
            ->method('_new_Connection')
            ->willReturn($connection);
        $connection->expects($this->once())
            ->method('EscapeString')
            ->with('input-string')
            ->willReturn('escaped-string');

        $this->assertSame('escaped-string', $sut->EscapeString('input-string'));
    }

    #endregion EscapeString

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
