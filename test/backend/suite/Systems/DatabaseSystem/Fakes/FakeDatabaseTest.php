<?php declare(strict_types=1);
use \PHPUnit\Framework\TestCase;
use \PHPUnit\Framework\Attributes\CoversClass;

use \Harmonia\Systems\DatabaseSystem\Fakes\FakeDatabase;
use \Harmonia\Systems\DatabaseSystem\Fakes\FakeResultSet;
use \Harmonia\Systems\DatabaseSystem\Queries\Query;
use \Harmonia\Systems\DatabaseSystem\ResultSet;
use \TestToolkit\AccessHelper;

#[CoversClass(FakeDatabase::class)]
class FakeDatabaseTest extends TestCase
{
    private function createQuery(string $sql, array $bindings = []): Query
    {
        $mock = $this->getMockBuilder(Query::class)
            ->onlyMethods(['buildSql'])
            ->getMock();
        $mock->method('buildSql')->willReturn($sql);
        if (!empty($bindings)) {
            $mock->Bind($bindings);
        }
        return $mock;
    }

    #region __construct --------------------------------------------------------

    function testConstructInitializesWithDefaults()
    {
        $sut = new FakeDatabase();

        $this->assertSame([], AccessHelper::GetProperty($sut, 'expectations'));
        $this->assertSame(0, AccessHelper::GetProperty($sut, 'lastInsertId'));
        $this->assertSame(0, AccessHelper::GetProperty($sut, 'lastAffectedRowCount'));
    }

    #endregion __construct

    #region Expect -------------------------------------------------------------

    function testExpectAllowsMatchingSqlAndBindings()
    {
        $sut = new FakeDatabase();
        $sut->Expect(
            'SELECT * FROM users WHERE id = :id',
            ['id' => 1],
            [['id' => 1, 'name' => 'Alice']]
        );
        $query = $this->createQuery(
            'SELECT * FROM users WHERE id = :id',
            ['id' => 1]
        );

        $result = $sut->Execute($query);
        $this->assertInstanceOf(FakeResultSet::class, $result);
        $this->assertSame(['id' => 1, 'name' => 'Alice'], $result->Row());
    }

    function testExpectReturnsNullWhenConfiguredSo()
    {
        $sut = new FakeDatabase();
        $sut->Expect(
            'SELECT 1',
            [],
            result: null
        );
        $query = $this->createQuery('SELECT 1');

        $result = $sut->Execute($query);
        $this->assertNull($result);
    }

    #endregion Expect

    #region VerifyAllExpectationsMet -------------------------------------------

    function testVerifyAllExpectationsMetThrowsWhenExpectationIsNotExecuted()
    {
        $sut = new FakeDatabase();
        $sut->Expect('SELECT 1', times: 1);

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage(
            'One or more expected queries were not executed.');
        $sut->VerifyAllExpectationsMet();
    }

    function testVerifyAllExpectationsMetThrowsWhenExpectationsPartiallyUsed()
    {
        $sut = new FakeDatabase();
        $sut->Expect('SELECT 1', times: 3);

        $query = $this->createQuery('SELECT 1');
        $sut->Execute($query);
        $sut->Execute($query);

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage(
            'One or more expected queries were not executed.');
        $sut->VerifyAllExpectationsMet();
    }

    function testVerifyAllExpectationsMetSucceedsWhenAllExpectationsAreUsed()
    {
        $sut = new FakeDatabase();
        $sut->Expect('SELECT 1', times: 1);
        $sut->Execute($this->createQuery('SELECT 1'));

        $this->expectNotToPerformAssertions();
        $sut->VerifyAllExpectationsMet();
    }

    function testVerifyAllExpectationsMetIgnoresUnlimitedExpectations()
    {
        $sut = new FakeDatabase();
        $sut->Expect('SELECT 1'); // times: null (unlimited)

        $this->expectNotToPerformAssertions();
        $sut->VerifyAllExpectationsMet();
    }

    #endregion VerifyAllExpectationsMet

    #region Execute ------------------------------------------------------------

