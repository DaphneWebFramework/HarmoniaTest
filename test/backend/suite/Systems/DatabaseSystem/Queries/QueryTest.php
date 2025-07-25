<?php declare(strict_types=1);
use \PHPUnit\Framework\TestCase;
use \PHPUnit\Framework\Attributes\CoversClass;

use \Harmonia\Systems\DatabaseSystem\Queries\Query;

use \TestToolkit\AccessHelper;

#[CoversClass(Query::class)]
class QueryTest extends TestCase
{
    private ?Query $query = null;

    protected function setUp(): void
    {
        $this->query = $this->getMockBuilder(Query::class)
            ->onlyMethods(['buildSql'])
            ->getMock();
    }

    #region ToSql --------------------------------------------------------------

    function testToSqlWithNoBindings()
    {
        $this->query->expects($this->once())
            ->method('buildSql')
            ->willReturn('SELECT * FROM `users`');
        $this->assertSame('SELECT * FROM `users`', $this->query->ToSql());
        $this->assertEmpty($this->query->Bindings());
    }

    function testToSqlWithSingleMissingBinding()
    {
        $this->query->expects($this->once())
            ->method('buildSql')
            ->willReturn('SELECT * FROM `users` WHERE id = :id');
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Missing bindings: id');
        $this->query->ToSql();
    }

    function testToSqlWithMultipleMissingBindings()
    {
        $this->query->expects($this->once())
            ->method('buildSql')
            ->willReturn('SELECT * FROM `users` WHERE id = :id AND name = :name');
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Missing bindings: id, name');
        $this->query->ToSql();
    }

    function testToSqlWithSingleMissingPlaceholder()
    {
        $this->query->expects($this->once())
            ->method('buildSql')
            ->willReturn('SELECT * FROM `users` WHERE id = :id');
        $this->query->Bind(['id' => 42, 'name' => 'John']);
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Missing placeholders: name');
        $this->query->ToSql();
    }

    function testToSqlWithMultipleMissingPlaceholders()
    {
        $this->query->expects($this->once())
            ->method('buildSql')
            ->willReturn('SELECT * FROM `users` WHERE 1 = 1');
        $this->query->Bind(['id' => 42, 'name' => 'John']);
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Missing placeholders: id, name');
        $this->query->ToSql();
    }

    function testToSqlWithNoMissingBindingsOrPlaceholders()
    {
        $this->query->expects($this->once())
            ->method('buildSql')
            ->willReturn('SELECT * FROM `users` WHERE id = :id AND name = :name');
        $this->query->Bind(['id' => 42, 'name' => 'John']);
        $this->assertSame(
            'SELECT * FROM `users` WHERE id = :id AND name = :name',
            $this->query->ToSql()
        );
        $this->assertSame(
            ['id' => 42, 'name' => 'John'],
            $this->query->Bindings()
        );
    }

    function testToSqlWithDuplicatePlaceholders()
    {
        $this->query->expects($this->once())
            ->method('buildSql')
            ->willReturn('SELECT * FROM `users` WHERE id = :id AND id = :id');
        $this->query->Bind(['id' => 42]);
        $this->assertSame(
            'SELECT * FROM `users` WHERE id = :id AND id = :id',
            $this->query->ToSql()
        );
        $this->assertSame(
            ['id' => 42],
            $this->query->Bindings()
        );
    }

    #endregion ToSql

    #region Bind ---------------------------------------------------------------

