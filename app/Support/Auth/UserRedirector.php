<?php

namespace App\Support\Auth;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Routing\Route;
use Illuminate\Routing\Router;
use Symfony\Component\HttpKernel\Exception\MethodNotAllowedHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class UserRedirector
{
    public function __construct(private readonly Router $router) {}

    public function pathFor(User $user): string
    {
        return route($this->routeNameFor($user), absolute: false);
    }

    public function routeNameFor(User $user): string
    {
        return match ($user->role) {
            'super_admin', 'hr_admin' => 'dashboard',
            'panitia' => 'scanner.dashboard',
            'pegawai' => 'pegawai.dashboard',
            default => 'profile.edit',
        };
    }

    public function pullSafeIntendedPath(Request $request, User $user): ?string
    {
        $intended = $request->session()->pull('url.intended');

        if (! is_string($intended) || blank($intended)) {
            return null;
        }

        $parts = parse_url($intended);

        if ($parts === false || ! $this->hasSafeHost($request, $parts['host'] ?? null)) {
            return null;
        }

        $path = $parts['path'] ?? '/';
        $pathWithQuery = $path.(isset($parts['query']) ? '?'.$parts['query'] : '');

        try {
            $route = $this->router->getRoutes()->match(Request::create($pathWithQuery, 'GET'));
        } catch (NotFoundHttpException|MethodNotAllowedHttpException) {
            return null;
        }

        return $this->userCanEnter($route, $user) ? $pathWithQuery : null;
    }

    private function hasSafeHost(Request $request, ?string $host): bool
    {
        return $host === null || hash_equals(strtolower($request->getHost()), strtolower($host));
    }

    private function userCanEnter(Route $route, User $user): bool
    {
        $middleware = $route->gatherMiddleware();

        if (in_array('guest', $middleware, true)) {
            return false;
        }

        $requiresAuthentication = collect($middleware)->contains(
            fn (string $name): bool => $name === 'auth' || str_starts_with($name, 'auth:')
        );

        if (! $requiresAuthentication) {
            return false;
        }

        foreach ($middleware as $name) {
            if (! str_starts_with($name, 'role:')) {
                continue;
            }

            $roles = explode(',', substr($name, strlen('role:')));

            if (! in_array($user->role, $roles, true)) {
                return false;
            }
        }

        return true;
    }
}
