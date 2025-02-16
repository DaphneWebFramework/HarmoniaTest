<?php declare(strict_types=1);
use \PHPUnit\Framework\TestCase;
use \PHPUnit\Framework\Attributes\CoversClass;
use \PHPUnit\Framework\Attributes\DataProvider;
use \PHPUnit\Framework\Attributes\RequiresPhp;

use \Harmonia\Database\Connection;

use \Harmonia\Database\MySQLiHandle;
use \Harmonia\Database\MySQLiStatement;
use \Harmonia\Database\MySQLiResult;
use \Harmonia\Database\Queries\Query;
use \TestToolkit\AccessHelper;

#[CoversClass(Connection::class)]
class ConnectionTest extends TestCase
{
    private ?Connection $connection = null;
    private ?MySQLiHandle $mysqliHandle = null;

    /**
     * Creates a partially mocked Connection object with its internal
     * MySQLiHandle object mocked. The Connection object's constructor
     * is NOT invoked at this point.
     */
    protected function setUp(): void
    {
        $this->mysqliHandle = $this->createMock(MySQLiHandle::class);
        $this->connection = $this->getMockBuilder(Connection::class)
            ->onlyMethods(['connect', 'prepareStatement', 'executeQuery'])
            ->disableOriginalConstructor()
            ->getMock();
        $this->connection->expects($this->once())
            ->method('connect')
            ->willReturn($this->mysqliHandle);
    }

    /**
     * Ensures that the Connection object's destructor is called and its
     * internal MySQLiHandle object is closed.
     */
    protected function tearDown(): void
    {
        $this->mysqliHandle->expects($this->once())
            ->method('__call')
            ->with('close');
        $this->connection = null;
        $this->mysqliHandle = null;
    }

    #region __construct --------------------------------------------------------

    function testConstructThrowsExceptionWhenConnectionFails()
    {
        $this->mysqliHandle->expects($invokedCount = $this->exactly(3))
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
            ->method('connect')
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
        $this->mysqliHandle->expects($invokedCount = $this->exactly(3))
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
        $this->mysqliHandle->expects($invokedCount = $this->exactly(2))
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
            ->method('connect')
            ->with('localhost', 'user1', 'pass123');

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Unknown character set');
        $this->expectExceptionCode(2019);

        AccessHelper::CallNonPublicConstructor(
            $this->connection,
            [ 'localhost', 'user1', 'pass123', 'invalidmb4']
        );
    }

    #endregion __construct

    #region Execute ------------------------------------------------------------

    #[RequiresPhp('< 8.2.0')]
    function testExecuteThrowsExceptionWhenStatementPreparationFails()
    {
        $this->mysqliHandle->expects($invokedCount = $this->exactly(3))
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

        AccessHelper::CallNonPublicConstructor($this->connection, ['', '', '']);

        $query = $this->getMockBuilder(Query::class)
            ->setConstructorArgs([''])
            ->onlyMethods(['buildSql'])
            ->getMock();
        $query->expects($this->once())
            ->method('buildSql')
            ->willReturn('SELECT * FROM `users`');

        $this->connection->expects($this->once())
            ->method('prepareStatement')
            ->with('SELECT * FROM `users`')
            ->willReturn(null);

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Syntax error');
        $this->expectExceptionCode(1064);

        $this->connection->Execute($query);
    }

    #[RequiresPhp('< 8.2.0')]
    function testExecuteThrowsExceptionWhenParameterBindingFails()
    {
        $this->mysqliHandle->expects($this->once())
            ->method('__get')
            ->with('connect_errno')
            ->willReturn(0);

        AccessHelper::CallNonPublicConstructor($this->connection, ['', '', '']);

        $query = $this->getMockBuilder(Query::class)
            ->setConstructorArgs([''])
            ->onlyMethods(['buildSql'])
            ->getMock();
        $query->expects($this->once())
            ->method('buildSql')
            ->willReturn('SELECT * FROM `users` WHERE id = :id AND name = :name');
        $query->Bind(['id' => 42, 'name' => 'John']);

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
            ->method('prepareStatement')
            ->with('SELECT * FROM `users` WHERE id = ? AND name = ?')
            ->willReturn($statement);

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Invalid parameter');
        $this->expectExceptionCode(2031);

        $this->connection->Execute($query);
    }

