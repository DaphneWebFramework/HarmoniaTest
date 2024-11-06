<?php declare(strict_types=1);
use \PHPUnit\Framework\TestCase;
use \PHPUnit\Framework\Attributes\CoversClass;
use \PHPUnit\Framework\Attributes\DataProvider;
use \PHPUnit\Framework\Attributes\DataProviderExternal;

use \Harmonia\Core\CSequentialArray;
use \Harmonia\Core\CArray; // testCopyConstructorWith[Non]SequentialCArray

use \TestToolkit\AccessHelper;
use \TestToolkit\DataHelper;

#[CoversClass(CSequentialArray::class)]
class CSequentialArrayTest extends TestCase
{
    #region __construct --------------------------------------------------------

    #[DataProviderExternal(DataHelper::class, 'NonArrayProvider')]
    function testConstructorWithInvalidValueType($value)
    {
        $this->expectException(\TypeError::class);
        new CSequentialArray($value);
    }

    function testConstructorWithNonSequentialArray()
    {
        $this->expectException(\InvalidArgumentException::class);
        new CSequentialArray([1 => 'one', 3 => 'three']);
    }

    function testConstructorWithMixedKeysArray()
    {
        $this->expectException(\InvalidArgumentException::class);
        new CSequentialArray([0 => 'a', 'key' => 'value', 2 => 'c']);
    }

    function testConstructorWithNumericStringKeysTreatedAsInteger()
    {
        $carr = new CSequentialArray([0 => 'a', '1' => 'b', 2 => 'c']);
        $this->assertSame(['a', 'b', 'c'],
            AccessHelper::GetNonPublicProperty($carr, 'value'));
    }

    function testDefaultConstructor()
    {
        $carr = new CSequentialArray();
        $this->assertSame([], AccessHelper::GetNonPublicProperty($carr, 'value'));
    }

    function testCopyConstructor()
    {
        $original = new CSequentialArray([1, 2, 3]);
        $copy = new CSequentialArray($original);
        $this->assertSame(
            AccessHelper::GetNonPublicProperty($original, 'value'),
            AccessHelper::GetNonPublicProperty($copy, 'value')
        );
    }

    function testCopyConstructorWithNonSequentialCArray()
    {
        $carr = new CArray([1 => 'one', 3 => 'three']);
        $this->expectException(\InvalidArgumentException::class);
        new CSequentialArray($carr);
    }

    function testCopyConstructorWithSequentialCArray()
    {
        $carr = new CArray([0 => 'a', 1 => 'b', 2 => 'c']);
        $cseqarr = new CSequentialArray($carr);
        $this->assertSame(
            AccessHelper::GetNonPublicProperty($carr, 'value'),
            AccessHelper::GetNonPublicProperty($cseqarr, 'value')
        );
    }

    function testConstructorWithNativeArray()
    {
        $arr = [1, 2, 3];
        $carr = new CSequentialArray($arr);
        $this->assertSame($arr, AccessHelper::GetNonPublicProperty($carr, 'value'));
    }

    #endregion __construct

    #region Has ----------------------------------------------------------------

    #[DataProviderExternal(DataHelper::class, 'NonStringOrIntegerProvider')]
    function testHasWithInvalidIndexType($index)
    {
        $carr = new CSequentialArray();
        $this->expectException(\TypeError::class);
        $carr->Has($index);
    }

    function testHasWithStringIndex()
    {
        $carr = new CSequentialArray();
        $this->expectException(\InvalidArgumentException::class);
        $carr->Has('key');
    }

    #[DataProvider('hasDataProvider')]
    function testHas(bool $expected, array $arr, string|int $index)
    {
        $carr = new CSequentialArray($arr);
        $this->assertSame($expected, $carr->Has($index));
    }

    #endregion Has

    #region Get ----------------------------------------------------------------

    #[DataProviderExternal(DataHelper::class, 'NonStringOrIntegerProvider')]
    function testGetWithInvalidIndexType($index)
    {
        $carr = new CSequentialArray();
        $this->expectException(\TypeError::class);
        $carr->Get($index);
    }

