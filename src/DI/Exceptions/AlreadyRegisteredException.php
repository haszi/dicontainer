<?php

declare(strict_types=1);

namespace haszi\DI\Exceptions;

use Psr\Container\ContainerExceptionInterface;

class AlreadyRegisteredException extends \Exception implements ContainerExceptionInterface {}
