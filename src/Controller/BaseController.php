<?php

declare(strict_types=1);

namespace App\Controller;

use App\Core\Database;

abstract class BaseController
{
    protected Database $db;
    protected int $userId;
    protected int $roleId;
    protected int $orgId;
    protected string $module;
    protected string $moduleCaption;
    protected int $moduleId;

    final public function __construct(
        Database $db,
        int $userId,
        int $roleId,
        int $orgId,
    ) {
        $this->db = $db;
        $this->userId = $userId;
        $this->roleId = $roleId;
        $this->orgId = $orgId;
    }

    protected function requiresModule(string $slug, string $caption): void
    {
        $this->module = $slug;
        $this->moduleCaption = $caption;

        $sql = "SELECT id FROM `erp_modules` WHERE slug = :slug LIMIT 1";
        $this->moduleId = (int)($this->db->fetchOne($sql, ['slug' => $slug])['id'] ?? 0);
    }

    protected function canView(): bool
    {
        return granted('view', $this->moduleId);
    }

    protected function canCreate(): bool
    {
        return granted('create', $this->moduleId);
    }

    protected function canEdit(): bool
    {
        return granted('edit', $this->moduleId);
    }

    protected function canDelete(): bool
    {
        return granted('delete', $this->moduleId);
    }

    protected function validateCsrf(): bool
    {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            return validate_csrf_token($_POST['csrf_token'] ?? '');
        }
        return true;
    }

    abstract public function handle(): void;
}
