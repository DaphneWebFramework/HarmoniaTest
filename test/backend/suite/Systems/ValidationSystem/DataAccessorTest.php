<?php declare(strict_types=1);
use \PHPUnit\Framework\TestCase;
use \PHPUnit\Framework\Attributes\CoversClass;
use \PHPUnit\Framework\Attributes\DataProvider;

use \Harmonia\Systems\ValidationSystem\DataAccessor;

use \Harmonia\Config;
use \Harmonia\Core\CArray;
use \TestToolkit\AccessHelper;

#[CoversClass(DataAccessor::class)]
class DataAccessorTest extends TestCase
{
    private ?Config $originalConfig = null;

    protected function setUp(): void
    {
        $this->originalConfig = Config::ReplaceInstance($this->createConfig());
    }

    protected function tearDown(): void
    {
        Config::ReplaceInstance($this->originalConfig);
    }

    private function createConfig(): Config
    {
        $mock = $this->createMock(Config::class);
        $mock->method('Option')->with('Language')->willReturn('en');
        return $mock;
    }

    #region __construct --------------------------------------------------------

    function testConstructWithArray()
    {
        $sut = new DataAccessor(['name' => 'John']);
        $this->assertIsArray(AccessHelper::GetProperty($sut, 'data'));
        $this->assertSame('John', $sut->GetField('name'));
    }

    function testConstructWithObject()
    {
        $data = new \stdClass();
        $data->name = 'John';
        $sut = new DataAccessor($data);
        $this->assertIsObject(AccessHelper::GetProperty($sut, 'data'));
        $this->assertSame('John', $sut->GetField('name'));
    }

    function testConstructWithCArray()
    {
        $data = new CArray(['name' => 'John']);
        $sut = new DataAccessor($data);
        $this->assertIsArray(AccessHelper::GetProperty($sut, 'data'));
        $this->assertSame('John', $sut->GetField('name'));
    }

    #endregion __construct

    #region Data ---------------------------------------------------------------

    function testDataReturnsOriginalArray()
    {
        $sut = new DataAccessor(['name' => 'John']);
        $this->assertSame(['name' => 'John'], $sut->Data());
    }

    function testDataReturnsOriginalObject()
    {
        $data = new \stdClass();
        $data->name = 'John';
        $sut = new DataAccessor($data);
        $this->assertSame($data, $sut->Data());
    }

    #endregion Data

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

    #region GetFieldOrDefault --------------------------------------------------

    function testGetFieldOrDefaultReturnsNullWhenFieldDoesNotExist()
    {
        $sut = $this->getMockBuilder(DataAccessor::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['HasField', 'GetField'])
            ->getMock();

        $sut->expects($this->once())
            ->method('HasField')
            ->with('key')
            ->willReturn(false);
        $sut->expects($this->never())
            ->method('GetField');

        $this->assertNull($sut->GetFieldOrDefault('key'));
    }

    function testGetFieldOrDefaultReturnsDefaultWhenFieldDoesNotExist()
    {
        $sut = $this->getMockBuilder(DataAccessor::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['HasField', 'GetField'])
            ->getMock();

        $sut->expects($this->once())
            ->method('HasField')
            ->with('key')
            ->willReturn(false);
        $sut->expects($this->never())
            ->method('GetField');

        $this->assertSame('default', $sut->GetFieldOrDefault('key', 'default'));
    }

    function testGetFieldOrDefaultReturnsValueWhenFieldExists()
    {
        $sut = $this->getMockBuilder(DataAccessor::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['HasField', 'GetField'])
            ->getMock();

        $sut->expects($this->once())
            ->method('HasField')
            ->with('key')
            ->willReturn(true);
        $sut->expects($this->once())
            ->method('GetField')
            ->with('key')
            ->willReturn('value');

        $this->assertSame('value', $sut->GetFieldOrDefault('key', 'default'));
    }

    #endregion GetFieldOrDefault
}
