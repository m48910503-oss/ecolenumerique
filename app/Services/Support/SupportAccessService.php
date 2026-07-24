<?php
declare(strict_types=1);

namespace App\Services\Support;

use App\Core\Service;

final class SupportAccessService extends Service
{
    /**
     * @param array<string,mixed>|null $user
     * @return array<int,string>
     */
    public function rolesFromUser(?array $user): array
    {
        if (!$user) {
            return [];
        }
        $roles = json_decode((string) ($user['roles_json'] ?? '[]'), true);
        if (!is_array($roles)) {
            return [];
        }

        return array_values(array_filter(
            $roles,
            static fn($role): bool => is_string($role) && $role !== ''
        ));
    }

    /**
     * @param array<int,string> $roles
     */
    public function isStaff(array $roles): bool
    {
        return in_array('admin', $roles, true) || in_array('support', $roles, true);
    }
}

