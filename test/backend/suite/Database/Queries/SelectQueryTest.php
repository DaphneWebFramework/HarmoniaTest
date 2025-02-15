<?php declare(strict_types=1);
use \PHPUnit\Framework\TestCase;
use \PHPUnit\Framework\Attributes\CoversClass;

use \Harmonia\Database\Queries\SelectQuery;

#[CoversClass(SelectQuery::class)]
class SelectQueryTest extends TestCase
{
    function testWithDefaults()
    {
        $query = new SelectQuery('my_table');
        $this->assertSame(
            'SELECT * FROM `my_table`',
            $query->ToSql()
        );
    }

    #region Select -------------------------------------------------------------

    function testSelectWithEmptyColumns()
    {
        $query = new SelectQuery('my_table');
        $query->Select([]);
        $this->assertSame(
            'SELECT * FROM `my_table`',
            $query->ToSql()
        );
    }

    function testSelectWithIdentifierColumns()
    {
        $query = new SelectQuery('my_table');
        $query->Select(['column1', '_column2']);
        $this->assertSame(
            'SELECT `column1`, `_column2` FROM `my_table`',
            $query->ToSql()
        );
    }

    function testSelectWithNonIdentifierColumns()
    {
        $query = new SelectQuery('my_table');
        $query->Select(['AVG(column1)', 'COUNT(*)']);
        $this->assertSame(
            'SELECT AVG(column1), COUNT(*) FROM `my_table`',
            $query->ToSql()
        );
    }

    function testSelectWithMixtureOfIdentifierAndNonIdentifierColumns()
    {
        $query = new SelectQuery('my_table');
        $query->Select(['column1', 'COUNT(*) AS count']);
        $this->assertSame(
            'SELECT `column1`, COUNT(*) AS count FROM `my_table`',
            $query->ToSql()
        );
    }

    function testSelectReplacesPreviousColumns()
    {
        $query = new SelectQuery('my_table');
        $query->Select(['column1']);
        $query->Select(['column2']);
        $this->assertSame('SELECT `column2` FROM `my_table`', $query->ToSql());
    }

    #endregion Select

    #region Where --------------------------------------------------------------

    function testWhereWithNoBindings()
    {
        $query = new SelectQuery('my_table');
        $query->Where('column2 = 42');
        $this->assertSame(
            'SELECT * FROM `my_table` WHERE column2 = 42',
            $query->ToSql()
        );
    }

    function testWhereWithMatchingBindings()
    {
        $query = new SelectQuery('my_table');
        $query->Where('column1 = :value1 AND column2 = :value2')
              ->Bind(['value1' => 42, 'value2' => 43]);
        $this->assertSame(
            'SELECT * FROM `my_table` WHERE column1 = :value1 AND column2 = :value2',
            $query->ToSql()
        );
        $this->assertSame(['value1' => 42, 'value2' => 43], $query->Bindings());
    }

    function testWhereReplacesPreviousCondition()
    {
        $query = new SelectQuery('my_table');
        $query->Where('column1 = 1');
        $query->Where('column2 = 99');
        $this->assertSame(
            'SELECT * FROM `my_table` WHERE column2 = 99',
            $query->ToSql()
        );
    }

    #endregion Where

    #region OrderBy ------------------------------------------------------------

    function testOrderByWithEmptySortingDirectionArray()
    {
        $query = new SelectQuery('my_table');
        $query->OrderBy([]);
        $this->assertSame(
            'SELECT * FROM `my_table`',
            $query->ToSql()
        );
    }

    function testOrderByWithNoSortingDirection()
    {
        $query = new SelectQuery('my_table');
        $query->OrderBy(['column1', 'LENGTH(column1)']);
        $this->assertSame(
            'SELECT * FROM `my_table` ORDER BY `column1`, LENGTH(column1)',
            $query->ToSql()
        );
    }

    function testOrderByWithMixedAssociativeAndIndexedArray()
    {
        $query = new SelectQuery('my_table');
        $query->OrderBy(['column1', 'column2'=>'DESC', 'LENGTH(column1)'=>'ASC']);
        $this->assertSame(
            'SELECT * FROM `my_table` ORDER BY `column1`, `column2` DESC, LENGTH(column1) ASC',
            $query->ToSql()
        );
    }

    function testOrderByWithNonUpperCaseSortingDirection()
    {
        $query = new SelectQuery('my_table');
        $query->OrderBy(['column1'=>'desc', 'LENGTH(column1)'=>'Asc']);
        $this->assertSame(
            'SELECT * FROM `my_table` ORDER BY `column1` DESC, LENGTH(column1) ASC',
            $query->ToSql()
        );
    }

    function testOrderByWithInvalidSortingDirection()
    {
        $query = new SelectQuery('my_table');
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid sorting direction: ASX');
        $query->OrderBy(['column1'=>'asx']);
    }

    function testOrderByReplacesPreviousOrder()
    {
        $query = new SelectQuery('my_table');
        $query->OrderBy(['column1' => 'ASC']);
        $query->OrderBy(['column2' => 'DESC']);
        $this->assertSame(
            'SELECT * FROM `my_table` ORDER BY `column2` DESC',
            $query->ToSql()
        );
    }

