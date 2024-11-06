<?php declare(strict_types=1);
use \PHPUnit\Framework\TestCase;
use \PHPUnit\Framework\Attributes\CoversClass;
use \PHPUnit\Framework\Attributes\DataProvider;
use \PHPUnit\Framework\Attributes\DataProviderExternal;

use \Harmonia\Core\CSequentialArray;
use \Harmonia\Core\CArray; // testCopyConstructorWith[Non]SequentialCArray

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
        $this->assertSame(['a', 'b', 'c'], $carr->ToArray());
    }

    function testDefaultConstructor()
    {
        $carr = new CSequentialArray();
        $this->assertSame([], $carr->ToArray());
    }

    function testCopyConstructor()
    {
        $original = new CSequentialArray([1, 2, 3]);
        $copy = new CSequentialArray($original);
        $this->assertSame($original->ToArray(), $copy->ToArray());
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
        $this->assertSame($carr->ToArray(), $cseqarr->ToArray());
    }

    function testConstructorWithNativeArray()
    {
        $arr = [1, 2, 3];
        $carr = new CSequentialArray($arr);
        $this->assertSame($arr, $carr->ToArray());
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

    #[DataProvider('setDataProvider')]
    public function testSet(array $expected, array $arr, string|int $index, mixed $value)
    {
        $carr = new CSequentialArray($arr);
        $carr->Set($index, $value);
        $this->assertSame($expected, $carr->ToArray());
    }

    function testSetChaining()
    {
        $carr = new CSequentialArray([100, 101]);
        $carr->Set(0, 200)->Set(1, 201);
        $this->assertSame([200, 201], $carr->ToArray());
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

    #[DataProvider('deleteDataProvider')]
    public function testDelete(array $expected, array $arr, string|int $index)
    {
        $carr = new CSequentialArray($arr);
        $carr->Delete($index);
        $this->assertSame($expected, $carr->ToArray());
    }

    function testDeleteChaining()
    {
        $carr = new CSequentialArray([100, 101, 102, 103]);
        $carr->Delete(1)->Delete(2);
        $this->assertSame([100, 102], $carr->ToArray());
    }

    #endregion Delete

    #region PushBack -----------------------------------------------------------

    function testPushBack()
    {
        $carr = new CSequentialArray([1, 2]);
        $carr->PushBack(3)->PushBack(4);
        $this->assertSame([1, 2, 3, 4], $carr->ToArray());
    }

    #endregion PushBack

    #region PushFront ----------------------------------------------------------

    function testPushFront()
    {
        $carr = new CSequentialArray([3, 4]);
        $carr->PushFront(2)->PushFront(1);
        $this->assertSame([1, 2, 3, 4], $carr->ToArray());
    }

    #endregion PushFront

    #region PopBack ------------------------------------------------------------

    function testPopBack()
    {
        $carr = new CSequentialArray([1, 2, 3, 4]);
        $this->assertSame(4, $carr->PopBack());
        $this->assertSame(3, $carr->PopBack());
        $this->assertSame([1, 2], $carr->ToArray());
    }

    function testPopBackWithEmptyArray()
    {
        $carr = new CSequentialArray();
        $this->assertNull($carr->PopBack());
        $this->assertSame([], $carr->ToArray());
    }

    #endregion PopBack

    #region PopFront -----------------------------------------------------------

    function testPopFront()
    {
        $carr = new CSequentialArray([1, 2, 3, 4]);
        $this->assertSame(1, $carr->PopFront());
        $this->assertSame(2, $carr->PopFront());
        $this->assertSame([3, 4], $carr->ToArray());
    }

    function testPopFrontWithEmptyArray()
    {
        $carr = new CSequentialArray();
        $this->assertNull($carr->PopFront());
        $this->assertSame([], $carr->ToArray());
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

    #[DataProvider('insertBeforeDataProvider')]
    public function testInsertBefore(array $expected, array $arr, int $index,
        mixed $element)
    {
        $carr = new CSequentialArray($arr);
        $carr->InsertBefore($index, $element);
        $this->assertSame($expected, $carr->ToArray());
    }

    function testInsertBeforeChaining()
    {
        $carr = new CSequentialArray([100, 103]);
        $carr->InsertBefore(1, 101)->InsertBefore(2, 102);
        $this->assertSame([100, 101, 102, 103], $carr->ToArray());
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

    #[DataProvider('insertAfterDataProvider')]
    public function testInsertAfter(array $expected, array $arr, int $index,
        mixed $element)
    {
        $carr = new CSequentialArray($arr);
        $carr->InsertAfter($index, $element);
        $this->assertSame($expected, $carr->ToArray());
    }

    function testInsertAfterChaining()
    {
        $carr = new CSequentialArray([100, 101]);
        $carr->InsertAfter(0, 100.5)->InsertAfter(2, 101.5);
        $this->assertSame([100, 100.5, 101, 101.5], $carr->ToArray());
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
            'negative index' => [
                [100, 101, 102],
                [100, 101, 102],
                -1,
                199
            ],
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
            'index exceeds size' => [
                [100, 101, 102],
                [100, 101, 102],
                3,
                203
            ],
        ];
    }

    static function deleteDataProvider()
    {
        return [
            'negative index' => [
                [100, 101, 102],
                [100, 101, 102],
                -1
            ],
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
            ],
            'index exceeds size' => [
                [100, 101, 102],
                [100, 101, 102],
                3
            ],
        ];
    }

    static function insertBeforeDataProvider()
    {
        return [
            'negative index' => [
                [100, 101, 102],
                [100, 101, 102],
                -1,
                99
            ],
            'index at start' => [
                [100, 101, 102, 103],
                [101, 102, 103],
                0,
                100
            ],
            'index in middle' => [
                [100, 101, 102, 103],
                [100, 102, 103],
                1,
                101
            ],
            'index at end' => [
                [100, 101, 102, 103],
                [100, 101, 103],
                2,
                102
            ],
            'index exceeds size' => [
                [100, 101, 102],
                [100, 101, 102],
                3,
                103
            ],
        ];
    }

    static function insertAfterDataProvider()
    {
        return [
            'negative index' => [
                [100, 101, 102],
                [100, 101, 102],
                -1,
                100
            ],
            'index at beginning' => [
                [100, 101, 102, 103],
                [100, 102, 103],
                0,
                101
            ],
            'index in middle' => [
                [100, 101, 102, 103],
                [100, 101, 103],
                1,
                102
            ],
            'index at end' => [
                [100, 101, 102, 103],
                [100, 101, 102],
                2,
                103
            ],
            'index exceeds size' => [
                [100, 101, 102],
                [100, 101, 102],
                3,
                104
            ],
        ];
    }

    #endregion Data Providers
}
