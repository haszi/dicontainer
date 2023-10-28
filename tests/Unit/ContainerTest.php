<?php

declare(strict_types=1);

namespace haszi\DI\Test\UnitTest;

use haszi\DI\Container;
use haszi\DI\Exceptions\AlreadyRegisteredException;
use haszi\DI\Exceptions\ContainerException;
use haszi\DI\Exceptions\InvalidClosureReturnTypeException;
use haszi\DI\Exceptions\NotFoundException;
use haszi\DI\Exceptions\UnsupportedParametersException;
use haszi\DI\Test\UnitTest\Fixtures\AbstractClass;
use haszi\DI\Test\UnitTest\Fixtures\ClassExtendingAbstractClass;
use haszi\DI\Test\UnitTest\Fixtures\ClassImplementingInterface;
use haszi\DI\Test\UnitTest\Fixtures\ClassWithAbstractClassInConstructorParameters;
use haszi\DI\Test\UnitTest\Fixtures\ClassWithBuiltInTypeConstructorParameters;
use haszi\DI\Test\UnitTest\Fixtures\ClassWithInterfaceInConstructorParameters;
use haszi\DI\Test\UnitTest\Fixtures\EmptyInterface;
use PHPUnit\Framework\TestCase;

final class ContainerTest extends TestCase
{
    private ?Container $container;

    public function setUp(): void
    {
        $this->container = new Container();
    }

    public function testContainerIsCreated()
    {
        $this->assertInstanceOf(Container::class, $this->container);
    }

    public function testSetAsStringReturnsAppropriateInstance()
    {
        $this->container->set(Container::class, Container::class);

        $this->assertInstanceOf(Container::class, $this->container->get(Container::class));
    }

    public function testSetAsStringAndGetNewObject()
    {
        $this->container->set(Container::class, Container::class);

        $this->assertNotSame(
            $this->container->get(Container::class),
            $this->container->get(Container::class)
        );
    }

    public function testSetAsClosureReturnsAppropriateInstance()
    {
        $this->container->set(Container::class, function() {
            return new \stdClass;
        });

        $this->assertInstanceOf(\stdClass::class, $this->container->get(Container::class));
    }

    public function testSetAsClosureAndGetNewObject()
    {
        $this->container->set(Container::class, function() {
            return new \stdClass;
        });

        $this->assertNotSame(
            $this->container->get(Container::class),
            $this->container->get(Container::class)
        );
    }

    public function testGetThrowOnNonExistentService()
    {
        $this->expectException(NotFoundException::class);

        $this->container->get('NonExistentService');
    }

    public function testGetThrowOnClosureNotReturningObject()
    {
        $this->container->set(Container::class, function() {
            return 'Not an object';
        });

        $this->expectException(InvalidClosureReturnTypeException::class);

        $this->container->get(Container::class);
    }

    public function testSetThrowOnAlreadyRegisteredService()
    {
        $this->container->set(Container::class, function() {
            return new Container();
        });

        $this->expectException(AlreadyRegisteredException::class);

        $this->container->set(Container::class, Container::class);
    }

    public function testSetThrowOnNonExistentService()
    {
        $this->expectException(ContainerException::class);

        $this->container->set(Container::class, 'NonExistentClassName');
    }

    public function testHasIsCorrect()
    {
        $this->assertFalse($this->container->has(Container::class));

        $this->container->set(Container::class, function() {
            return new \stdClass;
        });

        $this->assertTrue($this->container->has(Container::class));
    }

    public function testUnsetRemovesService()
    {
        $this->container->set(Container::class, function() {
            return new \stdClass;
        });

        $this->assertTrue($this->container->has(Container::class));

        $this->container->unset(Container::class);

        $this->assertFalse($this->container->has(Container::class));
    }

    public function testSetSharedAsStringReturnsAppropriateInstance()
    {
        $this->container->setShared(Container::class, Container::class);

        $this->assertInstanceOf(Container::class, $this->container->get(Container::class));
    }

    public function testSetSharedAsStringAndGetSameObject()
    {
        $this->container->setShared(Container::class, Container::class);

        $this->assertSame(
            $this->container->get(Container::class),
            $this->container->get(Container::class)
        );
    }

    public function testSetSharedAsInstantiatedObjectReturnsAppropriateInstance()
    {
        $newObj = new Container();

        $this->container->setShared(Container::class, $newObj);

        $this->assertInstanceOf(Container::class, $newObj);
    }

    public function testSetSharedAsInstantiatedObjectAndGetSameObject()
    {
        $newObj = new Container();

        $this->container->setShared(Container::class, $newObj);

        $this->assertSame(
            $this->container->get(Container::class),
            $this->container->get(Container::class)
        );
    }

