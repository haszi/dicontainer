<?php

declare(strict_types=1);

namespace haszi\DI;

use Psr\Container\ContainerInterface;
use haszi\DI\Exceptions\AlreadyRegisteredException;
use haszi\DI\Exceptions\ContainerException;
use haszi\DI\Exceptions\InvalidClosureReturnTypeException;
use haszi\DI\Exceptions\NotFoundException;
use haszi\DI\Exceptions\UnsupportedParametersException;

class Container implements ContainerInterface
{
    /** @var array<string, string|\Closure> */
    private array $services = [];

    /** @var array<string, string|object> */
    private array $sharedServices = [];

    /** @var array<string, object> */
    private array $resolvedSharedServices = [];

    /** @var array<string, string|object> */
    private array $scopedServices = [];

    /** @var array<string, object> */
    private array $resolvedScopedServices = [];

    /**
     * Registers a transient service with the requested id
     *
     * @param string $id  id of the service
     * @param string|\Closure $service
     *      Name of a class to be instantiated or a Closure to be called
     *
     * @throws AlreadyRegisteredException   Service already registered in container
     * @throws ContainerException   Class name provided could not be resolved to a class
     */
    public function set(string $id, string|\Closure $service): void
    {
        if ($this->has($id)) {
            throw new AlreadyRegisteredException('Service ' . $id . ' is already registered in DI container. Unset before registering again.');
        }

        if (\is_string($service)
            && ! \class_exists($service)) {
            throw new ContainerException('Could not resolve ' . $service . ' to a class.');
        }

        $this->services[$id] = $service;
    }

    /**
     * Registers a shared service with the requested id in the container
     *
     * @param string $id  id of the service
     * @param string|object $service A classname, a Closure to be called or an already instantiated object
     *
     * @throws AlreadyRegisteredException   Shared service already registered in container
     */
    public function setShared(string $id, string|object $service): void
    {
        if ($this->has($id)) {
            throw new AlreadyRegisteredException(
                'Shared service ' . $id . ' is already registered in DI container. Unset before registering again.'
            );
        }

        $this->sharedServices[$id] = $service;
    }

    /**
     * Registers a scoped service with the requested id in the container
     *
     * @param string $id  id of the service
     * @param string|object $service A Closure to be called or an already instantiated object
     *
     * @throws AlreadyRegisteredException   Shared service already registered in container
     */
    public function setScoped(string $id, string|object $service): void
    {
        if ($this->has($id)) {
            throw new AlreadyRegisteredException(
                'Shared service ' . $id . ' is already registered in DI container. Unset before registering again.'
            );
        }

        $this->scopedServices[$id] = $service;
    }

    /**
     * Checks whether a service with the given id is registered
     *
     * @param string $id  id of the service
     *
     * @return bool
     */
    public function has(string $id): bool
    {
        return $this->hasTransient($id)
            || $this->hasShared($id)
            || $this->hasScoped($id);
    }

    /**
     * Checks whether a transient service with the given id is registered
     *
     * @param string $id  id of the service
     *
     * @return bool
     */
    private function hasTransient(string $id): bool
    {
        return isset($this->services[$id]);
    }

    /**
     * Checks whether a shared service with the given id is registered
     *
     * @param string $id  id of the service
     *
     * @return bool
     */
    private function hasShared(string $id): bool
    {
        return isset($this->sharedServices[$id]);
    }

    /**
     * Checks whether a scoped service with the given id is registered
     *
     * @param string $id  id of the service
     *
     * @return bool
     */
    private function hasScoped(string $id): bool
    {
        return isset($this->scopedServices[$id]);
    }

    /**
     * Unregisters a service
     *
     * @param string $id  id of the service
     */
    public function unset(string $id): void
    {
        if ($this->hasTransient($id)) {
            unset($this->services[$id]);
            return;
        }

        if ($this->hasShared($id)) {
            unset($this->sharedServices[$id]);
            unset($this->resolvedSharedServices[$id]);
            return;
        }

        if ($this->hasScoped($id)) {
            unset($this->scopedServices[$id]);
            $this->unsetScopedInstances($id);
        }
    }

    /**
     * Deletes scoped service instances
     *
     * @param string $id  id of the service
     */
    public function unsetScopedInstances(string $id): void
    {
        unset($this->resolvedScopedServices[$id]);
    }

    /**
     * Returns an object registered in the container
     *
     * @param string $id  id of the service
     *
     * @throws NotFoundException Service not registered
     * @throws InvalidClosureReturnTypeException Registered Closure not returning an object
     * @throws UnsupportedParametersException On any of the followings as constructor parameters:
     *   built-in types without default values, union types, intersection types,
     *   interfaces or abstract classes without explicitely registered concrete implementations
     *
     * @return object
     */
    public function get(string $id)
    {
        if (! $this->has($id)) {
            throw new NotFoundException('Service ' . $id . ' is not registered in the DI container.');
        }

        if ($this->hasShared($id)) {
            return $this->getShared($id);
        }

        if ($this->hasScoped($id)) {
            return $this->getScoped($id);
        }

        if ($this->hasTransient($id)) {
            return $this->getTransient($id);
        }

        throw new NotFoundException('Service ' . $id . ' is not registered in the DI container.');
    }

    /**
     * Returns a transient object with the requested id registered
     *
     * @param string $id  id of the service
     *
     * @throws InvalidClosureReturnTypeException Registered Closure not returning an object
     * @throws UnsupportedParametersException On any of the followings as constructor parameters:
     *   built-in types without default values, union types, intersection types,
     *   interfaces or abstract classes without explicitely registered concrete implementations
     *
     * @return object
     */
    private function getTransient(string $id)
    {
        try {
            return $this->resolve($this->services[$id]);
        } catch (InvalidClosureReturnTypeException $e) {
            throw new InvalidClosureReturnTypeException($e->getMessage() . ': ' . $id);
        }
    }

