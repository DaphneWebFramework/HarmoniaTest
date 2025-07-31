<?php declare(strict_types=1);
use \PHPUnit\Framework\TestCase;
use \PHPUnit\Framework\Attributes\CoversClass;
use \PHPUnit\Framework\Attributes\DataProvider;
use \PHPUnit\Framework\Attributes\RequiresPhp;

use \Harmonia\Systems\DatabaseSystem\Connection;

use \Harmonia\Systems\DatabaseSystem\Proxies\MySQLiHandle;
use \Harmonia\Systems\DatabaseSystem\Proxies\MySQLiResult;
use \Harmonia\Systems\DatabaseSystem\Proxies\MySQLiStatement;
use \Harmonia\Systems\DatabaseSystem\Queries\Query;
use \TestToolkit\AccessHelper;

if (!\class_exists('mysqli_sql_exception')) {
    class mysqli_sql_exception extends \RuntimeException {}
}

#[CoversClass(Connection::class)]
class ConnectionTest extends TestCase
{
    private function systemUnderTest(string ...$mockedMethods): Connection
    {
        return $this->getMockBuilder(Connection::class)
            ->disableOriginalConstructor()
            ->onlyMethods($mockedMethods)
            ->getMock();
    }

    private function createQuery(string $sql, array $bindings = []): Query
    {
        $query = $this->getMockBuilder(Query::class)
            ->onlyMethods(['buildSql'])
            ->getMock();
        $query->expects($this->once())
            ->method('buildSql')
            ->willReturn($sql);
        if (!empty($bindings)) {
            $query->Bind($bindings);
        }
        return $query;
    }

    #region __construct --------------------------------------------------------

    function testConstructorThrowsWhenCreateHandleThrows()
    {
        $sut = $this->systemUnderTest('createHandle');

        $sut->expects($this->once())
            ->method('createHandle')
            ->with('localhost', 'root', '')
            ->willThrowException(new \RuntimeException('Access denied', 1045));

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Access denied');
        $this->expectExceptionCode(1045);

        $sut->__construct('localhost', 'root', '');

        $this->assertNull(AccessHelper::GetMockProperty(
            Connection::class,
            $sut,
            'handle'
        ));
    }

    function testConstructorThrowsWhenSetCharsetThrows()
    {
        $sut = $this->systemUnderTest('createHandle', 'setCharset');
        $handle = $this->createMock(MySQLiHandle::class);

        $sut->expects($this->once())
            ->method('createHandle')
            ->with('localhost', 'root', '')
            ->willReturn($handle);
        $sut->expects($this->once())
            ->method('setCharset')
            ->with($handle, 'badcharset')
            ->willThrowException(new \RuntimeException('Unknown character set', 2019));
        $handle->expects($this->once())
            ->method('__call')
            ->with('close');

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Unknown character set');
        $this->expectExceptionCode(2019);

        $sut->__construct('localhost', 'root', '', 'badcharset');

        $this->assertNull(AccessHelper::GetMockProperty(
            Connection::class,
            $sut,
            'handle'
        ));
    }

    function testConstructorSucceedsWithoutCharset()
    {
        $sut = $this->systemUnderTest('createHandle', 'setCharset');
        $handle = $this->createMock(MySQLiHandle::class);

        $sut->expects($this->once())
            ->method('createHandle')
            ->with('localhost', 'root', '')
            ->willReturn($handle);
        $sut->expects($this->never())
            ->method('setCharset');
        $handle->expects($this->never())
            ->method('__call')
            ->with('close');

        $sut->__construct('localhost', 'root', '');

        $this->assertSame($handle, AccessHelper::GetMockProperty(
            Connection::class,
            $sut,
            'handle'
        ));
    }

    function testConstructorSucceedsWithNullCharset()
    {
        $sut = $this->systemUnderTest('createHandle', 'setCharset');
        $handle = $this->createMock(MySQLiHandle::class);

        $sut->expects($this->once())
            ->method('createHandle')
            ->with('localhost', 'root', '')
            ->willReturn($handle);
        $sut->expects($this->never())
            ->method('setCharset');
        $handle->expects($this->never())
            ->method('__call')
            ->with('close');

        $sut->__construct('localhost', 'root', '', null);

        $this->assertSame($handle, AccessHelper::GetMockProperty(
            Connection::class,
            $sut, 'handle'
        ));
    }

