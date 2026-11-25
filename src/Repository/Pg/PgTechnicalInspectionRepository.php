<?php

declare(strict_types=1);

namespace App\Repository\Pg;

use App\Database\Database;
use App\Domain\Entity\TechnicalInspection;
use App\Repository\Contract\TechnicalInspectionRepositoryInterface;
use DateTimeImmutable;

final class PgTechnicalInspectionRepository implements TechnicalInspectionRepositoryInterface
{
    public function __construct(private readonly Database $database)
    {
    }

    public function findByForklift(int $forkliftId): array
    {
        $rows = $this->database->fetchAll(
            <<<'SQL'
                SELECT id, forklift_id, inspection_date, valid_until, protocol_number, notes
                  FROM technical_inspections
                 WHERE forklift_id = :forklift_id
                 ORDER BY valid_until DESC
                SQL,
            ['forklift_id' => $forkliftId],
        );

        return array_map(
            static fn (array $row): TechnicalInspection => new TechnicalInspection(
                id: (int) $row['id'],
                forkliftId: (int) $row['forklift_id'],
                inspectionDate: new DateTimeImmutable((string) $row['inspection_date']),
                validUntil: new DateTimeImmutable((string) $row['valid_until']),
                protocolNumber: (string) $row['protocol_number'],
                notes: $row['notes'] === null ? null : (string) $row['notes'],
            ),
            $rows,
        );
    }
}
