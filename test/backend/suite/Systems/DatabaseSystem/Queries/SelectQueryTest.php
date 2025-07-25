<?php declare(strict_types=1);
use \PHPUnit\Framework\TestCase;
use \PHPUnit\Framework\Attributes\CoversClass;

use \Harmonia\Systems\DatabaseSystem\Queries\SelectQuery;

#[CoversClass(SelectQuery::class)]
class SelectQueryTest extends TestCase
{
    #region Table --------------------------------------------------------------

    function testTableCallsCheckString()
    {
        $query = $this->getMockBuilder(SelectQuery::class)
            ->onlyMethods(['checkString'])
            ->getMock();
        $query->expects($this->once())
            ->method('checkString')
            ->with('my_table');
        $query->Table('my_table');
    }

    function testTableReplacesPrevious()
    {
        $query = (new SelectQuery)
            ->Table('my_table');
        $query->Table('another_table');
        $this->assertSame(
            'SELECT * FROM `another_table`',
            $query->ToSql()
        );
    }

    function testTableReturnsSelf()
    {
        $query = new SelectQuery();
        $this->assertSame(
            $query,
            $query->Table('my_table')
        );
    }

    #endregion Table

    #region Columns ------------------------------------------------------------

    function testColumnsCallsFormatStringList()
    {
        $query = $this->getMockBuilder(SelectQuery::class)
            ->onlyMethods(['formatStringList'])
            ->getMock();
        $query->expects($this->once())
            ->method('formatStringList')
            ->with('column1', 'column2');
        $query->Columns('column1', 'column2');
    }

    function testColumnsReplacesPrevious()
    {
        $query = (new SelectQuery)
            ->Table('my_table')
            ->Columns('column1');
        $query->Columns('column2');
        $this->assertSame(
            'SELECT column2 FROM `my_table`',
            $query->ToSql()
        );
    }

    function testColumnsReturnsSelf()
    {
        $query = new SelectQuery();
        $this->assertSame(
            $query,
            $query->Columns('column1')
        );
    }

    #endregion Columns

    #region Where --------------------------------------------------------------

    function testWhereCallsCheckString()
    {
        $query = $this->getMockBuilder(SelectQuery::class)
            ->onlyMethods(['checkString'])
            ->getMock();
        $query->expects($this->once())
            ->method('checkString')
            ->with('column1 = 42');
        $query->Where('column1 = 42');
    }

    function testWhereReplacesPrevious()
    {
        $query = (new SelectQuery)
            ->Table('my_table')
            ->Where('column1 = 1');
        $query->Where('column2 = 99');
        $this->assertSame(
            'SELECT * FROM `my_table` WHERE column2 = 99',
            $query->ToSql()
        );
    }

    function testWhereReturnsSelf()
    {
        $query = new SelectQuery();
        $this->assertSame(
            $query,
            $query->Where('column1 = 42')
        );
    }

    #endregion Where

    #region OrderBy ------------------------------------------------------------

    function testOrderByCallsFormatStringList()
    {
        $query = $this->getMockBuilder(SelectQuery::class)
            ->onlyMethods(['formatStringList'])
            ->getMock();
        $query->expects($this->once())
            ->method('formatStringList')
            ->with('column1', 'column2 DESC');
        $query->OrderBy('column1', 'column2 DESC');
    }

    function testOrderByReplacesPrevious()
    {
        $query = (new SelectQuery)
            ->Table('my_table')
            ->OrderBy('column1 ASC');
        $query->OrderBy('column2 DESC');
        $this->assertSame(
            'SELECT * FROM `my_table` ORDER BY column2 DESC',
            $query->ToSql()
        );
    }

    function testOrderByReturnsSelf()
    {
        $query = new SelectQuery();
        $this->assertSame(
            $query,
            $query->OrderBy('column1')
        );
    }

    #endregion OrderBy

    #region Limit --------------------------------------------------------------

    function testLimitWithNegativeLimit()
    {
        $query = new SelectQuery();
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Limit must be a non-negative integer.');
        $query->Limit(-1);
    }

    function testLimitWithNegativeOffset()
    {
        $query = new SelectQuery();
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Offset must be a non-negative integer.');
        $query->Limit(10, -1);
    }

    function testLimitWithoutOffset()
    {
        $query = (new SelectQuery)
            ->Table('my_table')
            ->Limit(10);
        $this->assertSame(
            'SELECT * FROM `my_table` LIMIT 10',
            $query->ToSql()
        );
    }

    function testLimitWithOffset()
    {
        $query = (new SelectQuery)
            ->Table('my_table')
            ->Limit(10, 20);
        $this->assertSame(
            'SELECT * FROM `my_table` LIMIT 10 OFFSET 20',
            $query->ToSql()
        );
    }

    function testLimitReplacesPrevious()
    {
        $query = (new SelectQuery)
            ->Table('my_table')
            ->Limit(10);
        $query->Limit(5, 2);
        $this->assertSame(
            'SELECT * FROM `my_table` LIMIT 5 OFFSET 2',
            $query->ToSql()
        );
    }

    function testLimitReturnsSelf()
    {
        $query = new SelectQuery();
        $this->assertSame(
            $query,
            $query->Limit(10)
        );
    }

    #endregion Limit

    #region ToSql --------------------------------------------------------------

    function testToSqlWithoutTable()
    {
        $query = new SelectQuery();
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Table name must be provided.');
        $query->ToSql();
    }

    function testToSqlWithRealWorldScenario()
    {
        // Fetch active users who registered after a specific date.
        $query = (new SelectQuery)
            ->Table('users')
            ->Columns('name', 'email', 'COUNT(*) AS loginCount')
            ->Where('status = :status AND createdAt >= :startDate')
            ->OrderBy(
                'lastLogin DESC', // most recent login first
                'name ASC'        // then alphabetically by name
            )
            ->Limit(20, 10) // 20 records per page, skipping the first 10 (e.g., page 2)
            ->Bind([
                'status'    => 'active',
                'startDate' => '2025-01-01'
            ]);

        $this->assertSame(
            'SELECT name, email, COUNT(*) AS loginCount FROM `users`'
          . ' WHERE status = :status AND createdAt >= :startDate'
          . ' ORDER BY lastLogin DESC, name ASC'
          . ' LIMIT 20 OFFSET 10'
          , $query->ToSql()
        );
        $this->assertSame(
            ['status' => 'active', 'startDate' => '2025-01-01'],
            $query->Bindings()
        );
    }

    #endregion ToSql
}