    function testConstructorSucceedsWithCharset()
    {
        $sut = $this->systemUnderTest('createHandle', 'setCharset');
        $handle = $this->createMock(MySQLiHandle::class);

        $sut->expects($this->once())
            ->method('createHandle')
            ->with('localhost', 'root', '')
            ->willReturn($handle);
        $sut->expects($this->once())
            ->method('setCharset')
            ->with($handle, 'utf8mb4');
        $handle->expects($this->never())
            ->method('__call')
            ->with('close');

        $sut->__construct('localhost', 'root', '', 'utf8mb4');

        $this->assertSame($handle, AccessHelper::GetMockProperty(
            Connection::class,
            $sut,
            'handle'
        ));
    }

    #endregion __construct

    #region SelectDatabase -----------------------------------------------------

    function testSelectDatabaseThrowsIfHandleSelectDbFailsWhenReportModeIsOff()
    {
        $sut = $this->systemUnderTest('createHandle');
        $handle = $this->createMock(MySQLiHandle::class);

        $sut->expects($this->once())
            ->method('createHandle')
            ->with('localhost', 'root', '')
            ->willReturn($handle);
        $handle->expects($this->once())
            ->method('__call')
            ->with('select_db', ['nonexistent_db'])
            ->willReturn(false);
        $handle->expects($this->any())
            ->method('__get')
            ->willReturnMap([
                ['error', 'Unknown database'],
                ['errno', 1049]
            ]);

        $sut->__construct('localhost', 'root', '');

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Unknown database');
        $this->expectExceptionCode(1049);

        $sut->SelectDatabase('nonexistent_db');
    }

    function testSelectDatabaseThrowsIfHandleSelectDbFailsWhenReportModeIsStrict()
    {
        $sut = $this->systemUnderTest('createHandle');
        $handle = $this->createMock(MySQLiHandle::class);

        $sut->expects($this->once())
            ->method('createHandle')
            ->with('localhost', 'root', '')
            ->willReturn($handle);
        $handle->expects($this->once())
            ->method('__call')
            ->with('select_db', ['nonexistent_db'])
            ->willThrowException(new \mysqli_sql_exception('Unknown database', 1049));
        $handle->expects($this->never())
            ->method('__get');

        $sut->__construct('localhost', 'root', '');

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Unknown database');
        $this->expectExceptionCode(1049);

        $sut->SelectDatabase('nonexistent_db');
    }

    function testSelectDatabaseSucceedsWhenHandleSelectDbSucceeds()
    {
        $sut = $this->systemUnderTest('createHandle');
        $handle = $this->createMock(MySQLiHandle::class);

        $sut->expects($this->once())
            ->method('createHandle')
            ->with('localhost', 'root', '')
            ->willReturn($handle);
        $handle->expects($this->once())
            ->method('__call')
            ->with('select_db', ['test_db'])
            ->willReturn(true);

        $sut->__construct('localhost', 'root', '');
        $sut->SelectDatabase('test_db');
    }

    #endregion SelectDatabase

    #region Execute (PHP < 8.2.0) ----------------------------------------------

    #[RequiresPhp('< 8.2.0')]
    function testExecuteThrowsWhenPrepareStatementThrows()
    {
        $sut = $this->systemUnderTest('prepareStatement');
        $query = $this->createQuery('SELECT * FROM `users`');

        $sut->expects($this->once())
            ->method('prepareStatement')
            ->with('SELECT * FROM `users`')
            ->willThrowException(new \RuntimeException('Syntax error', 1064));

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Syntax error');
        $this->expectExceptionCode(1064);

        $sut->Execute($query);
    }

    #[RequiresPhp('< 8.2.0')]
    function testExecuteThrowsWhenExecuteStatementThrows()
    {
        $sut = $this->systemUnderTest('prepareStatement', 'executeStatement');
        $query = $this->createQuery('SELECT * FROM `users`');
        $statement = $this->createMock(MySQLiStatement::class);

        $sut->expects($this->once())
            ->method('prepareStatement')
            ->with('SELECT * FROM `users`')
            ->willReturn($statement);
        $sut->expects($this->once())
            ->method('executeStatement')
            ->willThrowException(new \RuntimeException('Execution error', 1064));
        $statement->expects($this->once())
            ->method('__call')
            ->with('close');


        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Execution error');
        $this->expectExceptionCode(1064);

        $sut->Execute($query);
    }

    #[RequiresPhp('< 8.2.0')]
    function testExecuteThrowsWhenGetStatementResultThrows()
    {
        $sut = $this->systemUnderTest(
            'prepareStatement',
            'executeStatement',
            'getStatementResult'
        );
        $query = $this->createQuery('SELECT * FROM `users`');
        $statement = $this->createMock(MySQLiStatement::class);

        $sut->expects($this->once())
            ->method('prepareStatement')
            ->with('SELECT * FROM `users`')
            ->willReturn($statement);
        $sut->expects($this->once())
            ->method('executeStatement');
        $sut->expects($this->once())
            ->method('getStatementResult')
            ->willThrowException(new \RuntimeException('Result error', 1064));
        $statement->expects($this->once())
            ->method('__call')
            ->with('close');

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Result error');
        $this->expectExceptionCode(1064);

        $sut->Execute($query);
    }

