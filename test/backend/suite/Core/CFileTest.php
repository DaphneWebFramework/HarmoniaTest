<?php declare(strict_types=1);
use \PHPUnit\Framework\TestCase;
use \PHPUnit\Framework\Attributes\CoversClass;

use \Harmonia\Core\CFile;

use \TestToolkit\AccessHelper as ah;

#[CoversClass(CFile::class)]
class CFileTest extends TestCase
{
    const FILENAME = 'test.txt';

    protected function tearDown(): void
    {
        if (\file_exists(self::FILENAME)) {
            \unlink(self::FILENAME);
        }
    }

    private function systemUnderTest(string ...$mockedMethods): CFile
    {
        return $this->getMockBuilder(CFile::class)
            ->disableOriginalConstructor()
            ->onlyMethods($mockedMethods)
            ->getMock();
    }

    #region Open ---------------------------------------------------------------

    function testOpenWithNonExistingFile()
    {
        $this->assertNull(CFile::Open('non_existing_file.txt'));
    }

    function testOpenWithModeRead()
    {
        $bytes = 'Hello World';
        \file_put_contents(self::FILENAME, $bytes);
        $file = CFile::Open(self::FILENAME, CFile::MODE_READ);
        $this->assertInstanceOf(CFile::class, $file);
        $this->assertSame($bytes, $file->Read());
        $file->Close();
    }

    function testOpenWithModeWrite()
    {
        $bytes = 'Written';
        $file = CFile::Open(self::FILENAME, CFile::MODE_WRITE);
        $this->assertInstanceOf(CFile::class, $file);
        $file->Write($bytes);
        $file->Close();
        $this->assertStringEqualsFile(self::FILENAME, $bytes);
    }

    function testOpenWithModeReadWrite()
    {
        $initialContent = 'Initial Content';
        $additionalContent = ' and More';
        $file = CFile::Open(self::FILENAME, CFile::MODE_READWRITE);
        $this->assertInstanceOf(CFile::class, $file);
        $file->Write($initialContent);
        $this->assertStringEqualsFile(self::FILENAME, $initialContent);
        $file->Write($additionalContent);
        $this->assertStringEqualsFile(self::FILENAME, $initialContent . $additionalContent);
        $file->Close();
    }

    function testOpenWithModeAppend()
    {
        \file_put_contents(self::FILENAME, 'Hello');
        $file = CFile::Open(self::FILENAME, CFile::MODE_APPEND);
        $this->assertInstanceOf(CFile::class, $file);
        $file->Write(' World');
        $file->Close();
        $this->assertStringEqualsFile(self::FILENAME, 'Hello World');
    }

    function testOpenWithStringableFilename()
    {
        $bytes = 'Hello World';
        \file_put_contents(self::FILENAME, $bytes);
        $file = CFile::Open(new class {
            public function __toString() {
                return CFileTest::FILENAME;
            }
        }, CFile::MODE_READ);
        $this->assertInstanceOf(CFile::class, $file);
        $this->assertSame($bytes, $file->Read());
        $file->Close();
    }

    #endregion Open

    #region Close --------------------------------------------------------------

    function testCloseReleasesHandle()
    {
        $file = CFile::Open(self::FILENAME, CFile::MODE_WRITE);
        $file->Close();
        $this->assertNull(ah::GetProperty($file, 'handle'));
    }

    function testCloseIsIdempotent()
    {
        $file = CFile::Open(self::FILENAME, CFile::MODE_WRITE);
        $file->Close();
        $file->Close(); // Calling Close a second time should have no effect
        $this->assertNull(ah::GetProperty($file, 'handle'));
    }

    #endregion Close

    #region Read ---------------------------------------------------------------

    function testReadFullContent()
    {
        $content = 'Full Content';
        \file_put_contents(self::FILENAME, $content);
        $file = CFile::Open(self::FILENAME, CFile::MODE_READ);
        $this->assertSame($content, $file->Read());
        $file->Close();
    }

    function testReadPartialContent()
    {
        $content = 'Partial Content';
        \file_put_contents(self::FILENAME, $content);
        $file = CFile::Open(self::FILENAME, CFile::MODE_READ);
        $this->assertSame(substr($content, 0, 7), $file->Read(7)); // "Partial"
        $file->Close();
    }

    function testReadWhenCursorReturnsNull()
    {
        $sut = $this->systemUnderTest('Cursor', '_flock');
        $sut->method('_flock')->willReturn(true);
        $sut->method('Cursor')->willReturn(null);
        $this->assertNull($sut->Read());
    }

