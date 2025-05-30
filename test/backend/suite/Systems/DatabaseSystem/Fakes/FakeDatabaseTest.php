<?php declare(strict_types=1);
use \PHPUnit\Framework\TestCase;
use \PHPUnit\Framework\Attributes\CoversClass;

use \Harmonia\Systems\DatabaseSystem\Fakes\FakeDatabase;

use \Harmonia\Systems\DatabaseSystem\Fakes\FakeResultSet;
use \Harmonia\Systems\DatabaseSystem\Queries\Query;

#[CoversClass(FakeDatabase::class)]
class FakeDatabaseTest extends TestCase
{
    private function createQuery(string $sql, array $bindings = []): Query
    {
        $query = $this->getMockBuilder(Query::class)
            ->onlyMethods(['buildSql'])
            ->getMock();
        $query->expects($this->any())
            ->method('buildSql')
            ->willReturn($sql);
        if (!empty($bindings)) {
            $query->Bind($bindings);
        }
        return $query;
    }

    function testExpectStoresAndLaterMatchesBySqlAndBindings()
    {
        $sut = new FakeDatabase();
        $sut->Expect(
            'SELECT * FROM users WHERE id = :id',
            ['id' => 1],
            [['id' => 1, 'name' => 'Alice']]
        );
        $query = $this->createQuery('SELECT * FROM users WHERE id = :id', ['id' => 1]);
        $result = $sut->Execute($query);
        $this->assertInstanceOf(FakeResultSet::class, $result);
        $this->assertSame(['id' => 1, 'name' => 'Alice'], $result->Row());
    }

    function testExecuteThrowsWhenNoMatch()
    {
        $sut = new FakeDatabase();
        $query = $this->createQuery('SELECT 1');
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Unexpected query: SELECT 1');
        $sut->Execute($query);
    }

    function testExecuteThrowsWhenExpectationIsExhausted()
    {
        $sut = new FakeDatabase();
        $sut->Expect(
            'SELECT * FROM users WHERE id = :id',
            ['id' => 1],
            [['id' => 1]],
            times: 1
        );
        $query = $this->createQuery('SELECT * FROM users WHERE id = :id', ['id' => 1]);
        $sut->Execute($query); // first OK
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Expectation exhausted: SELECT * FROM users WHERE id = :id');
        $sut->Execute($query); // second fails
    }

    function testLastInsertIdReturnsConfiguredValue()
    {
        $sut = new FakeDatabase();
        $sut->Expect(
            'INSERT INTO logs VALUES (...)',
            [],
            [],
            lastInsertId: 123
        );
        $query = $this->createQuery('INSERT INTO logs VALUES (...)');
        $sut->Execute($query);
        $this->assertSame(123, $sut->LastInsertId());
    }

    function testLastAffectedRowCountReturnsConfiguredValue()
    {
        $sut = new FakeDatabase();
        $sut->Expect(
            'DELETE FROM logs WHERE id = :id',
            ['id' => 5],
            [],
            lastAffectedRowCount: 1
        );
        $query = $this->createQuery('DELETE FROM logs WHERE id = :id', ['id' => 5]);
        $sut->Execute($query);
        $this->assertSame(1, $sut->LastAffectedRowCount());
    }

    function testWithTransactionReturnsCallbackResult()
    {
        $sut = new FakeDatabase();
        $result = $sut->WithTransaction(fn() => 'hello');
        $this->assertSame('hello', $result);
    }

    function testWithTransactionReturnsFalseOnException()
    {
        $sut = new FakeDatabase();
        $result = $sut->WithTransaction(function () {
            throw new RuntimeException("Boom");
        });
        $this->assertFalse($result);
    }
}