    #[RequiresPhp('< 8.2.0')]
    #[DataProvider('nullOrResultDataProvider')]
    function testExecuteReturnsWhatGetStatementResultReturns($result)
    {
        $sut = $this->systemUnderTest(
            'prepareStatement',
            'executeStatement',
            'getStatementResult'
        );
        $query = $this->createQuery('SELECT * FROM `users`');
        $statement = $this->createMock(MySQLiStatement::class);

        $sut->expects($this->once())
            ->method('prepareStatement')
            ->with('SELECT * FROM `users`')
            ->willReturn($statement);
        $sut->expects($this->once())
            ->method('executeStatement');
        $sut->expects($this->once())
            ->method('getStatementResult')
            ->willReturn($result);
        $statement->expects($this->once())
            ->method('__call')
            ->with('close');

        $this->assertSame($result, $sut->Execute($query));
    }

    #endregion Execute (PHP < 8.2.0)

    #region Execute (PHP >= 8.2.0) ---------------------------------------------

    #[RequiresPhp('>= 8.2.0')]
    function testExecuteThrowsWhenExecuteQueryThrows()
    {
        $sut = $this->systemUnderTest('executeQuery');
        $query = $this->createQuery('SELECT * FROM `users`');

        $sut->expects($this->once())
            ->method('executeQuery')
            ->with('SELECT * FROM `users`', [])
            ->willThrowException(new \RuntimeException('Syntax error', 1064));

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Syntax error');
        $this->expectExceptionCode(1064);

        $sut->Execute($query);
    }

    #[RequiresPhp('>= 8.2.0')]
    #[DataProvider('nullOrResultDataProvider')]
    function testExecuteReturnsWhatExecuteQueryReturns($result)
    {
        $sut = $this->systemUnderTest('executeQuery');
        $query = $this->createQuery('SELECT * FROM `users`');

        $sut->expects($this->once())
            ->method('executeQuery')
            ->with('SELECT * FROM `users`', [])
            ->willReturn($result);

        $this->assertSame($result, $sut->Execute($query));
    }

    #endregion Execute (PHP >= 8.2.0)

    #region LastInsertId -------------------------------------------------------

    function testLastInsertId()
    {
        $sut = $this->systemUnderTest('createHandle');
        $handle = $this->createMock(MySQLiHandle::class);

        $sut->expects($this->once())
            ->method('createHandle')
            ->with('localhost', 'root', '')
            ->willReturn($handle);
        $handle->expects($this->once())
            ->method('__get')
            ->with('insert_id')
            ->willReturn(42);

        $sut->__construct('localhost', 'root', '');

        $this->assertSame(42, $sut->LastInsertId());
    }

    #endregion LastInsertId

    #region LastAffectedRowCount -----------------------------------------------

    function testLastAffectedRowCount()
    {
        $sut = $this->systemUnderTest('createHandle');
        $handle = $this->createMock(MySQLiHandle::class);

        $sut->expects($this->once())
            ->method('createHandle')
            ->with('localhost', 'root', '')
            ->willReturn($handle);
        $handle->expects($this->once())
            ->method('__get')
            ->with('affected_rows')
            ->willReturn(42);

        $sut->__construct('localhost', 'root', '');

        $this->assertSame(42, $sut->LastAffectedRowCount());
    }

    #endregion LastAffectedRowCount

    #region BeginTransaction ---------------------------------------------------

    function testBeginTransactionThrowsIfHandleBeginTransactionFailsWhenReportModeIsOff()
    {
        $sut = $this->systemUnderTest('createHandle');
        $handle = $this->createMock(MySQLiHandle::class);

        $sut->expects($this->once())
            ->method('createHandle')
            ->with('localhost', 'root', '')
            ->willReturn($handle);
        $handle->expects($this->once())
            ->method('__call')
            ->with('begin_transaction')
            ->willReturn(false);
        $handle->expects($this->any())
            ->method('__get')
            ->willReturnMap([
                ['error', 'Unknown database'],
                ['errno', 1049]
            ]);

        $sut->__construct('localhost', 'root', '');

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Unknown database');
        $this->expectExceptionCode(1049);

        $sut->BeginTransaction();
    }

