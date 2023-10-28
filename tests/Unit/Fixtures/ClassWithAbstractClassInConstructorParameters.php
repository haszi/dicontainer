<?php

declare(strict_types=1);

namespace haszi\DI\Test\UnitTest\Fixtures;

use haszi\DI\Test\UnitTest\Fixtures\AbstractClass;

class ClassWithAbstractClassInConstructorParameters {
    public function __construct(AbstractClass $implementation) {}
}
