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

#[CoversClass(Connection::class)]
class ConnectionTest extends TestCase
{
    private ?Connection $connection = null;
    private ?MySQLiHandle $handle = null;

    /**
     * Creates a partially mocked Connection object with its internal
     * MySQLiHandle object mocked. The Connection object's constructor
     * is NOT invoked at this point.
     */
    protected function setUp(): void
    {
        $this->handle = $this->createMock(MySQLiHandle::class);
        $this->connection = $this->getMockBuilder(Connection::class)
            ->onlyMethods(['_new_mysqli', '_prepare', '_execute_query'])
            ->disableOriginalConstructor()
            ->getMock();
        $this->connection->expects($this->once())
            ->method('_new_mysqli')
            ->willReturn($this->handle);
    }

    /**
     * Ensures that the Connection object's destructor is called and its
     * internal MySQLiHandle object is closed.
     */
    protected function tearDown(): void
    {
        $this->handle->expects($this->once())
            ->method('__call')
            ->with('close');
        $this->connection = null;
        $this->handle = null;
    }

    private function createQueryObject(string $sql, array $bindings = []): Query
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

    function testConstructThrowsExceptionWhenConnectionFails()
    {
        $this->handle->expects($invokedCount = $this->exactly(3))
            ->method('__get')
            ->willReturnCallback(function($name) use($invokedCount) {
                switch ($invokedCount->numberOfInvocations()) {
                case 1:
                    $this->assertSame('connect_errno', $name);
                    return 1045;
                case 2:
                    $this->assertSame('connect_error', $name);
                    return 'Access denied for user';
                case 3:
                    $this->assertSame('connect_errno', $name);
                    return 1045;
                }
            });

        $this->connection->expects($this->once())
            ->method('_new_mysqli')
            ->with('localhost', 'user1', 'pass123');

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Access denied for user');
        $this->expectExceptionCode(1045);

        AccessHelper::CallNonPublicConstructor(
            $this->connection,
            [ 'localhost', 'user1', 'pass123']
        );
    }

    function testConstructThrowsExceptionWithInvalidCharset()
    {
        $this->handle->expects($invokedCount = $this->exactly(3))
            ->method('__get')
            ->willReturnCallback(function($name) use($invokedCount) {
                switch ($invokedCount->numberOfInvocations()) {
                case 1:
                    $this->assertSame('connect_errno', $name);
                    return 0;
                case 2:
                    $this->assertSame('error', $name);
                    return 'Unknown character set';
                case 3:
                    $this->assertSame('errno', $name);
                    return 2019;
                }
            });
        $this->handle->expects($invokedCount = $this->exactly(2))
            ->method('__call')
            ->willReturnCallback(function($name, $arguments) use($invokedCount) {
                switch ($invokedCount->numberOfInvocations()) {
                case 1:
                    $this->assertSame('set_charset', $name);
                    $this->assertSame('invalidmb4', $arguments[0]);
                    return false;
                case 2:
                    $this->assertSame('close', $name);
                    return null;
                }
            });

        $this->connection->expects($this->once())
            ->method('_new_mysqli')
            ->with('localhost', 'user1', 'pass123');

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Unknown character set');
        $this->expectExceptionCode(2019);

        AccessHelper::CallNonPublicConstructor(
            $this->connection,
            [ 'localhost', 'user1', 'pass123', 'invalidmb4']
        );
    }

    function testConstructSucceedsWithoutCharset()
    {
        $this->handle->expects($this->once())
            ->method('__get')
            ->with('connect_errno')
            ->willReturn(0);
        $this->handle->expects($this->never())
            ->method('__call')
            ->with('set_charset');

        $this->connection->expects($this->once())
            ->method('_new_mysqli')
            ->with('localhost', 'user1', 'pass123');

        AccessHelper::CallNonPublicConstructor(
            $this->connection,
            [ 'localhost', 'user1', 'pass123']
        );
    }

    function testConstructSucceedsWithCharset()
    {
        $this->handle->expects($this->once())
            ->method('__get')
            ->with('connect_errno')
            ->willReturn(0);
        $this->handle->expects($this->once())
            ->method('__call')
            ->with('set_charset', ['utf8mb4'])
            ->willReturn(true);

        $this->connection->expects($this->once())
            ->method('_new_mysqli')
            ->with('localhost', 'user1', 'pass123');

        AccessHelper::CallNonPublicConstructor(
            $this->connection,
            [ 'localhost', 'user1', 'pass123', 'utf8mb4']
        );
    }