    function testBeginTransactionThrowsIfHandleBeginTransactionFailsWhenReportModeIsStrict()
    {
        $sut = $this->systemUnderTest('createHandle');
        $handle = $this->createMock(MySQLiHandle::class);

        $sut->expects($this->once())
            ->method('createHandle')
            ->with('localhost', 'root', '')
            ->willReturn($handle);
        $handle->expects($this->once())
            ->method('__call')
            ->with('begin_transaction')
            ->willThrowException(new \mysqli_sql_exception('Unknown database', 1049));
        $handle->expects($this->never())
            ->method('__get');

        $sut->__construct('localhost', 'root', '');

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Unknown database');
        $this->expectExceptionCode(1049);

        $sut->BeginTransaction();
    }

    function testBeginTransactionSucceedsWhenHandleBeginTransactionSucceeds()
    {
        $sut = $this->systemUnderTest('createHandle');
        $handle = $this->createMock(MySQLiHandle::class);

        $sut->expects($this->once())
            ->method('createHandle')
            ->with('localhost', 'root', '')
            ->willReturn($handle);
        $handle->expects($this->once())
            ->method('__call')
            ->with('begin_transaction')
            ->willReturn(true);

        $sut->__construct('localhost', 'root', '');

        $sut->BeginTransaction();
    }

    #endregion BeginTransaction

    #region CommitTransaction --------------------------------------------------

    function testCommitTransactionThrowsIfHandleCommitFailsWhenReportModeIsOff()
    {
        $sut = $this->systemUnderTest('createHandle');
        $handle = $this->createMock(MySQLiHandle::class);

        $sut->expects($this->once())
            ->method('createHandle')
            ->with('localhost', 'root', '')
            ->willReturn($handle);
        $handle->expects($this->once())
            ->method('__call')
            ->with('commit')
            ->willReturn(false);
        $handle->expects($this->any())
            ->method('__get')
            ->willReturnMap([
                ['error', 'Unknown database'],
                ['errno', 1049]
            ]);

        $sut->__construct('localhost', 'root', '');

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Unknown database');
        $this->expectExceptionCode(1049);

        $sut->CommitTransaction();
    }

    function testCommitTransactionThrowsIfHandleCommitFailsWhenReportModeIsStrict()
    {
        $sut = $this->systemUnderTest('createHandle');
        $handle = $this->createMock(MySQLiHandle::class);

        $sut->expects($this->once())
            ->method('createHandle')
            ->with('localhost', 'root', '')
            ->willReturn($handle);
        $handle->expects($this->once())
            ->method('__call')
            ->with('commit')
            ->willThrowException(new \mysqli_sql_exception('Unknown database', 1049));
        $handle->expects($this->never())
            ->method('__get');

        $sut->__construct('localhost', 'root', '');

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Unknown database');
        $this->expectExceptionCode(1049);

        $sut->CommitTransaction();
    }

    function testCommitTransactionSucceedsWhenHandleCommitSucceeds()
    {
        $sut = $this->systemUnderTest('createHandle');
        $handle = $this->createMock(MySQLiHandle::class);

        $sut->expects($this->once())
            ->method('createHandle')
            ->with('localhost', 'root', '')
            ->willReturn($handle);
        $handle->expects($this->once())
            ->method('__call')
            ->with('commit')
            ->willReturn(true);

        $sut->__construct('localhost', 'root', '');
        $sut->CommitTransaction();
    }

    #endregion CommitTransaction

    #region RollbackTransaction ------------------------------------------------

    function testRollbackTransactionThrowsIfHandleRollbackFailsWhenReportModeIsOff()
    {
        $sut = $this->systemUnderTest('createHandle');
        $handle = $this->createMock(MySQLiHandle::class);

        $sut->expects($this->once())
            ->method('createHandle')
            ->with('localhost', 'root', '')
            ->willReturn($handle);
        $handle->expects($this->once())
            ->method('__call')
            ->with('rollback')
            ->willReturn(false);
        $handle->expects($this->any())
            ->method('__get')
            ->willReturnMap([
                ['error', 'Unknown database'],
                ['errno', 1049]
            ]);

        $sut->__construct('localhost', 'root', '');

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Unknown database');
        $this->expectExceptionCode(1049);

        $sut->RollbackTransaction();
    }

