<?php declare(strict_types=1);
use \PHPUnit\Framework\TestCase;
use \PHPUnit\Framework\Attributes\CoversClass;

use \Harmonia\Database\Queries\Query;

class DummyQuery extends Query {
    public function ToSql(): string {
        return "SELECT * FROM `{$this->tableName}`;";
    }
}

#[CoversClass(Query::class)]
class QueryTest extends TestCase
{
    function testToSql()
    {
        $query = new DummyQuery('dummy');
        $this->assertSame('SELECT * FROM `dummy`;', $query->ToSql());
    }

    function testSubstitutions()
    {
        $query = new DummyQuery('dummy');
        $this->assertSame([], $query->Substitutions());
    }
}