    function testBindWithInvalidKey()
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid binding key: 1id');
        $this->query->Bind(['1id' => 42]);
    }

    function testBindWithEmptyBindings()
    {
        $this->query->Bind([]);
        $this->assertEmpty($this->query->Bindings());
    }

    function testBindWithNullAsValue()
    {
        $this->query->Bind(['id' => null]);
        $this->assertSame(['id' => null], $this->query->Bindings());
    }

    function testBindWithBooleanAsValue()
    {
        $this->query->Bind(['id' => true]);
        $this->assertSame(['id' => true], $this->query->Bindings());
    }

    function testBindWithIntegerAsValue()
    {
        $this->query->Bind(['id' => 42]);
        $this->assertSame(['id' => 42], $this->query->Bindings());
    }

    function testBindWithFloatAsValue()
    {
        $this->query->Bind(['id' => 3.14]);
        $this->assertSame(['id' => 3.14], $this->query->Bindings());
    }

    function testBindWithStringAsValue()
    {
        $this->query->Bind(['id' => '42']);
        $this->assertSame(['id' => '42'], $this->query->Bindings());
    }

    function testBindWithEmptyStringAsValue()
    {
        $this->query->Bind(['id' => '']);
        $this->assertSame(['id' => ''], $this->query->Bindings());
    }

    function testBindWithArrayAsValue()
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage("Invalid binding value for 'id': Array not allowed.");
        $this->query->Bind(['id' => [42]]);
    }

    function testBindWithResourceAsValue()
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage("Invalid binding value for 'id': Resource not allowed.");
        $resource = \fopen('php://memory', 'r');
        try {
            $this->query->Bind(['id' => $resource]);
        } finally {
            \fclose($resource);
        }
    }

    function testBindWithObjectWithoutTostringAsValue()
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage(
            "Invalid binding value for 'id': Object without __toString() not allowed.");
        $objectWithoutToString = new class {};
        $this->query->Bind(['id' => $objectWithoutToString]);
    }

    function testBindWithObjectWithTostringAsValue()
    {
        $objectWithToString = new class {
            public function __toString() { return "I'm a string"; }
        };
        $this->query->Bind(['id' => $objectWithToString]);
        $this->assertSame(['id' => "I'm a string"], $this->query->Bindings());
    }

    function testBindWithEachCallOverwritingPreviousBindings()
    {
        $this->query->Bind(['id' => 42]);
        $this->query->Bind(['name' => 'John']);
        $this->assertSame(['name' => 'John'], $this->query->Bindings());
    }

    #endregion Bind

    #region checkString --------------------------------------------------------

    function testCheckStringWithEmptyString()
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('String cannot be empty.');
        AccessHelper::CallMethod($this->query, 'checkString', ['']);
    }

    function testCheckStringWithWhitespaceOnlyString()
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('String cannot be empty.');
        AccessHelper::CallMethod($this->query, 'checkString', ['   ']);
    }

    function testCheckStringTrimsString()
    {
        $result = AccessHelper::CallMethod(
            $this->query,
            'checkString',
            ['  id  ']
        );
        $this->assertSame('id', $result);
    }

    function testCheckStringWithNonEmptyString()
    {
        $result = AccessHelper::CallMethod(
            $this->query,
            'checkString',
            ['id']
        );
        $this->assertSame('id', $result);
    }

    #endregion checkString

    #region checkStringList ----------------------------------------------------

    function testCheckStringListWithNoStrings()
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('String list cannot be empty.');
        AccessHelper::CallMethod($this->query, 'checkStringList', []);
    }

    function testCheckStringListWithEmptyString()
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('String cannot be empty.');
        AccessHelper::CallMethod($this->query, 'checkStringList', ['']);
    }

    function testCheckStringListWithWhitespaceOnlyString()
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('String cannot be empty.');
        AccessHelper::CallMethod($this->query, 'checkStringList', ['   ']);
    }

    function testCheckStringListTrimsStrings()
    {
        $result = AccessHelper::CallMethod(
            $this->query,
            'checkStringList',
            ['  id  ', '  name  ']
        );
        $this->assertSame(['id', 'name'], $result);
    }

    function testCheckStringListWithSingleString()
    {
        $result = AccessHelper::CallMethod(
            $this->query,
            'checkStringList',
            ['id']
        );
        $this->assertSame(['id'], $result);
    }

    function testCheckStringListWithMultipleStrings()
    {
        $result = AccessHelper::CallMethod(
            $this->query,
            'checkStringList',
            ['id', 'name', 'AVG(*)']
        );
        $this->assertSame(['id', 'name', 'AVG(*)'], $result);
    }

    #endregion checkStringList

    #region formatStringList ---------------------------------------------------

    function testFormatStringListWithNoStrings()
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('String list cannot be empty.');
        AccessHelper::CallMethod($this->query, 'formatStringList', []);
    }

    function testFormatStringListWithEmptyString()
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('String cannot be empty.');
        AccessHelper::CallMethod($this->query, 'formatStringList', ['']);
    }

    function testFormatStringListWithWhitespaceOnlyString()
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('String cannot be empty.');
        AccessHelper::CallMethod($this->query, 'formatStringList', ['   ']);
    }

    function testFormatStringListTrimsStrings()
    {
        $result = AccessHelper::CallMethod(
            $this->query,
            'formatStringList',
            ['  id  ', '  name  ']
        );
        $this->assertSame('id, name', $result);
    }

    function testFormatStringListWithSingleString()
    {
        $result = AccessHelper::CallMethod(
            $this->query,
            'formatStringList',
            ['id']
        );
        $this->assertSame('id', $result);
    }

    function testFormatStringListWithMultipleStrings()
    {
        $result = AccessHelper::CallMethod(
            $this->query,
            'formatStringList',
            ['id', 'name', 'AVG(*)']
        );
        $this->assertSame('id, name, AVG(*)', $result);
    }

    #endregion formatStringList

    #region formatIdentifier ---------------------------------------------------

    function testFormatIdentifierWithEmptyString()
    {
        $result = AccessHelper::CallMethod(
            $this->query,
            'formatIdentifier',
            ['']
        );
        $this->assertSame('``', $result);
    }

    function testFormatIdentifierWithSimpleIdentifier()
    {
        $result = AccessHelper::CallMethod(
            $this->query,
            'formatIdentifier',
            ['my_identifier']
        );
        $this->assertSame('`my_identifier`', $result);
    }

    function testFormatIdentifierWithAlreadyBacktickedIdentifier()
    {
        $result = AccessHelper::CallMethod(
            $this->query,
            'formatIdentifier',
            ['`my_identifier`']
        );
        $this->assertSame('```my_identifier```', $result);
    }

    function testFormatIdentifierEscapesSingleBacktick()
    {
        $result = AccessHelper::CallMethod(
            $this->query,
            'formatIdentifier',
            ['my`identifier']
        );
        $this->assertSame('`my``identifier`', $result);
    }

    function testFormatIdentifierEscapesMultipleBackticks()
    {
        $result = AccessHelper::CallMethod(
            $this->query,
            'formatIdentifier',
            ['my``identifier`name']
        );
        $this->assertSame('`my````identifier``name`', $result);
    }

    #endregion formatIdentifier
}