    function testRollbackTransactionThrowsIfHandleRollbackFailsWhenReportModeIsStrict()
    {
        $sut = $this->systemUnderTest('createHandle');
        $handle = $this->createMock(MySQLiHandle::class);

        $sut->expects($this->once())
            ->method('createHandle')
            ->with('localhost', 'root', '')
            ->willReturn($handle);
        $handle->expects($this->once())
            ->method('__call')
            ->with('rollback')
            ->willThrowException(new \mysqli_sql_exception('Unknown database', 1049));
        $handle->expects($this->never())
            ->method('__get');

        $sut->__construct('localhost', 'root', '');

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Unknown database');
        $this->expectExceptionCode(1049);

        $sut->RollbackTransaction();
    }

    function testRollbackTransactionSucceedsWhenHandleRollbackSucceeds()
    {
        $sut = $this->systemUnderTest('createHandle');
        $handle = $this->createMock(MySQLiHandle::class);

        $sut->expects($this->once())
            ->method('createHandle')
            ->with('localhost', 'root', '')
            ->willReturn($handle);
        $handle->expects($this->once())
            ->method('__call')
            ->with('rollback')
            ->willReturn(true);

        $sut->__construct('localhost', 'root', '');
        $sut->RollbackTransaction();
    }

    #endregion RollbackTransaction

    #region EscapeString -------------------------------------------------------

    function testEscapeStringDelegatesToHandleRealEscapeString()
    {
        $sut = $this->systemUnderTest('createHandle');
        $handle = $this->createMock(MySQLiHandle::class);

        $sut->expects($this->once())
            ->method('createHandle')
            ->with('localhost', 'root', '')
            ->willReturn($handle);
        $handle->expects($this->once())
            ->method('__call')
            ->with('real_escape_string', ["John'; DROP TABLE students;--"])
            ->willReturn("John\\'; DROP TABLE students;--");

        $sut->__construct('localhost', 'root', '');

        $this->assertSame(
            "John\\'; DROP TABLE students;--",
            $sut->EscapeString("John'; DROP TABLE students;--")
        );
    }

    #endregion EscapeString

    #region createHandle -------------------------------------------------------

    function testCreateHandleThrowsIfNewMysqliFailsWhenReportModeIsOff()
    {
        $sut = $this->systemUnderTest('_new_mysqli');
        $handle = $this->createMock(MySQLiHandle::class);

        $sut->expects($this->once())
            ->method('_new_mysqli')
            ->with('localhost', 'root', '')
            ->willReturn($handle);
        $handle->expects($this->any())
            ->method('__get')
            ->willReturnMap([
                ['connect_error', 'Access denied'],
                ['connect_errno', 1045]
            ]);

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Access denied');
        $this->expectExceptionCode(1045);

        AccessHelper::CallMethod(
            $sut,
            'createHandle',
            ['localhost', 'root', '']
        );
    }

    function testCreateHandleThrowsIfNewMysqliFailsWhenReportModeIsStrict()
    {
        $sut = $this->systemUnderTest('_new_mysqli');
        $handle = $this->createMock(MySQLiHandle::class);

        $sut->expects($this->once())
            ->method('_new_mysqli')
            ->with('localhost', 'root', '')
            ->willThrowException(new \mysqli_sql_exception('Access denied', 1045));
        $handle->expects($this->never())
            ->method('__get');

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Access denied');
        $this->expectExceptionCode(1045);

        AccessHelper::CallMethod(
            $sut,
            'createHandle',
            ['localhost', 'root', '']
        );
    }

    function testCreateHandleReturnsHandleWhenNewMysqliSucceeds()
    {
        $sut = $this->systemUnderTest('_new_mysqli');
        $handle = $this->createMock(MySQLiHandle::class);

        $sut->expects($this->once())
            ->method('_new_mysqli')
            ->with('localhost', 'root', '')
            ->willReturn($handle);
        $handle->expects($this->any())
            ->method('__get')
            ->with('connect_errno')
            ->willReturn(0);

        $this->assertSame($handle, AccessHelper::CallMethod(
            $sut,
            'createHandle',
            ['localhost', 'root', '']
        ));
    }

    #endregion createHandle

    #region setCharset ---------------------------------------------------------

    function testSetCharsetThrowsIfHandleSetCharsetFailsWhenReportModeIsOff()
    {
        $sut = $this->systemUnderTest();
        $handle = $this->createMock(MySQLiHandle::class);

        $handle->expects($this->once())
            ->method('__call')
            ->with('set_charset', ['badcharset'])
            ->willReturn(false);
        $handle->expects($this->any())
            ->method('__get')
            ->willReturnMap([
                ['error', 'Unknown character set'],
                ['errno', 2019]
            ]);

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Unknown character set');
        $this->expectExceptionCode(2019);

        AccessHelper::CallMethod(
            $sut,
            'setCharset',
            [$handle, 'badcharset']
        );
    }

