<?php declare(strict_types=1);
use \PHPUnit\Framework\TestCase;
use \PHPUnit\Framework\Attributes\CoversClass;

use \Harmonia\Database\Queries\UpdateQuery;

#[CoversClass(UpdateQuery::class)]
class UpdateQueryTest extends TestCase
{
    #region Table --------------------------------------------------------------

    function testTableCallsCheckString()
    {
        $query = $this->getMockBuilder(UpdateQuery::class)
            ->onlyMethods(['checkString'])
            ->getMock();
        $query->expects($this->once())
            ->method('checkString')
            ->with('my_table');
        $query->Table('my_table');
    }

    function testTableReplacesPrevious()
    {
        $query = (new UpdateQuery)
            ->Table('my_table')
            ->Columns('email')
            ->Values("'user@mail.com'")
            ->Where('id = 42');
        $query->Table('another_table');
        $this->assertSame(
            "UPDATE another_table SET email = 'user@mail.com' WHERE id = 42",
            $query->ToSql()
        );
    }

    function testTableReturnsSelf()
    {
        $query = new UpdateQuery();
        $this->assertSame(
            $query,
            $query->Table('my_table')
        );
    }

    #endregion Table

    #region Columns ------------------------------------------------------------

    function testColumnsCallsCheckStringList()
    {
        $query = $this->getMockBuilder(UpdateQuery::class)
            ->onlyMethods(['checkStringList'])
            ->getMock();
        $query->expects($this->once())
            ->method('checkStringList')
            ->with('column1', 'column2');
        $query->Columns('column1', 'column2');
    }

    function testColumnsReplacesPrevious()
    {
        $query = (new UpdateQuery)
            ->Table('my_table')
            ->Columns('column1')
            ->Values('100')
            ->Where('id = 42');
        $query->Columns('column2');
        $this->assertSame(
            'UPDATE my_table SET column2 = 100 WHERE id = 42',
            $query->ToSql()
        );
    }

    function testColumnsReturnsSelf()
    {
        $query = new UpdateQuery();
        $this->assertSame(
            $query,
            $query->Columns('column1')
        );
    }

    #endregion Columns

    #region Values -------------------------------------------------------------

    function testValuesCallsCheckStringList()
    {
        $query = $this->getMockBuilder(UpdateQuery::class)
            ->onlyMethods(['checkStringList'])
            ->getMock();
        $query->expects($this->once())
            ->method('checkStringList')
            ->with(':value1', ':value2');
        $query->Values(':value1', ':value2');
    }

    function testValuesReplacesPrevious()
    {
        $query = (new UpdateQuery)
            ->Table('my_table')
            ->Columns('column1')
            ->Values('100')
            ->Where('id = 42');
        $query->Values('200');
        $this->assertSame(
            'UPDATE my_table SET column1 = 200 WHERE id = 42',
            $query->ToSql()
        );
    }

    function testValuesReturnsSelf()
    {
        $query = new UpdateQuery();
        $this->assertSame(
            $query,
            $query->Values(':value1')
        );
    }

    #endregion Values

    #region Where --------------------------------------------------------------

    function testWhereCallsCheckString()
    {
        $query = $this->getMockBuilder(UpdateQuery::class)
            ->onlyMethods(['checkString'])
            ->getMock();
        $query->expects($this->once())
            ->method('checkString')
            ->with('column1 = 42');
        $query->Where('column1 = 42');
    }

    function testWhereReplacesPrevious()
    {
        $query = (new UpdateQuery)
            ->Table('my_table')
            ->Columns('column1')
            ->Values('100')
            ->Where('column1 = 1');
        $query->Where('column2 = 99');
        $this->assertSame(
            'UPDATE my_table SET column1 = 100 WHERE column2 = 99',
            $query->ToSql()
        );
    }

    function testWhereReturnsSelf()
    {
        $query = new UpdateQuery();
        $this->assertSame(
            $query,
            $query->Where('column1 = 42')
        );
    }

    #endregion Where

    #region ToSql --------------------------------------------------------------

    function testToSqlWithoutTable()
    {
        $query = (new UpdateQuery)
            ->Columns('column1')
            ->Values(':value1')
            ->Where('id = :id');
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Table name must be provided.');
        $query->ToSql();
    }

    function testToSqlWithoutColumns()
    {
        $query = (new UpdateQuery)
            ->Table('my_table')
            ->Values(':value1')
            ->Where('id = :id');
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Columns must be provided.');
        $query->ToSql();
    }

    function testToSqlWithoutValues()
    {
        $query = (new UpdateQuery)
            ->Table('my_table')
            ->Columns('column1')
            ->Where('id = :id');
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Values must be provided.');
        $query->ToSql();
    }

    function testToSqlWithDifferentNumberOfColumnsAndValues()
    {
        $query = (new UpdateQuery)
            ->Table('my_table')
            ->Columns('column1', 'column2')
            ->Values(':value1')
            ->Where('id = :id');
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Number of columns must match number of values.');
        $query->ToSql();
    }

    function testToSqlWithRealWorldScenario()
    {
        // Updating user email and status
        $query = (new UpdateQuery)
            ->Table('users')
            ->Columns('email', 'status')
            ->Values(':email', ':status')
            ->Where('id = :id')
            ->Bind([
                'email'  => 'new.email@example.com',
                'status' => 'active',
                'id'     => 101
            ]);

        $this->assertSame(
            'UPDATE users SET email = :email, status = :status WHERE id = :id',
            $query->ToSql()
        );

        $this->assertSame(
            ['email' => 'new.email@example.com', 'status' => 'active', 'id' => 101],
            $query->Bindings()
        );
    }

    #endregion ToSql
}
