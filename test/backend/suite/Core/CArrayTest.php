<?php declare(strict_types=1);
use \PHPUnit\Framework\TestCase;
use \PHPUnit\Framework\Attributes\CoversClass;
use \PHPUnit\Framework\Attributes\DataProvider;
use \PHPUnit\Framework\Attributes\DataProviderExternal;

use \Harmonia\Core\CArray;
use \Harmonia\Core\CSequentialArray; // testCopyConstructorWithCSequentialArray

use \TestToolkit\DataHelper;

#[CoversClass(CArray::class)]
class CArrayTest extends TestCase
{
    #region __construct --------------------------------------------------------

    function testDefaultConstructor()
    {
        $carr = new CArray();
        $this->assertEmpty($carr->ToArray());
    }

    function testCopyConstructor()
    {
        $original = new CArray([1, 2, 3]);
        $copy = new CArray($original);
        $this->assertSame($original->ToArray(), $copy->ToArray());
    }

    function testCopyConstructorWithCSequentialArray()
    {
        $cseqarr = new CSequentialArray(['a', 'b', 'c']);
        $carr = new CArray($cseqarr);
        $this->assertSame($cseqarr->ToArray(), $carr->ToArray());
    }

    function testConstructorWithNativeArray()
    {
        $arr = [1, 2, 3];
        $carr = new CArray($arr);
        $this->assertSame($arr, $carr->ToArray());
    }

    #endregion __construct

    #region ToArray ------------------------------------------------------------

    function testToArrayReturnsIdenticalCopy()
    {
        $arr = ['a' => 1, 'b' => 2, 'c' => 3];
        $carr = new CArray($arr);
        $this->assertSame($arr, $carr->ToArray());
    }

    function testToArrayModificationDoesNotAffectInternalArray()
    {
        $arr = ['a' => 1, 'b' => 2, 'c' => 3];
        $carr = new CArray($arr);
        $arrCopy = $carr->ToArray();
        $arrCopy['a'] = 99;
        $this->assertNotSame($arrCopy, $carr->ToArray());
        $this->assertSame($arr, $carr->ToArray());
    }

    #endregion ToArray

    #region IsEmpty ------------------------------------------------------------

    #[DataProvider('isEmptyDataProvider')]
    function testIsEmpty($expected, array $arr)
    {
        $carr = new CArray($arr);
        $this->assertSame($expected, $carr->IsEmpty());
    }

    #endregion IsEmpty

    #region Has ----------------------------------------------------------------

    #[DataProvider('hasDataProvider')]
    function testHas(bool $expected, array $arr, string|int $key)
    {
        $carr = new CArray($arr);
        $this->assertSame($expected, $carr->Has($key));
    }

    #endregion Has

    #region Get, GetOrDefault --------------------------------------------------

    #[DataProvider('getDataProvider')]
    function testGet(mixed $expected, array $arr, string|int $key)
    {
        $carr = new CArray($arr);
        $this->assertSame($expected, $carr->Get($key));
    }

    #[DataProvider('getOrDefaultDataProvider')]
    function testGetOrDefault(mixed $expected, array $arr, string|int $key,
        mixed $defaultValue)
    {
        $carr = new CArray($arr);
        $this->assertSame($expected, $carr->GetOrDefault($key, $defaultValue));
    }

    #endregion Get, GetOrDefault

    #region Set ----------------------------------------------------------------

    #[DataProvider('setDataProvider')]
    function testSet(array $expected, array $arr, string|int $key,
        mixed $value)
    {
        $carr = new CArray($arr);
        $carr->Set($key, $value);
        $this->assertSame($expected, $carr->ToArray());
    }

    function testSetChaining()
    {
        $carr = new CArray(['x' => 10]);
        $carr->Set('y', 20)->Set('x', 15);
        $this->assertSame(['x' => 15, 'y' => 20], $carr->ToArray());
    }

    #endregion Set

    #region Remove -------------------------------------------------------------

    #[DataProvider('deleteDataProvider')]
    function testDelete(array $expected, array $arr, string|int $key)
    {
        $carr = new CArray($arr);
        $carr->Remove($key);
        $this->assertSame($expected, $carr->ToArray());
    }

