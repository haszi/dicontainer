# DI Container

A basic PSR-11 compatible dependency injection container written to learn about the concepts and implementation of object composition, lifetime managmenent and interception provided by DICs. Basic object composition and lifetime management functionality has been implemented (see [features](#supported) and [limitations](#not-supported)) with no support for interception of calls to managed objects.

### Requirements

PHP 8.0+

### Installation

Install with Composer

```
composer require haszi/dicontainer
```

## Getting started

### Create a container

```php
use haszi\DI\Container;

$container = new Container();
```

### PSR-11 compatibility

```php
if ($container->has('MyClass')) {
  $myObject = $container->get('MyClass');
}
```

### Register transient services

Transient services (returning a new instance each time you get them from the container) can be registered using a string representing a classname or a closure.

Classname
```php
$container->set('comment', Comment::class);
```

Closure
```php
$container->set(Comment::class, function () {
  return new Comment();
});
```

### Register shared services

Shared services (returning the same instance each time you get it from the container)can be registered using a string representing a classname, a closure or an instantiated object.
All shared service objects are lazily initialized on first use.

Classname
```php
$container->setShared('database', DatabaseConnection::class);
```

Closure
```php
$container->setShared(DatabaseConnection::class, function () {
  return new DatabaseConnection();
});
```

Instantiated Object
```php
$db = new DatabaseConnection();

$container->setShared(DatabaseConnection::class, $db);
```

### Register scoped services

Scoped services are identical to shared service with the exception that the instantiated objects can be flushed without deregistering the service itself. A common use for scoped services are request objects in an event loop.

Classname
```php
$container->setScoped('request', Request::class);
```

Closure
```php
$container->setScoped(Request::class, function () {
  return new Request();
});
```

Instantiated Object
```php
$request = new Request();

$container->setScoped(Request::class, $request);
```

### Flush initialized scoped objects

```php
$container->unsetScopedInstances(Request::class);
```

### Deregister services

```php
$container->unset(DatabaseConnection::class);
```

## Features

### Supported
- object composition
  - constructor injection
    - auto-wiring
- lifetime management
  - transient services (new object returned on each get request)
  - shared services (the same object returned on each get request)
  - scoped services (the same object returned on each get request with the instantiated object being disposable)

### Not supported
- object composition
  - setter and property injection
  - injection of:
    - union and intersection types
    - passing in values to built-in types (array, callable, bool, float, int, string, iterable, object, mixed)
- interception