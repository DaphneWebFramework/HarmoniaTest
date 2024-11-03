<?php declare(strict_types=1);
use \PHPUnit\Framework\TestCase;
use \PHPUnit\Framework\Attributes\CoversClass;
use \PHPUnit\Framework\Attributes\DataProvider;
use \PHPUnit\Framework\Attributes\DataProviderExternal;

use \Harmonia\Core\CString;

use \TestToolkit\AccessHelper;
use \TestToolkit\DataHelper;

#[CoversClass(CString::class)]
class CStringTest extends TestCase
{
    #region Self Test ----------------------------------------------------------

    static $encodingsWithAliases = [];

    static function setUpBeforeClass(): void
    {
        $encodings = \mb_list_encodings();
        self::$encodingsWithAliases = \array_unique(
            \array_merge(
                $encodings,
                \call_user_func_array(
                    '\array_merge',
                    \array_map('\mb_encoding_aliases', $encodings)
                )
            )
        );
    }

    #[DataProvider('singleByteEncodingProvider')]
    function testSingleByteEncoding($encoding, $sampleString)
    {
        $this->assertTrue(
            \in_array($encoding, self::$encodingsWithAliases, true),
            "Encoding {$encoding} is not supported in this environment."
        );
        $this->assertSame(
            \strlen($sampleString),
            \mb_strlen($sampleString, $encoding),
            "Encoding {$encoding} did not behave as a single-byte encoding."
        );
        $this->assertTrue(
            \mb_check_encoding($sampleString, $encoding),
            "Sample string is not valid in encoding {$encoding}."
        );
    }

    #[DataProvider('multiByteEncodingProvider')]
    function testMultiByteEncoding($encoding)
    {
        $this->assertTrue(
            \in_array($encoding, self::$encodingsWithAliases, true),
            "Encoding {$encoding} is not supported in this environment."
        );
    }

    #endregion Self Test

    #region __construct --------------------------------------------------------

    function testDefaultConstructor()
    {
        $cstr = new CString();
        $this->assertSame('', (string)$cstr);
        $this->assertSame(
            \mb_internal_encoding(),
            AccessHelper::GetNonPublicProperty($cstr, 'encoding')
        );
    }

    function testCopyConstructor()
    {
        $original = new CString('Hello, World!', 'ISO-8859-1');
        $copy = new CString($original, 'UTF-8'); // 'UTF-8' should be ignored
        $this->assertSame((string)$original, (string)$copy);
        $this->assertSame(
            AccessHelper::GetNonPublicProperty($original, 'encoding'),
            AccessHelper::GetNonPublicProperty($copy, 'encoding')
        );
        $this->assertSame(
            AccessHelper::GetNonPublicProperty($original, 'isSingleByte'),
            AccessHelper::GetNonPublicProperty($copy, 'isSingleByte')
        );
    }

    function testConstructorWithNativeString()
    {
        $str = 'Hello, World!';
        $cstr = new CString($str);
        $this->assertSame($str, (string)$cstr);
        $this->assertSame(
            \mb_internal_encoding(),
            AccessHelper::GetNonPublicProperty($cstr, 'encoding')
        );
    }

    function testConstructorWithNativeStringAndNullEncoding()
    {
        $str = 'Hello, World!';
        $cstr = new CString($str, null);
        $this->assertSame($str, (string)$cstr);
        $this->assertSame(
            \mb_internal_encoding(),
            AccessHelper::GetNonPublicProperty($cstr, 'encoding')
        );
    }

    function testConstructorWithNativeStringAndSpecifiedEncoding()
    {
        $str = 'Hello, World!';
        $cstr = new CString($str, 'ISO-8859-1');
        $this->assertSame($str, (string)$cstr);
        $this->assertSame(
            'ISO-8859-1',
            AccessHelper::GetNonPublicProperty($cstr, 'encoding')
        );
    }

    function testConstructorWithStringable()
    {
        $stringable = new class() implements \Stringable {
            function __toString() {
                return 'I am Stringable';
            }
        };
        $cstr = new CString($stringable);
        $this->assertSame('I am Stringable', (string)$cstr);
        $this->assertSame(
            \mb_internal_encoding(),
            AccessHelper::GetNonPublicProperty($cstr, 'encoding')
        );
    }

    function testConstructorWithStringableAndNullEncoding()
    {
        $stringable = new class() implements \Stringable {
            function __toString() {
                return 'I am Stringable';
            }
        };
        $cstr = new CString($stringable, null);
        $this->assertSame('I am Stringable', (string)$cstr);
        $this->assertSame(
            \mb_internal_encoding(),
            AccessHelper::GetNonPublicProperty($cstr, 'encoding')
        );
    }

    function testConstructorWithStringableAndSpecifiedEncoding()
    {
        $stringable = new class() implements \Stringable {
            function __toString() {
                return 'I am Stringable';
            }
        };
        $cstr = new CString($stringable, 'ISO-8859-1');
        $this->assertSame('I am Stringable', (string)$cstr);
        $this->assertSame(
            'ISO-8859-1',
            AccessHelper::GetNonPublicProperty($cstr, 'encoding')
        );
    }

    #[DataProviderExternal(DataHelper::class, 'NonStringProvider')]
    function testConstructorWithInvalidValueType($value)
    {
        $this->expectException(\TypeError::class);
        new CString($value);
    }

    #[DataProviderExternal(DataHelper::class, 'NonStringExcludingNullProvider')]
    function testConstructorWithInvalidEncodingType($encoding)
    {
        $this->expectException(\TypeError::class);
        new CString('Hello, World!', $encoding);
    }

    #[DataProvider('singleByteEncodingProvider')]
    function testConstructorWithSingleByteEncoding($encoding)
    {
        $cstr = new CString('Hello, World!', $encoding);
        $this->assertTrue(AccessHelper::GetNonPublicProperty($cstr, 'isSingleByte'));
    }

    #[DataProvider('multiByteEncodingProvider')]
    function testConstructorWithMultiByteEncoding($encoding)
    {
        $cstr = new CString('Hello, World!', $encoding);
        $this->assertFalse(AccessHelper::GetNonPublicProperty($cstr, 'isSingleByte'));
    }

    #endregion __construct

    #region IsEmpty ------------------------------------------------------------

    #[DataProvider('isEmptyDataProvider')]
    function testIsEmpty($expected, $value, $encoding)
    {
        $cstr = new CString($value, $encoding);
        $this->assertSame($expected, $cstr->IsEmpty());
    }

    #endregion IsEmpty

    #region Length -------------------------------------------------------------

    #[DataProvider('lengthDataProvider')]
    function testLength($expected, $value, $encoding)
    {
        $cstr = new CString($value, $encoding);
        $this->assertSame($expected, $cstr->Length());
    }

    function testLengthWithInvalidEncoding()
    {
        $cstr = new CString('Hello', 'INVALID-ENCODING');
        $this->expectException(\ValueError::class);
        $cstr->Length();
    }

    #endregion Length

    #region First --------------------------------------------------------------

    #[DataProvider('firstDataProvider')]
    function testFirst($expected, $value, $encoding)
    {
        $cstr = new CString($value, $encoding);
        $this->assertSame($expected, $cstr->First());
    }

    function testFirstWithInvalidEncoding()
    {
        $cstr = new CString('Hello', 'INVALID-ENCODING');
        $this->expectException(\ValueError::class);
        $cstr->First();
    }

    #endregion First

    #region Last ---------------------------------------------------------------

    #[DataProvider('lastDataProvider')]
    function testLast($expected, $value, $encoding)
    {
        $cstr = new CString($value, $encoding);
        $this->assertSame($expected, $cstr->Last());
    }

    function testLastWithInvalidEncoding()
    {
        $cstr = new CString('Hello', 'INVALID-ENCODING');
        $this->expectException(\ValueError::class);
        $cstr->Last();
    }

    #endregion Last

    #region At -----------------------------------------------------------------

    #[DataProvider('atDataProvider')]
    function testAt($expected, $value, $encoding, $offset)
    {
        $cstr = new CString($value, $encoding);
        $this->assertSame($expected, $cstr->At($offset));
    }

    function testAtWithInvalidEncoding()
    {
        $cstr = new CString('Hello', 'INVALID-ENCODING');
        $this->expectException(\ValueError::class);
        $cstr->At(1);
    }

    #[DataProviderExternal(DataHelper::class, 'NonIntegerProvider')]
    function testAtWithNonIntegerOffset($offset)
    {
        $cstr = new CString('Hello', 'ISO-8859-1');
        $this->expectException(\TypeError::class);
        $cstr->At($offset);
    }

    #endregion At

    #region SetAt --------------------------------------------------------------

    #[DataProvider('setAtDataProvider')]
    function testSetAt($expected, $value, $encoding, $offset, $character)
    {
        $cstr = new CString($value, $encoding);
        $cstr->SetAt($offset, $character);
        $this->assertSame($expected, (string)$cstr);
    }

    function testSetAtWithIncompatibleEncoding()
    {
        $cstr = new CString('Hello', 'ISO-8859-1');
        $this->expectException(\ValueError::class);
        $cstr->SetAt(0, 'さ');
    }

    function testSetAtWithInvalidEncoding()
    {
        $cstr = new CString('Hello', 'INVALID-ENCODING');
        $this->expectException(\ValueError::class);
        $cstr->SetAt(0, 'Y');
    }

