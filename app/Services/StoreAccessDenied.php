<?php

declare(strict_types=1);

namespace App\Services;

use CodeIgniter\HTTP\Exceptions\HTTPException;

/**
 * Domain exception for store ownership/authorization failures.
 *
 * Thrown by StoreAccess::assert* methods, then converted to the appropriate
 * HTTP response in the controller layer.
 *
 * Note: FrameworkException pulls in DebugTraceableTrait, which declares
 * __construct as final. So this class uses named constructors and assigns its
 * own fields afterwards rather than overriding the signature.
 */
final class StoreAccessDenied extends HTTPException
{
    public const TYPE_NOT_AUTHENTICATED = 'not-authenticated';
    public const TYPE_MISSING_PERMISSION = 'missing-permission';
    public const TYPE_UNKNOWN_STORE     = 'unknown-store';
    public const TYPE_NOT_OWNER         = 'not-owner';

    private string $denialType = self::TYPE_NOT_AUTHENTICATED;

    private ?string $deniedPermission = null;

    public static function notAuthenticated(): self
    {
        $e              = new self('You must be signed in to perform this action.', 403);
        $e->denialType  = self::TYPE_NOT_AUTHENTICATED;
        $e->deniedPermission = null;

        return $e;
    }

    public static function missingPermission(string $permission): self
    {
        $e              = new self(sprintf('You lack the required permission: %s', $permission), 403);
        $e->denialType  = self::TYPE_MISSING_PERMISSION;
        $e->deniedPermission = $permission;

        return $e;
    }

    public static function unknownStore(): self
    {
        $e              = new self('The requested store could not be found.', 403);
        $e->denialType  = self::TYPE_UNKNOWN_STORE;
        $e->deniedPermission = null;

        return $e;
    }

    public static function notOwner(): self
    {
        $e              = new self('You may only manage your own store.', 403);
        $e->denialType  = self::TYPE_NOT_OWNER;
        $e->deniedPermission = null;

        return $e;
    }

    public function getType(): string
    {
        return $this->denialType;
    }

    public function getPermission(): ?string
    {
        return $this->deniedPermission;
    }
}