    #endregion __construct

    #region SelectDatabase -----------------------------------------------------

    function testSelectDatabaseThrowsExceptionWhenSelectionFails()
    {
        $this->handle->expects($invokedCount = $this->exactly(3))
            ->method('__get')
            ->willReturnCallback(function($name) use($invokedCount) {
                switch ($invokedCount->numberOfInvocations()) {
                case 1:
                    $this->assertSame('connect_errno', $name);
                    return 0;
                case 2:
                    $this->assertSame('error', $name);
                    return 'Unknown database';
                case 3:
                    $this->assertSame('errno', $name);
                    return 1049;
                }
            });
        $this->handle->expects($invokedCount = $this->once())
            ->method('__call')
            ->with('select_db', ['nonexistent_db'])
            ->willReturn(false);

        AccessHelper::CallNonPublicConstructor(
            $this->connection,
            ['', '', '']
        );

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Unknown database');
        $this->expectExceptionCode(1049);

        $this->connection->SelectDatabase('nonexistent_db');
    }

    function testSelectDatabaseSucceedsWhenSelectionSucceeds()
    {
        $this->handle->expects($this->once())
            ->method('__get')
            ->with('connect_errno')
            ->willReturn(0);
        $this->handle->expects($this->once())
            ->method('__call')
            ->with('select_db', ['test_db'])
            ->willReturn(true);

        AccessHelper::CallNonPublicConstructor(
            $this->connection,
            ['', '', '']
        );

        $this->connection->SelectDatabase('test_db');
    }

    #endregion SelectDatabase

    #region Execute (PHP < 8.2.0) ----------------------------------------------

    #[RequiresPhp('< 8.2.0')]
    function testExecuteThrowsExceptionWhenStatementPreparationFails()
    {
        $this->handle->expects($invokedCount = $this->exactly(3))
            ->method('__get')
            ->willReturnCallback(function($name) use($invokedCount) {
                switch ($invokedCount->numberOfInvocations()) {
                case 1:
                    $this->assertSame('connect_errno', $name);
                    return 0;
                case 2:
                    $this->assertSame('error', $name);
                    return 'Syntax error';
                case 3:
                    $this->assertSame('errno', $name);
                    return 1064;
                }
            });

        AccessHelper::CallNonPublicConstructor(
            $this->connection,
            ['', '', '']
        );

        $this->connection->expects($this->once())
            ->method('_prepare')
            ->with('SELECT * FROM `users`')
            ->willReturn(null);

        $query = $this->createQueryObject('SELECT * FROM `users`');

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Syntax error');
        $this->expectExceptionCode(1064);

        $this->connection->Execute($query);
    }

    #[RequiresPhp('< 8.2.0')]
    function testExecuteThrowsExceptionWhenParameterBindingFails()
    {
        $this->handle->expects($this->once())
            ->method('__get')
            ->with('connect_errno')
            ->willReturn(0);

        AccessHelper::CallNonPublicConstructor(
            $this->connection,
            ['', '', '']
        );

        $statement = $this->createMock(MySQLiStatement::class);
        $statement->expects($invokedCount = $this->exactly(2))
            ->method('__get')
            ->willReturnCallback(function($name) use($invokedCount) {
                switch ($invokedCount->numberOfInvocations()) {
                case 1:
                    $this->assertSame('error', $name);
                    return 'Invalid parameter';
                case 2:
                    $this->assertSame('errno', $name);
                    return 2031;
                }
            });
        $statement->expects($invokedCount = $this->exactly(2))
            ->method('__call')
            ->willReturnCallback(function($name, $arguments) use($invokedCount) {
                switch ($invokedCount->numberOfInvocations()) {
                case 1:
                    $this->assertSame('bind_param', $name);
                    $this->assertCount(3, $arguments);
                    $this->assertSame('is', $arguments[0]);
                    $this->assertSame(42, $arguments[1]);
                    $this->assertSame('John', $arguments[2]);
                    return false;
                case 2:
                    $this->assertSame('close', $name);
                    return null;
                }
            });

        $this->connection->expects($this->once())
            ->method('_prepare')
            ->with('SELECT * FROM `users` WHERE id = ? AND name = ?')
            ->willReturn($statement);

        $query = $this->createQueryObject(
            'SELECT * FROM `users` WHERE id = :id AND name = :name',
            ['id' => 42, 'name' => 'John']
        );

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Invalid parameter');
        $this->expectExceptionCode(2031);

        $this->connection->Execute($query);
    }