    #[DataProviderExternal(DataHelper::class, 'NonIntegerProvider')]
    function testSetAtWithNonIntegerOffset($offset)
    {
        $cstr = new CString('Hello', 'ISO-8859-1');
        $this->expectException(\TypeError::class);
        $cstr->SetAt($offset, 'Y');
    }

    #[DataProviderExternal(DataHelper::class, 'NonStringProvider')]
    function testSetAtWithNonStringCharacter($character)
    {
        $cstr = new CString('Hello', 'ISO-8859-1');
        $this->expectException(\TypeError::class);
        $cstr->SetAt(0, $character);
    }

    #endregion SetAt

    #region InsertAt -----------------------------------------------------------

    #[DataProvider('insertAtDataProvider')]
    function testInsertAt($expected, $value, $encoding, $offset, $substring)
    {
        $cstr = new CString($value, $encoding);
        $cstr->InsertAt($offset, $substring);
        $this->assertSame($expected, (string)$cstr);
    }

    function testInsertAtWithIncompatibleEncoding()
    {
        $cstr = new CString('atladı', 'CP1254');
        $this->expectException(\ValueError::class);
        $cstr->InsertAt(0, 'Быстрая');
    }

    function testInsertAtWithInvalidEncoding()
    {
        $cstr = new CString('Hello', 'INVALID-ENCODING');
        $this->expectException(\ValueError::class);
        $cstr->InsertAt(0, 'Hey');
    }

    #[DataProviderExternal(DataHelper::class, 'NonIntegerProvider')]
    function testInsertAtWithNonIntegerOffset($offset)
    {
        $cstr = new CString('Hello', 'ISO-8859-1');
        $this->expectException(\TypeError::class);
        $cstr->InsertAt($offset, 'Hey');
    }

    #[DataProviderExternal(DataHelper::class, 'NonStringProvider')]
    function testInsertAtWithNonStringSubstring($substring)
    {
        $cstr = new CString('Hello', 'ISO-8859-1');
        $this->expectException(\TypeError::class);
        $cstr->InsertAt(0, $substring);
    }

    #endregion InsertAt

    #region DeleteAt -----------------------------------------------------------

    #[DataProvider('deleteAtDataProvider')]
    function testDeleteAt($expected, $value, $encoding, $offset, $count = 1)
    {
        $cstr = new CString($value, $encoding);
        $cstr->DeleteAt($offset, $count);
        $this->assertSame($expected, (string)$cstr);
    }

    function testDeleteAtWithInvalidEncoding()
    {
        $cstr = new CString('Hello', 'INVALID-ENCODING');
        $this->expectException(\ValueError::class);
        $cstr->DeleteAt(0);
    }

    #[DataProviderExternal(DataHelper::class, 'NonIntegerProvider')]
    function testDeleteAtWithNonIntegerOffset($offset)
    {
        $cstr = new CString('Hello', 'ISO-8859-1');
        $this->expectException(\TypeError::class);
        $cstr->DeleteAt($offset);
    }

    #[DataProviderExternal(DataHelper::class, 'NonIntegerProvider')]
    function testDeleteAtWithNonIntegerCount($count)
    {
        $cstr = new CString('Hello', 'ISO-8859-1');
        $this->expectException(\TypeError::class);
        $cstr->DeleteAt(0, $count);
    }

    #endregion DeleteAt

    #region Left ---------------------------------------------------------------

    #[DataProvider('leftDataProvider')]
    function testLeft($expected, $value, $encoding, $count)
    {
        $cstr = new CString($value, $encoding);
        $this->assertSame($expected, (string)$cstr->Left($count));
    }

    function testLeftWithInvalidEncoding()
    {
        $cstr = new CString('Hello', 'INVALID-ENCODING');
        $this->expectException(\ValueError::class);
        $cstr->Left(4);
    }

    #[DataProviderExternal(DataHelper::class, 'NonIntegerProvider')]
    function testLeftWithNonIntegerCount($count)
    {
        $cstr = new CString('Hello', 'ISO-8859-1');
        $this->expectException(\TypeError::class);
        $cstr->Left($count);
    }

    #endregion Left

    #region Right --------------------------------------------------------------

    #[DataProvider('rightDataProvider')]
    function testRight($expected, $value, $encoding, $count)
    {
        $cstr = new CString($value, $encoding);
        $this->assertSame($expected, (string)$cstr->Right($count));
    }

    function testRightWithInvalidEncoding()
    {
        $cstr = new CString('Hello', 'INVALID-ENCODING');
        $this->expectException(\ValueError::class);
        $cstr->Right(2);
    }

    #[DataProviderExternal(DataHelper::class, 'NonIntegerProvider')]
    function testRightWithNonIntegerCount($count)
    {
        $cstr = new CString('Hello', 'ISO-8859-1');
        $this->expectException(\TypeError::class);
        $cstr->Right($count);
    }

    #endregion Right

    #region Middle -------------------------------------------------------------

    #[DataProvider('middleDataProvider')]
    function testMiddle($expected, $value, $encoding, $offset, $count)
    {
        $cstr = new CString($value, $encoding);
        $this->assertSame($expected, (string)$cstr->Middle($offset, $count));
    }

    function testMiddleWithInvalidEncoding()
    {
        $cstr = new CString('Hello', 'INVALID-ENCODING');
        $this->expectException(\ValueError::class);
        $cstr->Middle(1, 3);
    }

    #[DataProviderExternal(DataHelper::class, 'NonIntegerProvider')]
    function testMiddleWithNonIntegerOffset($offset)
    {
        $cstr = new CString('Hello', 'ISO-8859-1');
        $this->expectException(\TypeError::class);
        $cstr->Middle($offset, 2);
    }

    #[DataProviderExternal(DataHelper::class, 'NonIntegerProvider')]
    function testMiddleWithNonIntegerCount($count)
    {
        $cstr = new CString('Hello', 'ISO-8859-1');
        $this->expectException(\TypeError::class);
        $cstr->Middle(1, $count);
    }

    #endregion Middle

    #region Trim ---------------------------------------------------------------

    #[DataProvider('trimDataProvider')]
    function testTrim(string $expected, string $value, string $encoding,
        ?string $characters = null)
    {
        $cstr = new CString($value, $encoding);
        $this->assertEquals($expected, (string)$cstr->Trim($characters));
        $this->assertEquals($value, (string)$cstr);
    }

    function testTrimWithInvalidEncoding()
    {
        $cstr = new CString('Hello', 'INVALID-ENCODING');
        $this->expectException(\ValueError::class);
        $cstr->Trim();
    }

    #[DataProviderExternal(DataHelper::class, 'NonStringExcludingNullProvider')]
    function testTrimWithInvalidCharacters($characters)
    {
        $cstr = new CString('Hello', 'ISO-8859-1');
        $this->expectException(\TypeError::class);
        $cstr->Trim($characters);
    }

    #endregion Trim

    #region TrimLeft -----------------------------------------------------------

    #[DataProvider('trimLeftDataProvider')]
    function testTrimLeft(string $expected, string $value, string $encoding,
        ?string $characters = null)
    {
        $cstr = new CString($value, $encoding);
        $this->assertEquals($expected, (string)$cstr->TrimLeft($characters));
        $this->assertEquals($value, (string)$cstr);
    }

    function testTrimLeftWithInvalidEncoding()
    {
        $cstr = new CString('Hello', 'INVALID-ENCODING');
        $this->expectException(\ValueError::class);
        $cstr->TrimLeft();
    }

    #[DataProviderExternal(DataHelper::class, 'NonStringExcludingNullProvider')]
    function testTrimLeftWithInvalidCharacters($characters)
    {
        $cstr = new CString('Hello', 'ISO-8859-1');
        $this->expectException(\TypeError::class);
        $cstr->TrimLeft($characters);
    }

    #endregion TrimLeft

    #region TrimRight ----------------------------------------------------------

    #[DataProvider('trimRightDataProvider')]
    function testTrimRight(string $expected, string $value, string $encoding,
        ?string $characters = null)
    {
        $cstr = new CString($value, $encoding);
        $this->assertEquals($expected, (string)$cstr->TrimRight($characters));
        $this->assertEquals($value, (string)$cstr);
    }

    function testTrimRightWithInvalidEncoding()
    {
        $cstr = new CString('Hello', 'INVALID-ENCODING');
        $this->expectException(\ValueError::class);
        $cstr->TrimRight();
    }

    #[DataProviderExternal(DataHelper::class, 'NonStringExcludingNullProvider')]
    function testTrimRightWithInvalidCharacters($characters)
    {
        $cstr = new CString('Hello', 'ISO-8859-1');
        $this->expectException(\TypeError::class);
        $cstr->TrimRight($characters);
    }

    #endregion TrimRight

    #region Lowercase ----------------------------------------------------------

    #[DataProvider('lowercaseDataProvider')]
    function testLowercase(string $expected, string $value, string $encoding)
    {
        $cstr = new CString($value, $encoding);
        $this->assertSame($expected, (string)$cstr->Lowercase());
        $this->assertSame($value, (string)$cstr);
    }

    function testLowercaseWithInvalidEncoding()
    {
        $cstr = new CString('HELLO', 'INVALID-ENCODING');
        $this->expectException(\ValueError::class);
        $cstr->Lowercase();
    }

    #endregion Lowercase

    #region Equals -------------------------------------------------------------

    #[DataProvider('equalsDataProvider')]
    function testEquals(bool $expected, string $value, string $encoding,
        string|CString $other, bool $caseSensitive = true)
    {
        $cstr = new CString($value, $encoding);
        $this->assertSame($expected, $cstr->Equals($other, $caseSensitive));
    }

