<?php

declare(strict_types=1);

namespace haszi\DI\Test\UnitTest\Fixtures;

class ClassWithBuiltInTypeConstructorParameters {
    public function __construct(string $string) {}
}
