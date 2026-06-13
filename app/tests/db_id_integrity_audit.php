<?php
declare(strict_types=1);

if (PHP_SAPI !== 'cli') {
    http_response_code(403);
    echo "CLI only\n";
    exit(1);
}

$rootDir = dirname(__DIR__, 2);
chdir($rootDir);

$config = require $rootDir . '/includes/config.php';
require_once $rootDir . '/includes/db.php';

/** @var PDO $pdo */
try {
    $pdo = db($config['db']);
} catch (Throwable $e) {
    fwrite(STDERR, "DB integrity audit FAILED\n");
    fwrite(STDERR, "Database connection error: " . $e->getMessage() . "\n");
    exit(1);
}

$schema = (string) $pdo->query('SELECT DATABASE()')->fetchColumn();
if ($schema === '') {
    fwrite(STDERR, "No active database selected.\n");
    exit(1);
}

/**
 * @param list<array<string,mixed>> $rows
 */
function print_rows(string $title, array $rows, array $columns): void
{
    echo $title . "\n";
    if ($rows === []) {
        echo "  - none\n";
        return;
    }

    foreach ($rows as $row) {
        $parts = [];
        foreach ($columns as $column) {
            $value = $row[$column] ?? '';
            if (is_bool($value)) {
                $value = $value ? 'yes' : 'no';
            }
            $parts[] = $column . '=' . (string) $value;
        }
        echo '  - ' . implode(', ', $parts) . "\n";
    }
}

/**
 * @return list<string>
 */
function fetch_column_names(PDO $pdo, string $schema, string $table): array
{
    $stmt = $pdo->prepare(
        'SELECT COLUMN_NAME
         FROM INFORMATION_SCHEMA.COLUMNS
         WHERE TABLE_SCHEMA = :schema AND TABLE_NAME = :table
         ORDER BY ORDINAL_POSITION'
    );
    $stmt->execute([
        ':schema' => $schema,
        ':table' => $table,
    ]);

    $names = [];
    foreach ($stmt->fetchAll(PDO::FETCH_COLUMN) as $name) {
        $names[] = (string) $name;
    }

    return $names;
}

/**
 * @return array<int,array{table_name:string, has_primary_id:bool, auto_increment:bool, total_rows:int, distinct_ids:int, zero_id_rows:int, duplicate_extra_rows:int}>
 */
function audit_id_columns(PDO $pdo, string $schema): array
{
    $stmt = $pdo->prepare(
        'SELECT c.TABLE_NAME,
                c.EXTRA,
                EXISTS(
                    SELECT 1
                    FROM INFORMATION_SCHEMA.KEY_COLUMN_USAGE k
                    WHERE k.TABLE_SCHEMA = c.TABLE_SCHEMA
                      AND k.TABLE_NAME = c.TABLE_NAME
                      AND k.COLUMN_NAME = c.COLUMN_NAME
                      AND k.CONSTRAINT_NAME = \'PRIMARY\'
                ) AS has_primary_id
         FROM INFORMATION_SCHEMA.COLUMNS c
         WHERE c.TABLE_SCHEMA = :schema
           AND c.COLUMN_NAME = \'id\'
         ORDER BY c.TABLE_NAME'
    );
    $stmt->execute([':schema' => $schema]);

    $results = [];
    foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
        $table = (string) $row['TABLE_NAME'];
        $stats = $pdo->query(
            'SELECT COUNT(*) AS total_rows,
                    COUNT(DISTINCT id) AS distinct_ids,
                    COALESCE(SUM(id = 0), 0) AS zero_id_rows
             FROM `' . str_replace('`', '``', $table) . '`'
        )->fetch(PDO::FETCH_ASSOC);

        $duplicateExtraRows = $pdo->query(
            'SELECT COALESCE(SUM(dup.cnt - 1), 0) AS duplicate_extra_rows
             FROM (
                 SELECT COUNT(*) AS cnt
                 FROM `' . str_replace('`', '``', $table) . '`
                 GROUP BY id
                 HAVING COUNT(*) > 1
             ) dup'
        )->fetchColumn();

        $results[] = [
            'table_name' => $table,
            'has_primary_id' => (bool) $row['has_primary_id'],
            'auto_increment' => str_contains(strtolower((string) $row['EXTRA']), 'auto_increment'),
            'total_rows' => (int) ($stats['total_rows'] ?? 0),
            'distinct_ids' => (int) ($stats['distinct_ids'] ?? 0),
            'zero_id_rows' => (int) ($stats['zero_id_rows'] ?? 0),
            'duplicate_extra_rows' => (int) $duplicateExtraRows,
        ];
    }

    return $results;
}

/**
 * @return array<int,array{table_name:string, column_name:string, zero_ref_rows:int}>
 */