    public function testSetSharedAsClosureReturnsAppropriateInstance()
    {
        $this->container->setShared(Container::class, function() {
            return new Container();
        });

        $this->assertInstanceOf(Container::class, $this->container->get(Container::class));
    }

    public function testSetSharedAsClosureAndGetSameObject()
    {
        $this->container->setShared(Container::class, function() {
            return new Container();
        });

        $this->assertSame(
            $this->container->get(Container::class),
            $this->container->get(Container::class)
        );
    }

    public function testUnsetRemovesSharedService()
    {
        $this->container->setShared(Container::class, function() {
            return new \stdClass;
        });

        $this->assertTrue($this->container->has(Container::class));

        $this->container->unset(Container::class);

        $this->assertFalse($this->container->has(Container::class));
    }

    public function testScopedAsStringReturnsAppropriateInstance()
    {
        $this->container->setScoped(Container::class, Container::class);

        $this->assertInstanceOf(Container::class, $this->container->get(Container::class));
    }

    public function testSetScopedAsStringAndGetSameObject()
    {
        $this->container->setScoped(Container::class, Container::class);

        $this->assertSame(
            $this->container->get(Container::class),
            $this->container->get(Container::class)
        );
    }

    public function testScopedAsInstantiatedObjectReturnsAppropriateInstance()
    {
        $existingObj = new Container();

        $this->container->setScoped(Container::class, $existingObj);

        $this->assertInstanceOf(Container::class, $this->container->get(Container::class));
    }

    public function testSetScopedAsInstantiatedObjectAndGetSameObject()
    {
        $existingObj = new Container();

        $this->container->setScoped(Container::class, $existingObj);

        $this->assertSame($existingObj, $this->container->get(Container::class));
    }

    public function testScopedAsClosureReturnsAppropriateInstance()
    {
        $this->container->setScoped(Container::class, function() {
            return new Container();
        });

        $this->assertInstanceOf(Container::class, $this->container->get(Container::class));
    }

    public function testSetScopedAsClosureAndGetSameObject()
    {
        $this->container->setScoped(Container::class, function() {
            return new Container();
        });

        $this->assertSame(
            $this->container->get(Container::class),
            $this->container->get(Container::class)
        );
    }

    public function testUnsetRemovesScopedService()
    {
        $this->container->setScoped(Container::class, function() {
            return new \stdClass;
        });

        $this->assertTrue($this->container->has(Container::class));

        $this->container->unset(Container::class);

        $this->assertFalse($this->container->has(Container::class));
    }

    public function testUnsetScopedInstancesRemovesInstance()
    {
        $this->container->setScoped(Container::class, function() {
            return new \stdClass;
        });

        $firstScopeObj = $this->container->get(Container::class);

        $this->container->unsetScopedInstances(Container::class);

        $this->assertNotSame($firstScopeObj, $this->container->get(Container::class));
    }

    public function testUnsetScopedInstancesDoesNotRemoveService()
    {
        $this->container->setScoped(Container::class, function() {
            return new \stdClass;
        });

        $this->container->unsetScopedInstances(Container::class);

        $this->assertTrue($this->container->has(Container::class));
    }

    public function testThrowOnBuiltInTypesInConstructor()
    {
        $this->container->set(
            ClassWithBuiltInTypeConstructorParameters::class,
            ClassWithBuiltInTypeConstructorParameters::class
        );

        $this->expectException(UnsupportedParametersException::class);

        $this->container->get(ClassWithBuiltInTypeConstructorParameters::class);
    }

    public function testGetSetRegisteredInterfaceImplementation()
    {
        $this->container->set(
            EmptyInterface::class,
            ClassImplementingInterface::class
        );

        $this->assertInstanceOf(
            ClassImplementingInterface::class,
            $this->container->get(EmptyInterface::class)
        );
    }

    public function testThrowOnUnregisteredInterfaceInConstructor()
    {
        $this->container->set(
            ClassWithInterfaceInConstructorParameters::class,
            ClassWithInterfaceInConstructorParameters::class
        );

        $this->expectException(UnsupportedParametersException::class);

        $this->container->get(ClassWithInterfaceInConstructorParameters::class);
    }

    public function testGetSetRegisteredAbstractClassImplementation()
    {
        $this->container->set(
            AbstractClass::class,
            ClassExtendingAbstractClass::class
        );

        $this->assertInstanceOf(
            ClassExtendingAbstractClass::class,
            $this->container->get(AbstractClass::class)
        );
    }

    public function testThrowOnUnregisteredAbstractClassInConstructor()
    {
        $this->container->set(
            ClassWithAbstractClassInConstructorParameters::class,
            ClassWithAbstractClassInConstructorParameters::class
        );

        $this->expectException(UnsupportedParametersException::class);

        $this->container->get(ClassWithAbstractClassInConstructorParameters::class);
    }
}
