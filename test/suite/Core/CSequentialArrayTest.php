<?php declare(strict_types=1);
use \PHPUnit\Framework\TestCase;
use \PHPUnit\Framework\Attributes\CoversClass;
use \PHPUnit\Framework\Attributes\DataProvider;
use \PHPUnit\Framework\Attributes\DataProviderExternal;

use \Harmonia\Core\CSequentialArray;

use \TestToolkit\AccessHelper;
use \TestToolkit\DataHelper;

#[CoversClass(CSequentialArray::class)]
class CSequentialArrayTest extends TestCase
{
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

    function testInsertBeforeReindexKeys()
    {
        $carr = new CSequentialArray([10 => 100, 11 => 101, 13 => 103]);
        $carr->InsertBefore(2, 102);
        $this->assertSame([0 => 100, 1 => 101, 2 => 102, 3 => 103],
            AccessHelper::GetNonPublicProperty($carr, 'value'));
    }

    function testInsertBeforeWithAssociativeArray()
    {
        $carr = new CSequentialArray(['ten' => 10, 'eleven' => 11, 'thirteen' => 13]);
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

    function testInsertAfterReindexKeys()
    {
        $carr = new CSequentialArray([10 => 100, 11 => 101, 13 => 103]);
        $carr->InsertAfter(1, 102);
        $this->assertSame([0 => 100, 1 => 101, 2 => 102, 3 => 103],
            AccessHelper::GetNonPublicProperty($carr, 'value'));
    }

    function testInsertAfterWithAssociativeArray()
    {
        $carr = new CSequentialArray(['ten' => 10, 'eleven' => 11, 'thirteen' => 13]);
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

    #region Data Providers -----------------------------------------------------

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