    function testEqualsWithInvalidEncodingCaseSensitive()
    {
        $cstr = new CString('Hello', 'INVALID-ENCODING');
        $this->expectException(\ValueError::class);
        $cstr->Equals('Hello', false); // throws for case-insensitive
    }

    function testEqualsWithInvalidEncodingCaseInsensitive()
    {
        $cstr = new CString('Hello', 'INVALID-ENCODING');
        // No exception is thrown because encoding validation is skipped when
        // the comparison is case-sensitive.
        $this->assertTrue ($cstr->Equals('Hello'));
    }

    #[DataProviderExternal(DataHelper::class, 'NonStringProvider')]
    function testEqualsWithNonStringOther($other)
    {
        $cstr = new CString('Hello', 'ISO-8859-1');
        $this->expectException(\TypeError::class);
        $cstr->Equals($other);
    }

    #[DataProviderExternal(DataHelper::class, 'NonBooleanProvider')]
    function testEqualsWithNonBooleanCaseSensitive($caseSensitive)
    {
        $cstr = new CString('Hello', 'ISO-8859-1');
        $this->expectException(\TypeError::class);
        $cstr->Equals('Hello', $caseSensitive);
    }

    #endregion Equals

    #region Uppercase ----------------------------------------------------------

    #[DataProvider('uppercaseDataProvider')]
    function testUppercase(string $expected, string $value, string $encoding)
    {
        $cstr = new CString($value, $encoding);
        $this->assertSame($expected, (string)$cstr->Uppercase());
        $this->assertSame($value, (string)$cstr);
    }

    function testUppercaseWithInvalidEncoding()
    {
        $cstr = new CString('hello', 'INVALID-ENCODING');
        $this->expectException(\ValueError::class);
        $cstr->Uppercase();
    }

    #endregion Uppercase

    #region StartsWith ---------------------------------------------------------

    #[DataProvider('startsWithDataProvider')]
    function testStartsWith(bool $expected, string $value, string $encoding,
        string $searchString, bool $caseSensitive = true)
    {
        $cstr = new CString($value, $encoding);
        $this->assertSame($expected, $cstr->StartsWith($searchString, $caseSensitive));
    }

    function testStartsWithWithInvalidEncoding()
    {
        $cstr = new CString('Hello', 'INVALID-ENCODING');
        $this->expectException(\ValueError::class);
        $cstr->StartsWith('Hell');
    }

    #[DataProviderExternal(DataHelper::class, 'NonStringProvider')]
    function testStartsWithWithNonStringSearchString($searchString)
    {
        $cstr = new CString('Hello', 'ISO-8859-1');
        $this->expectException(\TypeError::class);
        $cstr->StartsWith($searchString);
    }

    #[DataProviderExternal(DataHelper::class, 'NonBooleanProvider')]
    function testStartsWithWithNonBooleanCaseSensitive($caseSensitive)
    {
        $cstr = new CString('Hello', 'ISO-8859-1');
        $this->expectException(\TypeError::class);
        $cstr->StartsWith('Hell', $caseSensitive);
    }

    #endregion StartsWith

    #region EndsWith -----------------------------------------------------------

    #[DataProvider('endsWithDataProvider')]
    function testEndsWith(bool $expected, string $value, string $encoding,
        string $searchString, bool $caseSensitive = true)
    {
        $cstr = new CString($value, $encoding);
        $this->assertSame($expected, $cstr->EndsWith($searchString, $caseSensitive));
    }

    function testEndsWithWithInvalidEncoding()
    {
        $cstr = new CString('Hello', 'INVALID-ENCODING');
        $this->expectException(\ValueError::class);
        $cstr->EndsWith('llo');
    }

    #[DataProviderExternal(DataHelper::class, 'NonStringProvider')]
    function testEndsWithWithNonStringSearchString($searchString)
    {
        $cstr = new CString('Hello', 'ISO-8859-1');
        $this->expectException(\TypeError::class);
        $cstr->EndsWith($searchString);
    }

    #[DataProviderExternal(DataHelper::class, 'NonBooleanProvider')]
    function testEndsWithWithNonBooleanCaseSensitive($caseSensitive)
    {
        $cstr = new CString('Hello', 'ISO-8859-1');
        $this->expectException(\TypeError::class);
        $cstr->EndsWith('llo', $caseSensitive);
    }

    #endregion EndsWith

    #region IndexOf ------------------------------------------------------------

    #[DataProvider('indexOfDataProvider')]
    function testIndexOf(?int $expected, string $value, string $encoding,
        string $searchString, int $startOffset = 0, bool $caseSensitive = true)
    {
        $cstr = new CString($value, $encoding);
        $this->assertSame($expected, $cstr->IndexOf($searchString, $startOffset,
            $caseSensitive));
    }

    function testIndexOfWithInvalidEncoding()
    {
        $cstr = new CString('Hello', 'INVALID-ENCODING');
        $this->expectException(\ValueError::class);
        $cstr->IndexOf('Hell');
    }

    #[DataProviderExternal(DataHelper::class, 'NonStringProvider')]
    function testIndexOfWithNonStringSearchString($searchString)
    {
        $cstr = new CString('Hello', 'ISO-8859-1');
        $this->expectException(\TypeError::class);
        $cstr->IndexOf($searchString);
    }

    #[DataProviderExternal(DataHelper::class, 'NonIntegerProvider')]
    function testIndexOfWithNonIntegerStartOffset($startOffset)
    {
        $cstr = new CString('Hello', 'ISO-8859-1');
        $this->expectException(\TypeError::class);
        $cstr->IndexOf('Hell', $startOffset);
    }

    #[DataProviderExternal(DataHelper::class, 'NonBooleanProvider')]
    function testIndexOfWithNonBooleanCaseSensitive($caseSensitive)
    {
        $cstr = new CString('Hello', 'ISO-8859-1');
        $this->expectException(\TypeError::class);
        $cstr->IndexOf('Hell', 0, $caseSensitive);
    }

    #endregion IndexOf

    #region Replace ------------------------------------------------------------

    #[DataProvider('replaceDataProvider')]
    function testReplace(string $expected, string $value, string $encoding,
        string $searchString, string $replacement, bool $caseSensitive = true)
    {
        $cstr = new CString($value, $encoding);
        $result = $cstr->Replace($searchString, $replacement, $caseSensitive);
        $this->assertSame($expected, (string)$result);
    }

    function testReplaceWithInvalidEncoding()
    {
        $cstr = new CString('Hello', 'INVALID-ENCODING');
        $this->expectException(\ValueError::class);
        $cstr->Replace('Hello', 'Hi');
    }

    #[DataProviderExternal(DataHelper::class, 'NonStringProvider')]
    function testReplaceWithNonStringSearchString($searchString)
    {
        $cstr = new CString('Hello', 'ISO-8859-1');
        $this->expectException(\TypeError::class);
        $cstr->Replace($searchString, 'Hi');
    }

    #[DataProviderExternal(DataHelper::class, 'NonStringProvider')]
    function testReplaceWithNonStringReplacement($replacement)
    {
        $cstr = new CString('Hello', 'ISO-8859-1');
        $this->expectException(\TypeError::class);
        $cstr->Replace('Hello', $replacement);
    }

    #[DataProviderExternal(DataHelper::class, 'NonBooleanProvider')]
    function testReplaceWithNonBooleanCaseSensitive($caseSensitive)
    {
        $cstr = new CString('Hello', 'ISO-8859-1');
        $this->expectException(\TypeError::class);
        $cstr->Replace('Hello', 'Hi', $caseSensitive);
    }

    #endregion Replace

    #region Interface: Stringable ----------------------------------------------

    function testToString()
    {
        $str = 'Hello, World!';
        $cstr = new CString($str);
        $this->assertSame($str, (string)$cstr);
    }

    #endregion Interface: Stringable

    #region Interface: ArrayAccess ---------------------------------------------

    #[DataProvider('offsetExistsDataProvider')]
    function testOffsetExists(bool $expected, string $value, string $encoding,
        int $offset)
    {
        $cstr = new CString($value, $encoding);
        $this->assertSame($expected, isset($cstr[$offset]));
    }

    #[DataProviderExternal(DataHelper::class, 'NonIntegerProvider')]
    function testOffsetExistsWithNonIntegerOffset($nonInteger)
    {
        $cstr = new CString('Hello', 'ISO-8859-1');
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Offset must be an integer.');
        isset($cstr[$nonInteger]);
    }

    function testOffsetExistsWithNegativeOffset()
    {
        $cstr = new CString('Hello', 'ISO-8859-1');
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Offset must be a non-negative integer.');
        isset($cstr[-1]);
    }

    function testOffsetGet()
    {
        $cstr = $this->getMockBuilder(CString::class)
            ->setConstructorArgs(['Hello', 'ISO-8859-1'])
            ->onlyMethods(['At'])
            ->getMock();
        $cstr->expects($this->once())
            ->method('At')
            ->with(1)
            ->willReturn('e');
        $this->assertSame('e', $cstr[1]);
    }

    function testOffsetSet()
    {
        $cstr = $this->getMockBuilder(CString::class)
            ->setConstructorArgs(['Hello', 'ISO-8859-1'])
            ->onlyMethods(['SetAt'])
            ->getMock();
        $cstr->expects($this->once())
            ->method('SetAt')
            ->with(1, 'a');
        $cstr[1] = 'a';
    }

