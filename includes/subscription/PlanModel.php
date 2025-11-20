<?php

namespace App\Subscription;

use Medoo\Medoo;

require_once __DIR__ . '/helpers.php';

class PlanModel
{
    private string $table;

    public function __construct(private Medoo $db)
    {
        $this->table = subscription_medoo_table('plans');
    }

    public function allActive(): array
    {
        return $this->db->select($this->table, '*', [
            'is_active' => 1,
            'ORDER' => ['price_usd' => 'ASC'],
        ]) ?: [];
    }

    public function findBySlug(string $slug): ?array
    {
        $plan = $this->db->get($this->table, '*', [
            'slug' => $slug,
            'is_active' => 1,
        ]);

        return $plan ?: null;
    }

    public function create(array $data): int
    {
        $this->db->insert($this->table, $data);
        return (int) $this->db->id();
    }

    public function update(int $planId, array $data): void
    {
        $this->db->update($this->table, $data, ['id' => $planId]);
    }

    public function deactivate(int $planId): void
    {
        $this->db->update($this->table, ['is_active' => 0], ['id' => $planId]);
    }
}
