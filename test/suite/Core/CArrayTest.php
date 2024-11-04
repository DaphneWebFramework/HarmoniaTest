<?php declare(strict_types=1);
use \PHPUnit\Framework\TestCase;
use \PHPUnit\Framework\Attributes\CoversClass;
use \PHPUnit\Framework\Attributes\DataProvider;
use \PHPUnit\Framework\Attributes\DataProviderExternal;

use \Harmonia\Core\CArray;

use \TestToolkit\AccessHelper;
use \TestToolkit\DataHelper;

#[CoversClass(CArray::class)]
class CArrayTest extends TestCase
{
    #region __construct --------------------------------------------------------

    function testDefaultConstructor()
    {
        $carr = new CArray();
        $this->assertSame([], AccessHelper::GetNonPublicProperty($carr, 'value'));
    }

    function testCopyConstructor()
    {
        $original = new CArray([1, 2, 3]);
        $copy = new CArray($original);
        $this->assertSame(
            AccessHelper::GetNonPublicProperty($original, 'value'),
            AccessHelper::GetNonPublicProperty($copy, 'value')
        );
    }

    function testConstructorWithNativeArray()
    {
        $arr = [1, 2, 3];
        $carr = new CArray($arr);
        $this->assertSame($arr, AccessHelper::GetNonPublicProperty($carr, 'value'));
    }

    #[DataProviderExternal(DataHelper::class, 'NonArrayProvider')]
    function testConstructorWithInvalidValueType($value)
    {
        $this->expectException(\TypeError::class);
        new CArray($value);
    }

    #endregion __construct

    #region ContainsKey --------------------------------------------------------

    #[DataProvider('containsKeyDataProvider')]
    function testContainsKey(bool $expected, array $arr, string|int $key)
    {
        $carr = new CArray($arr);
        $this->assertSame($expected, $carr->ContainsKey($key));
    }

    #[DataProviderExternal(DataHelper::class, 'NonStringOrIntegerProvider')]
    function testContainsKeyWithInvalidKeyType($key)
    {
        $carr = new CArray();
        $this->expectException(\TypeError::class);
        $carr->ContainsKey($key);
    }

    #endregion ContainsKey

    #region ValueOrDefault -----------------------------------------------------

    #[DataProvider('valueOrDefaultDataProvider')]
    function testValueOrDefault(mixed $expected, array $arr, string|int $key,
        mixed $defaultValue = null)
    {
        $carr = new CArray($arr);
        $this->assertSame($expected, $carr->ValueOrDefault($key, $defaultValue));
    }

    #[DataProviderExternal(DataHelper::class, 'NonStringOrIntegerProvider')]
    function testValueOrDefaultWithInvalidKeyType($key)
    {
        $carr = new CArray();
        $this->expectException(\TypeError::class);
        $carr->ValueOrDefault($key);
    }

    #endregion ValueOrDefault

    #region PushBack -----------------------------------------------------------

    function testPushBack()
    {
        $carr = new CArray([1, 2]);
        $carr->PushBack(3)->PushBack(4);
        $this->assertSame([1, 2, 3, 4],
            AccessHelper::GetNonPublicProperty($carr, 'value'));
    }

    #endregion PushBack

    #region PushFront ----------------------------------------------------------

    function testPushFront()
    {
        $carr = new CArray([3, 4]);
        $carr->PushFront(2)->PushFront(1);
        $this->assertSame([1, 2, 3, 4],
            AccessHelper::GetNonPublicProperty($carr, 'value'));
    }

    #endregion PushFront

    #region PopBack ------------------------------------------------------------

    function testPopBack()
    {
        $carr = new CArray([1, 2, 3, 4]);
        $this->assertSame(4, $carr->PopBack());
        $this->assertSame(3, $carr->PopBack());
        $this->assertSame([1, 2],
            AccessHelper::GetNonPublicProperty($carr, 'value'));
    }

    function testPopBackOnEmptyArray()
    {
        $carr = new CArray();
        $this->assertNull($carr->PopBack());
        $this->assertSame([], AccessHelper::GetNonPublicProperty($carr, 'value'));
    }

    #endregion PopBack

    #region PopFront -----------------------------------------------------------

