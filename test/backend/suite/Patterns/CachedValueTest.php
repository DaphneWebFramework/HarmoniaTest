<?php declare(strict_types=1);
use \PHPUnit\Framework\TestCase;
use \PHPUnit\Framework\Attributes\CoversClass;

use \Harmonia\Patterns\CachedValue;

#[CoversClass(CachedValue::class)]
class CachedValueTest extends TestCase
{
    #region Get ----------------------------------------------------------------

    function testGetCallsResolverOnceAndReturnsItsValue()
    {
        $sut = new CachedValue();
        $called = 0;

        $firstValue = $sut->Get(function() use(&$called) {
            $called++;
            return 'resolved';
        });

        $this->assertSame('resolved', $firstValue);
        $this->assertSame(1, $called);

        $secondValue = $sut->Get(function() use(&$called) {
            $called++;
            return 'different';
        });

        $this->assertSame('resolved', $secondValue);
        $this->assertSame(1, $called);
    }

    #endregion Get

    #region Set ----------------------------------------------------------------

    function testSetOverridesTheCachedValue()
    {
        $sut = new CachedValue();

        $sut->Set('manual');
        $this->assertSame('manual', $sut->Get(function() {
            $this->fail('should not be called');
        }));
    }

    #endregion Set

    #region IsCached -----------------------------------------------------------

    function testIsCachedReturnsFalseBeforeResolution()
    {
        $sut = new CachedValue();

        $this->assertFalse($sut->IsCached());
    }

    function testIsCachedReturnsTrueAfterGet()
    {
        $sut = new CachedValue();

        $this->assertFalse($sut->IsCached());
        $sut->Get(fn() => 'abc');
        $this->assertTrue($sut->IsCached());
    }

    function testIsCachedReturnsTrueAfterSet()
    {
        $sut = new CachedValue();

        $this->assertFalse($sut->IsCached());
        $sut->Set('abc');
        $this->assertTrue($sut->IsCached());
    }

    #endregion IsCached

    #region Reset --------------------------------------------------------------

    function testResetClearsCachedState()
    {
        $sut = new CachedValue();

        $sut->Set('value');
        $this->assertTrue($sut->IsCached());

        $sut->Reset();
        $this->assertFalse($sut->IsCached());

        $called = false;
        $result = $sut->Get(function() use(&$called) {
            $called = true;
            return 'after-reset';
        });

        $this->assertTrue($called);
        $this->assertSame('after-reset', $result);
    }

    #endregion Reset
}
