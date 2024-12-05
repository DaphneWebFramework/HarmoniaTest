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
        $this->assertEmpty($carr->ToArray());
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

    #region Get, GetOrDefault --------------------------------------------------

    function testGetWithStringIndex()
    {
        $carr = new CSequentialArray();
        $this->expectException(\InvalidArgumentException::class);
        $carr->Get('key');
    }

    #[DataProvider('getDataProvider')]
    function testGet(mixed $expected, array $arr, string|int $index)
    {
        $carr = new CSequentialArray($arr);
        $this->assertSame($expected, $carr->Get($index));
    }

    function testGetOrDefaultWithStringIndex()
    {
        $carr = new CSequentialArray();
        $this->expectException(\InvalidArgumentException::class);
        $carr->GetOrDefault('key', null);
    }

    #[DataProvider('getOrDefaultDataProvider')]
    function testGetOrDefault(mixed $expected, array $arr, string|int $index,
        mixed $defaultValue)
    {
        $carr = new CSequentialArray($arr);
        $this->assertSame($expected, $carr->GetOrDefault($index, $defaultValue));
    }

    #endregion Get, GetOrDefault

    #region Set ----------------------------------------------------------------

    function testSetWithStringIndex()
    {
        $carr = new CSequentialArray();
        $this->expectException(\InvalidArgumentException::class);
        $carr->Set('key', 1);
    }

    #[DataProvider('setDataProvider')]
    function testSet(array $expected, array $arr, string|int $index, mixed $value)
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

    function testDeleteWithStringIndex()
    {
        $carr = new CSequentialArray();
        $this->expectException(\InvalidArgumentException::class);
        $carr->Delete('key');
    }

    #[DataProvider('deleteDataProvider')]
    function testDelete(array $expected, array $arr, string|int $index)
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
        $this->assertEmpty($carr->ToArray());
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
        $this->assertEmpty($carr->ToArray());
    }

    #endregion PopFront

    #region InsertBefore -------------------------------------------------------

    #[DataProvider('insertBeforeDataProvider')]
    function testInsertBefore(array $expected, array $arr, int $index,
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

    #[DataProvider('insertAfterDataProvider')]
    function testInsertAfter(array $expected, array $arr, int $index,
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
            'non-existing negative index' => [
                null, [100, 101, 102], -1
            ],
            'non-existing positive index' => [
                null, [100, 101, 102], 3
            ],
            'empty array' => [
                null, [], 0
            ],
        ];
    }

    static function getOrDefaultDataProvider()
    {
        return [
            'existing index' => [
                101, [100, 101, 102], 1, 0
            ],
            'non-existing negative index' => [
                0, [100, 101, 102], -1, 0
            ],
            'non-existing positive index' => [
                0, [100, 101, 102], 3, 0
            ],
            'empty array' => [
                -1, [], 0, -1
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
