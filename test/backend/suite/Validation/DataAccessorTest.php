<?php declare(strict_types=1);
use \PHPUnit\Framework\TestCase;
use \PHPUnit\Framework\Attributes\CoversClass;
use \PHPUnit\Framework\Attributes\DataProvider;

use \Harmonia\Validation\DataAccessor;

use \Harmonia\Config;

#[CoversClass(DataAccessor::class)]
class DataAccessorTest extends TestCase
{
    private ?Config $originalConfig = null;

    protected function setUp(): void
    {
        $this->originalConfig = Config::ReplaceInstance($this->createConfigMock());
    }

    protected function tearDown(): void
    {
        Config::ReplaceInstance($this->originalConfig);
    }

    private function createConfigMock()
    {
        $mock = $this->createMock(Config::class);
        $mock->expects($this->any())
            ->method('Option')
            ->with('Language')
            ->willReturn('en');
        return $mock;
    }

    #region HasField -----------------------------------------------------------

    function testHasFieldWithExistingFieldNameForArray()
    {
        $sut = new DataAccessor(['name' => 'John']);
        $this->assertTrue($sut->HasField('name'));
    }

    function testHasFieldWithNonExistingFieldNameForArray()
    {
        $sut = new DataAccessor(['name' => 'John']);
        $this->assertFalse($sut->HasField('age'));
    }

    function testHasFieldWithExistingFieldIndexForArray()
    {
        $sut = new DataAccessor(['John']);
        $this->assertTrue($sut->HasField(0));
    }

    function testHasFieldWithNonExistingFieldIndexForArray()
    {
        $sut = new DataAccessor(['John']);
        $this->assertFalse($sut->HasField(2));
    }

    function testHasFieldWithExistingDottedFieldNameForArray()
    {
        $sut = new DataAccessor(['user' => ['name' => 'John']]);
        $this->assertTrue($sut->HasField('user.name'));
    }

    function testHasFieldWithNonExistingDottedFieldNameForArray()
    {
        $sut = new DataAccessor(['user' => ['name' => 'John']]);
        $this->assertFalse($sut->HasField('user.age'));
    }

    function testHasFieldWithNonExistingDottedFieldNameWithScalarParentForArray()
    {
        $sut = new DataAccessor(['user' => 42]);
        $this->assertFalse($sut->HasField('user.age'));
    }

    function testHasFieldWithExistingDottedFieldIndexForArray()
    {
        $sut = new DataAccessor([['John']]);
        $this->assertTrue($sut->HasField('0.0'));
    }

    function testHasFieldWithNonExistingDottedFieldIndexForArray()
    {
        $sut = new DataAccessor([['John']]);
        $this->assertFalse($sut->HasField('0.1'));
    }

    function testHasFieldWithExistingFieldForObject()
    {
        $data = new \stdClass();
        $data->name = 'John';
        $sut = new DataAccessor($data);
        $this->assertTrue($sut->HasField('name'));
    }

    function testHasFieldWithNonExistingFieldForObject()
    {
        $data = new \stdClass();
        $data->name = 'John';
        $sut = new DataAccessor($data);
        $this->assertFalse($sut->HasField('age'));
    }

    function testHasFieldWithExistingDottedFieldForObject()
    {
        $data = new \stdClass();
        $data->user = new \stdClass();
        $data->user->name = 'John';
        $sut = new DataAccessor($data);
        $this->assertTrue($sut->HasField('user.name'));
    }

    function testHasFieldWithNonExistingDottedFieldForObject()
    {
        $data = new \stdClass();
        $data->user = new \stdClass();
        $data->user->name = 'John';
        $sut = new DataAccessor($data);
        $this->assertFalse($sut->HasField('user.age'));
    }

    function testHasFieldWithNonExistingDottedFieldWithScalarParentForObject()
    {
        $data = new \stdClass();
        $data->user = 42;
        $sut = new DataAccessor($data);
        $this->assertFalse($sut->HasField('user.age'));
    }

    function testHasFieldWithIntegerPropertyForObject()
    {
        $data = new \stdClass();
        $data->{123} = 'value';
        $sut = new DataAccessor($data);
        $this->assertTrue($sut->HasField(123));
    }

    #endregion HasField

    #region GetField -----------------------------------------------------------

    function testGetFieldWithExistingFieldNameForArray()
    {
        $sut = new DataAccessor(['name' => 'John']);
        $this->assertSame('John', $sut->GetField('name'));
    }

    function testGetFieldWithNonExistingFieldNameForArray()
    {
        $sut = new DataAccessor(['name' => 'John']);
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage("Field 'age' does not exist.");
        $sut->GetField('age');
    }

    function testGetFieldWithExistingFieldIndexForArray()
    {
        $sut = new DataAccessor(['John']);
        $this->assertSame('John', $sut->GetField(0));
    }

    function testGetFieldWithNonExistingFieldIndexForArray()
    {
        $sut = new DataAccessor(['John']);
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage("Field '2' does not exist.");
        $sut->GetField(2);
    }

    function testGetFieldWithExistingDottedFieldNameForArray()
    {
        $sut = new DataAccessor(['user' => ['name' => 'John']]);
        $this->assertSame('John', $sut->GetField('user.name'));
    }

    function testGetFieldWithNonExistingDottedFieldNameForArray()
    {
        $sut = new DataAccessor(['user' => ['name' => 'John']]);
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage("Field 'user.age' does not exist.");
        $sut->GetField('user.age');
    }

    function testGetFieldWithNonExistingDottedFieldNameWithScalarParentForArray()
    {
        $sut = new DataAccessor(['user' => 42]);
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage("Field 'user.age' does not exist.");
        $sut->GetField('user.age');
    }

    function testGetFieldWithExistingDottedFieldIndexForArray()
    {
        $sut = new DataAccessor([['John']]);
        $this->assertSame('John', $sut->GetField('0.0'));
    }

    function testGetFieldWithNonExistingDottedFieldIndexForArray()
    {
        $sut = new DataAccessor([['John']]);
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage("Field '0.1' does not exist.");
        $sut->GetField('0.1');
    }

    function testGetFieldWithExistingFieldForObject()
    {
        $data = new \stdClass();
        $data->name = 'John';
        $sut = new DataAccessor($data);
        $this->assertSame('John', $sut->GetField('name'));
    }

    function testGetFieldWithNonExistingFieldForObject()
    {
        $data = new \stdClass();
        $data->name = 'John';
        $sut = new DataAccessor($data);
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage("Field 'age' does not exist.");
        $sut->GetField('age');
    }

    function testGetFieldWithExistingDottedFieldForObject()
    {
        $data = new \stdClass();
        $data->user = new \stdClass();
        $data->user->name = 'John';
        $sut = new DataAccessor($data);
        $this->assertSame('John', $sut->GetField('user.name'));
    }

    function testGetFieldWithNonExistingDottedFieldForObject()
    {
        $data = new \stdClass();
        $data->user = new \stdClass();
        $data->user->name = 'John';
        $sut = new DataAccessor($data);
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage("Field 'user.age' does not exist.");
        $sut->GetField('user.age');
    }

    function testGetFieldWithNonExistingDottedFieldWithScalarParentForObject()
    {
        $data = new \stdClass();
        $data->user = 42;
        $sut = new DataAccessor($data);
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage("Field 'user.age' does not exist.");
        $sut->GetField('user.age');
    }

    function testGetFieldWithIntegerPropertyForObject()
    {
        $data = new \stdClass();
        $data->{123} = 'value';
        $sut = new DataAccessor($data);
        $this->assertSame('value', $sut->GetField(123));
    }

    #endregion GetField
}
