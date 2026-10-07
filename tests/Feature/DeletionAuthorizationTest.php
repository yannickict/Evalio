<?php

namespace Tests\Feature;

use App\Models\Role;
use App\Models\User;
use Illuminate\Support\Facades\Gate;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class DeletionAuthorizationTest extends TestCase
{
    public static function roles(): array
    {
        return [
            'admin' => ['admin', true],
            'editor' => ['editor', false],
            'instructor' => ['instructor', false],
            'unknown role' => ['unknown', false],
            'no role' => [null, false],
        ];
    }

    #[DataProvider('roles')]
    public function test_only_admins_have_deletion_permissions(?string $role, bool $allowed): void
    {
        $user = new User;
        $user->setRelation('role', $role === null ? null : new Role(['name' => $role]));

        foreach (['delete-courses', 'delete-sessions', 'delete-questionnaires'] as $permission) {
            $this->assertSame($allowed, Gate::forUser($user)->allows($permission));
        }
    }

    public function test_guests_cannot_delete(): void
    {
        foreach (['delete-courses', 'delete-sessions', 'delete-questionnaires'] as $permission) {
            $this->assertFalse(Gate::allows($permission));
        }
    }
}