function audit_zero_reference_columns(PDO $pdo, string $schema): array
{
    $stmt = $pdo->prepare(
        'SELECT TABLE_NAME, COLUMN_NAME
         FROM INFORMATION_SCHEMA.COLUMNS
         WHERE TABLE_SCHEMA = :schema
           AND COLUMN_NAME LIKE :pattern
           AND DATA_TYPE IN (\'tinyint\', \'smallint\', \'mediumint\', \'int\', \'bigint\')
         ORDER BY TABLE_NAME, ORDINAL_POSITION'
    );
    $stmt->execute([
        ':schema' => $schema,
        ':pattern' => '%\\_id',
    ]);

    $results = [];
    foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
        $table = (string) $row['TABLE_NAME'];
        $column = (string) $row['COLUMN_NAME'];
        $count = $pdo->query(
            'SELECT COUNT(*) FROM `' . str_replace('`', '``', $table) . '`
             WHERE `' . str_replace('`', '``', $column) . '` = 0'
        )->fetchColumn();

        $count = (int) $count;
        if ($count > 0) {
            $results[] = [
                'table_name' => $table,
                'column_name' => $column,
                'zero_ref_rows' => $count,
            ];
        }
    }

    return $results;
}

/**
 * @return array<int,array{table_name:string, id_value:int, rows_with_same_id:int}>
 */
function audit_duplicate_id_values(PDO $pdo, array $idAudit): array
{
    $results = [];
    foreach ($idAudit as $row) {
        $table = (string) $row['table_name'];
        if ((int) ($row['duplicate_extra_rows'] ?? 0) < 1) {
            continue;
        }

        $stmt = $pdo->query(
            'SELECT id, COUNT(*) AS cnt
             FROM `' . str_replace('`', '``', $table) . '`
             GROUP BY id
             HAVING COUNT(*) > 1
             ORDER BY cnt DESC, id ASC'
        );

        foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $dup) {
            $results[] = [
                'table_name' => $table,
                'id_value' => (int) ($dup['id'] ?? 0),
                'rows_with_same_id' => (int) ($dup['cnt'] ?? 0),
            ];
        }
    }

    return $results;
}

$idAudit = audit_id_columns($pdo, $schema);
$zeroRefAudit = audit_zero_reference_columns($pdo, $schema);
$duplicateIds = audit_duplicate_id_values($pdo, $idAudit);

$idWithoutPrimary = [];
$idWithoutAutoIncrement = [];
$idWithZeroRows = [];

foreach ($idAudit as $row) {
    if (!$row['has_primary_id']) {
        $idWithoutPrimary[] = $row;
    }
    if (!$row['auto_increment']) {
        $idWithoutAutoIncrement[] = $row;
    }
    if ($row['zero_id_rows'] > 0) {
        $idWithZeroRows[] = $row;
    }
}

$summary = [
    'tables_with_id' => count($idAudit),
    'tables_without_primary_id' => count($idWithoutPrimary),
    'tables_without_auto_increment' => count($idWithoutAutoIncrement),
    'tables_with_zero_id_rows' => count($idWithZeroRows),
    'columns_with_zero_references' => count($zeroRefAudit),
    'tables_with_duplicate_ids' => count(array_unique(array_map(
        static fn(array $row): string => (string) $row['table_name'],
        $duplicateIds
    ))),
];

echo "DB integrity audit for schema={$schema}\n";
echo "Summary\n";
foreach ($summary as $key => $value) {
    echo '  - ' . $key . '=' . $value . "\n";
}
echo "\n";

print_rows('Tables with id but no PRIMARY KEY on id', $idWithoutPrimary, ['table_name', 'total_rows', 'zero_id_rows', 'duplicate_extra_rows']);
echo "\n";
print_rows('Tables with id but no AUTO_INCREMENT', $idWithoutAutoIncrement, ['table_name', 'has_primary_id', 'total_rows', 'zero_id_rows', 'duplicate_extra_rows']);
echo "\n";
print_rows('Tables containing rows with id=0', $idWithZeroRows, ['table_name', 'total_rows', 'zero_id_rows', 'duplicate_extra_rows']);
echo "\n";
print_rows('Duplicate id values', $duplicateIds, ['table_name', 'id_value', 'rows_with_same_id']);
echo "\n";
print_rows('Columns with *_id = 0', $zeroRefAudit, ['table_name', 'column_name', 'zero_ref_rows']);

$hasIssues = $idWithoutPrimary !== []
    || $idWithoutAutoIncrement !== []
    || $idWithZeroRows !== []
    || $duplicateIds !== []
    || $zeroRefAudit !== [];

exit($hasIssues ? 1 : 0);
