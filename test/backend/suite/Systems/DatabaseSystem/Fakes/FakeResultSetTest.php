<?php declare(strict_types=1);
use \PHPUnit\Framework\TestCase;
use \PHPUnit\Framework\Attributes\CoversClass;

use \Harmonia\Systems\DatabaseSystem\Fakes\FakeResultSet;

use \Harmonia\Systems\DatabaseSystem\ResultSet;

#[CoversClass(FakeResultSet::class)]
class FakeResultSetTest extends TestCase
{
    function testColumnsReturnsFieldNames()
    {
        $sut = new FakeResultSet([
            ['id' => 1, 'email' => 'x@example.com']
        ]);
        $this->assertSame(['id', 'email'], $sut->Columns());
    }

    function testColumnsReturnsEmptyArrayWhenNoRows()
    {
        $sut = new FakeResultSet([]);
        $this->assertSame([], $sut->Columns());
    }

    function testRowCountIsAccurate()
    {
        $sut = new FakeResultSet([
            ['id' => 1],
            ['id' => 2],
            ['id' => 3],
        ]);
        $this->assertSame(3, $sut->RowCount());
    }

    function testRowReturnsRowsInSequence()
    {
        $rows = [
            ['id' => 1, 'email' => 'a@example.com'],
            ['id' => 2, 'email' => 'b@example.com'],
        ];
        $sut = new FakeResultSet($rows);
        $this->assertSame($rows[0], $sut->Row());
        $this->assertSame($rows[1], $sut->Row());
        $this->assertNull($sut->Row());
    }

    function testRowReturnsNullWhenExhausted()
    {
        $sut = new FakeResultSet([]);
        $this->assertNull($sut->Row());
    }

    function testRowModeNumericReturnsIndexedArray()
    {
        $sut = new FakeResultSet([
            ['id' => 42, 'email' => 'x@example.com']
        ]);
        $row = $sut->Row(ResultSet::ROW_MODE_NUMERIC);
        $this->assertSame([42, 'x@example.com'], $row);
    }

    function testRowModeInvalidThrows()
    {
        $sut = new FakeResultSet([['a' => 1]]);
        $this->expectException(\InvalidArgumentException::class);
        $sut->Row(999); // Invalid row mode
    }

    function testIteratorYieldsAllRows()
    {
        $rows = [
            ['id' => 1],
            ['id' => 2],
            ['id' => 3],
        ];
        $sut = new FakeResultSet($rows);
        $result = [];
        foreach ($sut as $row) {
            $result[] = $row;
        }
        $this->assertSame($rows, $result);
    }
}
