<?php

declare(strict_types=1);

namespace App\Database;

use BackedEnum;
use DateTimeInterface;
use PDO;
use PDOStatement;
use Throwable;

/**
 * Cienka warstwa nad PDO: wyłącznie zapytania z parametrami i transakcje.
 * Repozytoria nie dostają surowego PDO, więc nie da się w nich „skleić” SQL z danymi użytkownika.
 */
final class Database implements TransactionRunner
{
    private ?PDO $pdo = null;

    public function __construct(
        private readonly string $dsn,
        private readonly string $user,
        private readonly string $password,
        private readonly string $timezone,
    ) {
    }

    /**
     * @param array<string, mixed> $params
     * @return array<string, mixed>|null
     */
    public function fetchOne(string $sql, array $params = []): ?array
    {
        $row = $this->run($sql, $params)->fetch();

        return $row === false ? null : $row;
    }

    /**
     * @param array<string, mixed> $params
     * @return list<array<string, mixed>>
     */
    public function fetchAll(string $sql, array $params = []): array
    {
        return $this->run($sql, $params)->fetchAll();
    }

    /**
     * Pierwsza kolumna pierwszego wiersza, np. wynik COUNT(*) albo id z RETURNING.
     *
     * @param array<string, mixed> $params
     */
    public function fetchValue(string $sql, array $params = []): mixed
    {
        $value = $this->run($sql, $params)->fetchColumn();

        return $value === false ? null : $value;
    }

    /**
     * @param array<string, mixed> $params
     * @return int liczba zmienionych wierszy
     */
    public function execute(string $sql, array $params = []): int
    {
        return $this->run($sql, $params)->rowCount();
    }

    /**
     * Wykonuje operację w transakcji: zatwierdza po sukcesie, wycofuje przy wyjątku.
     * Wywołanie zagnieżdżone dołącza do już otwartej transakcji.
     *
     * @template T
     * @param callable(): T $operation
     * @return T
     */
    public function transactional(callable $operation): mixed
    {
        $pdo = $this->pdo();

        if ($pdo->inTransaction()) {
            return $operation();
        }

        $pdo->beginTransaction();

        try {
            $result = $operation();
            $pdo->commit();

            return $result;
        } catch (Throwable $e) {
            $pdo->rollBack();

            throw $e;
        }
    }

    /**
     * @param array<string, mixed> $params
     */
    private function run(string $sql, array $params): PDOStatement
    {
        $statement = $this->pdo()->prepare($sql);

        foreach ($params as $name => $value) {
            [$bound, $type] = $this->normalize($value);
            $statement->bindValue(':' . ltrim($name, ':'), $bound, $type);
        }

        $statement->execute();

        return $statement;
    }

    /**
     * @return array{0: mixed, 1: int}
     */
    private function normalize(mixed $value): array
    {
        return match (true) {
            $value === null => [null, PDO::PARAM_NULL],
            is_bool($value) => [$value, PDO::PARAM_BOOL],
            is_int($value) => [$value, PDO::PARAM_INT],
            $value instanceof DateTimeInterface => [$value->format(DateTimeInterface::ATOM), PDO::PARAM_STR],
            $value instanceof BackedEnum => [$value->value, is_int($value->value) ? PDO::PARAM_INT : PDO::PARAM_STR],
            default => [(string) $value, PDO::PARAM_STR],
        };
    }

    private function pdo(): PDO
    {
        if ($this->pdo === null) {
            // Połączenie powstaje dopiero przy pierwszym zapytaniu, więc strony bez bazy go nie otwierają.
            $this->pdo = new PDO($this->dsn, $this->user, $this->password, [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false,
                PDO::ATTR_STRINGIFY_FETCHES => false,
            ]);
            $this->run('SELECT set_config(\'TimeZone\', :timezone, false)', ['timezone' => $this->timezone]);
        }

        return $this->pdo;
    }
}
