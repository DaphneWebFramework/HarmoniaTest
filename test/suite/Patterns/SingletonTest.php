<?php declare(strict_types=1);
use \PHPUnit\Framework\TestCase;
use \PHPUnit\Framework\Attributes\CoversClass;

use \Harmonia\Patterns\Singleton;

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

    public function testNewOperatorInstantiationNotAllowed()
    {
        $this->expectException(\Error::class);
        new SingletonUnderTest();
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
        $serialized = serialize($instance);
        $this->assertSame('O:18:"SingletonUnderTest":0:{}', $serialized);
        $this->expectException(\RuntimeException::class);
        $unserialized = unserialize($serialized);
    }
}