    function testOffsetUnset()
    {
        $cstr = $this->getMockBuilder(CString::class)
            ->setConstructorArgs(['Hello', 'ISO-8859-1'])
            ->onlyMethods(['DeleteAt'])
            ->getMock();
        $cstr->expects($this->once())
            ->method('DeleteAt')
            ->with(1);
        unset($cstr[1]);
    }

    #endregion Interface: ArrayAccess

    #region Interface: IteratorAggregate ---------------------------------------

    function testGetIteratorForSingleByteEncoding()
    {
        $cstr = new CString('Hello', 'ISO-8859-1');
        $result = [];
        foreach ($cstr as $char) {
            $result[] = $char;
        }
        $this->assertEquals(['H', 'e', 'l', 'l', 'o'], $result);
    }

    function testGetIteratorForMultiByteEncoding()
    {
        $cstr = new CString('こんにちは', 'UTF-8');
        $result = [];
        foreach ($cstr as $char) {
            $result[] = $char;
        }
        $this->assertEquals(['こ', 'ん', 'に', 'ち', 'は'], $result);
    }

    function testGetIteratorForEmptyString()
    {
        $cstr = new CString();
        $result = [];
        foreach ($cstr as $char) {
            $result[] = $char;
        }
        $this->assertEmpty($result);
    }

    #endregion Interface: IteratorAggregate

    #region Private: wrap ------------------------------------------------------

    public function testWrapWithCompatibleEncoding()
    {
        $cstr = new CString('こんにちは', 'UTF-8');
        $str = 'おはよう';
        $cstr2 = AccessHelper::CallNonPublicMethod($cstr, 'wrap', [$str]);
        $this->assertInstanceOf(CString::class, $cstr2);
        $this->assertEquals($str, (string)$cstr2);
    }

    #[DataProvider('wrapIncompatibleEncodingDataProvider')]
    function testWrapWithIncompatibleEncoding($encoding, $incompatibleString)
    {
        $cstr = new CString('', $encoding);
        $this->expectException(\ValueError::class);
        AccessHelper::CallNonPublicMethod($cstr, 'wrap', [$incompatibleString]);
    }

    #endregion Private: wrap

    #region Private: withMultibyteRegexEncoding --------------------------------

    public function testWithMultibyteRegexEncodingNoChangeNeeded()
    {
        \mb_regex_encoding('UTF-8'); // Global encoding same as instance's encoding.
        $cstr = new CString('Hello', 'UTF-8');
        AccessHelper::CallNonPublicMethod($cstr, 'withMultibyteRegexEncoding', [
            function() {
                // Confirm that the encoding remains 'UTF-8'.
                $this->assertSame('UTF-8', \mb_regex_encoding());
            }
        ]);
        // Encoding should remain 'UTF-8' when no change is needed.
        $this->assertSame('UTF-8', \mb_regex_encoding());
    }

    public function testWithMultibyteRegexEncodingChangeNeeded()
    {
        $originalEncoding = \mb_regex_encoding();
        $cstr = new CString('Hello', 'EUC-JP'); // Different instance encoding.
        AccessHelper::CallNonPublicMethod($cstr, 'withMultibyteRegexEncoding', [
            function() {
                // Confirm that the encoding was changed to 'EUC-JP'
                $this->assertSame('EUC-JP', \mb_regex_encoding());
            }
        ]);
        // Original encoding should be restored after callback.
        $this->assertSame($originalEncoding, \mb_regex_encoding());
    }

    public function testWithMultibyteRegexEncodingCaseInsensitiveComparison()
    {
        \mb_regex_encoding('UTF-8'); // Uppercase global encoding.
        $cstr = new CString('Hello', 'utf-8'); // Lowercase instance encoding.
        AccessHelper::CallNonPublicMethod($cstr, 'withMultibyteRegexEncoding', [
            function() {
                // Confirm that the encoding remains 'UTF-8' thanks to
                // case-insensitive comparison.
                $this->assertSame('UTF-8', \mb_regex_encoding());
            }
        ]);
        // Encoding should remain 'UTF-8'.
        $this->assertSame('UTF-8', \mb_regex_encoding());
    }

    public function testWithMultibyteRegexEncodingReturnsCallbackResult()
    {
        $cstr = new CString();
        $result = AccessHelper::CallNonPublicMethod($cstr, 'withMultibyteRegexEncoding', [
            function() {
                return 42;
            }
        ]);
        $this->assertSame(42, $result);
    }

    #endregion Private: withMultibyteRegexEncoding

    #region Data Providers -----------------------------------------------------

    static function singleByteEncodingProvider()
    {
        return [
            'Standard ASCII (7-bit)' => [
                'ASCII',
                'The quick brown fox jumps over the lazy dog'
            ],
            'Alias for ASCII' => [
                'US-ASCII',
                'The quick brown fox jumps over the lazy dog'
            ],
            'Western European (DOS)' => [
                'CP850',
                'Le renard brun rapide saute par-dessus le chien paresseux'
            ],
            'Cyrillic (DOS)' => [
                'CP866',
                'Быстрая коричневая лиса прыгает через ленивую собаку'
            ],
            'Cyrillic (Windows)' => [
                'CP1251',
                'Быстрая коричневая лиса прыгает через ленивую собаку'
            ],
            'Turkish (Windows)' => [
                'CP1254',
                'Çabuk kahverengi tilki tembel köpeğin üzerine atlar'
            ],
            'Latin-1 (Western European)' => [
                'ISO-8859-1',
                'Le garçon était très heureux d’apprendre qu’il gagnerait bientôt une voiture.'
            ],
            'Latin-2 (Central European)' => [
                'ISO-8859-2',
                'Příliš žluťoučký kůň úpěl ďábelské ódy, často šťavnaté lízátko plné chvástání.'
            ],
            'Latin-3 (South European)' => [
                'ISO-8859-3',
                'Ċetta ħadet il-karozza sabiħa għax hemm raġel maġenb il-knisja.'
            ],
            'Latin-4 (North European)' => [
                'ISO-8859-4',
                'Lietuvių kalbos mokykloje buvau labai išsigandęs.'
            ],
            'Cyrillic' => [
                'ISO-8859-5',
                'Быстрая коричневая лиса перепрыгнула через ленивую собаку.'
            ],
            'Greek' => [
                'ISO-8859-7',
                'Η ταχεία καφέ αλεπού πηδά πάνω από τον τεμπέλη σκύλο.'
            ],
            'Latin-5 (Turkish)' => [
                'ISO-8859-9',
                'Çabuk kahverengi tilki tembel köpeğin üstünden atladı.'
            ],
            'Latin-6 (Nordic)' => [
                'ISO-8859-10',
                'Hunden hoppar över den bruna räven som vilar bredvid kyrkan.'
            ],
            'Baltic Rim' => [
                'ISO-8859-13',
                'Vėjas pūtė stipriai ir sukūrė didžiules bangas.'
            ],
            'Latin-8 (Celtic)' => [
                'ISO-8859-14',
                'Fuaire an madra bána faoi bhun an crainn.'
            ],
            'Latin-9 (Western European)' => [
                'ISO-8859-15',
                'L’élève réussit l’examen difficile grâce à l’aide de ses amis.'
            ],
            'Latin-10 (South-Eastern European)' => [
                'ISO-8859-16',
                'Vulpea maronie a sărit peste câinele leneș din curte.'
            ],
            'Cyrillic (Russian)' => [
                'KOI8-R',
                'Быстрая лиса перепрыгнула через ленивого пса возле дома.'
            ],
            'Cyrillic (Ukrainian)' => [
                'KOI8-U',
                'Швидка лисиця стрибнула через ледачого собаку.'
            ],
            'Cyrillic (Windows)' => [
                'Windows-1251',
                'Быстрая коричневая лиса перепрыгнула через ленивую собаку.'
            ],
            'Western European (Windows)' => [
                'Windows-1252',
                'Le rapide renard brun saute par-dessus le chien paresseux.'
            ],
            'Turkish (Windows)' => [
                'Windows-1254',
                'Çabuk kahverengi tilki tembel köpeğin üstünden atladı.'
            ],
        ];
    }

    static function supportedMultiByteEncodingProvider()
    {
        return [
            ['BIG-5'],    // Traditional Chinese (Taiwan, Hong Kong)
            ['CP932'],    // Shift-JIS variant (Windows Japanese)
            ['EUC-CN'],   // Simplified Chinese (Extended Unix Code)
            ['EUC-JP'],   // Extended Unix Code for Japanese
            ['EUC-KR'],   // Extended Unix Code for Korean
            ['EUC-TW'],   // Traditional Chinese (Extended Unix Code)
            ['SJIS-win'], // Shift-JIS variant for Windows
            ['UCS-4'],    // Universal Coded Character Set (4-byte)
            ['UCS-4LE'],  // UCS-4 Little Endian
            ['UTF-8'],    // Unicode Transformation Format (8-bit, variable-length)
            ['UTF-16'],   // Unicode Transformation Format (16-bit)
            ['UTF-16BE'], // UTF-16 Big Endian
            ['UTF-16LE'], // UTF-16 Little Endian
            ['UTF-32'],   // Unicode Transformation Format (32-bit)
            ['UTF-32BE'], // UTF-32 Big Endian
            ['UTF-32LE'], // UTF-32 Little Endian
        ];
    }