    function testReadWhenSizeEqualsCursor()
    {
        $sut = $this->systemUnderTest('Cursor', 'Size', '_flock');
        $sut->method('_flock')->willReturn(true);
        $sut->method('Cursor')->willReturn(100);
        $sut->method('Size')->willReturn(100);
        $this->assertSame('', $sut->Read());
    }

    function testReadWhenSizeIsLessThanCursor()
    {
        $sut = $this->systemUnderTest('Cursor', 'Size', '_flock');
        $sut->method('_flock')->willReturn(true);
        $sut->method('Cursor')->willReturn(100);
        $sut->method('Size')->willReturn(50);
        $this->assertNull($sut->Read());
    }

    function testReadWhenFreadFails()
    {
        $sut = $this->systemUnderTest('_fread', '_flock');
        $sut->method('_flock')->willReturn(true);
        $sut->method('_fread')->willReturn(false);
        $this->assertNull($sut->Read(10));
    }

    function testReadWhenFlockFails()
    {
        $sut = $this->systemUnderTest('_flock');
        $sut->method('_flock')->willReturn(false);
        $this->assertNull($sut->Read());
    }

    function testReadWithNegativeLength()
    {
        $content = 'Test Content';
        \file_put_contents(self::FILENAME, $content);
        $file = CFile::Open(self::FILENAME, CFile::MODE_READ);
        $this->assertNull($file->Read(-5));
        $file->Close();
    }

    function testReadWithZeroLength()
    {
        $content = 'Test Content';
        \file_put_contents(self::FILENAME, $content);
        $file = CFile::Open(self::FILENAME, CFile::MODE_READ);
        $this->assertSame('', $file->Read(0));
        $file->Close();
    }

    #endregion Read

    #region ReadLine -----------------------------------------------------------

    function testReadLine()
    {
        $content = "First Line\nSecond Line";
        \file_put_contents(self::FILENAME, $content);
        $file = CFile::Open(self::FILENAME, CFile::MODE_READ);
        $this->assertSame('First Line', $file->ReadLine());
        $this->assertSame('Second Line', $file->ReadLine());
        $file->Close();
    }

    function testReadLineWhenFgetsFails()
    {
        $sut = $this->systemUnderTest('_fgets', '_flock');
        $sut->method('_flock')->willReturn(true);
        $sut->method('_fgets')->willReturn(false);
        $this->assertNull($sut->ReadLine());
    }

    function testReadLineWhenFlockFails()
    {
        $sut = $this->systemUnderTest('_flock');
        $sut->method('_flock')->willReturn(false);
        $this->assertNull($sut->ReadLine());
    }

    #endregion ReadLine

    #region Write --------------------------------------------------------------

    function testWrite()
    {
        $file = CFile::Open(self::FILENAME, CFile::MODE_WRITE);
        $this->assertTrue($file->Write('Hello'));
        $file->Close();
        $file = CFile::Open(self::FILENAME, CFile::MODE_READ);
        $this->assertSame('Hello', $file->Read());
        $file->Close();
    }

    function testWriteWhenFwriteFails()
    {
        $sut = $this->systemUnderTest('_fwrite', '_flock');
        $sut->method('_flock')->willReturn(true);
        $sut->method('_fwrite')->willReturn(false);
        $this->assertFalse($sut->Write('Should fail'));
    }

    function testWriteWhenFlockFails()
    {
        $sut = $this->systemUnderTest('_flock');
        $sut->method('_flock')->willReturn(false);
        $this->assertFalse($sut->Write('Should fail'));
    }

    #endregion Write

    #region WriteLine ----------------------------------------------------------

    function testWriteLine()
    {
        $file = CFile::Open(self::FILENAME, CFile::MODE_WRITE);
        $this->assertTrue($file->WriteLine('Hello'));
        $this->assertTrue($file->WriteLine('World'));
        $file->Close();
        $this->assertStringEqualsFile(self::FILENAME, "Hello\nWorld\n");
    }

    function testWriteLineWhenFwriteFails()
    {
        $sut = $this->systemUnderTest('_fwrite', '_flock');
        $sut->method('_flock')->willReturn(true);
        $sut->method('_fwrite')->willReturn(false);
        $this->assertFalse($sut->WriteLine('Should fail'));
    }

    function testWriteLineWhenFlockFails()
    {
        $sut = $this->systemUnderTest('_flock');
        $sut->method('_flock')->willReturn(false);
        $this->assertFalse($sut->WriteLine('Should fail'));
    }

    #endregion WriteLine

    #region Size ---------------------------------------------------------------