    function testGetWithStringIndex()
    {
        $carr = new CSequentialArray();
        $this->expectException(\InvalidArgumentException::class);
        $carr->Get('key');
    }

    #[DataProvider('getDataProvider')]
    function testGet(mixed $expected, array $arr, string|int $index,
        mixed $defaultValue = null)
    {
        $carr = new CSequentialArray($arr);
        $this->assertSame($expected, $carr->Get($index, $defaultValue));
    }

    #endregion Get

    #region Set ----------------------------------------------------------------

    #[DataProviderExternal(DataHelper::class, 'NonStringOrIntegerProvider')]
    function testSetWithInvalidIndexType($index)
    {
        $carr = new CSequentialArray();
        $this->expectException(\TypeError::class);
        $carr->Set($index, 1);
    }

    function testSetWithStringIndex()
    {
        $carr = new CSequentialArray();
        $this->expectException(\InvalidArgumentException::class);
        $carr->Set('key', 1);
    }

    function testSetWithNegativeIndex()
    {
        $carr = new CSequentialArray();
        $this->expectException(\OutOfRangeException::class);
        $carr->Set(-1, 1);
    }

    function testSetWithIndexExceedingSize()
    {
        $carr = new CSequentialArray([100, 101, 102]);
        $this->expectException(\OutOfRangeException::class);
        $carr->Set(3, 103);
    }

    #[DataProvider('setDataProvider')]
    public function testSet(array $expected, array $arr, string|int $index, mixed $value)
    {
        $carr = new CSequentialArray($arr);
        $carr->Set($index, $value);
        $this->assertSame($expected, AccessHelper::GetNonPublicProperty($carr, 'value'));
    }

    function testSetChaining()
    {
        $carr = new CSequentialArray([100, 101]);
        $carr->Set(0, 200)->Set(1, 201);
        $this->assertSame([200, 201],
            AccessHelper::GetNonPublicProperty($carr, 'value'));
    }

    #endregion Set

    #region Delete -------------------------------------------------------------

    #[DataProviderExternal(DataHelper::class, 'NonStringOrIntegerProvider')]
    function testDeleteWithInvalidIndexType($index)
    {
        $carr = new CSequentialArray();
        $this->expectException(\TypeError::class);
        $carr->Delete($index);
    }

    function testDeleteWithStringIndex()
    {
        $carr = new CSequentialArray();
        $this->expectException(\InvalidArgumentException::class);
        $carr->Delete('key');
    }

    function testDeleteWithNegativeIndex()
    {
        $carr = new CSequentialArray();
        $this->expectException(\OutOfRangeException::class);
        $carr->Delete(-1);
    }

    function testDeleteWithIndexExceedingSize()
    {
        $carr = new CSequentialArray([100, 101, 102]);
        $this->expectException(\OutOfRangeException::class);
        $carr->Delete(3);
    }

    #[DataProvider('deleteDataProvider')]
    public function testDelete(array $expected, array $arr, string|int $index)
    {
        $carr = new CSequentialArray($arr);
        $carr->Delete($index);
        $this->assertSame($expected, AccessHelper::GetNonPublicProperty($carr, 'value'));
    }

    function testDeleteChaining()
    {
        $carr = new CSequentialArray([100, 101, 102, 103]);
        $carr->Delete(1)->Delete(2);
        $this->assertSame([100, 102],
            AccessHelper::GetNonPublicProperty($carr, 'value'));
    }

    #endregion Delete

    #region PushBack -----------------------------------------------------------

    function testPushBack()
    {
        $carr = new CSequentialArray([1, 2]);
        $carr->PushBack(3)->PushBack(4);
        $this->assertSame([1, 2, 3, 4],
            AccessHelper::GetNonPublicProperty($carr, 'value'));
    }

    #endregion PushBack

    #region PushFront ----------------------------------------------------------

    function testPushFront()
    {
        $carr = new CSequentialArray([3, 4]);
        $carr->PushFront(2)->PushFront(1);
        $this->assertSame([1, 2, 3, 4],
            AccessHelper::GetNonPublicProperty($carr, 'value'));
    }

    #endregion PushFront