    static function unsupportedMultiByteEncodingProvider()
    {
        return [
            ['CP936'],             // Simplified Chinese (GBK, Windows)
            ['CP950'],             // Traditional Chinese (Big5, Windows)
            ['CP50220'],           // Japanese, ISO-2022-JP with extensions (JIS)
            ['CP50221'],           // Japanese, ISO-2022-JP with 1-byte Kana
            ['CP50222'],           // Japanese, ISO-2022-JP variant
            ['CP51932'],           // Japanese, EUC variant for Windows
            ['GB18030'],           // Simplified Chinese (Unicode-compatible)
            ['ISO-2022-JP-2004'],  // Japanese (ISO-2022 variant, JIS X 0213:2004)
            ['ISO-2022-JP-MOBILE#KDDI'], // Japanese (ISO-2022 for mobile devices)
            ['ISO-2022-JP-MS'],    // Japanese (Microsoft variant of ISO-2022-JP)
            ['ISO-2022-JP'],       // Japanese (ISO 2022 standard)
            ['ISO-2022-KR'],       // Korean (ISO 2022 standard)
            ['ISO2022JPMS'],       // Japanese (ISO-2022 variant for Microsoft systems)
            ['SJIS-2004'],         // Shift-JIS variant for JIS X 0213:2004
            ['SJIS-mac'],          // Shift-JIS variant for Mac systems
            ['SJIS-Mobile#DOCOMO'], // Shift-JIS variant for DOCOMO mobile devices
            ['SJIS-Mobile#KDDI'],   // Shift-JIS variant for KDDI mobile devices
            ['SJIS-Mobile#SOFTBANK'], // Shift-JIS variant for SoftBank mobile devices
            ['UCS-2'],             // Universal Coded Character Set (2-byte, BMP)
            ['UCS-2BE'],           // UCS-2 Big Endian
            ['UCS-2LE'],           // UCS-2 Little Endian
            ['UCS-4BE'],           // UCS-4 Big Endian
            ['UTF-7'],             // Unicode Transformation Format (7-bit)
            ['UTF7-IMAP'],         // UTF-7 variant for IMAP (mail protocol)
        ];
    }

    static function multiByteEncodingProvider()
    {
        return \array_merge(
            self::supportedMultiByteEncodingProvider(),
            self::unsupportedMultiByteEncodingProvider()
        );
    }

    static function wrapIncompatibleEncodingDataProvider()
    {
        return [
            ['CP1254', 'Быстрая'],
            ['ISO-8859-1', chr(0xfe)],
            ['ISO-8859-1', 'こんにちは'],
        ];
    }

    static function isEmptyDataProvider()
    {
        return [
            'non-empty string (single-byte)' => [
                false, 'Hello', 'ISO-8859-1'
            ],
            'empty string (single-byte)' => [
                true, '', 'ISO-8859-1'
            ],
            'non-empty string (multibyte)' => [
                false, 'こんにちは', 'UTF-8'
            ],
            'empty string (multibyte)' => [
                true, '', 'UTF-8'
            ],
        ];
    }

    static function lengthDataProvider()
    {
        return [
            'non-empty string (single-byte)' => [
                5, 'Hello', 'ISO-8859-1'
            ],
            'empty string (single-byte)' => [
                0, '', 'ISO-8859-1'
            ],
            'non-empty string (multibyte)' => [
                5, 'こんにちは', 'UTF-8'
            ],
            'empty string (multibyte)' => [
                0, '', 'UTF-8'
            ],
        ];
    }

    static function firstDataProvider()
    {
        return [
            'non-empty string (single-byte)' => [
                'H', 'Hello', 'ISO-8859-1'
            ],
            'empty string (single-byte)' => [
                '', '', 'ISO-8859-1'
            ],
            'non-empty string (multibyte)' => [
                'こ', 'こんにちは', 'UTF-8'
            ],
            'empty string (multibyte)' => [
                '', '', 'UTF-8'
            ],
        ];
    }

    static function lastDataProvider()
    {
        return [
            'non-empty string (single-byte)' => [
                'o', 'Hello', 'ISO-8859-1'
            ],
            'empty string (single-byte)' => [
                '', '', 'ISO-8859-1'
            ],
            'non-empty string (multibyte)' => [
                'は', 'こんにちは', 'UTF-8'
            ],
            'empty string (multibyte)' => [
                '', '', 'UTF-8'
            ],
        ];
    }

    static function atDataProvider()
    {
        return [
        // Single-byte
            'offset at start (single-byte)' => [
                'H', 'Hello', 'ISO-8859-1', 0
            ],
            'offset in middle (single-byte)' => [
                'e', 'Hello', 'ISO-8859-1', 1
            ],
            'offset at end (single-byte)' => [
                'o', 'Hello', 'ISO-8859-1', 4
            ],
            'offset past length (single-byte)' => [
                '', 'Hello', 'ISO-8859-1', 10
            ],
            'negative offset (single-byte)' => [
                '', 'Hello', 'ISO-8859-1', -1
            ],
            'empty string (single-byte)' => [
                '', '', 'ISO-8859-1', 0
            ],
        // Multibyte
            'offset at start (multibyte)' => [
                'こ', 'こんにちは', 'UTF-8', 0
            ],
            'offset in middle (multibyte)' => [
                'ん', 'こんにちは', 'UTF-8', 1
            ],
            'offset at end (multibyte)' => [
                'は', 'こんにちは', 'UTF-8', 4
            ],
            'offset past length (multibyte)' => [
                '', 'こんにちは', 'UTF-8', 10
            ],
            'negative offset (multibyte)' => [
                '', 'こんにちは', 'UTF-8', -1
            ],
            'empty string (multibyte)' => [
                '', '', 'UTF-8', 0
            ],
        ];
    }

    static function setAtDataProvider()
    {
        return [
        // Single-byte
            'offset at start (single-byte)' => [
                'Yello', 'Hello', 'ISO-8859-1', 0, 'Y'
            ],
            'offset in middle (single-byte)' => [
                'Hallo', 'Hello', 'ISO-8859-1', 1, 'a'
            ],
            'offset at end (single-byte)' => [
                'Helly', 'Hello', 'ISO-8859-1', 4, 'y'
            ],
            'offset after last character (single-byte)' => [
                'Hello', 'Hello', 'ISO-8859-1', 5, '!'
            ],
            'offset past length (single-byte)' => [
                'Hello', 'Hello', 'ISO-8859-1', 10, 'Y'
            ],
            'offset past length in empty string (single-byte)' => [
                '', '', 'ISO-8859-1', 3, 'Y'
            ],
            'negative offset (single-byte)' => [
                'Hello', 'Hello', 'ISO-8859-1', -1, 'Y'
            ],
            'multi-character input (single-byte)' => [
                'Hello', 'Hello', 'ISO-8859-1', 0, 'ABC'
            ],
            'empty character (single-byte)' => [
                'Hello', 'Hello', 'ISO-8859-1', 0, ''
            ],
            'empty string (single-byte)' => [
                '', '', 'ISO-8859-1', 0, 'Y'
            ],
        // Multibyte
            'offset at start (multibyte)' => [
                'さんにちは', 'こんにちは', 'UTF-8', 0, 'さ'
            ],
            'offset in middle (multibyte)' => [
                'こすにちは', 'こんにちは', 'UTF-8', 1, 'す'
            ],
            'offset at end (multibyte)' => [
                'こんにちせ', 'こんにちは', 'UTF-8', 4, 'せ'
            ],
            'offset after last character (multibyte)' => [
                'こんにちは', 'こんにちは', 'UTF-8', 5, 'ぞ'
            ],
            'offset past length (multibyte)' => [
                'こんにちは', 'こんにちは', 'UTF-8', 10, 'さ'
            ],
            'offset past length in empty string (multibyte)' => [
                '', '', 'UTF-8', 3, 'さ'
            ],
            'negative offset (multibyte)' => [
                'こんにちは', 'こんにちは', 'UTF-8', -1, 'さ'
            ],
            'multi-character input (multibyte)' => [
                'こんにちは', 'こんにちは', 'UTF-8', 0, 'あいうえお'
            ],
            'empty character (multibyte)' => [
                'こんにちは', 'こんにちは', 'UTF-8', 0, ''
            ],
            'empty string (multibyte)' => [
                '', '', 'UTF-8', 0, 'さ'
            ],
        ];
    }

    static function insertAtDataProvider()
    {
        return [
        // Single-byte
            'offset at start (single-byte)' => [
                'Hello, World!', 'World!', 'ISO-8859-1', 0, 'Hello, '
            ],
            'offset in middle (single-byte)' => [
                'Hell no', 'Hello', 'ISO-8859-1', 4, ' n'
            ],
            'offset at end (single-byte)' => [
                'Hello!', 'Hello', 'ISO-8859-1', 5, '!'
            ],
            'offset past length (single-byte)' => [
                'Hello', 'Hello', 'ISO-8859-1', 6, ' everyone'
            ],
            'negative offset (single-byte)' => [
                'Hello', 'Hello', 'ISO-8859-1', -1, 'Oops'
            ],
            'empty substring (single-byte)' => [
                'Hello', 'Hello', 'ISO-8859-1', 0, ''
            ],
            'empty string (single-byte)' => [
                'Hello there', '', 'ISO-8859-1', 0, 'Hello there'
            ],
        // Multibyte
            'offset at start (multibyte)' => [
                'おはようこんにちは', 'こんにちは', 'UTF-8', 0, 'おはよう'
            ],
            'offset in middle (multibyte)' => [
                'こんさにちは', 'こんにちは', 'UTF-8', 2, 'さ'
            ],
            'offset at end (multibyte)' => [
                'こんにちは!', 'こんにちは', 'UTF-8', 5, '!'
            ],
            'offset past length (multibyte)' => [
                'こんにちは', 'こんにちは', 'UTF-8', 6, ' またね'
            ],
            'negative offset (multibyte)' => [
                'こんにちは', 'こんにちは', 'UTF-8', -1, 'さ'
            ],
            'empty substring (multibyte)' => [
                'こんにちは', 'こんにちは', 'UTF-8', 0, ''
            ],
            'empty string (multibyte)' => [
                'ありがとう', '', 'UTF-8', 0, 'ありがとう'
            ],
        ];
    }

