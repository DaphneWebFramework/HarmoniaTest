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

    #endregion Select

    #region Where --------------------------------------------------------------

    function testWhereWithNoSubstitutions()
    {
        $query = new SelectQuery('my_table');
        $query->Where('column2 = 42');
        $this->assertSame(
            'SELECT * FROM `my_table` WHERE column2 = 42',
            $query->ToSql()
        );
    }

    function testWhereWithInvalidSubstitutionKey()
    {
        $query = new SelectQuery('my_table');
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid substitution key: other-value');
        $query->Where('column1 = :1value OR column1 = :other-value',
                      ['other-value' => 43, '1value' => 42]);
    }

    function testWhereWithArraySubstitution()
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage(
            "Invalid substitution value for 'value': Array or resource not allowed.");
        $query = new SelectQuery('my_table');
        $query->Where('column1 = :value', ['value' => [1, 2, 3]]);
    }

    function testWhereWithResourceSubstitution()
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage(
            "Invalid substitution value for 'value': Array or resource not allowed.");
        $file = \fopen(__FILE__, 'r');
        try {
            $query = new SelectQuery('my_table');
            $query->Where('column1 = :value', ['value' => $file]);
        } finally {
            \fclose($file);
        }
    }

    function testWhereWithObjectWithoutToStringSubstitution()
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage(
            "Invalid substitution value for 'value': Object without __toString() method not allowed.");
        $objectWithoutToString = new class {};
        $query = new SelectQuery('my_table');
        $query->Where('column1 = :value', ['value' => $objectWithoutToString]);
    }

    function testWhereWithObjectWithToStringSubstitution()
    {
        $query = new SelectQuery('my_table');
        $objectWithToString = new class {
            public function __toString() { return "I'm a string"; }
        };
        $query->Where('column1 = :value', ['value' => $objectWithToString]);
        $this->assertSame(
            'SELECT * FROM `my_table` WHERE column1 = :value',
            $query->ToSql()
        );
        $this->assertSame(['value' => "I'm a string"], $query->Substitutions());
    }

    function testWhereWithMissingSubstitution()
    {
        $query = new SelectQuery('my_table');
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Missing substitutions: value1');
        $query->Where('column1 = :value1');
    }

    function testWhereWithMissingSubstitutions()
    {
        $query = new SelectQuery('my_table');
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Missing substitutions: value1, value2');
        $query->Where('column1 = :value1 AND column2 = :value2');
    }

    function testWhereWithMissingPlaceholder()
    {
        $query = new SelectQuery('my_table');
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Missing placeholders: value2');
        $query->Where('column1 = :value1', ['value1' => 42, 'value2' => 43]);
    }

    function testWhereWithMissingPlaceholders()
    {
        $query = new SelectQuery('my_table');
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Missing placeholders: value1, value2');
        $query->Where('column1 = value1 AND column2 = value2',
                      ['value1' => 42, 'value2' => 43]);
    }

    function testWhereWithMatchingSubstitutions()
    {
        $query = new SelectQuery('my_table');
        $query->Where('column1 = :value1 AND column2 = :value2',
                      ['value1' => 42, 'value2' => 43]);
        $this->assertSame(
            'SELECT * FROM `my_table` WHERE column1 = :value1 AND column2 = :value2',
            $query->ToSql()
        );
        $this->assertSame(['value1' => 42, 'value2' => 43], $query->Substitutions());
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

    function testOrderByWithMixedSortingDirection()
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

    #endregion Limit

    #region ToSql --------------------------------------------------------------

    function testToSqlWithSelectAndWhere()
    {
        $query = (new SelectQuery('my_table'))
            ->Select(['column1', 'COUNT(*) AS count'])
            ->Where('column2 = :value', ['value' => 42]);
        $this->assertSame(
            'SELECT `column1`, COUNT(*) AS count FROM `my_table` WHERE column2 = :value',
            $query->ToSql()
        );
        $this->assertSame(
            ['value' => 42],
            $query->Substitutions()
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
            ->Where('column2 = :value', ['value' => 42])
            ->OrderBy(['column1', 'column2'=>'desc', 'LENGTH(column1)'=>'ASC']);
        $this->assertSame(
            'SELECT * FROM `my_table` WHERE column2 = :value ORDER BY `column1`, `column2` DESC, LENGTH(column1) ASC',
            $query->ToSql()
        );
        $this->assertSame(
            ['value' => 42],
            $query->Substitutions()
        );
    }

    function testToSqlWithWhereAndLimit()
    {
        $query = (new SelectQuery('my_table'))
            ->Where('column2 = :value', ['value' => 42])
            ->Limit(10, 20);
        $this->assertSame(
            'SELECT * FROM `my_table` WHERE column2 = :value LIMIT 10 OFFSET 20',
            $query->ToSql()
        );
        $this->assertSame(
            ['value' => 42],
            $query->Substitutions()
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
            ->Where('column2 = :value', ['value' => 42])
            ->OrderBy(['column1', 'column2'=>'desc', 'LENGTH(column1)'=>'ASC'])
            ->Limit(10, 20);
        $this->assertSame(
            'SELECT `column1`, COUNT(*) AS count FROM `my_table` WHERE column2 = :value ORDER BY `column1`, `column2` DESC, LENGTH(column1) ASC LIMIT 10 OFFSET 20',
            $query->ToSql()
        );
        $this->assertSame(
            ['value' => 42],
            $query->Substitutions()
        );
    }

    #endregion ToSql
}