    #endregion OrderBy

    #region Limit --------------------------------------------------------------

    function testLimitWithNegativeLimit()
    {
        $query = new SelectQuery('my_table');
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Limit must be a non-negative integer.');
        $query->Limit(-1);
    }

    function testLimitWithNegativeOffset()
    {
        $query = new SelectQuery('my_table');
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Offset must be a non-negative integer.');
        $query->Limit(10, -1);
    }

    function testLimitWithoutOffset()
    {
        $query = new SelectQuery('my_table');
        $query->Limit(10);
        $this->assertSame(
            'SELECT * FROM `my_table` LIMIT 10',
            $query->ToSql()
        );
    }

    function testLimitWithOffset()
    {
        $query = new SelectQuery('my_table');
        $query->Limit(10, 20);
        $this->assertSame(
            'SELECT * FROM `my_table` LIMIT 10 OFFSET 20',
            $query->ToSql()
        );
    }

    function testLimitReplacesPreviousLimit()
    {
        $query = new SelectQuery('my_table');
        $query->Limit(10);
        $query->Limit(5, 2);
        $this->assertSame(
            'SELECT * FROM `my_table` LIMIT 5 OFFSET 2',
            $query->ToSql()
        );
    }

    #endregion Limit

    #region ToSql --------------------------------------------------------------

    function testToSqlWithSelectAndWhere()
    {
        $query = (new SelectQuery('my_table'))
            ->Select(['column1', 'COUNT(*) AS count'])
            ->Where('column2 = :value')
            ->Bind(['value' => 42]);
        $this->assertSame(
            'SELECT `column1`, COUNT(*) AS count FROM `my_table` WHERE column2 = :value',
            $query->ToSql()
        );
        $this->assertSame(
            ['value' => 42],
            $query->Bindings()
        );
    }

    function testToSqlWithSelectAndOrderBy()
    {
        $query = (new SelectQuery('my_table'))
            ->Select(['column1', 'COUNT(*) AS count'])
            ->OrderBy(['column1', 'column2'=>'desc', 'LENGTH(column1)'=>'ASC']);
        $this->assertSame(
            'SELECT `column1`, COUNT(*) AS count FROM `my_table` ORDER BY `column1`, `column2` DESC, LENGTH(column1) ASC',
            $query->ToSql()
        );
    }

    function testToSqlWithSelectAndLimit()
    {
        $query = (new SelectQuery('my_table'))
            ->Select(['column1', 'COUNT(*) AS count'])
            ->Limit(10, 20);
        $this->assertSame(
            'SELECT `column1`, COUNT(*) AS count FROM `my_table` LIMIT 10 OFFSET 20',
            $query->ToSql()
        );
    }

    function testToSqlWithWhereAndOrderBy()
    {
        $query = (new SelectQuery('my_table'))
            ->Where('column2 = :value')
            ->OrderBy(['column1', 'column2'=>'desc', 'LENGTH(column1)'=>'ASC'])
            ->Bind(['value' => 42]);
        $this->assertSame(
            'SELECT * FROM `my_table` WHERE column2 = :value ORDER BY `column1`, `column2` DESC, LENGTH(column1) ASC',
            $query->ToSql()
        );
        $this->assertSame(
            ['value' => 42],
            $query->Bindings()
        );
    }

    function testToSqlWithWhereAndLimit()
    {
        $query = (new SelectQuery('my_table'))
            ->Where('column2 = :value')
            ->Limit(10, 20)
            ->Bind(['value' => 42]);
        $this->assertSame(
            'SELECT * FROM `my_table` WHERE column2 = :value LIMIT 10 OFFSET 20',
            $query->ToSql()
        );
        $this->assertSame(
            ['value' => 42],
            $query->Bindings()
        );
    }

    function testToSqlWithOrderByAndLimit()
    {
        $query = (new SelectQuery('my_table'))
            ->OrderBy(['column1', 'column2'=>'desc', 'LENGTH(column1)'=>'ASC'])
            ->Limit(10, 20);
        $this->assertSame(
            'SELECT * FROM `my_table` ORDER BY `column1`, `column2` DESC, LENGTH(column1) ASC LIMIT 10 OFFSET 20',
            $query->ToSql()
        );
    }

    function testToSqlWithAllClauses()
    {
        $query = (new SelectQuery('my_table'))
            ->Select(['column1', 'COUNT(*) AS count'])
            ->Where('column2 = :value')
            ->OrderBy(['column1', 'column2'=>'desc', 'LENGTH(column1)'=>'ASC'])
            ->Limit(10, 20)
            ->Bind(['value' => 42]);
        $this->assertSame(
            'SELECT `column1`, COUNT(*) AS count FROM `my_table` WHERE column2 = :value ORDER BY `column1`, `column2` DESC, LENGTH(column1) ASC LIMIT 10 OFFSET 20',
            $query->ToSql()
        );
        $this->assertSame(
            ['value' => 42],
            $query->Bindings()
        );
    }

    #endregion ToSql
}