    function testSetCharsetThrowsIfHandleSetCharsetFailsWhenReportModeIsStrict()
    {
        $sut = $this->systemUnderTest();
        $handle = $this->createMock(MySQLiHandle::class);

        $handle->expects($this->once())
            ->method('__call')
            ->with('set_charset', ['badcharset'])
            ->willThrowException(new \mysqli_sql_exception('Unknown character set', 2019));
        $handle->expects($this->never())
            ->method('__get');

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Unknown character set');
        $this->expectExceptionCode(2019);

        AccessHelper::CallMethod(
            $sut,
            'setCharset',
            [$handle, 'badcharset']
        );
    }

    function testSetCharsetSucceedsWhenHandleSetCharsetSucceeds()
    {
        $sut = $this->systemUnderTest();
        $handle = $this->createMock(MySQLiHandle::class);

        $handle->expects($this->once())
            ->method('__call')
            ->with('set_charset', ['utf8mb4'])
            ->willReturn(true);

        AccessHelper::CallMethod(
            $sut,
            'setCharset',
            [$handle, 'utf8mb4']
        );
    }

    #endregion setCharset

    #region prepareStatement ---------------------------------------------------

    #[RequiresPhp('< 8.2.0')]
    function testPrepareStatementThrowsIfHandlePrepareFailsWhenReportModeIsOff()
    {
        $sut = $this->systemUnderTest();
        $handle = $this->createMock(MySQLiHandle::class);

        $handle->expects($this->once())
            ->method('prepare')
            ->with('SELECT * FROM `users`')
            ->willReturn(false);
        $handle->expects($this->any())
            ->method('__get')
            ->willReturnMap([
                ['error', 'Syntax error'],
                ['errno', 1064]
            ]);

        AccessHelper::SetMockProperty(
            Connection::class,
            $sut,
            'handle',
            $handle
        );

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Syntax error');
        $this->expectExceptionCode(1064);

        AccessHelper::CallMethod(
            $sut,
            'prepareStatement',
            ['SELECT * FROM `users`']
        );
    }

    #[RequiresPhp('< 8.2.0')]
    function testPrepareStatementThrowsIfHandlePrepareFailsWhenReportModeIsStrict()
    {
        $sut = $this->systemUnderTest();
        $handle = $this->createMock(MySQLiHandle::class);

        $handle->expects($this->once())
            ->method('prepare')
            ->with('SELECT * FROM `users`')
            ->willThrowException(new \mysqli_sql_exception('Syntax error', 1064));
        $handle->expects($this->never())
            ->method('__get');

        AccessHelper::SetMockProperty(
            Connection::class,
            $sut,
            'handle',
            $handle
        );

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Syntax error');
        $this->expectExceptionCode(1064);

        AccessHelper::CallMethod(
            $sut,
            'prepareStatement',
            ['SELECT * FROM `users`']
        );
    }

    #[RequiresPhp('< 8.2.0')]
    function testPrepareStatementReturnsStatementWhenHandlePrepareSucceeds()
    {
        $sut = $this->systemUnderTest();
        $handle = $this->createMock(MySQLiHandle::class);
        $statement = $this->createStub(MySQLiStatement::class);

        $handle->expects($this->once())
            ->method('prepare')
            ->with('SELECT * FROM `users`')
            ->willReturn($statement);

        AccessHelper::SetMockProperty(
            Connection::class,
            $sut,
            'handle',
            $handle
        );

        $this->assertSame($statement, AccessHelper::CallMethod(
            $sut,
            'prepareStatement',
            ['SELECT * FROM `users`']
        ));
    }

    #endregion prepareStatement

    #region executeStatement ---------------------------------------------------

    #[RequiresPhp('< 8.2.0')]
    function testExecuteStatementThrowsIfStatementExecuteFailsWhenReportModeIsOff()
    {
        $sut = $this->systemUnderTest();
        $statement = $this->createMock(MySQLiStatement::class);

        $statement->expects($this->once())
            ->method('__call')
            ->with('execute', [[42, 'John']])
            ->willReturn(false);
        $statement->expects($this->any())
            ->method('__get')
            ->willReturnMap([
                ['error', 'Execution error'],
                ['errno', 1064]
            ]);

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Execution error');
        $this->expectExceptionCode(1064);

        AccessHelper::CallMethod(
            $sut,
            'executeStatement',
            [$statement, [42, 'John']]
        );
    }

