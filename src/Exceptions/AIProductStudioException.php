<?php

declare(strict_types=1);

namespace AIProductStudio\Exceptions;

use RuntimeException;

/**
 * Base exception for every error raised by the plugin. Catching this type
 * catches any domain error thrown by AI Product Studio.
 */
class AIProductStudioException extends RuntimeException {

}
