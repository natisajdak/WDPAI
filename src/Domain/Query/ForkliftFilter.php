<?php

declare(strict_types=1);

namespace App\Domain\Query;

use App\Domain\Enum\ForkliftStatus;
use App\Domain\Enum\FuelType;

/**
 * Filtry listy wózków z query stringa, np. /forklifts?status=AVAILABLE&warehouse=1&min_load=2000&q=linde&page=2.
 *
 * Bez filtra statusu lista pomija wózki wycofane (RETIRED), bo operatorom nie są potrzebne.
 * Niepoprawna wartość któregokolwiek parametru kończy się wyjątkiem (kontroler zwraca 400).
 */
final class ForkliftFilter
{
    public const PER_PAGE = 6;
    public const SORTS = ['code', 'load', 'udt'];

    private const MAX_SEARCH_LENGTH = 50;
    private const MAX_LOAD_KG = 100_000;
    private const MAX_PAGE = 10_000;

    public function __construct(
        public readonly ?ForkliftStatus $status = null,
        public readonly ?int $warehouseId = null,
        public readonly ?FuelType $fuelType = null,
        public readonly ?int $minLoadKg = null,
        public readonly ?string $search = null,
        public readonly string $sort = 'code',
        public readonly int $page = 1,
    ) {
    }

    /**
     * @param array<string, string> $query
     */
    public static function fromQuery(array $query): self
    {
        $value = static fn (string $key): ?string => isset($query[$key]) && trim($query[$key]) !== '' ? trim($query[$key]) : null;

        $status = $value('status');
        $fuel = $value('fuel');
        $search = $value('q');
        $sort = $value('sort') ?? 'code';

        if (!in_array($sort, self::SORTS, true)) {
            throw new InvalidFilterException('sort');
        }

        return new self(
            status: $status === null ? null : (ForkliftStatus::tryFrom($status) ?? throw new InvalidFilterException('status')),
            warehouseId: self::intParam($value('warehouse'), 'warehouse', 1, PHP_INT_MAX),
            fuelType: $fuel === null ? null : (FuelType::tryFrom($fuel) ?? throw new InvalidFilterException('fuel')),
            minLoadKg: self::intParam($value('min_load'), 'min_load', 0, self::MAX_LOAD_KG),
            search: $search === null ? null : mb_substr($search, 0, self::MAX_SEARCH_LENGTH),
            sort: $sort,
            page: self::intParam($value('page'), 'page', 1, self::MAX_PAGE) ?? 1,
        );
    }

    /**
     * Wzorzec dla ILIKE. Znaki % i _ z wyszukiwanej frazy są zwykłymi znakami, a nie symbolami wieloznacznymi.
     */
    public function searchPattern(): ?string
    {
        return $this->search === null ? null : '%' . addcslashes($this->search, '%_\\') . '%';
    }

    /**
     * Parametry do linków (np. paginacji), żeby przejście na inną stronę nie gubiło filtrów.
     *
     * @return array<string, string|int|null>
     */
    public function toQuery(): array
    {
        return [
            'status' => $this->status?->value,
            'warehouse' => $this->warehouseId,
            'fuel' => $this->fuelType?->value,
            'min_load' => $this->minLoadKg,
            'q' => $this->search,
            'sort' => $this->sort === 'code' ? null : $this->sort,
        ];
    }

    public function isFiltered(): bool
    {
        return array_filter($this->toQuery(), static fn (string|int|null $v): bool => $v !== null) !== [];
    }

    private static function intParam(?string $raw, string $name, int $min, int $max): ?int
    {
        if ($raw === null) {
            return null;
        }

        $int = filter_var($raw, FILTER_VALIDATE_INT, ['options' => ['min_range' => $min, 'max_range' => $max]]);

        return $int === false ? throw new InvalidFilterException($name) : $int;
    }
}