    #[RequiresPhp('< 8.2.0')]
    function testExecuteStatementThrowsIfStatementExecuteFailsWhenReportModeIsStrict()
    {
        $sut = $this->systemUnderTest();
        $statement = $this->createMock(MySQLiStatement::class);

        $statement->expects($this->once())
            ->method('__call')
            ->with('execute', [[42, 'John']])
            ->willThrowException(new \mysqli_sql_exception('Execution error', 1064));
        $statement->expects($this->never())
            ->method('__get');

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Execution error');
        $this->expectExceptionCode(1064);

        AccessHelper::CallMethod(
            $sut,
            'executeStatement',
            [$statement, [42, 'John']]
        );
    }

    #[RequiresPhp('< 8.2.0')]
    function testExecuteStatementSucceedsWhenStatementExecuteSucceeds()
    {
        $sut = $this->systemUnderTest();
        $statement = $this->createMock(MySQLiStatement::class);

        $statement->expects($this->once())
            ->method('__call')
            ->with('execute', [[42, 'John']])
            ->willReturn(true);

        AccessHelper::CallMethod(
            $sut,
            'executeStatement',
            [$statement, [42, 'John']]
        );
    }

    #endregion executeStatement

    #region getStatementResult -------------------------------------------------

    #[RequiresPhp('< 8.2.0')]
    function testGetStatementResultThrowsIfStatementGetResultFailsWhenReportModeIsOff()
    {
        $sut = $this->systemUnderTest();
        $statement = $this->createMock(MySQLiStatement::class);

        $statement->expects($this->once())
            ->method('get_result')
            ->willReturn(false);
        $statement->expects($this->any())
            ->method('__get')
            ->willReturnMap([
                ['error', 'Result error'],
                ['errno', 1064]
            ]);

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Result error');
        $this->expectExceptionCode(1064);

        AccessHelper::CallMethod(
            $sut,
            'getStatementResult',
            [$statement]
        );
    }

    #[RequiresPhp('< 8.2.0')]
    function testGetStatementResultThrowsIfStatementGetResultFailsWhenReportModeIsStrict()
    {
        $sut = $this->systemUnderTest();
        $statement = $this->createMock(MySQLiStatement::class);

        $statement->expects($this->once())
            ->method('get_result')
            ->willThrowException(new \mysqli_sql_exception('Result error', 1064));
        $statement->expects($this->never())
            ->method('__get');

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Result error');
        $this->expectExceptionCode(1064);

        AccessHelper::CallMethod(
            $sut,
            'getStatementResult',
            [$statement]
        );
    }

    #[RequiresPhp('< 8.2.0')]
    function testGetStatementResultReturnsNullWhenStatementGetResultReturnsFalse()
    {
        $sut = $this->systemUnderTest();
        $statement = $this->createMock(MySQLiStatement::class);

        $statement->expects($this->once())
            ->method('get_result')
            ->willReturn(false);
        $statement->expects($this->any())
            ->method('__get')
            ->with('errno')
            ->willReturn(0);

        $this->assertNull(AccessHelper::CallMethod(
            $sut,
            'getStatementResult',
            [$statement]
        ));
    }

    #[RequiresPhp('< 8.2.0')]
    function testGetStatementResultReturnsResultWhenStatementGetResultReturnsResult()
    {
        $sut = $this->systemUnderTest();
        $statement = $this->createMock(MySQLiStatement::class);
        $result = $this->createStub(MySQLiResult::class);

        $statement->expects($this->once())
            ->method('get_result')
            ->willReturn($result);

        $this->assertSame($result, AccessHelper::CallMethod(
            $sut,
            'getStatementResult',
            [$statement]
        ));
    }

    #endregion getStatementResult

    #region executeQuery -------------------------------------------------------

    #[RequiresPhp('>= 8.2.0')]
    function testExecuteQueryThrowsIfHandleExecuteQueryFailsWhenReportModeIsOff()
    {
        $sut = $this->systemUnderTest();
        $handle = $this->createMock(MySQLiHandle::class);

        $handle->expects($this->once())
            ->method('execute_query')
            ->with('SELECT * FROM `users` WHERE id = ? AND name = ?;', [42, 'John'])
            ->willReturn(false);
        $handle->expects($this->any())
            ->method('__get')
            ->willReturnMap([
                ['error', 'Syntax error'],
                ['errno', 1064]
            ]);

        AccessHelper::SetMockProperty(
            Connection::class,
            $sut,
            'handle',
            $handle
        );

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Syntax error');
        $this->expectExceptionCode(1064);

        AccessHelper::CallMethod(
            $sut,
            'executeQuery',
            ['SELECT * FROM `users` WHERE id = ? AND name = ?;', [42, 'John']]
        );
    }