    static function deleteAtDataProvider()
    {
        return [
        // Single-byte
            'offset at start, single character (single-byte)' => [
                'ello', 'Hello', 'ISO-8859-1', 0
            ],
            'offset at start, multiple characters (single-byte)' => [
                'llo', 'Hello', 'ISO-8859-1', 0, 2
            ],
            'offset in middle, single character (single-byte)' => [
                'Hllo', 'Hello', 'ISO-8859-1', 1
            ],
            'offset in middle, multiple characters (single-byte)' => [
                'Ho', 'Hello', 'ISO-8859-1', 1, 3
            ],
            'offset at end, single character (single-byte)' => [
                'Hell', 'Hello', 'ISO-8859-1', 4
            ],
            'offset at end, multiple characters (single-byte)' => [
                'Hel', 'Hello', 'ISO-8859-1', 3, 2
            ],
            'offset past length, single character (single-byte)' => [
                'Hello', 'Hello', 'ISO-8859-1', 10
            ],
            'offset past length, multiple characters (single-byte)' => [
                'Hello', 'Hello', 'ISO-8859-1', 10, 2
            ],
            'negative offset, single character (single-byte)' => [
                'Hello', 'Hello', 'ISO-8859-1', -1
            ],
            'negative offset, multiple characters (single-byte)' => [
                'Hello', 'Hello', 'ISO-8859-1', -1, 3
            ],
            'zero count (single-byte)' => [
                'Hello', 'Hello', 'ISO-8859-1', 2, 0
            ],
            'negative count (single-byte)' => [
                'Hello', 'Hello', 'ISO-8859-1', 2, -1
            ],
            'count exceeds available (single-byte)' => [
                'He', 'Hello', 'ISO-8859-1', 2, 10
            ],
            'empty string, single character (single-byte)' => [
                '', '', 'ISO-8859-1', 0
            ],
            'empty string, multiple characters (single-byte)' => [
                '', '', 'ISO-8859-1', 0, 3
            ],
        // Multibyte
            'offset at start, single character (multibyte)' => [
                'んにちは', 'こんにちは', 'UTF-8', 0
            ],
            'offset at start, multiple characters (multibyte)' => [
                'にちは', 'こんにちは', 'UTF-8', 0, 2
            ],
            'offset in middle, single character (multibyte)' => [
                'こにちは', 'こんにちは', 'UTF-8', 1
            ],
            'offset in middle, multiple characters (multibyte)' => [
                'こは', 'こんにちは', 'UTF-8', 1, 3
            ],
            'offset at end, single character (multibyte)' => [
                'こんにち', 'こんにちは', 'UTF-8', 4
            ],
            'offset at end, multiple characters (multibyte)' => [
                'こんに', 'こんにちは', 'UTF-8', 3, 2
            ],
            'offset past length, single character (multibyte)' => [
                'こんにちは', 'こんにちは', 'UTF-8', 10
            ],
            'offset past length, multiple characters (multibyte)' => [
                'こんにちは', 'こんにちは', 'UTF-8', 10, 2
            ],
            'negative offset, single character (multibyte)' => [
                'こんにちは', 'こんにちは', 'UTF-8', -1
            ],
            'negative offset, multiple characters (multibyte)' => [
                'こんにちは', 'こんにちは', 'UTF-8', -1, 3
            ],
            'zero count (multibyte)' => [
                'こんにちは', 'こんにちは', 'UTF-8', 2, 0
            ],
            'negative count (multibyte)' => [
                'こんにちは', 'こんにちは', 'UTF-8', 2, -1
            ],
            'count exceeds available (multibyte)' => [
                'こん', 'こんにちは', 'UTF-8', 2, 10
            ],
            'empty string, single character (multibyte)' => [
                '', '', 'UTF-8', 0
            ],
            'empty string, multiple characters (multibyte)' => [
                '', '', 'UTF-8', 0, 3
            ],
        ];
    }

    static function leftDataProvider()
    {
        return [
        // Single-byte
            ['Hel', 'Hello', 'ISO-8859-1', 3],
            ['Hello', 'Hello', 'ISO-8859-1', 10],
            ['', 'Hello', 'ISO-8859-1', 0],
            ['', 'Hello', 'ISO-8859-1', -1],
            ['', '', 'ISO-8859-1', 2],
        // Multibyte
            ['こん', 'こんにちは', 'UTF-8', 2],
            ['こんにちは', 'こんにちは', 'UTF-8', 10],
            ['', 'こんにちは', 'UTF-8', 0],
            ['', 'こんにちは', 'UTF-8', -3],
            ['', '', 'UTF-8', 3],
        ];
    }

    static function rightDataProvider()
    {
        return [
        // Single-byte
            ['llo', 'Hello', 'ISO-8859-1', 3],
            ['Hello', 'Hello', 'ISO-8859-1', 10],
            ['', 'Hello', 'ISO-8859-1', 0],
            ['', 'Hello', 'ISO-8859-1', -1],
            ['', '', 'ISO-8859-1', 2],
        // Multibyte
            ['ちは', 'こんにちは', 'UTF-8', 2],
            ['こんにちは', 'こんにちは', 'UTF-8', 10],
            ['', 'こんにちは', 'UTF-8', 0],
            ['', 'こんにちは', 'UTF-8', -3],
            ['', '', 'UTF-8', 3],
        ];
    }

    static function middleDataProvider()
    {
        return [
        // Single-byte
            ['ell', 'Hello', 'ISO-8859-1', 1, 3],
            ['Hello', 'Hello', 'ISO-8859-1', 0, 10],
            ['', 'Hello', 'ISO-8859-1', 10, 2],
            ['', 'Hello', 'ISO-8859-1', -1, 3],
            ['', 'Hello', 'ISO-8859-1', 1, -3],
            ['', '', 'ISO-8859-1', 3, 5],
        // Multibyte
            ['んに', 'こんにちは', 'UTF-8', 1, 2],
            ['こんにちは', 'こんにちは', 'UTF-8', 0, 10],
            ['', 'こんにちは', 'UTF-8', 10, 2],
            ['', 'こんにちは', 'UTF-8', -2, 3],
            ['', 'こんにちは', 'UTF-8', 1, -5],
            ['', '', 'UTF-8', 3, 5],
        ];
    }

    static function trimDataProvider()
    {
        return [
        // Single-byte
            'default whitespace (single-byte)' => [
                'Hello', " \n Hello \r\t", 'ISO-8859-1'
            ],
            'custom characters (single-byte)' => [
                'Hello', '-=Hello=-', 'ISO-8859-1', '=-'
            ],
            'no whitespace (single-byte)' => [
                'Hello', 'Hello', 'ISO-8859-1'
            ],
            'only whitespace (single-byte)' => [
                '', '    ', 'ISO-8859-1'
            ],
            'only trim characters (single-byte)' => [
                '', '====', 'ISO-8859-1', '='
            ],
            'non-present characters (single-byte)' => [
                'Hello', 'Hello', 'ISO-8859-1', '=*'
            ],
            'empty string (single-byte)' => [
                '', '', 'ISO-8859-1'
            ],
            'empty characters (single-byte)' => [
                ' Hello ', ' Hello ', 'ISO-8859-1', ''
            ],
            'null characters (single-byte)' => [
                'Hello', " \n Hello \t ", 'ISO-8859-1', null
            ],
        // Multibyte
            'default whitespace (multibyte)' => [
                'こんにちは', '　こんにちは　', 'UTF-8'
            ],
            'custom characters (multibyte)' => [
                'こんにちは', '=*=こんにちは=*=', 'UTF-8', '=*'
            ],
            'no whitespace (multibyte)' => [
                'こんにちは', 'こんにちは', 'UTF-8'
            ],
            'only whitespace (single-byte)' => [
                '', '　　　　', 'UTF-8'
            ],
            'only trim characters (multibyte)' => [
                '', '＝＝＝', 'UTF-8', '＝'
            ],
            'non-present characters (multibyte)' => [
                'こんにちは', 'こんにちは', 'UTF-8', '=*'
            ],
            'empty string (multibyte)' => [
                '', '', 'UTF-8'
            ],
            'empty characters (multibyte)' => [
                '　こんにちは　', '　こんにちは　', 'UTF-8', ''
            ],
            'null characters (multibyte)' => [
                'こんにちは', '　こんにちは　', 'UTF-8', null
            ],
        ];
    }