    function testDeleteChaining()
    {
        $carr = new CArray(['x' => 10, 'y' => 20, 'z' => 30]);
        $carr->Remove('x')->Remove('z');
        $this->assertSame(['y' => 20], $carr->ToArray());
    }

    #endregion Remove

    #region Clear --------------------------------------------------------------

    function testClear()
    {
        $carr = new CArray(['a' => 1, 'b' => 2, 'c' => 3]);
        $carr->Clear();
        $this->assertTrue($carr->IsEmpty());
    }

    function testClearChaining()
    {
        $carr = new CArray(['a' => 1, 'b' => 2, 'c' => 3]);
        $carr->Clear()->Set('x', 10);
        $this->assertSame(['x' => 10], $carr->ToArray());
    }

    #endregion Clear

    #region Interface: ArrayAccess ---------------------------------------------

    function testOffsetExists()
    {
        $carr = $this->getMockBuilder(CArray::class)
            ->onlyMethods(['Has'])
            ->getMock();
        $carr->expects($this->once())
            ->method('Has')
            ->with('a')
            ->willReturn(true);
        $this->assertTrue(isset($carr['a']));
    }

    function testOffsetGet()
    {
        $carr = $this->getMockBuilder(CArray::class)
            ->onlyMethods(['Get'])
            ->getMock();
        $carr->expects($this->once())
            ->method('Get')
            ->with('a')
            ->willReturn(1);
        $this->assertSame(1, $carr['a']);
    }

    function testOffsetSet()
    {
        $carr = $this->getMockBuilder(CArray::class)
            ->onlyMethods(['Set'])
            ->getMock();
        $carr->expects($this->once())
            ->method('Set')
            ->with('b', 2);
        $carr['b'] = 2;
    }

    function testOffsetUnset()
    {
        $carr = $this->getMockBuilder(CArray::class)
            ->onlyMethods(['Remove'])
            ->getMock();
        $carr->expects($this->once())
            ->method('Remove')
            ->with('a');
        unset($carr['a']);
    }

    #endregion Interface: ArrayAccess

    #region Interface: Countable -----------------------------------------------

    #[DataProvider('countDataProvider')]
    function testCount(int $expected, array $arr)
    {
        $carr = new CArray($arr);
        $this->assertSame($expected, \count($carr));
        $this->assertSame($expected, $carr->count());
        $this->assertSame($expected, $carr->Count());
    }

    #endregion Interface: Countable

    #region Interface: IteratorAggregate ---------------------------------------

    function testGetIteratorForSequentialArray()
    {
        $carr = new CArray([1, 2, 3, 4, 5]);
        $result = [];
        foreach ($carr as $element) {
            $result[] = $element;
        }
        $this->assertSame([1, 2, 3, 4, 5], $result);
    }

    function testGetIteratorForAssociativeArray()
    {
        $carr = new CArray(['a' => 'apple', 'b' => 'banana', 'c' => 'cherry']);
        $result = [];
        foreach ($carr as $key => $value) {
            $result[$key] = $value;
        }
        $this->assertSame(['a' => 'apple', 'b' => 'banana', 'c' => 'cherry'], $result);
    }

    function testGetIteratorForEmptyArray()
    {
        $carr = new CArray();
        $result = [];
        foreach ($carr as $element) {
            $result[] = $element;
        }
        $this->assertEmpty($result);
    }

    #endregion Interface: IteratorAggregate

    #region Data Providers -----------------------------------------------------

    static function isEmptyDataProvider()
    {
        return [
            'empty array' => [true, []],
            'single element' => [false, [42]],
            'multiple elements' => [false, [1, 2, 3]],
        ];
    }

    static function hasDataProvider()
    {
        return [
            'existing string key' => [
                true, ['a' => 1, 'b' => 2], 'a'
            ],
            'existing integer key' => [
                true, [0 => 'first', 1 => 'second'], 0
            ],
            'non-existing string key' => [
                false, ['x' => 10, 'y' => 20], 'z'
            ],
            'non-existing integer key' => [
                false, [0 => 'first', 1 => 'second'], 2
            ],
            'existing integer key accessed as string' => [
                true, [1 => 'one', '2' => 'two'], '1'
            ],
            'existing numeric string key accessed as integer' => [
                true, ['1' => 'one', '2' => 'two'], 1
            ],
            'empty array' => [
                false, [], 'missing'
            ],
        ];
    }

