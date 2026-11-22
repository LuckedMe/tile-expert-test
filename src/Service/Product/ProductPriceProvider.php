<?php

declare(strict_types=1);

namespace App\Service\Product;

use App\Exception\CommunicationException;
use App\Exception\Validation\EntityNotFoundException;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Contracts\Cache\CacheInterface;
use Symfony\Contracts\Cache\ItemInterface;
use Symfony\Contracts\HttpClient\Exception\ExceptionInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

use function hash;
use function sprintf;

final readonly class ProductPriceProvider
{
    public function __construct(
        private HttpClientInterface $httpClient,
        private CacheInterface $cache,
        private ProductPriceParser $parser,
        private TranslatorInterface $translator,
        #[Autowire('%env(string:PRODUCT_PAGE_URL_TEMPLATE)%')]
        private string $urlTemplate,
        #[Autowire('%env(int:PRODUCT_PRICE_CACHE_TTL)%')]
        private int $cacheTtl,
    ) {
    }

    /**
     * @throws EntityNotFoundException when the product page or its price does not exist
     * @throws CommunicationException  when the upstream site fails
     */
    public function getPrice(string $factory, string $collection, string $article): float
    {
        $url = sprintf($this->urlTemplate, $factory, $collection, $article);

        return $this->cache->get(
            'product_price_' . hash('xxh3', $url),
            function (ItemInterface $item) use ($url): float {
                $item->expiresAfter($this->cacheTtl);

                return $this->fetch($url);
            }
        );
    }

    private function fetch(string $url): float
    {
        try {
            $response = $this->httpClient->request('GET', $url, ['timeout' => 10]);

            if ($response->getStatusCode() === 404) {
                throw new EntityNotFoundException($this->translator->trans('PRODUCT.NOT_FOUND', [], 'validators'));
            }

            $html = $response->getContent();
        } catch (ExceptionInterface $e) {
            throw CommunicationException::createFromPrevious($e);
        }

        $price = $this->parser->parse($html);

        if ($price === null) {
            throw new EntityNotFoundException($this->translator->trans('PRODUCT.PRICE_NOT_FOUND', [], 'validators'));
        }

        return $price;
    }
}