    #[RequiresPhp('< 8.2.0')]
    function testExecuteThrowsExceptionWhenStatementExecutionFails()
    {
        $this->handle->expects($this->once())
            ->method('__get')
            ->with('connect_errno')
            ->willReturn(0);

        AccessHelper::CallNonPublicConstructor(
            $this->connection,
            ['', '', '']
        );

        $statement = $this->createMock(MySQLiStatement::class);
        $statement->expects($invokedCount = $this->exactly(2))
            ->method('__get')
            ->willReturnCallback(function($name) use($invokedCount) {
                switch ($invokedCount->numberOfInvocations()) {
                case 1:
                    $this->assertSame('error', $name);
                    return 'Execution error';
                case 2:
                    $this->assertSame('errno', $name);
                    return 1064;
                }
            });
        $statement->expects($invokedCount = $this->exactly(3))
            ->method('__call')
            ->willReturnCallback(function($name, $arguments) use($invokedCount) {
                switch ($invokedCount->numberOfInvocations()) {
                case 1:
                    $this->assertSame('bind_param', $name);
                    $this->assertCount(3, $arguments);
                    $this->assertSame('is', $arguments[0]);
                    $this->assertSame(42, $arguments[1]);
                    $this->assertSame('John', $arguments[2]);
                    return true;
                case 2:
                    $this->assertSame('execute', $name);
                    $this->assertEmpty($arguments);
                    return false;
                case 3:
                    $this->assertSame('close', $name);
                    return null;
                }
            });

        $this->connection->expects($this->once())
            ->method('_prepare')
            ->with('SELECT * FROM `users` WHERE id = ? AND name = ?')
            ->willReturn($statement);

        $query = $this->createQueryObject(
            'SELECT * FROM `users` WHERE id = :id AND name = :name',
            ['id' => 42, 'name' => 'John']
        );

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Execution error');
        $this->expectExceptionCode(1064);

        $this->connection->Execute($query);
    }

    #[RequiresPhp('< 8.2.0')]
    function testExecuteThrowsExceptionWhenResultRetrievalFails()
    {
        $this->handle->expects($this->once())
            ->method('__get')
            ->with('connect_errno')
            ->willReturn(0);

        AccessHelper::CallNonPublicConstructor(
            $this->connection,
            ['', '', '']
        );

        $statement = $this->createMock(MySQLiStatement::class);
        $statement->expects($invokedCount = $this->exactly(3))
            ->method('__get')
            ->willReturnCallback(function($name) use($invokedCount) {
                switch ($invokedCount->numberOfInvocations()) {
                case 1:
                    $this->assertSame('errno', $name);
                    return 2008;
                case 2:
                    $this->assertSame('error', $name);
                    return 'Out of memory';
                case 3:
                    $this->assertSame('errno', $name);
                    return 2008;
                }
            });
        $statement->expects($invokedCount = $this->exactly(3))
            ->method('__call')
            ->willReturnCallback(function($name, $arguments) use($invokedCount) {
                switch ($invokedCount->numberOfInvocations()) {
                case 1:
                    $this->assertSame('bind_param', $name);
                    $this->assertCount(3, $arguments);
                    $this->assertSame('is', $arguments[0]);
                    $this->assertSame(42, $arguments[1]);
                    $this->assertSame('John', $arguments[2]);
                    return true;
                case 2:
                    $this->assertSame('execute', $name);
                    $this->assertEmpty($arguments);
                    return true;
                case 3:
                    $this->assertSame('close', $name);
                    return null;
                }
            });
        $statement->expects($this->once())
            ->method('get_result')
            ->willReturn(false);

        $this->connection->expects($this->once())
            ->method('_prepare')
            ->with('SELECT * FROM `users` WHERE id = ? AND name = ?')
            ->willReturn($statement);

        $query = $this->createQueryObject(
            'SELECT * FROM `users` WHERE id = :id AND name = :name',
            ['id' => 42, 'name' => 'John']
        );

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Out of memory');
        $this->expectExceptionCode(2008);

        $this->connection->Execute($query);
    }

