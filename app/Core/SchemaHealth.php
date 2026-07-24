<?php
declare(strict_types=1);

namespace App\Core;

use PDO;

final class SchemaHealth
{
    private PDO $pdo;

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    /**
     * @return array{tables:array<int,string>,columns:array<int,string>,indexes:array<int,string>}
     */
    public function check(): array
    {
        $dbName = (string) ($this->pdo->query('SELECT DATABASE()')->fetchColumn() ?? '');
        if ($dbName === '') {
            return ['tables' => ['unknown_database'], 'columns' => [], 'indexes' => []];
        }

        $requiredTables = [
            'company_metrics_daily',
        ];
        $requiredColumns = [
            ['table' => 'company_metrics_daily', 'column' => 'metric_date'],
        ];
        $requiredIndexes = [
            ['table' => 'company_metrics_daily', 'index' => 'uq_company_metrics_daily'],
            ['table' => 'exam_questions', 'index' => 'idx_exam_questions_variant'],
            ['table' => 'order_items', 'index' => 'idx_order_items_coupon_code'],
            ['table' => 'order_items', 'index' => 'idx_order_items_promo_scope'],
            ['table' => 'order_items', 'index' => 'idx_order_items_promotion_id'],
            ['table' => 'payment_webhook_events', 'index' => 'uq_payment_webhook_events_provider_key'],
            ['table' => 'push_notification_queue', 'index' => 'idx_push_queue_status_next'],
            ['table' => 'push_subscriptions', 'index' => 'idx_push_subscriptions_user_active'],
            ['table' => 'push_subscriptions', 'index' => 'uq_push_subscriptions_endpoint_hash'],
            ['table' => 'teacher_payouts', 'index' => 'idx_teacher_payouts_status'],
            ['table' => 'teacher_payouts', 'index' => 'idx_teacher_payouts_teacher'],
            ['table' => 'users', 'index' => 'idx_users_auth_provider'],
            ['table' => 'users', 'index' => 'idx_users_mfa_enabled'],
            ['table' => 'users', 'index' => 'uq_users_referral_code'],
        ];

        $missingTables = $this->missingTables($dbName, $requiredTables);
        $missingColumns = $this->missingColumns($dbName, $requiredColumns);
        $missingIndexes = $this->missingIndexes($dbName, $requiredIndexes);

        return [
            'tables' => $missingTables,
            'columns' => $missingColumns,
            'indexes' => $missingIndexes,
        ];
    }

    /**
     * @param array<int,string> $tables
     * @return array<int,string>
     */
    private function missingTables(string $dbName, array $tables): array
    {
        if ($tables === []) {
            return [];
        }
        $placeholders = implode(', ', array_fill(0, count($tables), '?'));
        $stmt = $this->pdo->prepare(
            "SELECT table_name FROM information_schema.tables WHERE table_schema = ? AND table_name IN ($placeholders)"
        );
        $stmt->execute(array_merge([$dbName], $tables));
        $found = $stmt->fetchAll(PDO::FETCH_COLUMN) ?: [];
        return array_values(array_diff($tables, $found));
    }

    /**
     * @param array<int,array{table:string,column:string}> $columns
     * @return array<int,string>
     */
    private function missingColumns(string $dbName, array $columns): array
    {
        if ($columns === []) {
            return [];
        }
        $clauses = [];
        $params = [$dbName];
        foreach ($columns as $item) {
            $clauses[] = '(table_name = ? AND column_name = ?)';
            $params[] = $item['table'];
            $params[] = $item['column'];
        }
        $sql = 'SELECT table_name, column_name FROM information_schema.columns'
            . ' WHERE table_schema = ? AND (' . implode(' OR ', $clauses) . ')';
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        $found = $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
        $foundKeys = array_map(
            static fn(array $row): string => $row['table_name'] . '.' . $row['column_name'],
            $found
        );
        $missing = [];
        foreach ($columns as $item) {
            $key = $item['table'] . '.' . $item['column'];
            if (!in_array($key, $foundKeys, true)) {
                $missing[] = $key;
            }
        }
        return $missing;
    }

    /**
     * @param array<int,array{table:string,index:string}> $indexes
     * @return array<int,string>
     */
    private function missingIndexes(string $dbName, array $indexes): array
    {
        if ($indexes === []) {
            return [];
        }
        $clauses = [];
        $params = [$dbName];
        foreach ($indexes as $item) {
            $clauses[] = '(table_name = ? AND index_name = ?)';
            $params[] = $item['table'];
            $params[] = $item['index'];
        }
        $sql = 'SELECT table_name, index_name FROM information_schema.statistics'
            . ' WHERE table_schema = ? AND (' . implode(' OR ', $clauses) . ')';
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        $found = $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
        $foundKeys = array_map(
            static fn(array $row): string => $row['table_name'] . '.' . $row['index_name'],
            $found
        );
        $missing = [];
        foreach ($indexes as $item) {
            $key = $item['table'] . '.' . $item['index'];
            if (!in_array($key, $foundKeys, true)) {
                $missing[] = $key;
            }
        }
        return $missing;
    }
}
