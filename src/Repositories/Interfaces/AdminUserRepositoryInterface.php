<?php
namespace App\Repositories\Interfaces;

interface AdminUserRepositoryInterface {
    public function findByUsername(string $username): ?array;
    public function find(int $id): ?array;
}
