<?php

declare(strict_types=1);

namespace haszi\DI\Exceptions;

use Psr\Container\ContainerExceptionInterface;

class InvalidClosureReturnTypeException extends \Exception implements ContainerExceptionInterface {}
