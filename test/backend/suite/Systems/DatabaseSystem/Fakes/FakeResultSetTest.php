<?php declare(strict_types=1);
use \PHPUnit\Framework\TestCase;
use \PHPUnit\Framework\Attributes\CoversClass;

use \Harmonia\Systems\DatabaseSystem\Fakes\FakeResultSet;

use \Harmonia\Systems\DatabaseSystem\ResultSet;
use \TestToolkit\AccessHelper;

#[CoversClass(FakeResultSet::class)]
class FakeResultSetTest extends TestCase
{
    #region __construct --------------------------------------------------------

    function testConstructInitializesEmptyResultSet()
    {
        $sut = new FakeResultSet();
        $this->assertSame([], AccessHelper::GetProperty($sut, 'rows'));
        $this->assertSame(0, AccessHelper::GetProperty($sut, 'cursor'));
    }

    function testConstructInitializesPopulatedResultSet()
    {
        $sut = new FakeResultSet([
            ['id' => 1, 'email' => 'john@example.com'],
            ['id' => 2, 'email' => 'marry@example.com']
        ]);
        $this->assertSame([
            ['id' => 1, 'email' => 'john@example.com'],
            ['id' => 2, 'email' => 'marry@example.com']
        ], AccessHelper::GetProperty($sut, 'rows'));
        $this->assertSame(0, AccessHelper::GetProperty($sut, 'cursor'));
    }

    #endregion __construct

    #region Columns ------------------------------------------------------------

    function testColumnsReturnsEmptyArrayForEmptyResultSet()
    {
        $sut = new FakeResultSet();
        $this->assertSame([], $sut->Columns());
    }

    function testColumnsReturnsFieldNamesFromFirstRow()
    {
        $sut = new FakeResultSet([
            ['id' => 1, 'email' => 'john@example.com'],
            ['id' => 2, 'displayName' => 'Marry']
        ]);
        $this->assertSame(['id', 'email'], $sut->Columns());
    }

    #endregion Columns

    #region RowCount -----------------------------------------------------------

    function testRowCountReturnsZeroForEmptyResultSet()
    {
        $sut = new FakeResultSet();
        $this->assertSame(0, $sut->RowCount());
    }

    function testRowCountReturnsCorrectCountForPopulatedResultSet()
    {
        $sut = new FakeResultSet([
            ['id' => 1],
            ['id' => 2],
            ['id' => 3],
        ]);
        $this->assertSame(3, $sut->RowCount());
    }

    #endregion RowCount

    #region Row ----------------------------------------------------------------

    function testRowReturnsNullForEmptyResultSet()
    {
        $sut = new FakeResultSet();
        $this->assertNull($sut->Row());
    }

    function testRowReturnsSingleRowCorrectly()
    {
        $sut = new FakeResultSet([
            ['id' => 1, 'email' => 'john@example.com']
        ]);
        $this->assertSame(['id' => 1, 'email' => 'john@example.com'], $sut->Row());
        $this->assertNull($sut->Row());
    }

    function testRowReturnsRowsInDefinedOrder()
    {
        $sut = new FakeResultSet([
            ['id' => 1],
            ['id' => 2]
        ]);
        $this->assertSame(['id' => 1], $sut->Row());
        $this->assertSame(['id' => 2], $sut->Row());
        $this->assertNull($sut->Row());
    }

    function testRowReturnsNumericArrayWhenModeIsNumeric()
    {
        $sut = new FakeResultSet([
            ['id' => 42, 'email' => 'john@example.com']
        ]);
        $this->assertSame(
            [42, 'john@example.com'],
            $sut->Row(ResultSet::ROW_MODE_NUMERIC)
        );
    }

    function testRowThrowsExceptionWhenModeIsInvalid()
    {
        $sut = new FakeResultSet([['id' => 1]]);
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid row mode: 999');
        $sut->Row(999);
    }

    #endregion Row

    #region getIterator --------------------------------------------------------

    function testGetIteratorWithEmptyResultSet()
    {
        $sut = new FakeResultSet();
        $iterator = $sut->getIterator();
        $this->assertInstanceOf(\Traversable::class, $iterator);
        $this->assertEmpty(\iterator_to_array($iterator));
    }

    function testGetIteratorWithNonEmptyResultSet()
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

    #endregion getIterator
}
