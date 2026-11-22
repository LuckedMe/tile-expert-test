<?php

declare(strict_types=1);

namespace App\DTO\Collection;

use App\Contract\Dictionary\OrderGroupBy;
use App\DTOFactory\Collection\OrderCriteriaDTOFactory;

use function array_keys;

/**
 * @see OrderCriteriaDTOFactory
 */
final readonly class OrderCriteriaDTO
{
    public const string FILTER_QUERY     = 'query';
    public const string FILTER_GROUP_BY  = 'group_by';
    public const string FILTER_START_DAY = 'start_day';
    public const string FILTER_END_DAY   = 'end_day';

    public const array FILTERS = [
        self::FILTER_QUERY     => true,
        self::FILTER_GROUP_BY  => true,
        self::FILTER_START_DAY => true,
        self::FILTER_END_DAY   => true,
    ];

    public const string SORT_GROUP = 'group';
    public const string SORT_COUNT = 'count';

    public const array SORT_BY = [
        // front-end => sql alias
        self::SORT_GROUP => 'grp',
        self::SORT_COUNT => 'cnt',
    ];

    public function __construct(
        public OrderGroupBy $groupBy = OrderGroupBy::Day,
        public int $page = 1,
        public int $perPage = 10,
        public ?string $query = null,
        public ?string $startDay = null,
        public ?string $endDay = null,
        public string $orderBy = self::SORT_GROUP,
        public string $orderDirection = 'desc',
        /** @var int[]|null restricts the orders to these ids (result of the search), null - no restriction */
        public ?array $orderIds = null,
    ) {
    }

    /** @param int[]|null $orderIds */
    public function withOrderIds(?array $orderIds): self
    {
        return new self(
            $this->groupBy,
            $this->page,
            $this->perPage,
            $this->query,
            $this->startDay,
            $this->endDay,
            $this->orderBy,
            $this->orderDirection,
            $orderIds,
        );
    }

    /** @return string[] */
    public function availableSortBy(): array
    {
        return array_keys(self::SORT_BY);
    }

    public function availableFilters(): array
    {
        return [
            ...self::FILTERS,
            self::FILTER_GROUP_BY => OrderGroupBy::values(),
        ];
    }

    public function appliedFilters(): array
    {
        return [
            self::FILTER_QUERY     => $this->query,
            self::FILTER_GROUP_BY  => $this->groupBy->value,
            self::FILTER_START_DAY => $this->startDay,
            self::FILTER_END_DAY   => $this->endDay,
        ];
    }

    public function sort(): string
    {
        return $this->orderBy . ' ' . $this->orderDirection;
    }

    public function sqlOrderBy(): string
    {
        return self::SORT_BY[$this->orderBy] ?? self::SORT_BY[self::SORT_GROUP];
    }
}
