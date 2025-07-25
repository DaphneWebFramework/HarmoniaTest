<?php declare(strict_types=1);
use \PHPUnit\Framework\TestCase;
use \PHPUnit\Framework\Attributes\CoversClass;

use \Harmonia\Systems\DatabaseSystem\Queries\DeleteQuery;

#[CoversClass(DeleteQuery::class)]
class DeleteQueryTest extends TestCase
{
    #region Table --------------------------------------------------------------

    function testTableCallsCheckString()
    {
        $query = $this->getMockBuilder(DeleteQuery::class)
            ->onlyMethods(['checkString'])
            ->getMock();
        $query->expects($this->once())
            ->method('checkString')
            ->with('my_table');
        $query->Table('my_table');
    }

    function testTableReplacesPrevious()
    {
        $query = (new DeleteQuery)
            ->Table('my_table')
            ->Where('id = 42');
        $query->Table('another_table');
        $this->assertSame(
            'DELETE FROM `another_table` WHERE id = 42',
            $query->ToSql()
        );
    }

    function testTableReturnsSelf()
    {
        $query = new DeleteQuery();
        $this->assertSame(
            $query,
            $query->Table('my_table')
        );
    }

    #endregion Table

    #region Where --------------------------------------------------------------

    function testWhereCallsCheckString()
    {
        $query = $this->getMockBuilder(DeleteQuery::class)
            ->onlyMethods(['checkString'])
            ->getMock();
        $query->expects($this->once())
            ->method('checkString')
            ->with('column1 = 42');
        $query->Where('column1 = 42');
    }

    function testWhereReplacesPrevious()
    {
        $query = (new DeleteQuery)
            ->Table('my_table')
            ->Where('column1 = 1');
        $query->Where('column2 = 99');
        $this->assertSame(
            'DELETE FROM `my_table` WHERE column2 = 99',
            $query->ToSql()
        );
    }

    function testWhereReturnsSelf()
    {
        $query = new DeleteQuery();
        $this->assertSame(
            $query,
            $query->Where('column1 = 42')
        );
    }

    #endregion Where

    #region ToSql --------------------------------------------------------------

    function testToSqlWithoutTable()
    {
        $query = (new DeleteQuery)
            ->Where('id = :id');
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Table name must be provided.');
        $query->ToSql();
    }

    function testToSqlWithoutCondition()
    {
        $query = (new DeleteQuery)
            ->Table('my_table');
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Condition must be provided.');
        $query->ToSql();
    }

    function testToSqlWithRealWorldScenario()
    {
        // Deleting a user by ID
        $query = (new DeleteQuery)
            ->Table('users')
            ->Where('id = :id')
            ->Bind(['id' => 101]);

        $this->assertSame(
            'DELETE FROM `users` WHERE id = :id',
            $query->ToSql()
        );
        $this->assertSame(
            ['id' => 101],
            $query->Bindings()
        );
    }

    #endregion ToSql
}
