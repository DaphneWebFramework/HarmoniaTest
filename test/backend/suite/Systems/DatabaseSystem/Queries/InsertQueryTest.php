<?php declare(strict_types=1);
use \PHPUnit\Framework\TestCase;
use \PHPUnit\Framework\Attributes\CoversClass;

use \Harmonia\Systems\DatabaseSystem\Queries\InsertQuery;

#[CoversClass(InsertQuery::class)]
class InsertQueryTest extends TestCase
{
    #region Table --------------------------------------------------------------

    function testTableCallsCheckString()
    {
        $query = $this->getMockBuilder(InsertQuery::class)
            ->onlyMethods(['checkString'])
            ->getMock();
        $query->expects($this->once())
            ->method('checkString')
            ->with('my_table');
        $query->Table('my_table');
    }

    function testTableReplacesPrevious()
    {
        $query = (new InsertQuery)
            ->Table('my_table')
            ->Values('value1');
        $query->Table('another_table');
        $this->assertSame(
            'INSERT INTO another_table VALUES (value1)',
            $query->ToSql()
        );
    }

    function testTableReturnsSelf()
    {
        $query = new InsertQuery();
        $this->assertSame(
            $query,
            $query->Table('my_table')
        );
    }

    #endregion Table

    #region Columns ------------------------------------------------------------

    function testColumnsCallsFormatStringList()
    {
        $query = $this->getMockBuilder(InsertQuery::class)
            ->onlyMethods(['formatStringList'])
            ->getMock();
        $query->expects($this->once())
            ->method('formatStringList')
            ->with('column1', 'column2');
        $query->Columns('column1', 'column2');
    }

    function testColumnsReplacesPrevious()
    {
        $query = (new InsertQuery)
            ->Table('my_table')
            ->Columns('column1')
            ->Values('value1');
        $query->Columns('column2');
        $this->assertSame(
            'INSERT INTO my_table (column2) VALUES (value1)',
            $query->ToSql()
        );
    }

    function testColumnsReturnsSelf()
    {
        $query = new InsertQuery();
        $this->assertSame(
            $query,
            $query->Columns('column1')
        );
    }

    #endregion Columns

    #region Values -------------------------------------------------------------

    function testValuesCallsFormatStringList()
    {
        $query = $this->getMockBuilder(InsertQuery::class)
            ->onlyMethods(['formatStringList'])
            ->getMock();
        $query->expects($this->once())
            ->method('formatStringList')
            ->with('value1', 'value2');
        $query->Values('value1', 'value2');
    }

    function testValuesReplacesPrevious()
    {
        $query = (new InsertQuery)
            ->Table('my_table')
            ->Values('value1');
        $query->Values('value2');
        $this->assertSame(
            'INSERT INTO my_table VALUES (value2)',
            $query->ToSql()
        );
    }

    function testValuesReturnsSelf()
    {
        $query = new InsertQuery();
        $this->assertSame(
            $query,
            $query->Values('value1')
        );
    }

    #endregion Values

    #region ToSql --------------------------------------------------------------

    function testToSqlWithoutTable()
    {
        $query = (new InsertQuery)
            ->Values('value1');
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Table name must be provided.');
        $query->ToSql();
    }

    function testToSqlWithoutValues()
    {
        $query = (new InsertQuery)
            ->Table('my_table');
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Values must be provided.');
        $query->ToSql();
    }

    function testToSqlWithRealWorldScenario()
    {
        // Insert a new user with specified columns and values.
        $query = (new InsertQuery)
            ->Table('users')
            ->Columns('id', 'name', 'email', 'status', 'createdAt')
            ->Values(':id', ':name', ':email', ':status', ':createdAt')
            ->Bind([
                'id'        => 101,
                'name'      => 'John Doe',
                'email'     => 'john.doe@example.com',
                'status'    => 'active',
                'createdAt' => '2025-02-23 15:30:00'
            ]);

        $this->assertSame(
            'INSERT INTO users (id, name, email, status, createdAt) VALUES (:id, :name, :email, :status, :createdAt)',
            $query->ToSql()
        );
        $this->assertSame(
            [ 'id' => 101, 'name' => 'John Doe', 'email' => 'john.doe@example.com',
              'status'    => 'active', 'createdAt' => '2025-02-23 15:30:00' ],
            $query->Bindings()
        );
    }

    #endregion ToSql
}