    static function trimLeftDataProvider()
    {
        return [
        // Single-byte
            'default whitespace (single-byte)' => [
                "Hello \r\t", " \n Hello \r\t", 'ISO-8859-1'
            ],
            'custom characters (single-byte)' => [
                'Hello=-', '-=Hello=-', 'ISO-8859-1', '=-'
            ],
            'no whitespace (single-byte)' => [
                'Hello', 'Hello', 'ISO-8859-1'
            ],
            'only whitespace (single-byte)' => [
                '', '    ', 'ISO-8859-1'
            ],
            'only trim characters (single-byte)' => [
                '', '====', 'ISO-8859-1', '='
            ],
            'non-present characters (single-byte)' => [
                'Hello', 'Hello', 'ISO-8859-1', '=*'
            ],
            'empty string (single-byte)' => [
                '', '', 'ISO-8859-1'
            ],
            'empty characters (single-byte)' => [
                ' Hello ', ' Hello ', 'ISO-8859-1', ''
            ],
            'null characters (single-byte)' => [
                "Hello \t ", " \n Hello \t ", 'ISO-8859-1', null
            ],
        // Multibyte
            'default whitespace (multibyte)' => [
                'こんにちは　', '　こんにちは　', 'UTF-8'
            ],
            'custom characters (multibyte)' => [
                'こんにちは=*=', '=*=こんにちは=*=', 'UTF-8', '=*'
            ],
            'no whitespace (multibyte)' => [
                'こんにちは', 'こんにちは', 'UTF-8'
            ],
            'only whitespace (single-byte)' => [
                '', '　　　　', 'UTF-8'
            ],
            'only trim characters (multibyte)' => [
                '', '＝＝＝', 'UTF-8', '＝'
            ],
            'non-present characters (multibyte)' => [
                'こんにちは', 'こんにちは', 'UTF-8', '=*'
            ],
            'empty string (multibyte)' => [
                '', '', 'UTF-8'
            ],
            'empty characters (multibyte)' => [
                '　こんにちは　', '　こんにちは　', 'UTF-8', ''
            ],
            'null characters (multibyte)' => [
                'こんにちは　', '　こんにちは　', 'UTF-8', null
            ],
        ];
    }

    static function trimRightDataProvider()
    {
        return [
        // Single-byte
            'default whitespace (single-byte)' => [
                " \n Hello", " \n Hello \r\t", 'ISO-8859-1'
            ],
            'custom characters (single-byte)' => [
                '-=Hello', '-=Hello=-', 'ISO-8859-1', '=-'
            ],
            'no whitespace (single-byte)' => [
                'Hello', 'Hello', 'ISO-8859-1'
            ],
            'only whitespace (single-byte)' => [
                '', '    ', 'ISO-8859-1'
            ],
            'only trim characters (single-byte)' => [
                '', '====', 'ISO-8859-1', '='
            ],
            'non-present characters (single-byte)' => [
                'Hello', 'Hello', 'ISO-8859-1', '=*'
            ],
            'empty string (single-byte)' => [
                '', '', 'ISO-8859-1'
            ],
            'empty characters (single-byte)' => [
                ' Hello ', ' Hello ', 'ISO-8859-1', ''
            ],
            'null characters (single-byte)' => [
                " \n Hello", " \n Hello \t ", 'ISO-8859-1', null
            ],
        // Multibyte
            'default whitespace (multibyte)' => [
                '　こんにちは', '　こんにちは　', 'UTF-8'
            ],
            'custom characters (multibyte)' => [
                '=*=こんにちは', '=*=こんにちは=*=', 'UTF-8', '=*'
            ],
            'no whitespace (multibyte)' => [
                'こんにちは', 'こんにちは', 'UTF-8'
            ],
            'only whitespace (single-byte)' => [
                '', '　　　　', 'UTF-8'
            ],
            'only trim characters (multibyte)' => [
                '', '＝＝＝', 'UTF-8', '＝'
            ],
            'non-present characters (multibyte)' => [
                'こんにちは', 'こんにちは', 'UTF-8', '=*'
            ],
            'empty string (multibyte)' => [
                '', '', 'UTF-8'
            ],
            'empty characters (multibyte)' => [
                '　こんにちは　', '　こんにちは　', 'UTF-8', ''
            ],
            'null characters (multibyte)' => [
                '　こんにちは', '　こんにちは　', 'UTF-8', null
            ],
        ];
    }

    static function lowercaseDataProvider()
    {
        return [
            ['hello', 'HELLO', 'ISO-8859-1'],
            ['hello', 'hElLo', 'ISO-8859-1'],
            ['hello', 'hello', 'ISO-8859-1'],
            ['こんにちは', 'こんにちは', 'UTF-8'],
            ['schön', 'SCHÖN', 'UTF-8'],
            ['éléphant', 'ÉLÉPHANT', 'UTF-8'],
            ['çöğüş i̇şi̇ güçtür', 'ÇÖĞÜŞ İŞİ GÜÇTÜR', 'UTF-8']
        ];
    }

    static function uppercaseDataProvider()
    {
        return [
            ['HELLO', 'hello', 'ISO-8859-1'],
            ['HELLO', 'HeLlO', 'ISO-8859-1'],
            ['HELLO', 'HELLO', 'ISO-8859-1'],
            ['こんにちは', 'こんにちは', 'UTF-8'],
            ['SCHÖN', 'schön', 'UTF-8'],
            ['ÉLÉPHANT', 'éléphant', 'UTF-8'],
            ['ÇÖĞÜŞ İŞİ GÜÇTÜR', 'çöğüş i̇şi̇ güçtür', 'UTF-8']
        ];
    }

    static function startsWithDataProvider()
    {
        return [
        // Single-byte
            'exact match (single-byte)' => [
                true, 'Hello', 'ISO-8859-1', 'Hello'
            ],
            'partial match (single-byte)' => [
                true, 'Hello', 'ISO-8859-1', 'Hell'
            ],
            'single character match (single-byte)' => [
                true, 'Hello', 'ISO-8859-1', 'H'
            ],
            'case-sensitive fails (single-byte)' => [
                false, 'Hello', 'ISO-8859-1', 'hello'
            ],
            'case-insensitive match (single-byte)' => [
                true, 'Hello', 'ISO-8859-1', 'HELLO', false
            ],
            'completely different string (single-byte)' => [
                false, 'Hello', 'ISO-8859-1', 'World'
            ],
            'same length, different content (single-byte)' => [
                false, 'Hello', 'ISO-8859-1', 'HeLLo'
            ],
            'empty search string (single-byte)' => [
                true, 'Hello', 'ISO-8859-1', ''
            ],
            'empty instance string (single-byte)' => [
                false, '', 'ISO-8859-1', 'Hello'
            ],
            'empty instance and search strings (single-byte)' => [
                true, '', 'ISO-8859-1', ''
            ],
            'leading whitespace fails (single-byte)' => [
                false, ' Hello', 'ISO-8859-1', 'Hello'
            ],
            'leading whitespace matches (single-byte)' => [
                true, ' Hello', 'ISO-8859-1', ' Hello'
            ],
            'search string longer than instance (single-byte)' => [
                false, 'Hello', 'ISO-8859-1', 'Hello World'
            ],
        // Multibyte
            'exact match (multibyte)' => [
                true, 'Résumé', 'UTF-8', 'Résumé'
            ],
            'partial match (multibyte)' => [
                true, 'Résumé', 'UTF-8', 'Ré'
            ],
            'single character match (multibyte)' => [
                true, 'Résumé', 'UTF-8', 'R'
            ],
            'case-sensitive fails (multibyte)' => [
                false, 'Résumé', 'UTF-8', 'résumé'
            ],
            'case-insensitive match (multibyte)' => [
                true, 'Résumé', 'UTF-8', 'RÉSUMÉ', false
            ],
            'completely different string (multibyte)' => [
                false, 'Résumé', 'UTF-8', 'World'
            ],
            'same length, different content (multibyte)' => [
                false, 'Résumé', 'UTF-8', 'RéSumé'
            ],
            'empty search string (multibyte)' => [
                true, 'Résumé', 'UTF-8', ''
            ],
            'empty instance string (multibyte)' => [
                false, '', 'UTF-8', 'Résumé'
            ],
            'empty instance and search strings (multibyte)' => [
                true, '', 'UTF-8', ''
            ],
            'leading whitespace fails (multibyte)' => [
                false, ' Résumé', 'UTF-8', 'Résumé'
            ],
            'leading whitespace matches (multibyte)' => [
                true, ' Résumé', 'UTF-8', ' Résumé'
            ],
            'search string longer than instance (multibyte)' => [
                false, 'Résumé', 'UTF-8', 'Résumé Long'
            ],
        ];
    }

