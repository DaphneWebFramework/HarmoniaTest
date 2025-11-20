<?php declare(strict_types=1);
use \PHPUnit\Framework\TestCase;
use \PHPUnit\Framework\Attributes\CoversClass;

use \Harmonia\Http\Client;

use \TestToolkit\AccessHelper as ah;

#[CoversClass(Client::class)]
class ClientTest extends TestCase
{
    private ?\CurlHandle $curl = null;

    protected function setUp(): void
    {
        // Since CurlHandle is declared as "final", we cannot mock it.
        // It also cannot be constructed, so we must use curl_init().
        $this->curl = \curl_init();
    }

    protected function tearDown(): void
    {
        if ($this->curl instanceof \CurlHandle) {
            if (\PHP_VERSION_ID < 80500) {
                \curl_close($this->curl);
            }
            $this->curl = null;
        }
    }

    private function systemUnderTest(string ...$mockedMethods): Client
    {
        return $this->getMockBuilder(Client::class)
            ->disableOriginalConstructor()
            ->onlyMethods($mockedMethods)
            ->getMock();
    }

    #region __construct --------------------------------------------------------

    function testConstructFails()
    {
        $sut = $this->systemUnderTest('_curl_init', '_curl_close');

        $sut->expects($this->once())
            ->method('_curl_init')
            ->willReturn(false);
        $sut->expects($this->never())
            ->method('_curl_close');

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage("Failed to initialize cURL.");
        $sut->__construct();
        $this->assertNull(ah::GetProperty($sut, 'curl'));
        $sut->__destruct();
        $this->assertNull(ah::GetProperty($sut, 'curl'));
    }

    function testConstructSucceeds()
    {
        $sut = $this->systemUnderTest('_curl_init', '_curl_close');

        $sut->expects($this->once())
            ->method('_curl_init')
            ->willReturn($this->curl);
        if (\PHP_VERSION_ID < 80500) {
            $sut->expects($this->once())
                ->method('_curl_close');
        }

        $sut->__construct();
        $this->assertSame($this->curl, ah::GetProperty($sut, 'curl'));
        $sut->__destruct();
        $this->assertNull(ah::GetProperty($sut, 'curl'));
    }

    #endregion __construct
}