    #[RequiresPhp('< 8.2.0')]
    function testExecuteThrowsExceptionWhenStatementExecutionFails()
    {
        $this->mysqliHandle->expects($this->once())
            ->method('__get')
            ->with('connect_errno')
            ->willReturn(0);

        AccessHelper::CallNonPublicConstructor($this->connection, ['', '', '']);

        $query = $this->getMockBuilder(Query::class)
            ->setConstructorArgs([''])
            ->onlyMethods(['buildSql'])
            ->getMock();
        $query->expects($this->once())
            ->method('buildSql')
            ->willReturn('SELECT * FROM `users` WHERE id = :id AND name = :name');
        $query->Bind(['id' => 42, 'name' => 'John']);

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
            ->method('prepareStatement')
            ->with('SELECT * FROM `users` WHERE id = ? AND name = ?')
            ->willReturn($statement);

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Execution error');
        $this->expectExceptionCode(1064);

        $this->connection->Execute($query);
    }

    #[RequiresPhp('< 8.2.0')]
    function testExecuteThrowsExceptionWhenResultRetrievalFails()
    {
        $this->mysqliHandle->expects($this->once())
            ->method('__get')
            ->with('connect_errno')
            ->willReturn(0);

        AccessHelper::CallNonPublicConstructor($this->connection, ['', '', '']);

        $query = $this->getMockBuilder(Query::class)
            ->setConstructorArgs([''])
            ->onlyMethods(['buildSql'])
            ->getMock();
        $query->expects($this->once())
            ->method('buildSql')
            ->willReturn('SELECT * FROM `users` WHERE id = :id AND name = :name');
        $query->Bind(['id' => 42, 'name' => 'John']);

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
            ->willReturn(null);

        $this->connection->expects($this->once())
            ->method('prepareStatement')
            ->with('SELECT * FROM `users` WHERE id = ? AND name = ?')
            ->willReturn($statement);

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Out of memory');
        $this->expectExceptionCode(2008);

        $this->connection->Execute($query);
    }

    #[RequiresPhp('< 8.2.0')]
    function testExecuteReturnsNullWhenResultRetrievalSucceedsWithNoResultSet()
    {
        $this->mysqliHandle->expects($this->once())
            ->method('__get')
            ->with('connect_errno')
            ->willReturn(0);

        AccessHelper::CallNonPublicConstructor($this->connection, ['', '', '']);

        $query = $this->getMockBuilder(Query::class)
            ->setConstructorArgs([''])
            ->onlyMethods(['buildSql'])
            ->getMock();
        $query->expects($this->once())
            ->method('buildSql')
            ->willReturn('SELECT * FROM `users` WHERE id = :id AND name = :name');
        $query->Bind(['id' => 42, 'name' => 'John']);

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
            ->willReturn(null);

        $this->connection->expects($this->once())
            ->method('prepareStatement')
            ->with('SELECT * FROM `users` WHERE id = ? AND name = ?')
            ->willReturn($statement);

        $result = $this->connection->Execute($query);
        $this->assertNull($result);
    }

    #[RequiresPhp('< 8.2.0')]
    function testExecuteReturnsResultObjectWhenResultRetrievalSucceedsWithResultSet()
    {
        $this->mysqliHandle->expects($this->once())
            ->method('__get')
            ->with('connect_errno')
            ->willReturn(0);

        AccessHelper::CallNonPublicConstructor($this->connection, ['', '', '']);

        $query = $this->getMockBuilder(Query::class)
            ->setConstructorArgs([''])
            ->onlyMethods(['buildSql'])
            ->getMock();
        $query->expects($this->once())
            ->method('buildSql')
            ->willReturn('SELECT * FROM `users` WHERE id = :id AND name = :name');
        $query->Bind(['id' => 42, 'name' => 'John']);

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
            ->method('prepareStatement')
            ->with('SELECT * FROM `users` WHERE id = ? AND name = ?')
            ->willReturn($statement);

        $result = $this->connection->Execute($query);
        $this->assertInstanceOf(MySQLiResult::class, $result);
    }

