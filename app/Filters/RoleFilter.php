<?php

declare(strict_types=1);

namespace App\Filters;

use CodeIgniter\Filters\FilterInterface;
use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;

/**
 * Restricts a route to one Shield group, written as `role:seller`.
 *
 * AGENTS.md allows exactly two roles, `customer` and `seller`, and there is no
 * admin system, so this is a membership check against Shield's own groups
 * rather than a permission table.
 *
 * The check runs *after* Shield's `session` filter, which is why every route
 * using it also lists `session`: an unauthenticated request is rejected there,
 * and an authenticated request without the group is rejected here.
 */
class RoleFilter implements FilterInterface
{
    /**
     * @param list<string>|string $arguments One or more group names.
     */
    public function before(RequestInterface $request, $arguments = null)
    {
        if (! auth()->loggedIn()) {
            return redirect()->to(route_to('login'));
        }

        $allowed = is_array($arguments) ? $arguments : [$arguments];
        $groups  = auth()->user()->getGroups();

        foreach ($allowed as $group) {
            if (in_array($group, $groups, true)) {
                return null;
            }
        }

        // A signed-in user without the role is sent to their own account rather
        // than shown a 403, which matches the "Kembali ke Toko" pattern.
        return redirect()->to(route_to('profile'))
            ->with('error', 'Halaman ini hanya untuk akun mitra UMKM.');
    }

    public function after(RequestInterface $request, ResponseInterface $response, $arguments = null)
    {
    }
}
