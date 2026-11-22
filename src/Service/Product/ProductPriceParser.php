<?php

declare(strict_types=1);

namespace App\Service\Product;

use DOMDocument;
use DOMXPath;

use function is_array;
use function is_numeric;
use function is_string;
use function json_decode;
use function libxml_use_internal_errors;
use function preg_replace;
use function str_contains;
use function str_replace;
use function trim;

/**
 * Extracts the EUR price from a tile.expert product page.
 * Tries structured data first (JSON-LD, microdata, Open Graph) as it is the most stable part of the markup.
 */
final class ProductPriceParser
{
    public function parse(string $html): ?float
    {
        $previous = libxml_use_internal_errors(true);
        $document = new DOMDocument();
        $document->loadHTML('<?xml encoding="UTF-8">' . $html);
        libxml_use_internal_errors($previous);

        $xpath = new DOMXPath($document);

        return $this->fromJsonLd($xpath)
            ?? $this->fromAttribute($xpath, '//meta[@property="product:price:amount" or @property="og:price:amount"]', 'content')
            ?? $this->fromAttribute($xpath, '//*[@itemprop="price"]', 'content')
            ?? $this->fromText($xpath, '//*[@itemprop="price"]');
    }

    private function fromJsonLd(DOMXPath $xpath): ?float
    {
        foreach ($xpath->query('//script[@type="application/ld+json"]') ?: [] as $node) {
            $data = json_decode($node->textContent, true);

            if (is_array($data) && null !== ($price = $this->findOfferPrice($data))) {
                return $price;
            }
        }

        return null;
    }

    private function findOfferPrice(array $data): ?float
    {
        foreach ($data as $key => $value) {
            if (($key === 'price' || $key === 'lowPrice') && null !== ($price = $this->toFloat($value))) {
                return $price;
            }

            if (is_array($value) && null !== ($price = $this->findOfferPrice($value))) {
                return $price;
            }
        }

        return null;
    }

    private function fromAttribute(DOMXPath $xpath, string $query, string $attribute): ?float
    {
        foreach ($xpath->query($query) ?: [] as $node) {
            if (null !== ($price = $this->toFloat($node->attributes?->getNamedItem($attribute)?->nodeValue))) {
                return $price;
            }
        }

        return null;
    }

    private function fromText(DOMXPath $xpath, string $query): ?float
    {
        foreach ($xpath->query($query) ?: [] as $node) {
            if (null !== ($price = $this->toFloat($node->textContent))) {
                return $price;
            }
        }

        return null;
    }

    private function toFloat(mixed $value): ?float
    {
        if (is_numeric($value)) {
            return (float) $value;
        }

        if (!is_string($value)) {
            return null;
        }

        $value = preg_replace('/[^\d.,]/', '', $value) ?? '';

        // "1.234,56" -> "1234.56"; "59,99" -> "59.99"
        if (str_contains($value, ',')) {
            $value = str_replace(',', '.', str_replace('.', '', $value));
        }

        return is_numeric(trim($value)) ? (float) $value : null;
    }
}
