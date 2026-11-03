<?php

namespace App\Services;

use DOMDocument;
use DOMXPath;
use RuntimeException;

class ImdbTop250Parser
{
    public function parse(string $html): array
    {
        $previous = libxml_use_internal_errors(true);
        try {
            $document = new DOMDocument();
            $document->loadHTML('<?xml encoding="UTF-8">'.$html, LIBXML_NONET | LIBXML_NOERROR | LIBXML_NOWARNING);
            $xpath = new DOMXPath($document);
            $rows = [];
            foreach ($xpath->query('//script[@id="__NEXT_DATA__"]') as $script) {
                $data = json_decode($script->textContent, true);
                $edges = $this->findChart(is_array($data) ? $data : []);
                if ($edges) {
                    foreach ($edges as $index => $edge) {
                        $node = $edge['node'];
                        $rows[] = [
                            'position' => $edge['currentRank'] ?? $index + 1,
                            'imdb_id' => $node['id'] ?? '',
                            'name' => $node['titleText']['text'] ?? '',
                            'year' => $node['releaseYear']['year'] ?? null,
                            'rating' => $node['ratingsSummary']['aggregateRating'] ?? null,
                            'image_url' => $node['primaryImage']['url'] ?? null,
                        ];
                    }
                    break;
                }
            }
            if (!$rows) {
                foreach ($xpath->query('//li[contains(concat(" ", normalize-space(@class), " "), " ipc-metadata-list-summary-item ")]') as $item) {
                    $link = $xpath->query('.//a[contains(@class,"ipc-title-link-wrapper")]', $item)->item(0);
                    if (!$link || !preg_match('~/title/(tt\d+)/~', $link->getAttribute('href'), $code)) {
                        continue;
                    }
                    $title = trim($link->textContent);
                    // Compact chart uses a separate #rank, expanded chart prefixes the title.
                    preg_match('/^(\d+)\.\s*/u', $title, $prefix);
                    $position = isset($prefix[1]) ? (int) $prefix[1] : null;
                    $title = preg_replace('/^\d+\.\s*/u', '', $title);
                    $year = null;
                    foreach ($xpath->query('.//text()[not(ancestor::a)]', $item) as $text) {
                        if (preg_match('/^#(\d+)$/', trim($text->nodeValue), $rank)) {
                            $position = (int) $rank[1];
                        }
                        if (preg_match('/^((?:18|19|20|21)\d{2})$/', trim($text->nodeValue), $match)) {
                            $year = (int) $match[1];
                        }
                    }
                    $image = $xpath->query('.//img', $item)->item(0);
                    $url = $image ? $image->getAttribute('src') : null;
                    if ($image && preg_match_all('/(\S+)\s+(\d+)w/', $image->getAttribute('srcset'), $sizes, PREG_SET_ORDER)) {
                        usort($sizes, fn ($a, $b) => (int) $b[2] <=> (int) $a[2]);
                        $url = $sizes[0][1];
                    }
                    $ratingNode = $xpath->query('.//*[contains(@class,"ipc-rating-star--rating")]', $item)->item(0);
                    $rows[] = [
                        'position' => $position,
                        'imdb_id' => $code[1],
                        'name' => $title,
                        'year' => $year,
                        'rating' => $ratingNode ? trim($ratingNode->textContent) : null,
                        'image_url' => $url,
                    ];
                }
            }
            return $this->validate($rows);
        } finally {
            libxml_clear_errors();
            libxml_use_internal_errors($previous);
        }
    }

    private function findChart(array $data): array
    {
        if (isset($data['chartTitles']['edges']) && is_array($data['chartTitles']['edges'])) {
            return $data['chartTitles']['edges'];
        }
        foreach ($data as $value) {
            if (is_array($value) && ($edges = $this->findChart($value))) {
                return $edges;
            }
        }
        return [];
    }

    private function validate(array $rows): array
    {
        if (count($rows) !== 250) {
            throw new RuntimeException('IMDb returned '.count($rows).' movies; expected the complete Top 250. No movies were updated.');
        }
        foreach ($rows as &$row) {
            if (!is_numeric($row['position']) || !preg_match('/^tt\d{5,12}$/', $row['imdb_id'])
                || !is_string($row['name']) || trim($row['name']) === '' || mb_strlen($row['name']) > 512
                || !is_numeric($row['year']) || $row['year'] < 1880 || $row['year'] > 2155) {
                throw new RuntimeException('The IMDb chart contains incomplete movie details. No movies were updated.');
            }
            $row['position'] = (int) $row['position'];
            $row['year'] = (int) $row['year'];
            $row['name'] = trim($row['name']);
            $rating = $row['rating'];
            if ($rating !== null && $rating !== '' && (!is_numeric($rating) || $rating < 0 || $rating > 10)) {
                throw new RuntimeException('The IMDb chart contains an invalid rating. No movies were updated.');
            }
            $row['rating'] = $rating === null || $rating === '' ? null : (float) $rating;
            $url = $row['image_url'];
            $row['image_url'] = is_string($url) && strlen($url) <= 4096
                && filter_var($url, FILTER_VALIDATE_URL) && parse_url($url, PHP_URL_SCHEME) === 'https'
                && parse_url($url, PHP_URL_HOST) === 'm.media-amazon.com' ? $url : null;
        }
        unset($row);
        usort($rows, fn ($a, $b) => $a['position'] <=> $b['position']);
        if (array_column($rows, 'position') !== range(1, 250) || count(array_unique(array_column($rows, 'imdb_id'))) !== 250) {
            throw new RuntimeException('IMDb returned duplicate movies or incomplete chart positions. No movies were updated.');
        }
        return $rows;
    }
}
