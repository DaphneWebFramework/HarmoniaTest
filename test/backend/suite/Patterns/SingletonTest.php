<?php declare(strict_types=1);
use \PHPUnit\Framework\TestCase;
use \PHPUnit\Framework\Attributes\CoversClass;

use \Harmonia\Patterns\Singleton;

use \TestToolkit\AccessHelper;

class SingletonUnderTest extends Singleton {}
class AnotherSingletonUnderTest extends Singleton {}

#[CoversClass(Singleton::class)]
class SingletonTest extends TestCase
{
    function testInstanceReturnsSameInstance()
    {
        $instance1 = SingletonUnderTest::Instance();
        $instance2 = SingletonUnderTest::Instance();
        $this->assertInstanceOf(SingletonUnderTest::class, $instance1);
        $this->assertInstanceOf(SingletonUnderTest::class, $instance2);
        $this->assertSame($instance1, $instance2);
    }

    function testDifferentSubclassesHaveDifferentInstances()
    {
        $instance1 = SingletonUnderTest::Instance();
        $instance2 = AnotherSingletonUnderTest::Instance();
        $this->assertNotSame($instance1, $instance2);
    }

    function testReflectionCannotBypassSingleton()
    {
        // Ensure that a new instance created via reflection is not the same as
        // the singleton instance managed by the class.
        $reflectionClass = new \ReflectionClass(SingletonUnderTest::class);
        $instance1 = SingletonUnderTest::Instance();
        $instance2 = $reflectionClass->newInstanceWithoutConstructor();
        $this->assertNotSame($instance1, $instance2);
    }

    function testNewOperatorInstantiationNotAllowed()
    {
        $this->expectException(\Error::class);
        new SingletonUnderTest();
    }

    function testReplaceInstanceReturnsPreviousInstance()
    {
        $original = SingletonUnderTest::Instance();
        $replacement = AccessHelper::CallConstructor(SingletonUnderTest::class);
        $previous = SingletonUnderTest::ReplaceInstance($replacement);
        $this->assertSame($previous, $original);
        $this->assertNotSame($original, SingletonUnderTest::Instance());
        $this->assertSame($replacement, SingletonUnderTest::Instance());
    }

    function testReplaceInstanceWhenNoPreviousInstanceExists()
    {
        $instances = AccessHelper::GetStaticProperty(Singleton::class, 'instances');
        unset($instances[SingletonUnderTest::class]);
        AccessHelper::SetStaticProperty(Singleton::class, 'instances', $instances);
        $replacement = AccessHelper::CallConstructor(SingletonUnderTest::class);
        $previous = SingletonUnderTest::ReplaceInstance($replacement);
        $this->assertNull($previous);
        $this->assertSame($replacement, SingletonUnderTest::Instance());
    }

    function testReplaceInstanceWithNullResetsInstance()
    {
        $original = SingletonUnderTest::Instance();
        $previous = SingletonUnderTest::ReplaceInstance(null);
        $this->assertSame($previous, $original);
        $this->assertArrayNotHasKey(SingletonUnderTest::class,
            AccessHelper::GetStaticProperty(Singleton::class, 'instances'));
    }

    function testConstructorIsNotPublic()
    {
        $reflectionClass = new \ReflectionClass(SingletonUnderTest::class);
        $constructor = $reflectionClass->getConstructor();
        $this->assertFalse($constructor->isPublic());
    }

    function testCloneIsNotAllowed()
    {
        $instance = SingletonUnderTest::Instance();
        $this->expectException(\Error::class);
        $clone = clone $instance;
    }

    function testUnserializeIsNotAllowed()
    {
        $instance = SingletonUnderTest::Instance();
        $serialized = \serialize($instance);
        $this->assertSame('O:18:"SingletonUnderTest":0:{}', $serialized);
        $this->expectException(\RuntimeException::class);
        $unserialized = \unserialize($serialized);
    }
}
