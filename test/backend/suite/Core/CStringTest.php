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
            AccessHelper::GetProperty($cstr, 'encoding')
        );
    }

    function testCopyConstructor()
    {
        $original = new CString('Hello, World!', 'ISO-8859-1');
        $copy = new CString($original, 'UTF-8'); // 'UTF-8' should be ignored
        $this->assertSame((string)$original, (string)$copy);
        $this->assertSame(
            AccessHelper::GetProperty($original, 'encoding'),
            AccessHelper::GetProperty($copy, 'encoding')
        );
        $this->assertSame(
            AccessHelper::GetProperty($original, 'isSingleByte'),
            AccessHelper::GetProperty($copy, 'isSingleByte')
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
            AccessHelper::GetProperty($cstr, 'encoding')
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
            AccessHelper::GetProperty($cstr, 'encoding')
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
            AccessHelper::GetProperty($cstr, 'encoding')
        );
    }

    function testConstructorWithNativeString()
    {
        $str = 'Hello, World!';
        $cstr = new CString($str);
        $this->assertSame($str, (string)$cstr);
        $this->assertSame(
            \mb_internal_encoding(),
            AccessHelper::GetProperty($cstr, 'encoding')
        );
    }

    function testConstructorWithNativeStringAndNullEncoding()
    {
        $str = 'Hello, World!';
        $cstr = new CString($str, null);
        $this->assertSame($str, (string)$cstr);
        $this->assertSame(
            \mb_internal_encoding(),
            AccessHelper::GetProperty($cstr, 'encoding')
        );
    }

    function testConstructorWithNativeStringAndSpecifiedEncoding()
    {
        $str = 'Hello, World!';
        $cstr = new CString($str, 'ISO-8859-1');
        $this->assertSame($str, (string)$cstr);
        $this->assertSame(
            'ISO-8859-1',
            AccessHelper::GetProperty($cstr, 'encoding')
        );
    }

    #[DataProvider('singleByteEncodingProvider')]
    function testConstructorWithSingleByteEncoding($encoding)
    {
        $cstr = new CString('', $encoding);
        $this->assertTrue(AccessHelper::GetProperty($cstr, 'isSingleByte'));
    }

    #[DataProvider('multiByteEncodingProvider')]
    function testConstructorWithMultiByteEncoding($encoding)
    {
        $cstr = new CString('', $encoding);
        $this->assertFalse(AccessHelper::GetProperty($cstr, 'isSingleByte'));
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

    #endregion Length

    #region First --------------------------------------------------------------

    #[DataProvider('firstDataProvider')]
    function testFirst($expected, $value, $encoding)
    {
        $cstr = new CString($value, $encoding);
        $this->assertSame($expected, $cstr->First());
    }

    #endregion First

    #region Last ---------------------------------------------------------------

    #[DataProvider('lastDataProvider')]
    function testLast($expected, $value, $encoding)
    {
        $cstr = new CString($value, $encoding);
        $this->assertSame($expected, $cstr->Last());
    }

    #endregion Last

    #region Get ----------------------------------------------------------------

    #[DataProvider('getDataProvider')]
    function testGet($expected, $value, $encoding, $offset)
    {
        $cstr = new CString($value, $encoding);
        $this->assertSame($expected, $cstr->Get($offset));
    }

    #endregion Get

    #region SetInPlace ---------------------------------------------------------

    #[DataProvider('setDataProvider')]
    function testSetInPlace($expected, $value, $encoding, $offset, $character)
    {
        $cstr = new CString($value, $encoding);
        $cstr->SetInPlace($offset, $character);
        $this->assertSame($expected, (string)$cstr);
    }

    #endregion SetInPlace

    #region InsertInPlace ------------------------------------------------------

    #[DataProvider('insertDataProvider')]
    function testInsertInPlace($expected, $value, $encoding, $offset, $substring)
    {
        $cstr = new CString($value, $encoding);
        $cstr->InsertInPlace($offset, $substring);
        $this->assertSame($expected, (string)$cstr);
    }

    function testInsertInPlaceWithStringable()
    {
        $cstr = new CString('World!');
        $stringable = new class() implements \Stringable {
            function __toString() {
                return 'Hello, ';
            }
        };
        $cstr->InsertInPlace(0, $stringable);
        $this->assertSame('Hello, World!', (string)$cstr);
    }

    #endregion InsertInPlace

    #region Prepend, PrependInPlace --------------------------------------------

    #[DataProvider('prependDataProvider')]
    function testPrepend(string $expected, string $value, string $encoding,
        string $substring)
    {
        $cstr = new CString($value, $encoding);
        $prepended = $cstr->Prepend($substring);
        $this->assertNotSame($cstr, $prepended);
        $this->assertSame($value, (string)$cstr);
        $this->assertSame($expected, (string)$prepended);
        $this->assertSame(
            AccessHelper::GetProperty($cstr, 'encoding'),
            AccessHelper::GetProperty($prepended, 'encoding'),
        );
    }

    #[DataProvider('prependDataProvider')]
    function testPrependInPlace(string $expected, string $value, string $encoding,
        string $substring)
    {
        $cstr = new CString($value, $encoding);
        $this->assertSame($cstr, $cstr->PrependInPlace($substring));
        $this->assertSame($expected, (string)$cstr);
    }

    function testPrependWithStringable()
    {
        $cstr = new CString('World!', 'ISO-8859-1');
        $stringable = new class() implements \Stringable {
            function __toString() {
                return 'Hello, ';
            }
        };
        $this->assertSame('Hello, World!',
            (string)$cstr->Prepend($stringable));
    }

    function testPrependInPlaceWithStringable()
    {
        $cstr = new CString('World!', 'ISO-8859-1');
        $stringable = new class() implements \Stringable {
            function __toString() {
                return 'Hello, ';
            }
        };
        $this->assertSame('Hello, World!',
            (string)$cstr->PrependInPlace($stringable));
    }

    #endregion Prepend, PrependInPlace

    #region Append, AppendInPlace ----------------------------------------------

    #[DataProvider('appendDataProvider')]
    function testAppend(string $expected, string $value, string $encoding,
        string $substring)
    {
        $cstr = new CString($value, $encoding);
        $appended = $cstr->Append($substring);
        $this->assertNotSame($cstr, $appended);
        $this->assertSame($value, (string)$cstr);
        $this->assertSame($expected, (string)$appended);
        $this->assertSame(
            AccessHelper::GetProperty($cstr, 'encoding'),
            AccessHelper::GetProperty($appended, 'encoding'),
        );
    }

    #[DataProvider('appendDataProvider')]
    function testAppendInPlace(string $expected, string $value, string $encoding,
        string $substring)
    {
        $cstr = new CString($value, $encoding);
        $this->assertSame($cstr, $cstr->AppendInPlace($substring));
        $this->assertSame($expected, (string)$cstr);
    }

    function testAppendWithStringable()
    {
        $cstr = new CString('Hello', 'ISO-8859-1');
        $stringable = new class() implements \Stringable {
            function __toString() {
                return ', World!';
            }
        };
        $this->assertSame('Hello, World!',
            (string)$cstr->Append($stringable));
    }

    function testAppendInPlaceWithStringable()
    {
        $cstr = new CString('Hello', 'ISO-8859-1');
        $stringable = new class() implements \Stringable {
            function __toString() {
                return ', World!';
            }
        };
        $this->assertSame('Hello, World!',
            (string)$cstr->AppendInPlace($stringable));
    }

    #endregion Append, AppendInPlace

    #region DeleteInPlace ------------------------------------------------------

    #[DataProvider('deleteDataProvider')]
    function testDeleteInPlace($expected, $value, $encoding, $offset, $count = 1)
    {
        $cstr = new CString($value, $encoding);
        $cstr->DeleteInPlace($offset, $count);
        $this->assertSame($expected, (string)$cstr);
    }

    #endregion DeleteInPlace

    #region Left ---------------------------------------------------------------

    #[DataProvider('leftDataProvider')]
    function testLeft($expected, $value, $encoding, $count)
    {
        $cstr = new CString($value, $encoding);
        $left = $cstr->Left($count);
        $this->assertSame($expected, (string)$left);
        $this->assertSame(
            AccessHelper::GetProperty($cstr, 'encoding'),
            AccessHelper::GetProperty($left, 'encoding'),
        );
    }

    #endregion Left

    #region Right --------------------------------------------------------------

    #[DataProvider('rightDataProvider')]
    function testRight($expected, $value, $encoding, $count)
    {
        $cstr = new CString($value, $encoding);
        $right = $cstr->Right($count);
        $this->assertSame($expected, (string)$right);
        $this->assertSame(
            AccessHelper::GetProperty($cstr, 'encoding'),
            AccessHelper::GetProperty($right, 'encoding'),
        );
    }

    #endregion Right

    #region Middle -------------------------------------------------------------

    #[DataProvider('middleDataProvider')]
    function testMiddle($expected, $value, $encoding, $offset, $count)
    {
        $cstr = new CString($value, $encoding);
        $middle = $cstr->Middle($offset, $count);
        $this->assertSame($expected, (string)$middle);
        $this->assertSame(
            AccessHelper::GetProperty($cstr, 'encoding'),
            AccessHelper::GetProperty($middle, 'encoding'),
        );
    }

    #endregion Middle

    #region Trim, TrimInPlace --------------------------------------------------

    #[DataProvider('trimDataProvider')]
    function testTrim(string $expected, string $value, string $encoding,
        ?string $characters = null)
    {
        $cstr = new CString($value, $encoding);
        $trimmed = $cstr->Trim($characters);
        $this->assertNotSame($cstr, $trimmed);
        $this->assertSame($value, (string)$cstr);
        $this->assertSame($expected, (string)$trimmed);
        $this->assertSame(
            AccessHelper::GetProperty($cstr, 'encoding'),
            AccessHelper::GetProperty($trimmed, 'encoding'),
        );
    }

    #[DataProvider('trimDataProvider')]
    function testTrimInPlace(string $expected, string $value, string $encoding,
        ?string $characters = null)
    {
        $cstr = new CString($value, $encoding);
        $this->assertSame($cstr, $cstr->TrimInPlace($characters));
        $this->assertSame($expected, (string)$cstr);
    }

    #endregion Trim, TrimInPlace

    #region TrimLeft, TrimLeftInPlace ------------------------------------------

    #[DataProvider('trimLeftDataProvider')]
    function testTrimLeft(string $expected, string $value, string $encoding,
        ?string $characters = null)
    {
        $cstr = new CString($value, $encoding);
        $trimmed = $cstr->TrimLeft($characters);
        $this->assertNotSame($cstr, $trimmed);
        $this->assertSame($value, (string)$cstr);
        $this->assertSame($expected, (string)$trimmed);
        $this->assertSame(
            AccessHelper::GetProperty($cstr, 'encoding'),
            AccessHelper::GetProperty($trimmed, 'encoding'),
        );
    }

    #[DataProvider('trimLeftDataProvider')]
    function testTrimLeftInPlace(string $expected, string $value, string $encoding,
        ?string $characters = null)
    {
        $cstr = new CString($value, $encoding);
        $this->assertSame($cstr, $cstr->TrimLeftInPlace($characters));
        $this->assertSame($expected, (string)$cstr);
    }

    #endregion TrimLeft, TrimLeftInPlace

    #region TrimRight, TrimRightInPlace ----------------------------------------

    #[DataProvider('trimRightDataProvider')]
    function testTrimRight(string $expected, string $value, string $encoding,
        ?string $characters = null)
    {
        $cstr = new CString($value, $encoding);
        $trimmed = $cstr->TrimRight($characters);
        $this->assertNotSame($cstr, $trimmed);
        $this->assertSame($value, (string)$cstr);
        $this->assertSame($expected, (string)$trimmed);
        $this->assertSame(
            AccessHelper::GetProperty($cstr, 'encoding'),
            AccessHelper::GetProperty($trimmed, 'encoding'),
        );
    }

    #[DataProvider('trimRightDataProvider')]
    function testTrimRightInPlace(string $expected, string $value, string $encoding,
        ?string $characters = null)
    {
        $cstr = new CString($value, $encoding);
        $this->assertSame($cstr, $cstr->TrimRightInPlace($characters));
        $this->assertSame($expected, (string)$cstr);
    }

    #endregion TrimRight, TrimRightInPlace

    #region Lowercase, LowercaseInPlace ----------------------------------------

    #[DataProvider('lowercaseDataProvider')]
    function testLowercase(string $expected, string $value, string $encoding)
    {
        $cstr = new CString($value, $encoding);
        $lowercased = $cstr->Lowercase();
        $this->assertNotSame($cstr, $lowercased);
        $this->assertSame($value, (string)$cstr);
        $this->assertSame($expected, (string)$lowercased);
        $this->assertSame(
            AccessHelper::GetProperty($cstr, 'encoding'),
            AccessHelper::GetProperty($lowercased, 'encoding'),
        );
    }

    #[DataProvider('lowercaseDataProvider')]
    function testLowercaseInPlace(string $expected, string $value, string $encoding)
    {
        $cstr = new CString($value, $encoding);
        $this->assertSame($cstr, $cstr->LowercaseInPlace());
        $this->assertSame($expected, (string)$cstr);
    }

    #endregion Lowercase, LowercaseInPlace

    #region Uppercase, UppercaseInPlace ----------------------------------------

    #[DataProvider('uppercaseDataProvider')]
    function testUppercase(string $expected, string $value, string $encoding)
    {
        $cstr = new CString($value, $encoding);
        $uppercased = $cstr->Uppercase();
        $this->assertNotSame($cstr, $uppercased);
        $this->assertSame($value, (string)$cstr);
        $this->assertSame($expected, (string)$uppercased);
        $this->assertSame(
            AccessHelper::GetProperty($cstr, 'encoding'),
            AccessHelper::GetProperty($uppercased, 'encoding'),
        );
    }

    #[DataProvider('uppercaseDataProvider')]
    function testUppercaseInPlace(string $expected, string $value, string $encoding)
    {
        $cstr = new CString($value, $encoding);
        $this->assertSame($cstr, $cstr->UppercaseInPlace());
        $this->assertSame($expected, (string)$cstr);
    }

    #endregion Uppercase, UppercaseInPlace

    #region Equals -------------------------------------------------------------

    #[DataProvider('equalsDataProvider')]
    function testEquals(bool $expected, string $value, string $encoding,
        string|CString $other, bool $caseSensitive = true)
    {
        $cstr = new CString($value, $encoding);
        $this->assertSame($expected, $cstr->Equals($other, $caseSensitive));
    }

    function testEqualsWithStringable()
    {
        $cstr = new CString('Hello');
        $other = new class() implements \Stringable {
            function __toString() {
                return 'hELLO';
            }
        };
        $this->assertFalse($cstr->Equals($other));
        $this->assertTrue($cstr->Equals($other, false));
    }

    function testEqualsWithCstring()
    {
        $cstr = new CString('Hello');
        $other = new CString('hELLO');
        $this->assertFalse($cstr->Equals($other));
        $this->assertTrue($cstr->Equals($other, false));
    }

    #endregion Equals

    #region StartsWith ---------------------------------------------------------

    #[DataProvider('startsWithDataProvider')]
    function testStartsWith(bool $expected, string $value, string $encoding,
        string $searchString, bool $caseSensitive = true)
    {
        $cstr = new CString($value, $encoding);
        $this->assertSame($expected, $cstr->StartsWith($searchString, $caseSensitive));
    }

    function testStartsWithWithStringable()
    {
        $cstr = new CString('Hello, World!');
        $searchString = new class() implements \Stringable {
            function __toString() {
                return 'hELLO';
            }
        };
        $this->assertFalse($cstr->StartsWith($searchString));
        $this->assertTrue($cstr->StartsWith($searchString, false));
    }

    function testStartsWithWithCstring()
    {
        $cstr = new CString('Hello, World!');
        $searchString = new CString('hELLO');
        $this->assertFalse($cstr->StartsWith($searchString));
        $this->assertTrue($cstr->StartsWith($searchString, false));
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

    function testEndsWithWithStringable()
    {
        $cstr = new CString('Hello, World!');
        $searchString = new class() implements \Stringable {
            function __toString() {
                return 'wORLD!';
            }
        };
        $this->assertFalse($cstr->EndsWith($searchString));
        $this->assertTrue($cstr->EndsWith($searchString, false));
    }

    function testEndsWithWithCstring()
    {
        $cstr = new CString('Hello, World!');
        $searchString = new CString('wORLD!');
        $this->assertFalse($cstr->EndsWith($searchString));
        $this->assertTrue($cstr->EndsWith($searchString, false));
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

    function testIndexOfWithStringable()
    {
        $cstr = new CString('Hello, World!');
        $searchString = new class() implements \Stringable {
            function __toString() {
                return 'wORLD!';
            }
        };
        $this->assertNull($cstr->IndexOf($searchString));
        $this->assertSame(7, $cstr->IndexOf($searchString, 0, false));
    }

    #endregion IndexOf

    #region Replace, ReplaceInPlace --------------------------------------------

    #[DataProvider('replaceDataProvider')]
    function testReplace(string $expected, string $value, string $encoding,
        string $searchString, string $replacement, bool $caseSensitive = true)
    {
        $cstr = new CString($value, $encoding);
        $replaced = $cstr->Replace($searchString, $replacement, $caseSensitive);
        $this->assertNotSame($cstr, $replaced);
        $this->assertSame($value, (string)$cstr);
        $this->assertSame($expected, (string)$replaced);
        $this->assertSame(
            AccessHelper::GetProperty($cstr, 'encoding'),
            AccessHelper::GetProperty($replaced, 'encoding'),
        );
    }

    #[DataProvider('replaceDataProvider')]
    function testReplaceInPlace(string $expected, string $value, string $encoding,
        string $searchString, string $replacement, bool $caseSensitive = true)
    {
        $cstr = new CString($value, $encoding);
        $this->assertSame($cstr, $cstr->ReplaceInPlace($searchString, $replacement,
            $caseSensitive));
        $this->assertSame($expected, (string)$cstr);
    }

    function testReplaceWithStringable()
    {
        $cstr = new CString('Hello, World!');
        $searchString = new class() implements \Stringable {
            function __toString() {
                return 'wORLD!';
            }
        };
        $replacement = new class() implements \Stringable {
            function __toString() {
                return 'Universe!';
            }
        };
        $this->assertSame('Hello, World!',
            (string)$cstr->Replace($searchString, $replacement));
        $this->assertSame('Hello, Universe!',
            (string)$cstr->Replace($searchString, $replacement, false));
    }

    function testReplaceInPlaceWithStringable()
    {
        $cstr = new CString('Hello, World!');
        $searchString = new class() implements \Stringable {
            function __toString() {
                return 'wORLD!';
            }
        };
        $replacement = new class() implements \Stringable {
            function __toString() {
                return 'Universe!';
            }
        };
        $this->assertSame('Hello, World!',
            (string)$cstr->ReplaceInPlace($searchString, $replacement));
        $this->assertSame('Hello, Universe!',
            (string)$cstr->ReplaceInPlace($searchString, $replacement, false));
    }

    function testReplaceInPlaceWithInvalidPatternUnderEncoding()
    {
        $cstr = new CString("\xC7\xCF\xB0\xA1", 'EUC-KR');
        // Suppress warning with `@`: "mb_ereg_replace(): Pattern is not valid
        // under EUC-KR encoding"
        @$cstr->ReplaceInPlace("\xC7\xCF\xB0", "\xC7\xCF");
        // Assert the value remains unchanged due to invalid pattern.
        $this->assertSame("\xC7\xCF\xB0\xA1", (string)$cstr);
    }

    #endregion Replace, ReplaceInPlace

    #region Split --------------------------------------------------------------

    #[DataProvider('splitDataProvider')]
    function testSplit(array $expected, string $value, string $encoding,
        string $delimiter, int $options = CString::SPLIT_OPTION_NONE)
    {
        $cstr = new CString($value, $encoding);
        $substrings = $cstr->Split($delimiter, $options);
        $result = [];
        foreach ($substrings as $substring) {
            $this->assertInstanceOf(CString::class, $substring);
            $result[] = (string)$substring;
        }
        $this->assertSame($expected, $result);
    }

    #endregion Split

    #region SplitToArray -------------------------------------------------------

    #[DataProvider('splitDataProvider')]
    function testSplitToArray(array $expected, string $value, string $encoding,
        string $delimiter, int $options = CString::SPLIT_OPTION_NONE)
    {
        $cstr = new CString($value, $encoding);
        $substrings = $cstr->SplitToArray($delimiter, $options);
        foreach ($substrings as $substring) {
            $this->assertInstanceOf(CString::class, $substring);
        }
        $result = array_map(fn($substring) => (string)$substring, $substrings);
        $this->assertSame($expected, $result);
    }

    #endregion SplitToArray

    #region Apply, ApplyInPlace ------------------------------------------------

    function testApplyThrowsOnNonStringReturn()
    {
        $this->expectException(\UnexpectedValueException::class);
        $this->expectExceptionMessage('Applied function must return an string.');
        $cstr = new CString('hello');
        $cstr->Apply(function(string $value) {
            return 123;
        });
    }

    function testApplyWithoutAdditionalArguments()
    {
        $cstr = new CString('hello world', 'ASCII');
        $applied = $cstr->Apply('rawurlencode');
        $this->assertNotSame($cstr, $applied);
        $this->assertSame('hello%20world', (string)$applied);
        $this->assertSame('hello world', (string)$cstr);
        $this->assertSame(
            AccessHelper::GetProperty($cstr, 'encoding'),
            AccessHelper::GetProperty($applied, 'encoding')
        );
    }

    function testApplyWithAdditionalArguments()
    {
        $cstr = new CString('hello world');
        $applied = $cstr->Apply('substr', 6, 5);
        $this->assertNotSame($cstr, $applied);
        $this->assertSame('world', (string)$applied);
        $this->assertSame('hello world', (string)$cstr);
        $this->assertSame(
            AccessHelper::GetProperty($cstr, 'encoding'),
            AccessHelper::GetProperty($applied, 'encoding')
        );
    }

    function testApplyWithLambdaWithoutAdditionalArguments()
    {
        $cstr = new CString('hello');
        $applied = $cstr->Apply(
            function(string $value) {
                return str_replace('ll', 'xx', $value);
            }
        );
        $this->assertNotSame($cstr, $applied);
        $this->assertSame('hexxo', (string)$applied);
        $this->assertSame('hello', (string)$cstr);
        $this->assertSame(
            AccessHelper::GetProperty($cstr, 'encoding'),
            AccessHelper::GetProperty($applied, 'encoding')
        );
    }

    function testApplyWithLambdaWithAdditionalArguments()
    {
        $cstr = new CString('hello');
        $applied = $cstr->Apply(
            function(string $value, string $prefix, string $suffix) {
                return $prefix . $value . $suffix;
            },
            '<',
            '>'
        );
        $this->assertNotSame($cstr, $applied);
        $this->assertSame('hello', (string)$cstr);
        $this->assertSame('<hello>', (string)$applied);
        $this->assertSame(
            AccessHelper::GetProperty($cstr, 'encoding'),
            AccessHelper::GetProperty($applied, 'encoding')
        );
    }

    function testApplyInPlaceThrowsOnNonStringReturn()
    {
        $this->expectException(\UnexpectedValueException::class);
        $this->expectExceptionMessage('Applied function must return an string.');
        $cstr = new CString('hello');
        $cstr->ApplyInPlace(function(string $value) {
            return 456;
        });
    }

    function testApplyInPlaceWithoutAdditionalArguments()
    {
        $cstr = new CString('hello world');
        $this->assertSame($cstr, $cstr->ApplyInPlace('rawurlencode'));
        $this->assertSame('hello%20world', (string)$cstr);
    }

    function testApplyInPlaceWithAdditionalArguments()
    {
        $cstr = new CString('hello world');
        $this->assertSame($cstr, $cstr->ApplyInPlace('substr', 6, 5));
        $this->assertSame('world', (string)$cstr);
    }

    function testApplyInPlaceWithLambdaWithoutAdditionalArguments()
    {
        $cstr = new CString('hello');
        $this->assertSame(
            $cstr,
            $cstr->ApplyInPlace(
                function(string $value) {
                    return str_replace('ll', 'xx', $value);
                }
            )
        );
        $this->assertSame('hexxo', (string)$cstr);
    }

    function testApplyInPlaceWithLambdaWithAdditionalArguments()
    {
        $cstr = new CString('hello');
        $this->assertSame(
            $cstr,
            $cstr->ApplyInPlace(
                function(string $value, string $prefix, string $suffix) {
                    return $prefix . $value . $suffix;
                },
                '<',
                '>'
            )
        );
        $this->assertSame('<hello>', (string)$cstr);
    }

    #endregion Apply, ApplyInPlace

    #region Match --------------------------------------------------------------

    #[DataProvider('matchDataProvider')]
    function testMatch(
        ?array $expected,
        string $value,
        string $encoding,
        string $pattern,
        int $options = CString::REGEX_OPTION_NONE,
        string $delimiter = '/'
    ) {
        $cstr = new CString($value, $encoding);
        $matches = $cstr->Match($pattern, $options, $delimiter);
        if ($expected === null) {
            $this->assertNull($matches);
        } else {
            $this->assertIsArray($matches);
            $this->assertSame($expected, $matches);
        }
    }

    #endregion Match

    #region Interface: Stringable ----------------------------------------------

    function testToString()
    {
        $str = 'Hello, World!';
        $cstr = new CString($str);
        $this->assertSame($str, (string)$cstr);
    }

    #endregion Interface: Stringable

    #region Interface: ArrayAccess ---------------------------------------------

    #[DataProviderExternal(DataHelper::class, 'NonIntegerProvider')]
    function testOffsetExistsWithInvalidOffsetType($offset)
    {
        $cstr = new CString('Hello', 'ISO-8859-1');
        $this->expectException(\InvalidArgumentException::class);
        isset($cstr[$offset]);
    }

    #[DataProvider('offsetExistsDataProvider')]
    function testOffsetExists(bool $expected, string $value, string $encoding,
        int $offset)
    {
        $cstr = new CString($value, $encoding);
        $this->assertSame($expected, isset($cstr[$offset]));
    }

    function testOffsetGet()
    {
        $cstr = $this->getMockBuilder(CString::class)
            ->onlyMethods(['Get'])
            ->getMock();
        $cstr->expects($this->once())
            ->method('Get')
            ->with(1)
            ->willReturn('e');
        $this->assertSame('e', $cstr[1]);
    }

    function testOffsetSet()
    {
        $cstr = $this->getMockBuilder(CString::class)
            ->onlyMethods(['SetInPlace'])
            ->getMock();
        $cstr->expects($this->once())
            ->method('SetInPlace')
            ->with(1, 'a');
        $cstr[1] = 'a';
    }

    function testOffsetUnset()
    {
        $cstr = $this->getMockBuilder(CString::class)
            ->onlyMethods(['DeleteInPlace'])
            ->getMock();
        $cstr->expects($this->once())
            ->method('DeleteInPlace')
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
        $this->assertSame(['H', 'e', 'l', 'l', 'o'], $result);
    }

    function testGetIteratorForMultiByteEncoding()
    {
        $cstr = new CString('こんにちは', 'UTF-8');
        $result = [];
        foreach ($cstr as $char) {
            $result[] = $char;
        }
        $this->assertSame(['こ', 'ん', 'に', 'ち', 'は'], $result);
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

    static function getDataProvider()
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

    static function setDataProvider()
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

    static function insertDataProvider()
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

    static function prependDataProvider()
    {
        return [
        // Single-byte
            ['Hello, World!', 'World!', 'ISO-8859-1', 'Hello, '],
            ['Hello', 'Hello', 'ISO-8859-1', ''],
            ['World!', '', 'ISO-8859-1', 'World!'],
        // Multibyte
            ['こんにちは世界', '世界', 'UTF-8', 'こんにちは'],
            ['こんにちは', 'こんにちは', 'UTF-8', ''],
            ['世界', '', 'UTF-8', '世界'],
        ];
    }

    static function appendDataProvider()
    {
        return [
        // Single-byte
            ['Hello World!', 'Hello', 'ISO-8859-1', ' World!'],
            ['Hello', 'Hello', 'ISO-8859-1', ''],
            ['World!', '', 'ISO-8859-1', 'World!'],
        // Multibyte
            ['こんにちは世界', 'こんにちは', 'UTF-8', '世界'],
            ['こんにちは', 'こんにちは', 'UTF-8', ''],
            ['世界', '', 'UTF-8', '世界'],
        ];
    }

    static function deleteDataProvider()
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
        // Multibyte Extras
            'exact match replace (BIG-5)' => [
                "\xA4\xA4\xA4\xE5\xAC\xDB", // "教育部" ("Jiao Yu Bu" - "Ministry of Education")
                "\xA4\xA4\xA4\xE5\xA4\xEB", // "教育局" ("Jiao Yu Ju" - "Education Bureau")
                'BIG-5',
                "\xA4\xEB", // "局" ("Ju" - "Bureau")
                "\xAC\xDB", // "部" ("Bu" - "Ministry")
            ],
            'exact match replace (CP932)' => [
                "\x93\xFA\x96\x7B\x8C\xEA", // "東京大学" ("Tokyo Daigaku" - "University of Tokyo")
                "\x93\xFA\x96\x7B\x89\xE3", // "東京京都" ("Tokyo Kyouto" - "Tokyo Kyoto")
                'CP932',
                "\x89\xE3", // "京都" ("Kyouto" - "Kyoto")
                "\x8C\xEA", // "大学" ("Daigaku" - "University")
            ],
            'exact match replace (EUC-KR)' => [
                "\xC7\xCF\xB1\xD7", // "한국" ("Hanguk" - "Korea")
                "\xC7\xCF\xB0\xA1", // "하가" ("Ha-ga")
                'EUC-KR',
                "\xB0\xA1", // "가" ("Ga")
                "\xB1\xD7", // "국" ("Guk" - "Country")
            ],
        ];
    }

    static function splitDataProvider()
    {
        return [
            'single-byte, comma-separated, basic case' => [
                ['foo', 'bar', 'baz'],
                'foo,bar,baz',
                'ASCII',
                ','
            ],
            'single-byte, comma-separated, with leading and trailing delimiter' => [
                ['', 'foo', 'bar', 'baz', ''],
                ',foo,bar,baz,',
                'ASCII',
                ','
            ],
            'single-byte, comma-separated, with leading and trailing delimiter, exclude empty' => [
                ['foo', 'bar', 'baz'],
                ',foo,bar,baz,',
                'ASCII',
                ',',
                CString::SPLIT_OPTION_EXCLUDE_EMPTY
            ],
            'single-byte, comma-separated, with spaces' => [
                ['foo', ' bar', ' baz'],
                'foo, bar, baz',
                'ASCII',
                ','
            ],
            'single-byte, comma-separated, with spaces, trim' => [
                ['foo', 'bar', 'baz'],
                'foo, bar, baz',
                'ASCII',
                ',',
                CString::SPLIT_OPTION_TRIM
            ],
            'single-byte, comma-separated, with empty' => [
                ['', '', 'foo', '', 'bar', '', 'baz', '', ''],
                ',,foo,,bar,,baz,,',
                'ASCII',
                ','
            ],
            'single-byte, comma-separated, with empty, exclude empty' => [
                ['foo', 'bar', 'baz'],
                ',,foo,,bar,,baz,,',
                'ASCII',
                ',',
                CString::SPLIT_OPTION_EXCLUDE_EMPTY
            ],
            'single-byte, comma-separated, with spaces and empty' => [
                [' ', ' ', ' foo ', ' ', ' bar ', ' ', ' baz ', ' ', ' '],
                ' , , foo , , bar , , baz , , ',
                'ASCII',
                ','
            ],
            'single-byte, comma-separated, with spaces and empty, trim' => [
                ['', '', 'foo', '', 'bar', '', 'baz', '', ''],
                ' , , foo , , bar , , baz , , ',
                'ASCII',
                ',',
                CString::SPLIT_OPTION_TRIM
            ],
            'single-byte, comma-separated, with spaces and empty, exclude empty' => [
                [' ', ' ', ' foo ', ' ', ' bar ', ' ', ' baz ', ' ', ' '],
                ' , , foo , , bar , , baz , , ',
                'ASCII',
                ',',
                CString::SPLIT_OPTION_EXCLUDE_EMPTY
            ],
            'single-byte, comma-separated, with spaces and empty, trim and exclude empty' => [
                ['foo', 'bar', 'baz'],
                ' , , foo , , bar , , baz , , ',
                'ASCII',
                ',',
                CString::SPLIT_OPTION_TRIM | CString::SPLIT_OPTION_EXCLUDE_EMPTY
            ],
            'single-byte, lorem ipsum' => [
                ['', '', 'Orci', 'varius', '', 'natoque', '', '', 'penatibus', '', '', '', ''],
                '  Orci varius  natoque   penatibus    ',
                'ASCII',
                ' '
            ],
            'single-byte, lorem ipsum, trim' => [
                ['', '', 'Orci', 'varius', '', 'natoque', '', '', 'penatibus', '', '', '', ''],
                '  Orci varius  natoque   penatibus    ',
                'ASCII',
                ' ',
                CString::SPLIT_OPTION_TRIM
            ],
            'single-byte, lorem ipsum, exclude empty' => [
                ['Orci', 'varius', 'natoque', 'penatibus'],
                '  Orci varius  natoque   penatibus    ',
                'ASCII',
                ' ',
                CString::SPLIT_OPTION_EXCLUDE_EMPTY
            ],
            'single-byte, lorem ipsum, trim and exclude empty' => [
                ['Orci', 'varius', 'natoque', 'penatibus'],
                '  Orci varius  natoque   penatibus    ',
                'ASCII',
                ' ',
                CString::SPLIT_OPTION_TRIM | CString::SPLIT_OPTION_EXCLUDE_EMPTY
            ],
            'single-byte, http header' => [
                ['application/json', ' charset=utf-8'],
                'application/json; charset=utf-8',
                'ASCII',
                ';'
            ],
            'single-byte, http header, trim' => [
                ['application/json', 'charset=utf-8'],
                'application/json; charset=utf-8',
                'ASCII',
                ';',
                CString::SPLIT_OPTION_TRIM
            ],
            'single-byte, etc passwd' => [
                ['foo', '*', '1023', '1000', '', '/home/foo', '/bin/sh'],
                'foo:*:1023:1000::/home/foo:/bin/sh',
                'ASCII',
                ':'
            ],
            'single-byte, etc passwd, exclude empty' => [
                ['foo', '*', '1023', '1000', '/home/foo', '/bin/sh'],
                'foo:*:1023:1000::/home/foo:/bin/sh',
                'ASCII',
                ':',
                CString::SPLIT_OPTION_EXCLUDE_EMPTY
            ],
            'single-byte, empty delimiter' => [
                [],
                'sample text',
                'ASCII',
                ''
            ],
            'single-byte, empty delimiter, trim' => [
                [],
                'sample text',
                'ASCII',
                '',
                CString::SPLIT_OPTION_TRIM
            ],
            'single-byte, empty delimiter, exclude empty' => [
                [],
                'sample text',
                'ASCII',
                '',
                CString::SPLIT_OPTION_EXCLUDE_EMPTY
            ],
            'single-byte, empty delimiter, trim and exclude empty' => [
                [],
                'sample text',
                'ASCII',
                '',
                CString::SPLIT_OPTION_TRIM | CString::SPLIT_OPTION_EXCLUDE_EMPTY
            ],
            'multibyte, mixed languages, comma-separated, basic case' => [
                ['Hello', '伝統', 'Résumé'],
                'Hello,伝統,Résumé',
                'UTF-8',
                ','
            ],
            'multibyte, turkish, comma-separated, basic case' => [
                ['çayı', 'balığı', 'öğrenmek', 'üşümek', 'şüphe', 'ızgara', 'ölmek', 'ığdır', 'göğüs'],
                'çayı,balığı,öğrenmek,üşümek,şüphe,ızgara,ölmek,ığdır,göğüs',
                'UTF-8',
                ','
            ],
            'multibyte, turkish, comma-separated, with spaces, trim' => [
                ['çayı', 'balığı', 'öğrenmek', 'üşümek', 'şüphe', 'ızgara', 'ölmek', 'ığdır', 'göğüs'],
                'çayı, balığı, öğrenmek, üşümek, şüphe, ızgara, ölmek, ığdır, göğüs',
                'UTF-8',
                ',',
                CString::SPLIT_OPTION_TRIM
            ],
            'multibyte, turkish, comma-separated, with leading and trailing delimiter, exclude empty' => [
                ['çayı', 'balığı', 'öğrenmek'],
                ',çayı,balığı,öğrenmek,',
                'UTF-8',
                ',',
                CString::SPLIT_OPTION_EXCLUDE_EMPTY
            ],
            'multibyte, japanese, comma-separated, basic case' => [
                ['こんにちは', 'すし', '魚', '例', 'ありがとう', '試験', '物語', '伝統'],
                'こんにちは、すし、魚、例、ありがとう、試験、物語、伝統',
                'UTF-8',
                '、'
            ],
            'multibyte, japanese, comma-separated, with spaces, trim' => [
                ['こんにちは', 'すし', '魚', '例', 'ありがとう', '試験', '物語', '伝統'],
                'こんにちは 、 すし 、 魚 、 例 、 ありがとう 、 試験 、 物語 、 伝統 ',
                'UTF-8',
                '、',
                CString::SPLIT_OPTION_TRIM
            ],
            'multibyte, japanese, comma-separated, with leading and trailing delimiter, exclude empty' => [
                ['伝統', '文化', '試験'],
                '、伝統、文化、試験、',
                'UTF-8',
                '、',
                CString::SPLIT_OPTION_EXCLUDE_EMPTY
            ],
            'multibyte, japanese, space-delimited, trim' => [
                ['ありがとう', '文化', '試験', '例'],
                'ありがとう　文化　試験　例',
                'UTF-8',
                '　',
                CString::SPLIT_OPTION_TRIM
            ],
            'multibyte, japanese, lorem ipsum' => [
                ['伝統', '文化', '芸術', 'ありがとう', '食べ物', 'すし', '魚', '日本'],
                ' 伝統 文化 芸術 ありがとう 食べ物 すし 魚 日本 ',
                'UTF-8',
                ' ',
                CString::SPLIT_OPTION_TRIM | CString::SPLIT_OPTION_EXCLUDE_EMPTY
            ],
            'multibyte, japanese, lorem ipsum, trim and exclude empty' => [
                ['伝統', '文化', '芸術', 'ありがとう', '食べ物', 'すし', '魚', '日本'],
                '  伝統  文化   芸術    ありがとう   食べ物    すし    魚   日本 ',
                'UTF-8',
                ' ',
                CString::SPLIT_OPTION_TRIM | CString::SPLIT_OPTION_EXCLUDE_EMPTY
            ],
        ];
    }

    static function matchDataProvider()
    {
        return [
            'no match, singlebyte' => [
                null, 'Hello, World!', 'ASCII', '\d+'
            ],
            'no match, multibyte' => [
                null, 'Hello, World!', 'UTF-8', '\d+'
            ],
            'empty string, singlebyte' => [
                null, '', 'ASCII', '\w+'
            ],
            'empty string, multibyte'  => [
                null, '', 'UTF-8', '\w+'
            ],
            'empty match, singlebyte' => [
                [''], '', 'ASCII', '^$'
            ],
            'empty match, multibyte'  => [
                [false], '', 'UTF-8', '^$'
            ],
            'basic match, singlebyte' => [
                ['Hello'], 'Hello, World!', 'ASCII', '\w+'
            ],
            'basic match, multibyte'  => [
                ['Hello'], 'Hello, World!', 'UTF-8', '\w+'
            ],
            'case insensitive, singlebyte' => [
                ['World'], 'Hello, World!', 'ASCII', 'world', CString::REGEX_OPTION_CASE_INSENSITIVE
            ],
            'case insensitive, multibyte'  => [
                ['World'], 'Hello, World!', 'UTF-8', 'world', CString::REGEX_OPTION_CASE_INSENSITIVE
            ],
            'multiline, singlebyte' => [
                ['W'], "Hello\nWorld!", 'ASCII', '^W', CString::REGEX_OPTION_MULTILINE
            ],
            'multiline, multibyte' => [
                ['W'], "Hello\nWorld!", 'UTF-8', '^W', CString::REGEX_OPTION_MULTILINE
            ],
            'capture groups, singlebyte' => [
                ['2024-06-15', '2024', '06', '15'],
                'Today is 2024-06-15.',
                'ASCII',
                '(\d{4})-(\d{2})-(\d{2})'
            ],
            'capture groups, multibyte' => [
                ['2024-06-15', '2024', '06', '15'],
                'Today is 2024-06-15.',
                'UTF-8',
                '(\d{4})-(\d{2})-(\d{2})'
            ],
            'custom delimiter, singlebyte' => [
                ['/path/to/file/'],
                '/path/to/file/',
                'ASCII',
                '/path/to/file/',
                CString::REGEX_OPTION_NONE,
                '#'
            ],
            'custom delimiter, multibyte' => [
                ['/path/to/file/'],
                '/path/to/file/',
                'UTF-8',
                '/path/to/file/',
                CString::REGEX_OPTION_NONE,
                '#' // Though ignored in multibyte, it should still work
            ],
        ];
    }

    static function offsetExistsDataProvider()
    {
        return [
        // Single-byte
            [false, 'Hello', 'ISO-8859-1', -1],
            [true, 'Hello', 'ISO-8859-1', 0],
            [true, 'Hello', 'ISO-8859-1', 1],
            [true, 'Hello', 'ISO-8859-1', 4],
            [false, 'Hello', 'ISO-8859-1', 5],
        // Multibyte
            [false, 'こんにちは', 'UTF-8', -1],
            [true, 'こんにちは', 'UTF-8', 0],
            [true, 'こんにちは', 'UTF-8', 1],
            [true, 'こんにちは', 'UTF-8', 4],
            [false, 'こんにちは', 'UTF-8', 5],
        ];
    }

    #endregion Data Providers
}
