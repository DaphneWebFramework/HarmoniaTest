<?php declare(strict_types=1);
use \PHPUnit\Framework\TestCase;
use \PHPUnit\Framework\Attributes\CoversClass;
use \PHPUnit\Framework\Attributes\DataProvider;
use \PHPUnit\Framework\Attributes\RequiresPhp;

use \Harmonia\Database\Connection;

use \Harmonia\Database\Proxies\MySQLiHandle;
use \Harmonia\Database\Proxies\MySQLiResult;
use \Harmonia\Database\Proxies\MySQLiStatement;
use \Harmonia\Database\Queries\Query;
use \TestToolkit\AccessHelper;

if (!\class_exists('mysqli_sql_exception')) {
    class mysqli_sql_exception extends \RuntimeException {}
}

#[CoversClass(Connection::class)]
class ConnectionTest extends TestCase
{
    private function createQuery(string $sql, array $bindings = []): Query
    {
        $query = $this->getMockBuilder(Query::class)
            ->setConstructorArgs(['']) // no table name needed
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

    function testConstructThrowsWhenCreateHandleThrows()
    {
        $connection = $this->getMockBuilder(Connection::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['createHandle'])
            ->getMock();
        $connection->expects($this->once())
            ->method('createHandle')
            ->with('localhost', 'root', '')
            ->willThrowException(new \RuntimeException('Access denied', 1045));

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Access denied');
        $this->expectExceptionCode(1045);

        $connection->__construct('localhost', 'root', '');

        $this->assertNull(AccessHelper::GetNonPublicMockProperty(
            Connection::class,
            $connection,
            'handle'
        ));
    }

    function testConstructThrowsWhenSetCharsetThrows()
    {
        $handle = $this->createMock(MySQLiHandle::class);
        $handle->expects($this->once())
            ->method('__call')
            ->with('close');

        $connection = $this->getMockBuilder(Connection::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['createHandle', 'setCharset'])
            ->getMock();
        $connection->expects($this->once())
            ->method('createHandle')
            ->with('localhost', 'root', '')
            ->willReturn($handle);
        $connection->expects($this->once())
            ->method('setCharset')
            ->with($handle, 'badcharset')
            ->willThrowException(new \RuntimeException('Unknown character set', 2019));

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Unknown character set');
        $this->expectExceptionCode(2019);

        $connection->__construct('localhost', 'root', '', 'badcharset');

        $this->assertNull(AccessHelper::GetNonPublicMockProperty(
            Connection::class,
            $connection,
            'handle'
        ));
    }

    function testConstructSucceedsWithoutCharset()
    {
        $handle = $this->createMock(MySQLiHandle::class);
        $handle->expects($this->never())
            ->method('__call')
            ->with('close');

        $connection = $this->getMockBuilder(Connection::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['createHandle', 'setCharset'])
            ->getMock();
        $connection->expects($this->once())
            ->method('createHandle')
            ->with('localhost', 'root', '')
            ->willReturn($handle);
        $connection->expects($this->never())
            ->method('setCharset');

        $connection->__construct('localhost', 'root', '');

        $this->assertSame($handle, AccessHelper::GetNonPublicMockProperty(
            Connection::class,
            $connection,
            'handle'
        ));
    }

    function testConstructSucceedsWithNullCharset()
    {
        $handle = $this->createMock(MySQLiHandle::class);
        $handle->expects($this->never())
            ->method('__call')
            ->with('close');

        $connection = $this->getMockBuilder(Connection::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['createHandle', 'setCharset'])
            ->getMock();
        $connection->expects($this->once())
            ->method('createHandle')
            ->with('localhost', 'root', '')
            ->willReturn($handle);
        $connection->expects($this->never())
            ->method('setCharset');

        $connection->__construct('localhost', 'root', '', null);

        $this->assertSame($handle, AccessHelper::GetNonPublicMockProperty(
            Connection::class,
            $connection, 'handle'
        ));
    }

    function testConstructSucceedsWithCharset()
    {
        $handle = $this->createMock(MySQLiHandle::class);
        $handle->expects($this->never())
            ->method('__call')
            ->with('close');

        $connection = $this->getMockBuilder(Connection::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['createHandle', 'setCharset'])
            ->getMock();
        $connection->expects($this->once())
            ->method('createHandle')
            ->with('localhost', 'root', '')
            ->willReturn($handle);
        $connection->expects($this->once())
            ->method('setCharset')
            ->with($handle, 'utf8mb4');

        $connection->__construct('localhost', 'root', '', 'utf8mb4');

        $this->assertSame($handle, AccessHelper::GetNonPublicMockProperty(
            Connection::class,
            $connection,
            'handle'
        ));
    }

    #endregion __construct

    #region createHandle -------------------------------------------------------

    function testCreateHandleThrowsIfNewMysqliFailsWhenReportModeIsOff()
    {
        $handle = $this->createMock(MySQLiHandle::class);
        $handle->expects($this->any())
            ->method('__get')
            ->willReturnMap([
                ['connect_error', 'Access denied'],
                ['connect_errno', 1045]
            ]);

        $connection = $this->getMockBuilder(Connection::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['_new_mysqli'])
            ->getMock();
        $connection->expects($this->once())
            ->method('_new_mysqli')
            ->with('localhost', 'root', '')
            ->willReturn($handle);

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Access denied');
        $this->expectExceptionCode(1045);

        AccessHelper::CallNonPublicMethod(
            $connection,
            'createHandle',
            ['localhost', 'root', '']
        );
    }

    function testCreateHandleThrowsIfNewMysqliFailsWhenReportModeIsStrict()
    {
        $handle = $this->createMock(MySQLiHandle::class);
        $handle->expects($this->never())
            ->method('__get');

        $connection = $this->getMockBuilder(Connection::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['_new_mysqli'])
            ->getMock();
        $connection->expects($this->once())
            ->method('_new_mysqli')
            ->with('localhost', 'root', '')
            ->willThrowException(new \mysqli_sql_exception('Access denied', 1045));

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Access denied');
        $this->expectExceptionCode(1045);

        AccessHelper::CallNonPublicMethod(
            $connection,
            'createHandle',
            ['localhost', 'root', '']
        );
    }

    function testCreateHandleReturnsHandleWhenNewMysqliSucceeds()
    {
        $handle = $this->createMock(MySQLiHandle::class);
        $handle->expects($this->any())
            ->method('__get')
            ->with('connect_errno')
            ->willReturn(0);

        $connection = $this->getMockBuilder(Connection::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['_new_mysqli'])
            ->getMock();
        $connection->expects($this->once())
            ->method('_new_mysqli')
            ->with('localhost', 'root', '')
            ->willReturn($handle);

        $this->assertSame($handle, AccessHelper::CallNonPublicMethod(
            $connection,
            'createHandle',
            ['localhost', 'root', '']
        ));
    }

    #endregion createHandle

    #region setCharset ---------------------------------------------------------

    function testSetCharsetThrowsIfHandleSetCharsetFailsWhenReportModeIsOff()
    {
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

        $connection = $this->createMock(Connection::class);

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Unknown character set');
        $this->expectExceptionCode(2019);

        AccessHelper::CallNonPublicMethod(
            $connection,
            'setCharset',
            [$handle, 'badcharset']
        );
    }

    function testSetCharsetThrowsIfHandleSetCharsetFailsWhenReportModeIsStrict()
    {
        $handle = $this->createMock(MySQLiHandle::class);
        $handle->expects($this->once())
            ->method('__call')
            ->with('set_charset', ['badcharset'])
            ->willThrowException(new \mysqli_sql_exception('Unknown character set', 2019));
        $handle->expects($this->never())
            ->method('__get');

        $connection = $this->createMock(Connection::class);

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Unknown character set');
        $this->expectExceptionCode(2019);

        AccessHelper::CallNonPublicMethod(
            $connection,
            'setCharset',
            [$handle, 'badcharset']
        );
    }

    function testSetCharsetSucceedsWhenHandleSetCharsetSucceeds()
    {
        $handle = $this->createMock(MySQLiHandle::class);
        $handle->expects($this->once())
            ->method('__call')
            ->with('set_charset', ['utf8mb4'])
            ->willReturn(true);

        $connection = $this->createMock(Connection::class);

        AccessHelper::CallNonPublicMethod(
            $connection,
            'setCharset',
            [$handle, 'utf8mb4']
        );
    }

    #endregion setCharset

    #region SelectDatabase -----------------------------------------------------

    function testSelectDatabaseThrowsIfHandleSelectDbFailsWhenReportModeIsOff()
    {
        $handle = $this->createMock(MySQLiHandle::class);
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

        $connection = $this->getMockBuilder(Connection::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['createHandle'])
            ->getMock();
        $connection->expects($this->once())
            ->method('createHandle')
            ->with('localhost', 'root', '')
            ->willReturn($handle);
        $connection->__construct('localhost', 'root', '');

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Unknown database');
        $this->expectExceptionCode(1049);

        $connection->SelectDatabase('nonexistent_db');
    }

    function testSelectDatabaseThrowsIfHandleSelectDbFailsWhenReportModeIsStrict()
    {
        $handle = $this->createMock(MySQLiHandle::class);
        $handle->expects($this->once())
            ->method('__call')
            ->with('select_db', ['nonexistent_db'])
            ->willThrowException(new \mysqli_sql_exception('Unknown database', 1049));
        $handle->expects($this->never())
            ->method('__get');

        $connection = $this->getMockBuilder(Connection::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['createHandle'])
            ->getMock();
        $connection->expects($this->once())
            ->method('createHandle')
            ->with('localhost', 'root', '')
            ->willReturn($handle);
        $connection->__construct('localhost', 'root', '');

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Unknown database');
        $this->expectExceptionCode(1049);

        $connection->SelectDatabase('nonexistent_db');
    }

    function testSelectDatabaseSucceedsWhenHandleSelectDbSucceeds()
    {
        $handle = $this->createMock(MySQLiHandle::class);
        $handle->expects($this->once())
            ->method('__call')
            ->with('select_db', ['test_db'])
            ->willReturn(true);

        $connection = $this->getMockBuilder(Connection::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['createHandle'])
            ->getMock();
        $connection->expects($this->once())
            ->method('createHandle')
            ->with('localhost', 'root', '')
            ->willReturn($handle);
        $connection->__construct('localhost', 'root', '');

        $connection->SelectDatabase('test_db');
    }

    #endregion SelectDatabase

    #region Execute (PHP < 8.2.0) ----------------------------------------------

    #[RequiresPhp('< 8.2.0')]
    function testExecuteThrowsWhenPrepareStatementThrows()
    {
        $connection = $this->getMockBuilder(Connection::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['prepareStatement'])
            ->getMock();
        $connection->expects($this->once())
            ->method('prepareStatement')
            ->with('SELECT * FROM `users`')
            ->willThrowException(new \RuntimeException('Syntax error', 1064));

        $query = $this->createQuery('SELECT * FROM `users`');

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Syntax error');
        $this->expectExceptionCode(1064);

        $connection->Execute($query);
    }

    #[RequiresPhp('< 8.2.0')]
    function testExecuteThrowsWhenExecuteStatementThrows()
    {
        $statement = $this->createMock(MySQLiStatement::class);
        $statement->expects($this->once())
            ->method('__call')
            ->with('close');

        $connection = $this->getMockBuilder(Connection::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['prepareStatement', 'executeStatement'])
            ->getMock();
        $connection->expects($this->once())
            ->method('prepareStatement')
            ->with('SELECT * FROM `users`')
            ->willReturn($statement);
        $connection->expects($this->once())
            ->method('executeStatement')
            ->willThrowException(new \RuntimeException('Execution error', 1064));

        $query = $this->createQuery('SELECT * FROM `users`');

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Execution error');
        $this->expectExceptionCode(1064);

        $connection->Execute($query);
    }

    #[RequiresPhp('< 8.2.0')]
    function testExecuteThrowsWhenGetStatementResultThrows()
    {
        $statement = $this->createMock(MySQLiStatement::class);
        $statement->expects($this->once())
            ->method('__call')
            ->with('close');

        $connection = $this->getMockBuilder(Connection::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['prepareStatement', 'executeStatement', 'getStatementResult'])
            ->getMock();
        $connection->expects($this->once())
            ->method('prepareStatement')
            ->with('SELECT * FROM `users`')
            ->willReturn($statement);
        $connection->expects($this->once())
            ->method('executeStatement');
        $connection->expects($this->once())
            ->method('getStatementResult')
            ->willThrowException(new \RuntimeException('Result error', 1064));

        $query = $this->createQuery('SELECT * FROM `users`');

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Result error');
        $this->expectExceptionCode(1064);

        $connection->Execute($query);
    }

    #[RequiresPhp('< 8.2.0')]
    #[DataProvider('nullOrResultDataProvider')]
    function testExecuteReturnsWhatGetStatementResultReturns($result)
    {
        $statement = $this->createMock(MySQLiStatement::class);
        $statement->expects($this->once())
            ->method('__call')
            ->with('close');

        $connection = $this->getMockBuilder(Connection::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['prepareStatement', 'executeStatement', 'getStatementResult'])
            ->getMock();
        $connection->expects($this->once())
            ->method('prepareStatement')
            ->with('SELECT * FROM `users`')
            ->willReturn($statement);
        $connection->expects($this->once())
            ->method('executeStatement');
        $connection->expects($this->once())
            ->method('getStatementResult')
            ->willReturn($result);

        $query = $this->createQuery('SELECT * FROM `users`');

        $this->assertSame($result, $connection->Execute($query));
    }

    #endregion Execute (PHP < 8.2.0)

    #region prepareStatement ---------------------------------------------------

    #[RequiresPhp('< 8.2.0')]
    function testPrepareStatementThrowsIfHandlePrepareFailsWhenReportModeIsOff()
    {
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

        $connection = $this->createMock(Connection::class);
        AccessHelper::SetNonPublicMockProperty(
            Connection::class,
            $connection,
            'handle',
            $handle
        );

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Syntax error');
        $this->expectExceptionCode(1064);

        AccessHelper::CallNonPublicMethod(
            $connection,
            'prepareStatement',
            ['SELECT * FROM `users`']
        );
    }

    #[RequiresPhp('< 8.2.0')]
    function testPrepareStatementThrowsIfHandlePrepareFailsWhenReportModeIsStrict()
    {
        $handle = $this->createMock(MySQLiHandle::class);
        $handle->expects($this->once())
            ->method('prepare')
            ->with('SELECT * FROM `users`')
            ->willThrowException(new \mysqli_sql_exception('Syntax error', 1064));
        $handle->expects($this->never())
            ->method('__get');

        $connection = $this->createMock(Connection::class);
        AccessHelper::SetNonPublicMockProperty(
            Connection::class,
            $connection,
            'handle',
            $handle
        );

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Syntax error');
        $this->expectExceptionCode(1064);

        AccessHelper::CallNonPublicMethod(
            $connection,
            'prepareStatement',
            ['SELECT * FROM `users`']
        );
    }

    #[RequiresPhp('< 8.2.0')]
    function testPrepareStatementReturnsStatementWhenHandlePrepareSucceeds()
    {
        $statement = $this->createStub(MySQLiStatement::class);

        $handle = $this->createMock(MySQLiHandle::class);
        $handle->expects($this->once())
            ->method('prepare')
            ->with('SELECT * FROM `users`')
            ->willReturn($statement);

        $connection = $this->createMock(Connection::class);
        AccessHelper::SetNonPublicMockProperty(
            Connection::class,
            $connection,
            'handle',
            $handle
        );

        $this->assertSame($statement, AccessHelper::CallNonPublicMethod(
            $connection,
            'prepareStatement',
            ['SELECT * FROM `users`']
        ));
    }

    #endregion prepareStatement

    #region executeStatement ---------------------------------------------------

    #[RequiresPhp('< 8.2.0')]
    function testExecuteStatementThrowsIfStatementExecuteFailsWhenReportModeIsOff()
    {
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

        $connection = $this->createMock(Connection::class);

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Execution error');
        $this->expectExceptionCode(1064);

        AccessHelper::CallNonPublicMethod(
            $connection,
            'executeStatement',
            [$statement, [42, 'John']]
        );
    }

    #[RequiresPhp('< 8.2.0')]
    function testExecuteStatementThrowsIfStatementExecuteFailsWhenReportModeIsStrict()
    {
        $statement = $this->createMock(MySQLiStatement::class);
        $statement->expects($this->once())
            ->method('__call')
            ->with('execute', [[42, 'John']])
            ->willThrowException(new \mysqli_sql_exception('Execution error', 1064));
        $statement->expects($this->never())
            ->method('__get');

        $connection = $this->createMock(Connection::class);

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Execution error');
        $this->expectExceptionCode(1064);

        AccessHelper::CallNonPublicMethod(
            $connection,
            'executeStatement',
            [$statement, [42, 'John']]
        );
    }

    #[RequiresPhp('< 8.2.0')]
    function testExecuteStatementSucceedsWhenStatementExecuteSucceeds()
    {
        $statement = $this->createMock(MySQLiStatement::class);
        $statement->expects($this->once())
            ->method('__call')
            ->with('execute', [[42, 'John']])
            ->willReturn(true);

        $connection = $this->createMock(Connection::class);

        AccessHelper::CallNonPublicMethod(
            $connection,
            'executeStatement',
            [$statement, [42, 'John']]
        );
    }

    #endregion executeStatement

    #region getStatementResult -------------------------------------------------

    #[RequiresPhp('< 8.2.0')]
    function testGetStatementResultThrowsIfStatementGetResultFailsWhenReportModeIsOff()
    {
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

        $connection = $this->createMock(Connection::class);

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Result error');
        $this->expectExceptionCode(1064);

        AccessHelper::CallNonPublicMethod(
            $connection,
            'getStatementResult',
            [$statement]
        );
    }

    #[RequiresPhp('< 8.2.0')]
    function testGetStatementResultThrowsIfStatementGetResultFailsWhenReportModeIsStrict()
    {
        $statement = $this->createMock(MySQLiStatement::class);
        $statement->expects($this->once())
            ->method('get_result')
            ->willThrowException(new \mysqli_sql_exception('Result error', 1064));
        $statement->expects($this->never())
            ->method('__get');

        $connection = $this->createMock(Connection::class);

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Result error');
        $this->expectExceptionCode(1064);

        AccessHelper::CallNonPublicMethod(
            $connection,
            'getStatementResult',
            [$statement]
        );
    }

    #[RequiresPhp('< 8.2.0')]
    function testGetStatementResultReturnsNullWhenStatementGetResultReturnsFalse()
    {
        $statement = $this->createMock(MySQLiStatement::class);
        $statement->expects($this->once())
            ->method('get_result')
            ->willReturn(false);
        $statement->expects($this->any())
            ->method('__get')
            ->with('errno')
            ->willReturn(0);

        $connection = $this->createMock(Connection::class);

        $this->assertNull(AccessHelper::CallNonPublicMethod(
            $connection,
            'getStatementResult',
            [$statement]
        ));
    }

    #[RequiresPhp('< 8.2.0')]
    function testGetStatementResultReturnsResultWhenStatementGetResultReturnsResult()
    {
        $result = $this->createStub(MySQLiResult::class);

        $statement = $this->createMock(MySQLiStatement::class);
        $statement->expects($this->once())
            ->method('get_result')
            ->willReturn($result);

        $connection = $this->createMock(Connection::class);

        $this->assertSame($result, AccessHelper::CallNonPublicMethod(
            $connection,
            'getStatementResult',
            [$statement]
        ));
    }

    #endregion getStatementResult

    #region executeQuery -------------------------------------------------------

    #[RequiresPhp('>= 8.2.0')]
    function testExecuteQueryThrowsIfHandleExecuteQueryFailsWhenReportModeIsOff()
    {
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

        $connection = $this->createMock(Connection::class);
        AccessHelper::SetNonPublicMockProperty(
            Connection::class,
            $connection,
            'handle',
            $handle
        );

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Syntax error');
        $this->expectExceptionCode(1064);

        AccessHelper::CallNonPublicMethod(
            $connection,
            'executeQuery',
            ['SELECT * FROM `users` WHERE id = ? AND name = ?;', [42, 'John']]
        );
    }

    #[RequiresPhp('>= 8.2.0')]
    function testExecuteQueryThrowsIfHandleExecuteQueryFailsWhenReportModeIsStrict()
    {
        $handle = $this->createMock(MySQLiHandle::class);
        $handle->expects($this->once())
            ->method('execute_query')
            ->with('SELECT * FROM `users` WHERE id = ? AND name = ?;', [42, 'John'])
            ->willThrowException(new \mysqli_sql_exception('Syntax error', 1064));
        $handle->expects($this->never())
            ->method('__get');

        $connection = $this->createMock(Connection::class);
        AccessHelper::SetNonPublicMockProperty(
            Connection::class,
            $connection,
            'handle',
            $handle
        );

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Syntax error');
        $this->expectExceptionCode(1064);

        AccessHelper::CallNonPublicMethod(
            $connection,
            'executeQuery',
            ['SELECT * FROM `users` WHERE id = ? AND name = ?;', [42, 'John']]
        );
    }

    #[RequiresPhp('>= 8.2.0')]
    function testExecuteQueryReturnsNullWhenHandleExecuteQueryReturnsTrue()
    {
        $handle = $this->createMock(MySQLiHandle::class);
        $handle->expects($this->once())
            ->method('execute_query')
            ->with('SELECT * FROM `users` WHERE id = ? AND name = ?;', [42, 'John'])
            ->willReturn(true);

        $connection = $this->createMock(Connection::class);
        AccessHelper::SetNonPublicMockProperty(
            Connection::class,
            $connection,
            'handle',
            $handle
        );

        $this->assertNull(AccessHelper::CallNonPublicMethod(
            $connection,
            'executeQuery',
            ['SELECT * FROM `users` WHERE id = ? AND name = ?;', [42, 'John']]
        ));
    }

    #[RequiresPhp('>= 8.2.0')]
    function testExecuteQueryReturnsResultWhenHandleExecuteQueryReturnsResult()
    {
        $result = $this->createStub(MySQLiResult::class);

        $handle = $this->createMock(MySQLiHandle::class);
        $handle->expects($this->once())
            ->method('execute_query')
            ->with('SELECT * FROM `users` WHERE id = ? AND name = ?;', [42, 'John'])
            ->willReturn($result);

        $connection = $this->createMock(Connection::class);
        AccessHelper::SetNonPublicMockProperty(
            Connection::class,
            $connection,
            'handle',
            $handle
        );

        $this->assertSame($result, AccessHelper::CallNonPublicMethod(
            $connection,
            'executeQuery',
            ['SELECT * FROM `users` WHERE id = ? AND name = ?;', [42, 'John']]
        ));
    }

    #endregion executeQuery

    #region Execute (PHP >= 8.2.0) ---------------------------------------------

    #[RequiresPhp('>= 8.2.0')]
    function testExecuteThrowsWhenExecuteQueryThrows()
    {
        $connection = $this->getMockBuilder(Connection::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['executeQuery'])
            ->getMock();
        $connection->expects($this->once())
            ->method('executeQuery')
            ->with('SELECT * FROM `users`', [])
            ->willThrowException(new \RuntimeException('Syntax error', 1064));

        $query = $this->createQuery('SELECT * FROM `users`');

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Syntax error');
        $this->expectExceptionCode(1064);

        $connection->Execute($query);
    }

    #[RequiresPhp('>= 8.2.0')]
    #[DataProvider('nullOrResultDataProvider')]
    function testExecuteReturnsWhatExecuteQueryReturns($result)
    {
        $connection = $this->getMockBuilder(Connection::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['executeQuery'])
            ->getMock();
        $connection->expects($this->once())
            ->method('executeQuery')
            ->with('SELECT * FROM `users`', [])
            ->willReturn($result);

        $query = $this->createQuery('SELECT * FROM `users`');

        $this->assertSame($result, $connection->Execute($query));
    }

    #endregion Execute (PHP >= 8.2.0)

    #region transformQuery -----------------------------------------------------

    #[DataProvider('transformQueryDataProvider')]
    public function testTransformQuery(string $expectedSql, array $expectedValues,
        string $sql, array $bindings)
    {
        $query = $this->createQuery($sql, $bindings);

        $transformedQuery = AccessHelper::CallNonPublicStaticMethod(
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
