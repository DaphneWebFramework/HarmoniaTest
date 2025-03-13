<?php declare(strict_types=1);
use \PHPUnit\Framework\TestCase;
use \PHPUnit\Framework\Attributes\CoversClass;
use \PHPUnit\Framework\Attributes\DataProvider;

use \Harmonia\Validation\Rules\FileRule;

use \Harmonia\Config;
use \Harmonia\Validation\NativeFunctions;
use \TestToolkit\AccessHelper;

#[CoversClass(FileRule::class)]
class FileRuleTest extends TestCase
{
    private ?Config $originalConfig = null;

    protected function setUp(): void
    {
        $this->originalConfig = Config::ReplaceInstance($this->config());
    }

    protected function tearDown(): void
    {
        Config::ReplaceInstance($this->originalConfig);
    }

    private function config()
    {
        $mock = $this->createMock(Config::class);
        $mock->method('Option')->with('Language')->willReturn('en');
        return $mock;
    }

    private function systemUnderTest(): FileRule
    {
        return new FileRule($this->createMock(NativeFunctions::class));
    }

    #region Validate -----------------------------------------------------------

    function testValidateSucceedsWhenValueIsUploadedFile()
    {
        $sut = $this->systemUnderTest();
        $nativeFunctions = AccessHelper::GetProperty($sut, 'nativeFunctions');

        $nativeFunctions->expects($this->once())
            ->method('IsUploadedFile')
            ->with(['name' => 'file.txt', 'tmp_name' => '/tmp/php123.tmp'])
            ->willReturn(true);

        $sut->Validate(
            'field1',
            ['name' => 'file.txt', 'tmp_name' => '/tmp/php123.tmp'],
            null
        );
    }

    #[DataProvider('uploadErrorDataProvider')]
    function testValidateThrowsWhenUploadErrorOccurs($uploadError, $exceptionMessage)
    {
        $sut = $this->systemUnderTest();
        $nativeFunctions = AccessHelper::GetProperty($sut, 'nativeFunctions');

        $nativeFunctions->expects($this->once())
            ->method('IsUploadedFile')
            ->with(['error' => $uploadError])
            ->willReturn(false);

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage($exceptionMessage);
        $sut->Validate('field1', ['error' => $uploadError], null);
    }

    function testValidateThrowsWhenValueIsNotUploadedFile()
    {
        $sut = $this->systemUnderTest();
        $nativeFunctions = AccessHelper::GetProperty($sut, 'nativeFunctions');

        $nativeFunctions->expects($this->once())
            ->method('IsUploadedFile')
            ->with('not-a-file')
            ->willReturn(false);

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage("Field 'field1' must be a file.");
        $sut->Validate('field1', 'not-a-file', null);
    }

    #endregion Validate

    #region Data Providers -----------------------------------------------------

    static function uploadErrorDataProvider()
    {
        return [
            [\UPLOAD_ERR_INI_SIZE, 'The uploaded file exceeds the upload_max_filesize directive in php.ini.'],
            [\UPLOAD_ERR_FORM_SIZE, 'The uploaded file exceeds the MAX_FILE_SIZE directive that was specified in the HTML form.'],
            [\UPLOAD_ERR_PARTIAL, 'The uploaded file was only partially uploaded.'],
            [\UPLOAD_ERR_NO_FILE, 'No file was uploaded.'],
            [\UPLOAD_ERR_NO_TMP_DIR, 'Missing a temporary folder.'],
            [\UPLOAD_ERR_CANT_WRITE, 'Failed to write file to disk.'],
            [\UPLOAD_ERR_EXTENSION, 'A PHP extension stopped the file upload.'],
            [999, 'Unknown upload error: 999'],
        ];
    }

    #endregion Data Providers
}