    function testPopFront()
    {
        $carr = new CArray([1, 2, 3, 4]);
        $this->assertSame(1, $carr->PopFront());
        $this->assertSame(2, $carr->PopFront());
        $this->assertSame([3, 4],
            AccessHelper::GetNonPublicProperty($carr, 'value'));
    }

    function testPopFrontOnEmptyArray()
    {
        $carr = new CArray();
        $this->assertNull($carr->PopFront());
        $this->assertSame([], AccessHelper::GetNonPublicProperty($carr, 'value'));
    }

    #endregion PopFront

    #region InsertBefore -------------------------------------------------------

    function testInsertBeforeInMiddle()
    {
        $carr = new CArray([100, 102, 103]);
        $carr->InsertBefore(1, 101);
        $this->assertSame([100, 101, 102, 103],
            AccessHelper::GetNonPublicProperty($carr, 'value'));
    }

    function testInsertBeforeAtBeginning()
    {
        $carr = new CArray([101, 102, 103]);
        $carr->InsertBefore(0, 100);
        $this->assertSame([100, 101, 102, 103],
            AccessHelper::GetNonPublicProperty($carr, 'value'));
    }

    function testInsertBeforeAtEnd()
    {
        $carr = new CArray([100, 101, 102]);
        $carr->InsertBefore(3, 103);
        $this->assertSame([100, 101, 102, 103],
            AccessHelper::GetNonPublicProperty($carr, 'value'));
    }

    function testInsertBeforeChaining()
    {
        $carr = new CArray([100, 103]);
        $carr->InsertBefore(1, 101)->InsertBefore(2, 102);
        $this->assertSame([100, 101, 102, 103],
            AccessHelper::GetNonPublicProperty($carr, 'value'));
    }

    function testInsertBeforeWithNegativeOffset()
    {
        $carr = new CArray([100, 101, 102]);
        $this->expectException(\OutOfRangeException::class);
        $carr->InsertBefore(-1, 99);
    }

    function testInsertBeforeWithOffsetExceedingSize()
    {
        $carr = new CArray([100, 101, 102]);
        $this->expectException(\OutOfRangeException::class);
        $carr->InsertBefore(4, 103);
    }

    function testInsertBeforeReindexKeys()
    {
        $carr = new CArray([10 => 100, 11 => 101, 13 => 103]);
        $carr->InsertBefore(2, 102);
        $this->assertSame([0 => 100, 1 => 101, 2 => 102, 3 => 103],
            AccessHelper::GetNonPublicProperty($carr, 'value'));
    }

    function testInsertBeforeWithAssociativeArray()
    {
        $carr = new CArray(['ten' => 10, 'eleven' => 11, 'thirteen' => 13]);
        $carr->InsertBefore(2, 12);
        $this->assertSame([
            'ten' => 10,
            'eleven' => 11,
            0 => 12,
            'thirteen' => 13
        ], AccessHelper::GetNonPublicProperty($carr, 'value'));
        $carr->InsertBefore(1, 10.5);
        $this->assertSame([
            'ten' => 10,
            0 => 10.5,
            'eleven' => 11,
            1 => 12,
            'thirteen' => 13
        ], AccessHelper::GetNonPublicProperty($carr, 'value'));
        $carr->InsertBefore(4, 12.5);
        $this->assertSame([
            'ten' => 10,
            0 => 10.5,
            'eleven' => 11,
            1 => 12,
            2 => 12.5,
            'thirteen' => 13
        ], AccessHelper::GetNonPublicProperty($carr, 'value'));
    }

    #endregion InsertBefore

    #region InsertAfter --------------------------------------------------------

    function testInsertAfterInMiddle()
    {
        $carr = new CArray([100, 101, 103]);
        $carr->InsertAfter(1, 102);
        $this->assertSame([100, 101, 102, 103],
            AccessHelper::GetNonPublicProperty($carr, 'value'));
    }

    function testInsertAfterAtBeginning()
    {
        $carr = new CArray([101, 102, 103]);
        $carr->InsertAfter(0, 100);
        $this->assertSame([101, 100, 102, 103],
            AccessHelper::GetNonPublicProperty($carr, 'value'));
    }

