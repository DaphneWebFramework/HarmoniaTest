<?php declare(strict_types=1);
namespace suite\Systems\DatabaseSystem\Queries;

use \PHPUnit\Framework\TestCase;
use \PHPUnit\Framework\Attributes\CoversClass;

use \Harmonia\Systems\DatabaseSystem\Queries\Query;

use \TestToolkit\AccessHelper as ah;

enum TPureEnum { case Zero; case One; case Two; }
enum TIntegerEnum: int { case Zero = 0; case One = 1; case Two = 2; }
enum TStringEnum: string { case Zero = 'zero'; case One = 'one'; case Two = 'two'; }

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
        $this->expectExceptionMessage("Missing bindings: id");
        $this->query->ToSql();
    }

    function testToSqlWithMultipleMissingBindings()
    {
        $this->query->expects($this->once())
            ->method('buildSql')
            ->willReturn('SELECT * FROM `users` WHERE id = :id AND name = :name');
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage("Missing bindings: id, name");
        $this->query->ToSql();
    }

    function testToSqlWithSingleMissingPlaceholder()
    {
        $this->query->expects($this->once())
            ->method('buildSql')
            ->willReturn('SELECT * FROM `users` WHERE id = :id');
        $this->query->Bind(['id' => 42, 'name' => 'John']);
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage("Missing placeholders: name");
        $this->query->ToSql();
    }

    function testToSqlWithMultipleMissingPlaceholders()
    {
        $this->query->expects($this->once())
            ->method('buildSql')
            ->willReturn('SELECT * FROM `users` WHERE 1 = 1');
        $this->query->Bind(['id' => 42, 'name' => 'John']);
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage("Missing placeholders: id, name");
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
        $this->expectExceptionMessage("Invalid binding key: 1id");
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
        $this->expectExceptionMessage(
            "Invalid binding value for 'id': Array not allowed.");
        $this->query->Bind(['id' => [42]]);
    }

    function testBindWithResourceAsValue()
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage(
            "Invalid binding value for 'id': Resource not allowed.");
        $resource = \fopen('php://memory', 'r');
        try {
            $this->query->Bind(['id' => $resource]);
        } finally {
            \fclose($resource);
        }
    }

    function testBindWithPureEnumAsValue()
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage(
            "Invalid binding value for 'id': Object must be a BackedEnum or"
          . " implement __toString().");
        $this->query->Bind(['id' => TPureEnum::One]);
    }

    function testBindWithObjectWithoutTostringAsValue()
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage(
            "Invalid binding value for 'id': Object must be a BackedEnum"
          . " or implement __toString().");
        $objectWithoutToString = new class {};
        $this->query->Bind(['id' => $objectWithoutToString]);
    }

    function testBindWithIntegerEnumAsValue()
    {
        $this->query->Bind(['id' => TIntegerEnum::One]);
        $this->assertSame(['id' => 1], $this->query->Bindings());
    }

    function testBindWithStringEnumAsValue()
    {
        $this->query->Bind(['id' => TStringEnum::One]);
        $this->assertSame(['id' => 'one'], $this->query->Bindings());
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
        $this->expectExceptionMessage("String cannot be empty.");
        ah::CallMethod($this->query, 'checkString', ['']);
    }

    function testCheckStringWithWhitespaceOnlyString()
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage("String cannot be empty.");
        ah::CallMethod($this->query, 'checkString', ['   ']);
    }

    function testCheckStringTrimsString()
    {
        $result = ah::CallMethod(
            $this->query,
            'checkString',
            ['  id  ']
        );
        $this->assertSame('id', $result);
    }

    function testCheckStringWithNonEmptyString()
    {
        $result = ah::CallMethod(
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
        $this->expectExceptionMessage("String list cannot be empty.");
        ah::CallMethod($this->query, 'checkStringList', []);
    }

    function testCheckStringListWithEmptyString()
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage("String cannot be empty.");
        ah::CallMethod($this->query, 'checkStringList', ['']);
    }

    function testCheckStringListWithWhitespaceOnlyString()
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage("String cannot be empty.");
        ah::CallMethod($this->query, 'checkStringList', ['   ']);
    }

    function testCheckStringListTrimsStrings()
    {
        $result = ah::CallMethod(
            $this->query,
            'checkStringList',
            ['  id  ', '  name  ']
        );
        $this->assertSame(['id', 'name'], $result);
    }

    function testCheckStringListWithSingleString()
    {
        $result = ah::CallMethod(
            $this->query,
            'checkStringList',
            ['id']
        );
        $this->assertSame(['id'], $result);
    }

    function testCheckStringListWithMultipleStrings()
    {
        $result = ah::CallMethod(
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
        $this->expectExceptionMessage("String list cannot be empty.");
        ah::CallMethod($this->query, 'formatStringList', []);
    }

    function testFormatStringListWithEmptyString()
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage("String cannot be empty.");
        ah::CallMethod($this->query, 'formatStringList', ['']);
    }

    function testFormatStringListWithWhitespaceOnlyString()
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage("String cannot be empty.");
        ah::CallMethod($this->query, 'formatStringList', ['   ']);
    }

    function testFormatStringListTrimsStrings()
    {
        $result = ah::CallMethod(
            $this->query,
            'formatStringList',
            ['  id  ', '  name  ']
        );
        $this->assertSame('id, name', $result);
    }

    function testFormatStringListWithSingleString()
    {
        $result = ah::CallMethod(
            $this->query,
            'formatStringList',
            ['id']
        );
        $this->assertSame('id', $result);
    }

    function testFormatStringListWithMultipleStrings()
    {
        $result = ah::CallMethod(
            $this->query,
            'formatStringList',
            ['id', 'name', 'AVG(*)']
        );
        $this->assertSame('id, name, AVG(*)', $result);
    }

    #endregion formatStringList
}