    #[RequiresPhp('< 8.2.0')]
    function testExecuteReturnsNullWhenResultRetrievalSucceedsWithNoResultSet()
    {
        $this->handle->expects($this->once())
            ->method('__get')
            ->with('connect_errno')
            ->willReturn(0);

        AccessHelper::CallNonPublicConstructor(
            $this->connection,
            ['', '', '']
        );

        $statement = $this->createMock(MySQLiStatement::class);
        $statement->expects($this->once())
            ->method('__get')
            ->with('errno')
            ->willReturn(0);
        $statement->expects($invokedCount = $this->exactly(3))
            ->method('__call')
            ->willReturnCallback(function($name, $arguments) use($invokedCount) {
                switch ($invokedCount->numberOfInvocations()) {
                case 1:
                    $this->assertSame('bind_param', $name);
                    $this->assertCount(3, $arguments);
                    $this->assertSame('is', $arguments[0]);
                    $this->assertSame(42, $arguments[1]);
                    $this->assertSame('John', $arguments[2]);
                    return true;
                case 2:
                    $this->assertSame('execute', $name);
                    $this->assertEmpty($arguments);
                    return true;
                case 3:
                    $this->assertSame('close', $name);
                    return null;
                }
            });
        $statement->expects($this->once())
            ->method('get_result')
            ->willReturn(false);

        $this->connection->expects($this->once())
            ->method('_prepare')
            ->with('SELECT * FROM `users` WHERE id = ? AND name = ?')
            ->willReturn($statement);

        $query = $this->createQueryObject(
            'SELECT * FROM `users` WHERE id = :id AND name = :name',
            ['id' => 42, 'name' => 'John']
        );

        $result = $this->connection->Execute($query);
        $this->assertNull($result);
    }

    #[RequiresPhp('< 8.2.0')]
    function testExecuteReturnsResultObjectWhenResultRetrievalSucceedsWithResultSet()
    {
        $this->handle->expects($this->once())
            ->method('__get')
            ->with('connect_errno')
            ->willReturn(0);

        AccessHelper::CallNonPublicConstructor(
            $this->connection,
            ['', '', '']
        );

        $statement = $this->createMock(MySQLiStatement::class);
        $statement->expects($this->never())
            ->method('__get');
        $statement->expects($invokedCount = $this->exactly(3))
            ->method('__call')
            ->willReturnCallback(function($name, $arguments) use($invokedCount) {
                switch ($invokedCount->numberOfInvocations()) {
                case 1:
                    $this->assertSame('bind_param', $name);
                    $this->assertCount(3, $arguments);
                    $this->assertSame('is', $arguments[0]);
                    $this->assertSame(42, $arguments[1]);
                    $this->assertSame('John', $arguments[2]);
                    return true;
                case 2:
                    $this->assertSame('execute', $name);
                    $this->assertEmpty($arguments);
                    return true;
                case 3:
                    $this->assertSame('close', $name);
                    return null;
                }
            });
        $statement->expects($this->once())
            ->method('get_result')
            ->willReturn($this->createMock(MySQLiResult::class));

        $this->connection->expects($this->once())
            ->method('_prepare')
            ->with('SELECT * FROM `users` WHERE id = ? AND name = ?')
            ->willReturn($statement);

        $query = $this->createQueryObject(
            'SELECT * FROM `users` WHERE id = :id AND name = :name',
            ['id' => 42, 'name' => 'John']
        );

        $result = $this->connection->Execute($query);
        $this->assertInstanceOf(MySQLiResult::class, $result);
    }

    #endregion Execute (PHP < 8.2.0)

    #region Execute (PHP >= 8.2.0) ---------------------------------------------

    #[RequiresPhp('>= 8.2.0')]
    function testExecuteThrowsExceptionWhenQueryExecutionFails()
    {
        $this->handle->expects($invokedCount = $this->exactly(3))
            ->method('__get')
            ->willReturnCallback(function($name) use($invokedCount) {
                switch ($invokedCount->numberOfInvocations()) {
                case 1:
                    $this->assertSame('connect_errno', $name);
                    return 0;
                case 2:
                    $this->assertSame('error', $name);
                    return 'Syntax error';
                case 3:
                    $this->assertSame('errno', $name);
                    return 1064;
                }
            });

        AccessHelper::CallNonPublicConstructor(
            $this->connection,
            ['', '', '']
        );

        $this->connection->expects($this->once())
            ->method('_execute_query')
            ->with('SELECT * FROM `users` WHERE id = ? AND name = ?', [42, 'John'])
            ->willReturn(false);

        $query = $this->createQueryObject(
            'SELECT * FROM `users` WHERE id = :id AND name = :name',
            ['id' => 42, 'name' => 'John']
        );

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Syntax error');
        $this->expectExceptionCode(1064);

        $this->connection->Execute($query);
    }