    #region PopBack ------------------------------------------------------------

    function testPopBack()
    {
        $carr = new CSequentialArray([1, 2, 3, 4]);
        $this->assertSame(4, $carr->PopBack());
        $this->assertSame(3, $carr->PopBack());
        $this->assertSame([1, 2],
            AccessHelper::GetNonPublicProperty($carr, 'value'));
    }

    function testPopBackWithEmptyArray()
    {
        $carr = new CSequentialArray();
        $this->assertNull($carr->PopBack());
        $this->assertSame([], AccessHelper::GetNonPublicProperty($carr, 'value'));
    }

    #endregion PopBack

    #region PopFront -----------------------------------------------------------

    function testPopFront()
    {
        $carr = new CSequentialArray([1, 2, 3, 4]);
        $this->assertSame(1, $carr->PopFront());
        $this->assertSame(2, $carr->PopFront());
        $this->assertSame([3, 4],
            AccessHelper::GetNonPublicProperty($carr, 'value'));
    }

    function testPopFrontWithEmptyArray()
    {
        $carr = new CSequentialArray();
        $this->assertNull($carr->PopFront());
        $this->assertSame([], AccessHelper::GetNonPublicProperty($carr, 'value'));
    }

    #endregion PopFront

    #region InsertBefore -------------------------------------------------------

    #[DataProviderExternal(DataHelper::class, 'NonIntegerProvider')]
    function testInsertBeforeWithInvalidIndexType($index)
    {
        $cstr = new CSequentialArray();
        $this->expectException(\TypeError::class);
        $cstr->InsertBefore($index, 99);
    }

    function testInsertBeforeWithNegativeIndex()
    {
        $carr = new CSequentialArray();
        $this->expectException(\OutOfRangeException::class);
        $carr->InsertBefore(-1, 99);
    }

    function testInsertBeforeWithIndexExceedingSize()
    {
        $carr = new CSequentialArray([100, 101, 102]);
        $this->expectException(\OutOfRangeException::class);
        $carr->InsertBefore(4, 103);
    }

    function testInsertBeforeAtBeginning()
    {
        $carr = new CSequentialArray([101, 102, 103]);
        $carr->InsertBefore(0, 100);
        $this->assertSame([100, 101, 102, 103],
            AccessHelper::GetNonPublicProperty($carr, 'value'));
    }

    function testInsertBeforeInMiddle()
    {
        $carr = new CSequentialArray([100, 102, 103]);
        $carr->InsertBefore(1, 101);
        $this->assertSame([100, 101, 102, 103],
            AccessHelper::GetNonPublicProperty($carr, 'value'));
    }

    function testInsertBeforeAtEnd()
    {
        $carr = new CSequentialArray([100, 101, 102]);
        $carr->InsertBefore(3, 103);
        $this->assertSame([100, 101, 102, 103],
            AccessHelper::GetNonPublicProperty($carr, 'value'));
    }

    function testInsertBeforeChaining()
    {
        $carr = new CSequentialArray([100, 103]);
        $carr->InsertBefore(1, 101)->InsertBefore(2, 102);
        $this->assertSame([100, 101, 102, 103],
            AccessHelper::GetNonPublicProperty($carr, 'value'));
    }

    #endregion InsertBefore

    #region InsertAfter --------------------------------------------------------

    #[DataProviderExternal(DataHelper::class, 'NonIntegerProvider')]
    function testInsertAfterWithInvalidIndexType($index)
    {
        $cstr = new CSequentialArray();
        $this->expectException(\TypeError::class);
        $cstr->InsertAfter($index, 99);
    }

    function testInsertAfterWithNegativeIndex()
    {
        $carr = new CSequentialArray();
        $this->expectException(\OutOfRangeException::class);
        $carr->InsertAfter(-1, 99);
    }

    function testInsertAfterWithIndexExceedingSize()
    {
        $carr = new CSequentialArray([100, 101, 102]);
        $this->expectException(\OutOfRangeException::class);
        $carr->InsertAfter(3, 103);
    }

