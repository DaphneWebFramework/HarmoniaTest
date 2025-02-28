<?php declare(strict_types=1);
use \PHPUnit\Framework\TestCase;
use \PHPUnit\Framework\Attributes\CoversClass;

use \Harmonia\Database\ResultSet;

use \Harmonia\Database\Proxies\MySQLiResult;
use \TestToolkit\AccessHelper;

#[CoversClass(ResultSet::class)]
class ResultSetTest extends TestCase
{
    #region __construct --------------------------------------------------------

    function testConstructWithNoParameters()
    {
        $resultSet = new ResultSet();
        $this->assertNull(AccessHelper::GetProperty($resultSet, 'result'));
    }

    function testConstructWithNull()
    {
        $resultSet = new ResultSet(null);
        $this->assertNull(AccessHelper::GetProperty($resultSet, 'result'));
    }

    function testConstructWithResultObject()
    {
        $result = $this->createMock(MySQLiResult::class);
        $resultSet = new ResultSet($result);
        $this->assertInstanceOf(MySQLiResult::class,
            AccessHelper::GetProperty($resultSet, 'result'));
    }

    #endregion __construct

    #region __destruct ---------------------------------------------------------

    function testDestructWithEmptyResultSet()
    {
        $resultSet = new ResultSet();
        $resultSet->__destruct();
        $this->expectNotToPerformAssertions();
    }

    function testDestructWithNonEmptyResultSet()
    {
        $result = $this->createMock(MySQLiResult::class);
        $result->expects($this->once())
            ->method('__call')
            ->with('free');
        $resultSet = new ResultSet($result);
        $resultSet->__destruct();
    }

    #endregion __destruct

    #region Columns ------------------------------------------------------------

    function testColumnsWithEmptyResultSet()
    {
        $resultSet = new ResultSet();
        $columns = $resultSet->Columns();
        $this->assertEmpty($columns);
    }

    function testColumnsWithNonEmptyResultSet()
    {
        $result = $this->createMock(MySQLiResult::class);
        $result->expects($this->any())
            ->method('__call')
            ->willReturnCallback(function($name) {
                return match ($name) {
                    'fetch_fields' => [
                        (object)['name' => 'id'],
                        (object)['name' => 'name']
                    ],
                    'free' => null
                };
            });
        $resultSet = new ResultSet($result);
        $columns = $resultSet->Columns();
        $this->assertSame(['id', 'name'], $columns);
    }

    #endregion Columns

    #region RowCount -----------------------------------------------------------

    function testRowCountWithEmptyResultSet()
    {
        $resultSet = new ResultSet();
        $rowCount = $resultSet->RowCount();
        $this->assertSame(0, $rowCount);
    }

    function testRowCountWithNonEmptyResultSet()
    {
        $result = $this->createMock(MySQLiResult::class);
        $result->expects($this->once())
            ->method('__get')
            ->with('num_rows')
            ->willReturn(3);
        $resultSet = new ResultSet($result);
        $rowCount = $resultSet->RowCount();
        $this->assertSame(3, $rowCount);
    }

    #endregion RowCount

    #region Row ----------------------------------------------------------------

    function testRowWithEmptyResultSet()
    {
        $resultSet = new ResultSet();
        $row = $resultSet->Row();
        $this->assertNull($row);
    }

    function testRowWithNoParameters()
    {
        $result = $this->createMock(MySQLiResult::class);
        $result->expects($this->any())
            ->method('__call')
            ->willReturnCallback(function($name, $arguments) {
                return match ($name) {
                    'fetch_assoc' => ['id' => 42, 'name' => 'John'],
                    'free' => null
                };
            });
        $resultSet = new ResultSet($result);
        $row = $resultSet->Row();
        $this->assertSame(['id' => 42, 'name' => 'John'], $row);
    }

    function testRowWithAssociativeMode()
    {
        $result = $this->createMock(MySQLiResult::class);
        $result->expects($this->any())
            ->method('__call')
            ->willReturnCallback(function($name, $arguments) {
                return match ($name) {
                    'fetch_assoc' => ['id' => 42, 'name' => 'John'],
                    'free' => null
                };
            });
        $resultSet = new ResultSet($result);
        $row = $resultSet->Row(ResultSet::ROW_MODE_ASSOCIATIVE);
        $this->assertSame(['id' => 42, 'name' => 'John'], $row);
    }

    function testRowWithNumericMode()
    {
        $result = $this->createMock(MySQLiResult::class);
        $result->expects($this->any())
            ->method('__call')
            ->willReturnCallback(function($name, $arguments) {
                return match ($name) {
                    'fetch_row' => [42, 'John'],
                    'free' => null
                };
            });
        $resultSet = new ResultSet($result);
        $row = $resultSet->Row(ResultSet::ROW_MODE_NUMERIC);
        $this->assertSame([42, 'John'], $row);
    }

    function testRowWithInvalidMode()
    {
        $result = $this->createMock(MySQLiResult::class);
        $resultSet = new ResultSet($result);
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid row mode: 3');
        $row = $resultSet->Row(3);
    }

    #endregion Row

    #region Interface: IteratorAggregate ---------------------------------------

    function testGetIteratorWithEmptyResultSet()
    {
        $resultSet = new ResultSet();
        $iterator = $resultSet->getIterator();
        $this->assertInstanceOf(\Traversable::class, $iterator);
        $this->assertEmpty(\iterator_to_array($iterator));
    }

    function testGetIteratorWithNonEmptyResultSet()
    {
        $result = $this->createMock(MySQLiResult::class);
        $result->expects($invokedCount = $this->any())
            ->method('__call')
            ->willReturnCallback(function($name) use($invokedCount) {
                switch ($invokedCount->numberOfInvocations()) {
                case 1:
                    $this->assertSame('fetch_assoc', $name);
                    return ['id' => 42, 'name' => 'John'];
                case 2:
                    $this->assertSame('fetch_assoc', $name);
                    return ['id' => 43, 'name' => 'Jane'];
                case 3:
                    $this->assertSame('fetch_assoc', $name);
                    return ['id' => 44, 'name' => 'Jack'];
                case 4:
                    $this->assertSame('fetch_assoc', $name);
                    return null;
                case 5:
                    $this->assertSame('free', $name);
                    return null;
                }
            });
        $resultSet = new ResultSet($result);
        $rows = [];
        foreach ($resultSet as $row) {
            $rows[] = $row;
        }
        $this->assertSame(
            [ ['id' => 42, 'name' => 'John'],
              ['id' => 43, 'name' => 'Jane'],
              ['id' => 44, 'name' => 'Jack'] ],
            $rows);
    }

    #endregion Interface: IteratorAggregate
}