    function testExecuteThrowsWhenNoExpectationMatches()
    {
        $sut = new FakeDatabase(); // No expectations set
        $query = $this->createQuery('SELECT * FROM nonexistent');

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage(
            'Unexpected query: "SELECT * FROM nonexistent", bindings: []');
        $sut->Execute($query);
    }

    function testExecuteThrowsWhenBindingsDifferEvenIfSqlMatches()
    {
        $sut = new FakeDatabase();
        $sut->Expect(
            'SELECT * FROM users WHERE id = :id',
            ['id' => 1],
            [['id' => 1]]
        );
        $query = $this->createQuery(
            'SELECT * FROM users WHERE id = :id',
            ['id' => 2] // Different binding
        );

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage(
            'Unexpected query: "SELECT * FROM users WHERE id = :id",'
          . ' bindings: {"id":2}'
        );
        $sut->Execute($query);
    }

    function testExecuteThrowsWhenExpectationExhausted()
    {
        $sut = new FakeDatabase();
        $sql = 'SELECT * FROM users WHERE id = :id';
        $bindings = ['id' => 1];

        $sut->Expect($sql, $bindings, [['id' => 1]], times: 1);
        $query = $this->createQuery($sql, $bindings);

        $sut->Execute($query);

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage(
            'Exhausted query: "SELECT * FROM users WHERE id = :id",'
          . ' bindings: {"id":1}');
        $sut->Execute($query); // exceeds 1
    }

    function testExecuteSucceedsExactlySpecifiedNumberOfTimes()
    {
        $sut = new FakeDatabase();
        $sql = 'SELECT * FROM logs WHERE status = :status';
        $bindings = ['status' => 'archived'];

        $sut->Expect($sql, $bindings, [['id' => 1]], times: 3);
        $query = $this->createQuery($sql, $bindings);

        $sut->Execute($query); // 1
        $sut->Execute($query); // 2
        $sut->Execute($query); // 3

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage(
            'Exhausted query: "SELECT * FROM logs WHERE status = :status",'
          . ' bindings: {"status":"archived"}');
        $sut->Execute($query); // exceeds 3
    }

    function testExecuteMatchesMultipleExpectationsIndependently()
    {
        $sut = new FakeDatabase();
        $sut->Expect(
            'SELECT * FROM users WHERE id = :id',
            ['id' => 1],
            [['id' => 1, 'name' => 'Alice']]
        );
        $sut->Expect(
            'SELECT * FROM products WHERE id = :id',
            ['id' => 2],
            [['id' => 2, 'name' => 'Widget']]
        );
        $query1 = $this->createQuery(
            'SELECT * FROM users WHERE id = :id',
            ['id' => 1]
        );
        $query2 = $this->createQuery(
            'SELECT * FROM products WHERE id = :id',
            ['id' => 2]
        );

        $result1 = $sut->Execute($query1);
        $result2 = $sut->Execute($query2);
        $this->assertSame(['id' => 1, 'name' => 'Alice'], $result1->Row());
        $this->assertSame(['id' => 2, 'name' => 'Widget'], $result2->Row());
    }

    function testExecuteDifferentiatesExpectationsByBindings()
    {
        $sut = new FakeDatabase();
        $sql = 'SELECT * FROM users WHERE id = :id';
        $sut->Expect($sql, ['id' => 1], [['name' => 'Alice']]);
        $sut->Expect($sql, ['id' => 2], [['name' => 'Bob']]);

        $result1 = $sut->Execute($this->createQuery($sql, ['id' => 1]));
        $result2 = $sut->Execute($this->createQuery($sql, ['id' => 2]));
        $this->assertSame(['name' => 'Alice'], $result1->Row());
        $this->assertSame(['name' => 'Bob'], $result2->Row());
    }

    function testExecuteMatchesRegardlessOfBindingOrder()
    {
        $sut = new FakeDatabase();
        $sql = 'SELECT * FROM users WHERE id = :id AND name = :name';
        $sut->Expect($sql, ['id' => 1, 'name' => 'Alice'], [['ok' => true]]);
        $query = $this->createQuery($sql, ['name' => 'Alice', 'id' => 1]);

        $result = $sut->Execute($query);
        $this->assertSame(['ok' => true], $result->Row());
    }

    #endregion Execute