    function testInsertAfterAtBeginning()
    {
        $carr = new CSequentialArray([101, 102, 103]);
        $carr->InsertAfter(0, 100);
        $this->assertSame([101, 100, 102, 103],
            AccessHelper::GetNonPublicProperty($carr, 'value'));
    }

    function testInsertAfterInMiddle()
    {
        $carr = new CSequentialArray([100, 101, 103]);
        $carr->InsertAfter(1, 102);
        $this->assertSame([100, 101, 102, 103],
            AccessHelper::GetNonPublicProperty($carr, 'value'));
    }

    function testInsertAfterAtEnd()
    {
        $carr = new CSequentialArray([100, 101, 102]);
        $carr->InsertAfter(2, 103);
        $this->assertSame([100, 101, 102, 103],
            AccessHelper::GetNonPublicProperty($carr, 'value'));
    }

    function testInsertAfterChaining()
    {
        $carr = new CSequentialArray([100, 101]);
        $carr->InsertAfter(0, 100.5)->InsertAfter(2, 101.5);
        $this->assertSame([100, 100.5, 101, 101.5],
            AccessHelper::GetNonPublicProperty($carr, 'value'));
    }

    #endregion InsertAfter

    #region Interface: ArrayAccess ---------------------------------------------

    function testOffsetExists()
    {
        $carr = $this->getMockBuilder(CSequentialArray::class)
            ->onlyMethods(['Has'])
            ->getMock();
        $carr->expects($this->once())
            ->method('Has')
            ->with(1)
            ->willReturn(true);
        $this->assertTrue(isset($carr[1]));
    }

    function testOffsetGet()
    {
        $carr = $this->getMockBuilder(CSequentialArray::class)
            ->onlyMethods(['Get'])
            ->getMock();
        $carr->expects($this->once())
            ->method('Get')
            ->with(1)
            ->willReturn(100);
        $this->assertSame(100, $carr[1]);
    }

    function testOffsetSet()
    {
        $carr = $this->getMockBuilder(CSequentialArray::class)
            ->onlyMethods(['Set'])
            ->getMock();
        $carr->expects($this->once())
            ->method('Set')
            ->with(1, 200);
        $carr[1] = 200;
    }

    function testOffsetUnset()
    {
        $carr = $this->getMockBuilder(CSequentialArray::class)
            ->onlyMethods(['Delete'])
            ->getMock();
        $carr->expects($this->once())
            ->method('Delete')
            ->with(1);
        unset($carr[1]);
    }

    #endregion Interface: ArrayAccess

    #region Data Providers -----------------------------------------------------

    static function hasDataProvider()
    {
        return [
            'negative index' => [
                false, [100, 101, 102], -1
            ],
            'index at start' => [
                true, [100, 101, 102], 0
            ],
            'index in middle' => [
                true, [100, 101, 102], 1
            ],
            'index at end' => [
                true, [100, 101, 102], 2
            ],
            'index exceeds size' => [
                false, [100, 101, 102], 3
            ],
            'empty array' => [
                false, [], 0
            ],
        ];
    }

    static function getDataProvider()
    {
        return [
            'existing index' => [
                101, [100, 101, 102], 1
            ],
            'non-existing index with null default' => [
                null, [100, 101, 102], 3
            ],
            'non-existing index with non-null default' => [
                'default', [100, 101, 102], 3, 'default'
            ],
            'empty array with default value' => [
                'empty', [], 0, 'empty'
            ],
        ];
    }

    static function setDataProvider()
    {
        return [
            'index at start' => [
                [200, 101, 102],
                [100, 101, 102],
                0,
                200
            ],
            'index in middle' => [
                [100, 201, 102],
                [100, 101, 102],
                1,
                201
            ],
            'index at end' => [
                [100, 101, 202],
                [100, 101, 102],
                2,
                202
            ],
        ];
    }

    static function deleteDataProvider()
    {
        return [
            'index at beginning' => [
                [101, 102],
                [100, 101, 102],
                0
            ],
            'index in middle' => [
                [100, 102, 103],
                [100, 101, 102, 103],
                1
            ],
            'index at end' => [
                [100, 101],
                [100, 101, 102],
                2
            ]
        ];
    }

    #endregion Data Providers
}
