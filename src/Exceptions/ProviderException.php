<?php

declare(strict_types=1);

namespace AIProductStudio\Exceptions;

/**
 * Thrown when an AI provider fails (network error, invalid credentials,
 * unexpected response, rate limit, etc.).
 */
final class ProviderException extends AIProductStudioException {

}