    #[RequiresPhp('>= 8.2.0')]
    function testExecuteReturnsNullWhenQueryExecutionSucceedsWithNoResultSet()
    {
        $this->handle->expects($this->once())
            ->method('__get')
            ->with('connect_errno')
            ->willReturn(0);

        AccessHelper::CallNonPublicConstructor(
            $this->connection,
            ['', '', '']
        );

        $this->connection->expects($this->once())
            ->method('_execute_query')
            ->with('SELECT * FROM `users` WHERE id = ? AND name = ?', [42, 'John'])
            ->willReturn(true);

        $query = $this->createQueryObject(
            'SELECT * FROM `users` WHERE id = :id AND name = :name',
            ['id' => 42, 'name' => 'John']
        );

        $result = $this->connection->Execute($query);
        $this->assertNull($result);
    }

    #[RequiresPhp('>= 8.2.0')]
    function testExecuteReturnsResultObjectWhenQueryExecutionSucceedsWithResultSet()
    {
        $this->handle->expects($this->once())
            ->method('__get')
            ->with('connect_errno')
            ->willReturn(0);

        AccessHelper::CallNonPublicConstructor(
            $this->connection,
            ['', '', '']
        );

        $this->connection->expects($this->once())
            ->method('_execute_query')
            ->with('SELECT * FROM `users` WHERE id = ? AND name = ?', [42, 'John'])
            ->willReturn($this->createMock(MySQLiResult::class));

        $query = $this->createQueryObject(
            'SELECT * FROM `users` WHERE id = :id AND name = :name',
            ['id' => 42, 'name' => 'John']
        );

        $result = $this->connection->Execute($query);
        $this->assertInstanceOf(MySQLiResult::class, $result);
    }

    #endregion Execute (PHP >= 8.2.0)

    #region transformQuery -----------------------------------------------------

    #[DataProvider('transformQueryDataProvider')]
    public function testTransformQuery(
        string $expectedSql,
        string $expectedTypes,
        array  $expectedValues,
        string $sql,
        array  $bindings
    ) {
        $this->handle->expects($this->once())
            ->method('__get')
            ->with('connect_errno')
            ->willReturn(0);

        AccessHelper::CallNonPublicConstructor(
            $this->connection,
            ['', '', '']
        );

        $query = $this->createQueryObject($sql, $bindings);

        $result = AccessHelper::CallNonPublicMethod(
            $this->connection,
            'transformQuery',
            [$query]
        );
        $this->assertSame($expectedSql, $result->sql);
        $this->assertSame($expectedTypes, $result->types);
        $this->assertSame($expectedValues, $result->values);
    }

    #endregion transformQuery

    #region Data Providers -----------------------------------------------------

    static function transformQueryDataProvider()
    {
        return [
            'static sql' => [
                'expectedSql'   => 'SELECT name FROM `users` WHERE active = 1',
                'expectedTypes' => '',
                'expectedValues'=> [],
                'sql'           => 'SELECT name FROM `users` WHERE active = 1',
                'bindings'      => []
            ],
            'no placeholders' => [
                'expectedSql'   => 'SELECT * FROM `users`',
                'expectedTypes' => '',
                'expectedValues'=> [],
                'sql'           => 'SELECT * FROM `users`',
                'bindings'      => []
            ],
            'single placeholder' => [
                'expectedSql'   => 'SELECT * FROM `users` WHERE id = ?',
                'expectedTypes' => 'i',
                'expectedValues'=> [42],
                'sql'           => 'SELECT * FROM `users` WHERE id = :id',
                'bindings'      => ['id' => 42]
            ],
            'multiple placeholders' => [
                'expectedSql'   => 'SELECT * FROM `users` WHERE id = ? AND name = ? OR parent_id = ? OR duration = ?',
                'expectedTypes' => 'isid',
                'expectedValues'=> [42, 'John', 42, 32.5],
                'sql'           => 'SELECT * FROM `users` WHERE id = :id AND name = :name OR parent_id = :id OR duration = :duration',
                'bindings'      => ['id' => 42, 'name' => 'John', 'duration' => 32.5]
            ]
        ];
    }

    #endregion Data Providers
}