    #region LastInsertId -------------------------------------------------------

    function testLastInsertIdReturnsConfiguredValue()
    {
        $sut = new FakeDatabase();
        $sut->Expect(
            'INSERT INTO logs VALUES (...)',
            lastInsertId: 123
        );
        $query = $this->createQuery('INSERT INTO logs VALUES (...)');

        $sut->Execute($query);
        $this->assertSame(123, $sut->LastInsertId());
    }

    function testLastInsertIdDefaultsToZero()
    {
        $sut = new FakeDatabase();
        $sut->Expect('INSERT INTO logs VALUES (...)');
        $query = $this->createQuery('INSERT INTO logs VALUES (...)');

        $sut->Execute($query);
        $this->assertSame(0, $sut->LastInsertId());
    }

    function testLastInsertIdReflectsLastExecutedExpectation()
    {
        $sut = new FakeDatabase();
        $sut->Expect('INSERT INTO a', lastInsertId: 101);
        $sut->Expect('INSERT INTO b', lastInsertId: 202);

        $sut->Execute($this->createQuery('INSERT INTO a'));
        $this->assertSame(101, $sut->LastInsertId());

        $sut->Execute($this->createQuery('INSERT INTO b'));
        $this->assertSame(202, $sut->LastInsertId());
    }

    #endregion LastInsertId

    #region LastAffectedRowCount -----------------------------------------------

    function testLastAffectedRowCountReturnsConfiguredValue()
    {
        $sut = new FakeDatabase();
        $sut->Expect(
            'DELETE FROM logs WHERE id = :id',
            ['id' => 5],
            lastAffectedRowCount: 1
        );
        $query = $this->createQuery(
            'DELETE FROM logs WHERE id = :id',
            ['id' => 5]
        );

        $sut->Execute($query);
        $this->assertSame(1, $sut->LastAffectedRowCount());
    }

    function testLastAffectedRowCountDefaultsToZero()
    {
        $sut = new FakeDatabase();
        $sut->Expect('DELETE FROM logs');
        $query = $this->createQuery('DELETE FROM logs');

        $sut->Execute($query);
        $this->assertSame(0, $sut->LastAffectedRowCount());
    }

    function testLastAffectedRowCountReflectsLastExecutedExpectation()
    {
        $sut = new FakeDatabase();
        $sut->Expect('UPDATE a SET ...', [], [], lastAffectedRowCount: 2);
        $sut->Expect('DELETE FROM b', [], [], lastAffectedRowCount: 5);

        $sut->Execute($this->createQuery('UPDATE a SET ...'));
        $this->assertSame(2, $sut->LastAffectedRowCount());

        $sut->Execute($this->createQuery('DELETE FROM b'));
        $this->assertSame(5, $sut->LastAffectedRowCount());
    }

    #endregion LastAffectedRowCount

    #region WithTransaction ----------------------------------------------------

    function testWithTransactionDoesNotThrowIfCallbackDoesNotThrow()
    {
        $sut = new FakeDatabase();
        $callback = function() {};

        $sut->WithTransaction($callback);
        $this->expectNotToPerformAssertions();
    }

    function testWithTransactionThrowsIfCallbackThrows()
    {
        $sut = new FakeDatabase();
        $callback = function() {
            throw new \RuntimeException("Failed to execute callback.");
        };

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage("Failed to execute callback.");
        $sut->WithTransaction($callback);
    }

    #endregion WithTransaction

    #region EscapeString -------------------------------------------------------

    function testEscapeStringReturnsUnmodifiedWhenInputIsSafe()
    {
        $sut = new FakeDatabase();
        $input = 'plain text';
        $escaped = $sut->EscapeString($input);

        $this->assertSame($input, $escaped);
    }

    function testEscapeStringEscapesSpecialCharacters()
    {
        $sut = new FakeDatabase();
        $input = "O'Reilly\nNew\rLine\0with\x1aEscape\\\"Quote\"";
        $expected = "O\\'Reilly\\nNew\\rLine\\0with\\ZEscape\\\\\\\"Quote\\\"";
        $escaped = $sut->EscapeString($input);

        $this->assertSame($expected, $escaped);
    }

    #endregion EscapeString
}