    static function endsWithDataProvider()
    {
        return [
        // Single-byte
            'exact match (single-byte)' => [
                true, 'Hello', 'ISO-8859-1', 'Hello'
            ],
            'partial match (single-byte)' => [
                true, 'Hello', 'ISO-8859-1', 'llo'
            ],
            'single character match (single-byte)' => [
                true, 'Hello', 'ISO-8859-1', 'o'
            ],
            'case-sensitive fails (single-byte)' => [
                false, 'Hello', 'ISO-8859-1', 'hello'
            ],
            'case-insensitive match (single-byte)' => [
                true, 'Hello', 'ISO-8859-1', 'HELLO', false
            ],
            'completely different string (single-byte)' => [
                false, 'Hello', 'ISO-8859-1', 'World'
            ],
            'same length, different content (single-byte)' => [
                false, 'Hello', 'ISO-8859-1', 'HeLLo'
            ],
            'empty search string (single-byte)' => [
                true, 'Hello', 'ISO-8859-1', ''
            ],
            'empty instance string (single-byte)' => [
                false, '', 'ISO-8859-1', 'Hello'
            ],
            'empty instance and search strings (single-byte)' => [
                true, '', 'ISO-8859-1', ''
            ],
            'trailing whitespace fails (single-byte)' => [
                false, 'Hello ', 'ISO-8859-1', 'Hello'
            ],
            'trailing whitespace matches (single-byte)' => [
                true, 'Hello ', 'ISO-8859-1', 'Hello '
            ],
            'search string longer than instance (single-byte)' => [
                false, 'Hello', 'ISO-8859-1', 'Hello World'
            ],
        // Multibyte
            'exact match (multibyte)' => [
                true, 'Résumé', 'UTF-8', 'Résumé'
            ],
            'partial match (multibyte)' => [
                true, 'Résumé', 'UTF-8', 'umé'
            ],
            'single character match (multibyte)' => [
                true, 'Résumé', 'UTF-8', 'é'
            ],
            'case-sensitive fails (multibyte)' => [
                false, 'Résumé', 'UTF-8', 'résumé'
            ],
            'case-insensitive match (multibyte)' => [
                true, 'Résumé', 'UTF-8', 'RÉSUMÉ', false
            ],
            'completely different string (multibyte)' => [
                false, 'Résumé', 'UTF-8', 'World'
            ],
            'same length, different content (multibyte)' => [
                false, 'Résumé', 'UTF-8', 'RéSumé'
            ],
            'empty search string (multibyte)' => [
                true, 'Résumé', 'UTF-8', ''
            ],
            'empty instance string (multibyte)' => [
                false, '', 'UTF-8', 'Résumé'
            ],
            'empty instance and search strings (multibyte)' => [
                true, '', 'UTF-8', ''
            ],
            'trailing whitespace fails (multibyte)' => [
                false, 'Résumé ', 'UTF-8', 'Résumé'
            ],
            'trailing whitespace matches (multibyte)' => [
                true, 'Résumé ', 'UTF-8', 'Résumé '
            ],
            'search string longer than instance (multibyte)' => [
                false, 'Résumé', 'UTF-8', 'Résumé Long'
            ],
        ];
    }

    static function equalsDataProvider()
    {
        return [
        // Single-byte, case-sensitive
            [true, 'Hello', 'ISO-8859-1', 'Hello'],
            [false, 'Hello', 'ISO-8859-1', 'hello'],
            [false, 'Hello', 'ISO-8859-1', 'Helloo'],
            [true, 'Hello', 'ISO-8859-1', new CString('Hello', 'ISO-8859-1')],
            [false, 'Hello', 'ISO-8859-1', new CString('hello', 'ISO-8859-1')],
            [false, 'Hello', 'ISO-8859-1', new CString('Helloo', 'ISO-8859-1')],
        // Single-byte, case-insensitive
            [true, 'Hello', 'ISO-8859-1', 'hello', false],
            [true, 'Hello', 'ISO-8859-1', 'HELLO', false],
            [false, 'Hello', 'ISO-8859-1', 'Helloo', false],
            [true, 'Hello', 'ISO-8859-1', new CString('hello', 'ISO-8859-1'), false],
            [true, 'Hello', 'ISO-8859-1', new CString('HELLO', 'ISO-8859-1'), false],
            [false, 'Hello', 'ISO-8859-1', new CString('Helloo', 'ISO-8859-1'), false],
        // Multibyte, case-sensitive
            [true, 'Résumé', 'UTF-8', 'Résumé'],
            [false, 'Résumé', 'UTF-8', 'résumé'],
            [false, 'Résumé', 'UTF-8', 'RésuméExtra'],
            [true, 'Résumé', 'UTF-8', new CString('Résumé', 'UTF-8')],
            [false, 'Résumé', 'UTF-8', new CString('résumé', 'UTF-8')],
            [false, 'Résumé', 'UTF-8', new CString('RésuméExtra', 'UTF-8')],
        // Multibyte, case-insensitive
            [true, 'Résumé', 'UTF-8', 'résumé', false],
            [true, 'Résumé', 'UTF-8', 'RÉSUMÉ', false],
            [false, 'Résumé', 'UTF-8', 'RésuméExtra', false],
            [true, 'Résumé', 'UTF-8', new CString('résumé', 'UTF-8'), false],
            [true, 'Résumé', 'UTF-8', new CString('RÉSUMÉ', 'UTF-8'), false],
            [false, 'Résumé', 'UTF-8', new CString('RésuméExtra', 'UTF-8'), false],
        ];
    }

    static function indexOfDataProvider()
    {
        return [
        // Single-byte
            'match at start (single-byte)' => [
                0, 'Hello', 'ISO-8859-1', 'Hell'
            ],
            'match in middle (single-byte)' => [
                2, 'Hello', 'ISO-8859-1', 'llo'
            ],
            'match at end (single-byte)' => [
                4, 'Hello', 'ISO-8859-1', 'o'
            ],
            'no match due to case sensitivity (single-byte)' => [
                null, 'Hello', 'ISO-8859-1', 'hello'
            ],
            'match with case-insensitive (single-byte)' => [
                0, 'Hello', 'ISO-8859-1', 'hello', 0, false
            ],
            'no match for unrelated string (single-byte)' => [
                null, 'Hello', 'ISO-8859-1', 'World'
            ],
            'match with start offset (single-byte)' => [
                8, 'Hello, World!', 'ISO-8859-1', 'o', 5
            ],
        // Multibyte
            'match at start (multibyte)' => [
                0, 'こんにちは', 'UTF-8', 'こん'
            ],
            'match in middle (multibyte)' => [
                2, 'こんにちは', 'UTF-8', 'にち'
            ],
            'match at end (multibyte)' => [
                4, 'こんにちは', 'UTF-8', 'は'
            ],
            'no match due to case sensitivity (multibyte)' => [
                null, 'Résumé', 'UTF-8', 'résumé'
            ],
            'match with case-insensitive (multibyte)' => [
                0, 'Résumé', 'UTF-8', 'résumé', 0, false
            ],
            'no match for unrelated string (multibyte)' => [
                null, 'こんにちは', 'UTF-8', 'さようなら'
            ],
            'match with start offset (multibyte)' => [
                9, 'こんにちは、世界、こんにちは', 'UTF-8', 'こ', 5
            ],
        ];
    }

    static function offsetExistsDataProvider()
    {
        return [
        // Single-byte
            [true, 'Hello', 'ISO-8859-1', 0],
            [true, 'Hello', 'ISO-8859-1', 1],
            [true, 'Hello', 'ISO-8859-1', 4],
            [false, 'Hello', 'ISO-8859-1', 5],
            [false, 'Hello', 'ISO-8859-1', 10],
        // Multibyte
            [true, 'こんにちは', 'UTF-8', 0],
            [true, 'こんにちは', 'UTF-8', 1],
            [true, 'こんにちは', 'UTF-8', 4],
            [false, 'こんにちは', 'UTF-8', 5],
            [false, 'こんにちは', 'UTF-8', 10],
        ];
    }

    static function replaceDataProvider()
    {
        return [
        // Single-byte
            'exact match replace (single-byte)' => [
                'Hi', 'Hello', 'ISO-8859-1', 'Hello', 'Hi'
            ],
            'partial replace in middle (single-byte)' => [
                'Hell yes', 'Hello', 'ISO-8859-1', 'o', ' yes'
            ],
            'replace start of string (single-byte)' => [
                'Heyo', 'Hello', 'ISO-8859-1', 'Hell', 'Hey'
            ],
            'replace end of string (single-byte)' => [
                'Heaven', 'Hello', 'ISO-8859-1', 'llo', 'aven'
            ],
            'replace with empty string (single-byte)' => [
                'Hell', 'Hello', 'ISO-8859-1', 'o', ''
            ],
            'no match due to case sensitivity (single-byte)' => [
                'Hello', 'Hello', 'ISO-8859-1', 'hello', 'Hi'
            ],
            'case-insensitive replace (single-byte)' => [
                'Hi', 'Hello', 'ISO-8859-1', 'hello', 'Hi', false
            ],
            'replace all occurrences (single-byte)' => [
                'Hi Hi', 'Hello Hello', 'ISO-8859-1', 'Hello', 'Hi'
            ],
        // Multibyte
            'exact match replace (multibyte)' => [
                'さようなら', 'こんにちは', 'UTF-8', 'こんにちは', 'さようなら'
            ],
            'partial replace in middle (multibyte)' => [
                'こににちは', 'こんにちは', 'UTF-8', 'ん', 'に'
            ],
            'replace start of string (multibyte)' => [
                'さようならにちは', 'こんにちは', 'UTF-8', 'こん', 'さようなら'
            ],
            'replace end of string (multibyte)' => [
                'こんにち!', 'こんにちは', 'UTF-8', 'は', '!'
            ],
            'replace with empty string (multibyte)' => [
                'こんにち', 'こんにちは', 'UTF-8', 'は', ''
            ],
            'no match due to case sensitivity (multibyte)' => [
                'Résumé', 'Résumé', 'UTF-8', 'résumé', 'Summary'
            ],
            'case-insensitive replace (multibyte)' => [
                'Summary', 'Résumé', 'UTF-8', 'résumé', 'Summary', false
            ],
            'replace all occurrences (multibyte)' => [
                'さようならさようなら', 'こんにちはこんにちは', 'UTF-8', 'こんにちは', 'さようなら'
            ],
        ];
    }

    #endregion Data Providers
}