    #[RequiresPhp('>= 8.2.0')]
    function testExecuteQueryThrowsIfHandleExecuteQueryFailsWhenReportModeIsStrict()
    {
        $sut = $this->systemUnderTest();
        $handle = $this->createMock(MySQLiHandle::class);

        $handle->expects($this->once())
            ->method('execute_query')
            ->with('SELECT * FROM `users` WHERE id = ? AND name = ?;', [42, 'John'])
            ->willThrowException(new \mysqli_sql_exception('Syntax error', 1064));
        $handle->expects($this->never())
            ->method('__get');

        AccessHelper::SetMockProperty(
            Connection::class,
            $sut,
            'handle',
            $handle
        );

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Syntax error');
        $this->expectExceptionCode(1064);

        AccessHelper::CallMethod(
            $sut,
            'executeQuery',
            ['SELECT * FROM `users` WHERE id = ? AND name = ?;', [42, 'John']]
        );
    }

    #[RequiresPhp('>= 8.2.0')]
    function testExecuteQueryReturnsNullWhenHandleExecuteQueryReturnsTrue()
    {
        $sut = $this->systemUnderTest();
        $handle = $this->createMock(MySQLiHandle::class);

        $handle->expects($this->once())
            ->method('execute_query')
            ->with('SELECT * FROM `users` WHERE id = ? AND name = ?;', [42, 'John'])
            ->willReturn(true);

        AccessHelper::SetMockProperty(
            Connection::class,
            $sut,
            'handle',
            $handle
        );

        $this->assertNull(AccessHelper::CallMethod(
            $sut,
            'executeQuery',
            ['SELECT * FROM `users` WHERE id = ? AND name = ?;', [42, 'John']]
        ));
    }

    #[RequiresPhp('>= 8.2.0')]
    function testExecuteQueryReturnsResultWhenHandleExecuteQueryReturnsResult()
    {
        $sut = $this->systemUnderTest();
        $handle = $this->createMock(MySQLiHandle::class);
        $result = $this->createStub(MySQLiResult::class);

        $handle->expects($this->once())
            ->method('execute_query')
            ->with('SELECT * FROM `users` WHERE id = ? AND name = ?;', [42, 'John'])
            ->willReturn($result);

        AccessHelper::SetMockProperty(
            Connection::class,
            $sut,
            'handle',
            $handle
        );

        $this->assertSame($result, AccessHelper::CallMethod(
            $sut,
            'executeQuery',
            ['SELECT * FROM `users` WHERE id = ? AND name = ?;', [42, 'John']]
        ));
    }

    #endregion executeQuery

    #region transformQuery -----------------------------------------------------

    #[DataProvider('transformQueryDataProvider')]
    public function testTransformQuery(
        string $expectedSql,
        array $expectedValues,
        string $sql,
        array $bindings
    ) {
        $query = $this->createQuery($sql, $bindings);

        $transformedQuery = AccessHelper::CallStaticMethod(
            Connection::class,
            'transformQuery',
            [$query]
        );

        $this->assertSame($expectedSql, $transformedQuery->sql);
        $this->assertSame($expectedValues, $transformedQuery->values);
    }

    #endregion transformQuery

    #region Data Providers -----------------------------------------------------

    static function nullOrResultDataProvider()
    {
        return [
            'null' => [null],
            'result' => [self::createStub(MySQLiResult::class)]
        ];
    }

    static function transformQueryDataProvider()
    {
        return [
            'static sql' => [
                'expectedSql'   => 'SELECT name FROM `users` WHERE active = 1',
                'expectedValues'=> [],
                'sql'           => 'SELECT name FROM `users` WHERE active = 1',
                'bindings'      => []
            ],
            'no placeholders' => [
                'expectedSql'   => 'SELECT * FROM `users`',
                'expectedValues'=> [],
                'sql'           => 'SELECT * FROM `users`',
                'bindings'      => []
            ],
            'single placeholder' => [
                'expectedSql'   => 'SELECT * FROM `users` WHERE id = ?',
                'expectedValues'=> [42],
                'sql'           => 'SELECT * FROM `users` WHERE id = :id',
                'bindings'      => ['id' => 42]
            ],
            'multiple placeholders' => [
                'expectedSql'   => 'SELECT * FROM `users` WHERE id = ? AND name = ? OR parent_id = ? OR duration = ?',
                'expectedValues'=> [42, 'John', 42, 32.5],
                'sql'           => 'SELECT * FROM `users` WHERE id = :id AND name = :name OR parent_id = :id OR duration = :duration',
                'bindings'      => ['id' => 42, 'name' => 'John', 'duration' => 32.5]
            ]
        ];
    }

    #endregion Data Providers
}
