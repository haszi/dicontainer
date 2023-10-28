<?php

declare(strict_types=1);

namespace haszi\DI\Exceptions;

use Psr\Container\NotFoundExceptionInterface;

class NotFoundException extends \Exception implements NotFoundExceptionInterface {}