    function testSize()
    {
        $content = 'Test Size Content';

        // Test with MODE_WRITE
        $file = CFile::Open(self::FILENAME, CFile::MODE_WRITE);
        $this->assertTrue($file->Write($content));
        $this->assertSame(\strlen($content), $file->Size());
        $file->Close();

        // Test with MODE_READ
        $file = CFile::Open(self::FILENAME, CFile::MODE_READ);
        $this->assertSame(\strlen($content), $file->Size());
        $file->Close();

        // Test with MODE_READWRITE
        $file = CFile::Open(self::FILENAME, CFile::MODE_READWRITE);
        $this->assertSame(\strlen($content), $file->Size());
        $file->Close();

        // Test with MODE_APPEND
        $file = CFile::Open(self::FILENAME, CFile::MODE_APPEND);
        $this->assertSame(\strlen($content), $file->Size());
        $file->Close();
    }

    function testSizeOnEmptyFile()
    {
        // Test with MODE_WRITE
        $file = CFile::Open(self::FILENAME, CFile::MODE_WRITE);
        $this->assertSame(0, $file->Size());
        $file->Close();

        // Test with MODE_READ
        $file = CFile::Open(self::FILENAME, CFile::MODE_READ);
        $this->assertSame(0, $file->Size());
        $file->Close();

        // Test with MODE_READWRITE
        $file = CFile::Open(self::FILENAME, CFile::MODE_READWRITE);
        $this->assertSame(0, $file->Size());
        $file->Close();

        // Test with MODE_APPEND
        $file = CFile::Open(self::FILENAME, CFile::MODE_APPEND);
        $this->assertSame(0, $file->Size());
        $file->Close();
    }

    function testSizeWhenFstatFails()
    {
        $sut = $this->systemUnderTest('_fstat');
        $sut->method('_fstat')->willReturn(false);
        $this->assertSame(0, $sut->Size());
    }

    #endregion Size

    #region Cursor -------------------------------------------------------------

    function testCursor()
    {
        $content = 'Hello Cursor';
        \file_put_contents(self::FILENAME, $content);
        $file = CFile::Open(self::FILENAME, CFile::MODE_READ);
        $file->Read(6); // Read "Hello "
        $this->assertSame(6, $file->Cursor());
        $file->Close();
    }

    function testCursorWhenFtellFails()
    {
        $sut = $this->systemUnderTest('_ftell');
        $sut->method('_ftell')->willReturn(false);
        $this->assertNull($sut->Cursor());
    }

    #endregion Cursor

    #region SetCursor ----------------------------------------------------------

    function testSetCursorFromBeginning()
    {
        $content = 'Hello SetCursor';
        \file_put_contents(self::FILENAME, $content);
        $file = CFile::Open(self::FILENAME, CFile::MODE_READ);
        $file->SetCursor(6, CFile::ORIGIN_BEGIN);
        $this->assertSame('SetCursor', $file->Read());
        $file->Close();
    }

    function testSetCursorFromCurrentPosition()
    {
        $content = 'Hello SetCursor';
        \file_put_contents(self::FILENAME, $content);
        $file = CFile::Open(self::FILENAME, CFile::MODE_READ);
        $file->SetCursor(6, CFile::ORIGIN_BEGIN);
        $this->assertSame('Set', $file->Read(3));
        $file->SetCursor(3, CFile::ORIGIN_CURRENT);
        $this->assertSame('sor', $file->Read());
        $file->Close();
    }

    function testSetCursorFromEnd()
    {
        $content = 'Hello SetCursor';
        \file_put_contents(self::FILENAME, $content);
        $file = CFile::Open(self::FILENAME, CFile::MODE_READ);
        $file->SetCursor(-6, CFile::ORIGIN_END);
        $this->assertSame('Cursor', $file->Read());
        $file->Close();
    }

    function testSetCursorWhenFseekFails()
    {
        $sut = $this->systemUnderTest('_fseek');
        $sut->method('_fseek')->willReturn(-1);
        $this->assertFalse($sut->SetCursor(0, CFile::ORIGIN_BEGIN));
    }

    #endregion SetCursor

    #region WithReadLock -------------------------------------------------------

    function testWithReadLock()
    {
        $sut = $this->systemUnderTest('withLock');

        $sut->expects($this->once())
            ->method('withLock')
            ->with(\LOCK_SH, $this->isType('callable'))
            ->willReturn('Expected result');

        $this->assertSame(
            'Expected result',
            $sut->WithReadLock(function() { return 'Expected result'; })
        );
    }

    #endregion WithReadLock

    #region WithWriteLock ------------------------------------------------------

    function testWithWriteLock()
    {
        $sut = $this->systemUnderTest('withLock');

        $sut->expects($this->once())
            ->method('withLock')
            ->with(\LOCK_EX, $this->isType('callable'))
            ->willReturn('Expected result');

        $this->assertSame(
            'Expected result',
            $sut->WithWriteLock(function() { return 'Expected result'; })
        );
    }

    #endregion WithWriteLock
}
