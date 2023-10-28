<?php

declare(strict_types=1);

namespace haszi\DI\Test\UnitTest\Fixtures;

use haszi\DI\Test\UnitTest\Fixtures\EmptyInterface;

class ClassWithInterfaceInConstructorParameters {
    public function __construct(EmptyInterface $implementation) {}
}
