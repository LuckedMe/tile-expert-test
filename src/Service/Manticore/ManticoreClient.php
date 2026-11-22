<?php

declare(strict_types=1);

namespace App\Service\Manticore;

use App\Exception\CommunicationException;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Contracts\HttpClient\Exception\ExceptionInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;

use function array_map;
use function implode;
use function is_array;
use function is_string;
use function json_decode;
use function json_encode;

use const JSON_THROW_ON_ERROR;

/**
 * Thin client of the Manticore HTTP JSON API built on Symfony HttpClient.
 */
final readonly class ManticoreClient
{
    public function __construct(
        private HttpClientInterface $httpClient,
        #[Autowire('%env(string:MANTICORE_URL)%')]
        private string $baseUrl,
    ) {
    }

    /**
     * Executes a raw SQL statement, e.g. CREATE TABLE.
     */
    public function sql(string $query): array
    {
        return $this->request('/sql?mode=raw', ['body' => ['query' => $query]]);
    }

    /**
     * @param array<string, mixed> $body
     */
    public function search(array $body): array
    {
        return $this->request('/search', ['json' => $body]);
    }

    /**
     * @param list<array{id: int, doc: array<string, mixed>}> $documents
     */
    public function bulkReplace(string $table, array $documents): void
    {
        if (!$documents) {
            return;
        }

        $lines = array_map(
            static fn (array $d): string => json_encode(
                ['replace' => ['table' => $table, 'id' => $d['id'], 'doc' => $d['doc']]],
                JSON_THROW_ON_ERROR
            ),
            $documents
        );

        $response = $this->request('/bulk', [
            'body'    => implode("\n", $lines) . "\n",
            'headers' => ['Content-Type' => 'application/x-ndjson'],
        ]);

        if (!empty($response['errors'])) {
            throw new CommunicationException('Manticore bulk error: ' . json_encode($response, JSON_THROW_ON_ERROR));
        }
    }

    private function request(string $path, array $options): array
    {
        try {
            $response = $this->httpClient->request('POST', $this->baseUrl . $path, $options + ['timeout' => 5]);
            $content  = $response->getContent(false);
            $data     = json_decode($content, true);
        } catch (ExceptionInterface $e) {
            throw CommunicationException::createFromPrevious($e);
        }

        if (!is_array($data)) {
            throw new CommunicationException('Manticore returned an invalid response: ' . $content);
        }

        // /sql?mode=raw answers with a list of result sets
        $result = $data[0] ?? $data;

        if (!empty($result['error']) && is_string($result['error'])) {
            throw new CommunicationException('Manticore error: ' . $result['error']);
        }

        return $data;
    }
}