    /**
     * Returns a shared service with the requested id registered in the container
     *
     * @param string $id  id of the service
     *
     * @throws NotFoundException Unable to instantiate dependencies
     * @throws InvalidClosureReturnTypeException Registered Closure not returning an object
     * @throws UnsupportedParametersException On any of the followings as constructor parameters:
     *   built-in types without default values, union types, intersection types,
     *   interfaces or abstract classes without explicitely registered concrete implementations
     *
     * @return object
     */
    private function getShared(string $id)
    {
        if (! isset($this->resolvedSharedServices[$id])) {
            try {
                $this->resolvedSharedServices[$id] = $this->resolve($this->sharedServices[$id]);
            } catch (InvalidClosureReturnTypeException $e) {
                throw new InvalidClosureReturnTypeException($e->getMessage() . ': ' . $id);
            }
        }

        return $this->resolvedSharedServices[$id];

    }

    /**
     * Returns a scoped service with the requested id registered in the container
     *
     * @param string $id  id of the service
     *
     * @throws NotFoundException Unable to instantiate dependencies
     * @throws InvalidClosureReturnTypeException Registered Closure not returning an object
     * @throws UnsupportedParametersException On any of the followings as constructor parameters:
     *   built-in types without default values, union types, intersection types,
     *   interfaces or abstract classes without explicitely registered concrete implementations
     *
     * @return object
     */
    private function getScoped(string $id)
    {
        if (! isset($this->resolvedScopedServices[$id])) {
            try {
                $this->resolvedScopedServices[$id] = $this->resolve($this->scopedServices[$id]);
            } catch (InvalidClosureReturnTypeException $e) {
                throw new InvalidClosureReturnTypeException($e->getMessage() . ': ' . $id);
            }
        }

        return $this->resolvedScopedServices[$id];
    }

    /**
     * Returns a resolved object for a service,
     * ie. a newly instantiated object,
     * an object from a Closure
     * or an already registered instantiated object
     *
     * @param string|object $service  Instantiated object, Closure or name of class to instantiate
     *
     * @throws NotFoundException Unable to instantiate dependencies
     * @throws InvalidClosureReturnTypeException Registered Closure not returning an object
     * @throws UnsupportedParametersException On any of the followings as constructor parameters:
     *   built-in types without default values, union types, intersection types,
     *   interfaces or abstract classes without explicitely registered concrete implementations
     *
     * @return object
     */
    private function resolve(string|object $service)
    {
        if (\is_string($service)) {
            return $this->make($service);
        }

        if (! ($service instanceof \Closure)) {
            return $service;
        }

        $object = $service();

        if (! \is_object($object)) {
            throw new InvalidClosureReturnTypeException(
                'Service is of an invalid type (not a Closure or an instantiated object)'
            );
        }

        return $object;
    }

    /**
     * Creates an object of the requested class
     * using the services registered in the container
     *
     * @param string $classname Class to instantiate
     *
     * @throws NotFoundException Class was not found
     * @throws UnsupportedParametersException On any of the followings as constructor parameters:
     *   built-in types without default values, union types, intersection types,
     *   interfaces or abstract classes without explicitely registered concrete implementations
     *
     * @return object
     */
    private function make(string $classname)
    {
        try {
            $constructorParams = $this->getConstructorParameters($classname);
        } catch (\ReflectionException $e) {
            throw new NotFoundException('Could not find class ' . $classname . '.');
        }

        if ($constructorParams === []) {
            return new $classname();
        }

        $constructorArgs = [];
        foreach ($constructorParams as $constructorParam) {

            $constructorParamType = $constructorParam->getType();

            // Throw on built-in types without default values, union and intersection types
            // (array, callable, bool, float, int, string, iterable, object, mixed)
            if (
                ! ($constructorParamType instanceof \ReflectionNamedType)
                || ($constructorParamType->isBuiltin()
                    && ! $constructorParam->isDefaultValueAvailable())
                ) {

                throw new UnsupportedParametersException(
                    'Unsupported parameter types in the constructor of ' . $classname . '.'
                );
            }

            if ($constructorParamType->isBuiltin()
                && $constructorParam->isDefaultValueAvailable()) {
                continue;
            }

            $constructorParamTypeName = $constructorParamType->getName();

            if ($this->has($constructorParamTypeName)) {
                $constructorArgs[] = $this->get($constructorParamTypeName);
                continue;
            }

            if (\interface_exists($constructorParamTypeName)
                || (new \ReflectionClass($constructorParamTypeName))->isAbstract()) {
                throw new UnsupportedParametersException('Unregistered interface/abstract class types in the constructor of ' . $classname . '.' );
            }

            if (! \class_exists($constructorParamTypeName)) {
                throw new NotFoundException('Could not create service ' . $constructorParamTypeName . '.');
            }

            $constructorArgs[] = $this->make($constructorParamTypeName);
        }

        return new $classname(...$constructorArgs);
    }

    /**
     * Returns an array of ReflectionParameter objects for the class's constructor
     *
     * @param string $classname Name of the class
     *
     * @throws \ReflectionException If the class does not exist
     *
     * @return array<\ReflectionParameter>
     */
    private function getConstructorParameters(string $classname): array
    {
        $reflectionClass = new \ReflectionClass($classname);
        $constructor = $reflectionClass->getConstructor();
        $constructorParams = $constructor ? $constructor->getParameters() : [];

        return $constructorParams;
    }
}