    function testInsertAfterAtEnd()
    {
        $carr = new CArray([100, 101, 102]);
        $carr->InsertAfter(2, 103);
        $this->assertSame([100, 101, 102, 103],
            AccessHelper::GetNonPublicProperty($carr, 'value'));
    }

    function testInsertAfterChaining()
    {
        $carr = new CArray([100, 101]);
        $carr->InsertAfter(0, 100.5)->InsertAfter(2, 101.5);
        $this->assertSame([100, 100.5, 101, 101.5],
            AccessHelper::GetNonPublicProperty($carr, 'value'));
    }

    function testInsertAfterWithNegativeOffset()
    {
        $carr = new CArray([100, 101, 102]);
        $this->expectException(\OutOfRangeException::class);
        $carr->InsertAfter(-1, 99);
    }

    function testInsertAfterWithOffsetExceedingSize()
    {
        $carr = new CArray([100, 101, 102]);
        $this->expectException(\OutOfRangeException::class);
        $carr->InsertAfter(3, 103);
    }

    function testInsertAfterReindexKeys()
    {
        $carr = new CArray([10 => 100, 11 => 101, 13 => 103]);
        $carr->InsertAfter(1, 102);
        $this->assertSame([0 => 100, 1 => 101, 2 => 102, 3 => 103],
            AccessHelper::GetNonPublicProperty($carr, 'value'));
    }

    function testInsertAfterWithAssociativeArray()
    {
        $carr = new CArray(['ten' => 10, 'eleven' => 11, 'thirteen' => 13]);
        $carr->InsertAfter(1, 12);
        $this->assertSame([
            'ten' => 10,
            'eleven' => 11,
            0 => 12,
            'thirteen' => 13
        ], AccessHelper::GetNonPublicProperty($carr, 'value'));
        $carr->InsertAfter(0, 10.5);
        $this->assertSame([
            'ten' => 10,
            0 => 10.5,
            'eleven' => 11,
            1 => 12,
            'thirteen' => 13
        ], AccessHelper::GetNonPublicProperty($carr, 'value'));
        $carr->InsertAfter(3, 12.5);
        $this->assertSame([
            'ten' => 10,
            0 => 10.5,
            'eleven' => 11,
            1 => 12,
            2 => 12.5,
            'thirteen' => 13
        ], AccessHelper::GetNonPublicProperty($carr, 'value'));
    }

    #endregion InsertAfter

    #region Data Providers -----------------------------------------------------

    static function containsKeyDataProvider()
    {
        return [
            'string key that exists' => [
                true, ['a' => 1, 'b' => 2], 'a'
            ],
            'integer key that exists' => [
                true, [0 => 'first', 1 => 'second'], 0
            ],
            'string key that does not exist' => [
                false, ['x' => 10, 'y' => 20], 'z'
            ],
            'integer key that does not exist' => [
                false, [0 => 'first', 1 => 'second'], 2
            ],
            'numeric string key that exists as string' => [
                true, ['1' => 'one', '2' => 'two'], '1'
            ],
            'numeric string key that exists as integer' => [
                true, ['1' => 'one', '2' => 'two'], 1
            ],
            'key in empty array' => [
                false, [], 'missing'
            ],
        ];
    }

    static function valueOrDefaultDataProvider()
    {
        return [
            'string key that exists' => [
                42, ['a' => 42, 'b' => 24], 'a'
            ],
            'string key that does not exist with null default' => [
                null, ['a' => 42, 'b' => 24], 'c'
            ],
            'string key that does not exist with non-null default' => [
                'default', ['a' => 42, 'b' => 24], 'c', 'default'
            ],
            'integer key that exists' => [
                'one', [1 => 'one', 2 => 'two'], 1
            ],
            'integer key that does not exist with null default' => [
                null, [1 => 'one', 2 => 'two'], 3
            ],
            'integer key that does not exist with non-null default' => [
                'three', [1 => 'one', 2 => 'two'], 3, 'three'
            ],
            'numeric string key that exists as integer' => [
                'one', ['1' => 'one', '2' => 'two'], 1
            ],
            'numeric string key that exists as string' => [
                'one', [1 => 'one', 2 => 'two'], '1'
            ],
            'key in empty array with default value' => [
                'empty', [], 'missing', 'empty'
            ],
        ];
    }

    #endregion Data Providers
}
