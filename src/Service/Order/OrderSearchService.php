<?php

declare(strict_types=1);

namespace App\Service\Order;

use App\Exception\CommunicationException;
use App\Service\Manticore\ManticoreClient;
use Symfony\Component\DependencyInjection\Attribute\Autowire;

use function array_filter;
use function array_map;
use function array_values;
use function implode;
use function preg_replace;
use function preg_split;
use function str_contains;
use function trim;

/**
 * Full-text search of orders in Manticore with "*" wildcards.
 *
 *  - "*" or empty query  -> no filtering (returns null);
 *  - "foo"               -> *foo* (matches inside the words);
 *  - "foo* bar"          -> user wildcards are kept as they are; all terms must match.
 */
final readonly class OrderSearchService
{
    public const int MAX_MATCHES = 10000;

    public function __construct(
        private ManticoreClient $client,
        #[Autowire('%env(string:MANTICORE_ORDERS_TABLE)%')]
        private string $table,
    ) {
    }

    /**
     * @return int[]|null order ids, or null when the query does not restrict the result
     *
     * @throws CommunicationException when Manticore is unavailable
     */
    public function findIds(?string $query): ?array
    {
        $expression = $this->buildExpression((string) $query);

        if ($expression === null) {
            return null;
        }

        $response = $this->client->search([
            'table'   => $this->table,
            'query'   => ['query_string' => $expression],
            'limit'   => self::MAX_MATCHES,
            'options' => ['max_matches' => self::MAX_MATCHES],
            '_source' => 'id',
        ]);

        return array_map(
            static fn (array $hit): int => (int) $hit['_id'],
            $response['hits']['hits'] ?? []
        );
    }

    public function buildExpression(string $query): ?string
    {
        $terms = array_filter(
            preg_split('/\s+/', trim($query)) ?: [],
            static fn (string $t): bool => $t !== '' && $t !== '*'
        );

        if (!$terms) {
            return null;
        }

        return implode(' ', array_map(
            static function (string $term): string {
                // escape the query_string operators; "*" and "?" stay wildcards
                $term = preg_replace('/([\\\\()|\-!@~"&\/^$=<\'])/', '\\\\$1', $term) ?? $term;

                return str_contains($term, '*') ? $term : '*' . $term . '*';
            },
            array_values($terms)
        ));
    }
}