    #[RequiresPhp('>= 8.2.0')]
    function testExecuteThrowsExceptionWhenQueryExecutionFails()
    {
        $this->mysqliHandle->expects($invokedCount = $this->exactly(3))
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

        AccessHelper::CallNonPublicConstructor($this->connection, ['', '', '']);

        $query = $this->getMockBuilder(Query::class)
            ->setConstructorArgs([''])
            ->onlyMethods(['buildSql'])
            ->getMock();
        $query->expects($this->once())
            ->method('buildSql')
            ->willReturn('SELECT * FROM `users` WHERE id = :id AND name = :name');
        $query->Bind(['id' => 42, 'name' => 'John']);

        $this->connection->expects($this->once())
            ->method('executeQuery')
            ->with('SELECT * FROM `users` WHERE id = ? AND name = ?', [42, 'John'])
            ->willReturn(false);

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Syntax error');
        $this->expectExceptionCode(1064);

        $this->connection->Execute($query);
    }

    #[RequiresPhp('>= 8.2.0')]
    function testExecuteReturnsNullWhenQueryExecutionSucceedsWithNoResultSet()
    {
        $this->mysqliHandle->expects($this->once())
            ->method('__get')
            ->with('connect_errno')
            ->willReturn(0);

        AccessHelper::CallNonPublicConstructor($this->connection, ['', '', '']);

        $query = $this->getMockBuilder(Query::class)
            ->setConstructorArgs([''])
            ->onlyMethods(['buildSql'])
            ->getMock();
        $query->expects($this->once())
            ->method('buildSql')
            ->willReturn('SELECT * FROM `users` WHERE id = :id AND name = :name');
        $query->Bind(['id' => 42, 'name' => 'John']);

        $this->connection->expects($this->once())
            ->method('executeQuery')
            ->with('SELECT * FROM `users` WHERE id = ? AND name = ?', [42, 'John'])
            ->willReturn(true);

        $result = $this->connection->Execute($query);
        $this->assertNull($result);
    }

    #[RequiresPhp('>= 8.2.0')]
    function testExecuteReturnsResultObjectWhenQueryExecutionSucceedsWithResultSet()
    {
        $this->mysqliHandle->expects($this->once())
            ->method('__get')
            ->with('connect_errno')
            ->willReturn(0);

        AccessHelper::CallNonPublicConstructor($this->connection, ['', '', '']);

        $query = $this->getMockBuilder(Query::class)
            ->setConstructorArgs([''])
            ->onlyMethods(['buildSql'])
            ->getMock();
        $query->expects($this->once())
            ->method('buildSql')
            ->willReturn('SELECT * FROM `users` WHERE id = :id AND name = :name');
        $query->Bind(['id' => 42, 'name' => 'John']);

        $this->connection->expects($this->once())
            ->method('executeQuery')
            ->with('SELECT * FROM `users` WHERE id = ? AND name = ?', [42, 'John'])
            ->willReturn($this->createMock(MySQLiResult::class));

        $result = $this->connection->Execute($query);
        $this->assertInstanceOf(MySQLiResult::class, $result);
    }

    #endregion Execute

    #region transformQuery -----------------------------------------------------

    #[DataProvider('transformQueryDataProvider')]
    public function testTransformQuery(
        string $expectedSql,
        string $expectedTypes,
        array  $expectedValues,
        string $sql,
        array  $bindings
    ) {
        // Tell the mocked MySQLiHandle object to return 0 for connect_errno.
        // This will simulate a successful connection when the Connection object
        // is constructed.
        $this->mysqliHandle->expects($this->once())
            ->method('__get')
            ->with('connect_errno')
            ->willReturn(0);

        AccessHelper::CallNonPublicConstructor($this->connection, ['', '', '']);

        // Create a Query object by telling its abstract buildSql method to
        // return the specified SQL string. This will make the ToSql method
        // to validate and return the SQL string as is.
        $query = $this->getMockBuilder(Query::class)
            ->setConstructorArgs([''])
            ->onlyMethods(['buildSql'])
            ->getMock();
        $query->expects($this->once())
            ->method('buildSql')
            ->willReturn($sql);

        // Set the bindings for the Query object. These will be substituted
        // into the SQL string where matching placeholders are found.
        $query->Bind($bindings);

        // Call the protected transformQuery method and compare the result.
        $result = AccessHelper::CallNonPublicMethod($this->connection,
            'transformQuery', [$query]);
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