    static function getDataProvider()
    {
        return [
            'existing string key' => [
                42, ['a' => 42, 'b' => 24], 'a'
            ],
            'non-existing string key' => [
                null, ['a' => 42, 'b' => 24], 'c'
            ],
            'existing integer key' => [
                'one', [1 => 'one', 2 => 'two'], 1
            ],
            'non-existing integer key' => [
                null, [1 => 'one', 2 => 'two'], 3
            ],
            'existing integer key accessed as string' => [
                'one', [1 => 'one', 2 => 'two'], '1'
            ],
            'existing numeric string key accessed as integer' => [
                'one', ['1' => 'one', '2' => 'two'], 1
            ],
            'empty array' => [
                null, [], 'missing'
            ],
        ];
    }

    static function getOrDefaultDataProvider()
    {
        return [
            'existing string key' => [
                42, ['a' => 42, 'b' => 24], 'a', 0
            ],
            'non-existing string key' => [
                0, ['a' => 42, 'b' => 24], 'c', 0
            ],
            'existing integer key' => [
                'one', [1 => 'one', 2 => 'two'], 1, 0
            ],
            'non-existing integer key' => [
                '', [1 => 'one', 2 => 'two'], 3, ''
            ],
            'existing integer key accessed as string' => [
                'one', [1 => 'one', 2 => 'two'], '1', ''
            ],
            'existing numeric string key accessed as integer' => [
                'one', ['1' => 'one', '2' => 'two'], 1, ''
            ],
            'empty array' => [
                -1, [], 'missing', -1
            ],
        ];
    }

    static function setDataProvider()
    {
        return [
            'new string key' => [
                ['a' => 1, 'b' => 2, 'c' => 3],
                ['a' => 1, 'b' => 2],
                'c',
                3
            ],
            'new integer key' => [
                [0 => 'apple', 1 => 'banana', 2 => 'cherry'],
                [0 => 'apple', 1 => 'banana'],
                2,
                'cherry'
            ],
            'existing string key' => [
                ['a' => 1, 'b' => 20],
                ['a' => 1, 'b' => 2],
                'b',
                20
            ],
            'existing integer key' => [
                [0 => 'apple', 1 => 'blueberry'],
                [0 => 'apple', 1 => 'banana'],
                1,
                'blueberry'
            ],
            'existing integer key accessed as string' => [
                [1 => 'updated', 2 => 'two'],
                [1 => 'one', 2 => 'two'],
                '1',
                'updated'
            ],
            'existing numeric string key accessed as integer' => [
                ['1' => 'one', '2' => 'updated'],
                ['1' => 'one', '2' => 'two'],
                2,
                'updated'
            ],
            'empty array' => [
                ['new_key' => 'new_value'],
                [],
                'new_key',
                'new_value'
            ],
        ];
    }

    static function deleteDataProvider()
    {
        return [
            'existing string key' => [
                ['a' => 1, 'c' => 3],
                ['a' => 1, 'b' => 2, 'c' => 3],
                'b'
            ],
            'existing integer key' => [
                [0 => 'apple', 2 => 'cherry'],
                [0 => 'apple', 1 => 'banana', 2 => 'cherry'],
                1
            ],
            'non-existing string key' => [
                ['a' => 1, 'b' => 2],
                ['a' => 1, 'b' => 2],
                'c'
            ],
            'non-existing integer key' => [
                [0 => 'apple', 1 => 'banana'],
                [0 => 'apple', 1 => 'banana'],
                5
            ],
            'existing integer key accessed as string' => [
                [2 => 'two'],
                [1 => 'one', 2 => 'two'],
                '1'
            ],
            'existing numeric string key accessed as integer' => [
                ['2' => 'two'],
                ['1' => 'one', '2' => 'two'],
                1
            ],
            'empty array' => [
                [],
                [],
                'missing'
            ],
        ];
    }

    static function countDataProvider()
    {
        return [
            'empty array' => [0, []],
            'single element' => [1, [42]],
            'multiple elements' => [3, [1, 2, 3]],
        ];
    }

    #endregion Data Providers
}
