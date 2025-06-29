<?php declare(strict_types=1);
use \PHPUnit\Framework\TestCase;
use \PHPUnit\Framework\Attributes\CoversClass;

use \Harmonia\Systems\DatabaseSystem\Queries\RawQuery;

#[CoversClass(RawQuery::class)]
class RawQueryTest extends TestCase
{
    #region Sql ----------------------------------------------------------------

    function testSqlCallsCheckString()
    {
        $query = $this->getMockBuilder(RawQuery::class)
            ->onlyMethods(['checkString'])
            ->getMock();

        $query->expects($this->once())
            ->method('checkString')
            ->with('SELECT DATABASE()');

        $query->Sql('SELECT DATABASE()');
    }

    function testSqlReplacesPrevious()
    {
        $query = (new RawQuery)
            ->Sql('SELECT 1');
        $query->Sql('SELECT 2');

        $this->assertSame(
            'SELECT 2',
            $query->ToSql()
        );
    }

    function testSqlReturnsSelf()
    {
        $query = new RawQuery();
        $this->assertSame(
            $query,
            $query->Sql('SELECT 1')
        );
    }

    #endregion Sql

    #region ToSql --------------------------------------------------------------

    function testToSqlWithoutSql()
    {
        $query = new RawQuery();
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('SQL string must be provided.');
        $query->ToSql();
    }

    function testToSqlWithBindings()
    {
        $query = (new RawQuery)
            ->Sql('SHOW TABLES LIKE :pattern')
            ->Bind(['pattern' => 'user_%']);

        $this->assertSame(
            'SHOW TABLES LIKE :pattern',
            $query->ToSql()
        );
        $this->assertSame(
            ['pattern' => 'user_%'],
            $query->Bindings()
        );
    }

    #endregion ToSql
}
