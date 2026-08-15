<?php

namespace App\Services\Internet;

use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\RequestException;
use Illuminate\Support\Facades\Http;
use RuntimeException;

class BookmarkPreviewService
{
    private const UserAgent = 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/125.0.0.0 Safari/537.36';

    public function preview(string $url): array
    {
        $url = $this->normalizeUrl($url);

        $html = $this->fetchHtml($url);

        $metadata = $this->extractMetadata($html, $url);

        return array_merge([
            'url' => $url,
            'domain' => parse_url($url, PHP_URL_HOST),
            'fetched_at' => now()->toIso8601String(),
        ], $metadata);
    }

    public function normalizeUrl(string $url): string
    {
        $url = trim($url);

        if (! preg_match('#^https?://#i', $url)) {
            $url = 'https://'.$url;
        }

        $parsed = parse_url($url);

        if (! in_array($parsed['scheme'] ?? '', ['http', 'https'], true)) {
            throw new RuntimeException('URL harus menggunakan skema http atau https.');
        }

        if (! isset($parsed['host'])) {
            throw new RuntimeException('URL tidak valid.');
        }

        return $url;
    }

    private function fetchHtml(string $url): string
    {
        try {
            $response = Http::timeout(10)
                ->retry(2, 500)
                ->withUserAgent(self::UserAgent)
                ->withOptions([
                    'allow_redirects' => ['max' => 5],
                    'verify' => false,
                ])
                ->get($url);

            $response->throw();

            return $response->body();
        } catch (ConnectionException) {
            throw new RuntimeException('Tidak dapat terhubung ke URL.');
        } catch (RequestException) {
            throw new RuntimeException('Gagal mengambil data dari URL.');
        }
    }

    private function extractMetadata(string $html, string $url): array
    {
        $og = $this->parseOpenGraph($html);
        $twitter = $this->parseTwitterCards($html);
        $fallback = $this->parseFallback($html);

        $title = $og['title'] ?? $twitter['title'] ?? $fallback['title'] ?? null;
        $description = $og['description'] ?? $twitter['description'] ?? $fallback['description'] ?? null;
        $image = $og['image'] ?? $twitter['image'] ?? $fallback['image'] ?? null;
        $siteName = $og['site_name'] ?? null;

        if ($image && ! preg_match('#^https?://#i', $image)) {
            $image = $this->resolveUrl($image, $url);
        }

        $favicon = $this->extractFavicon($html, $url);

        return [
            'title' => $title ? mb_substr(strip_tags($title), 0, 500) : null,
            'description' => $description ? mb_substr(strip_tags($description), 0, 1000) : null,
            'image_url' => $image,
            'favicon_url' => $favicon,
            'site_name' => $siteName,
        ];
    }

    private function parseOpenGraph(string $html): array
    {
        $data = [];

        if (preg_match('/<meta\s+[^>]*property=["\']og:title["\'][^>]*content=["\']([^"\']*)["\'][^>]*\/?>/i', $html, $m)) {
            $data['title'] = $m[1];
        } elseif (preg_match('/<meta\s+[^>]*content=["\']([^"\']*)["\'][^>]*property=["\']og:title["\'][^>]*\/?>/i', $html, $m)) {
            $data['title'] = $m[1];
        }

        if (preg_match('/<meta\s+[^>]*property=["\']og:description["\'][^>]*content=["\']([^"\']*)["\'][^>]*\/?>/i', $html, $m)) {
            $data['description'] = $m[1];
        } elseif (preg_match('/<meta\s+[^>]*content=["\']([^"\']*)["\'][^>]*property=["\']og:description["\'][^>]*\/?>/i', $html, $m)) {
            $data['description'] = $m[1];
        }

        if (preg_match('/<meta\s+[^>]*property=["\']og:image["\'][^>]*content=["\']([^"\']*)["\'][^>]*\/?>/i', $html, $m)) {
            $data['image'] = $m[1];
        } elseif (preg_match('/<meta\s+[^>]*content=["\']([^"\']*)["\'][^>]*property=["\']og:image["\'][^>]*\/?>/i', $html, $m)) {
            $data['image'] = $m[1];
        }

        if (preg_match('/<meta\s+[^>]*property=["\']og:site_name["\'][^>]*content=["\']([^"\']*)["\'][^>]*\/?>/i', $html, $m)) {
            $data['site_name'] = $m[1];
        }

        return $data;
    }

    private function parseTwitterCards(string $html): array
    {
        $data = [];

        if (preg_match('/<meta\s+[^>]*name=["\']twitter:title["\'][^>]*content=["\']([^"\']*)["\'][^>]*\/?>/i', $html, $m)) {
            $data['title'] = $m[1];
        }

        if (preg_match('/<meta\s+[^>]*name=["\']twitter:description["\'][^>]*content=["\']([^"\']*)["\'][^>]*\/?>/i', $html, $m)) {
            $data['description'] = $m[1];
        }

        if (preg_match('/<meta\s+[^>]*name=["\']twitter:image["\'][^>]*content=["\']([^"\']*)["\'][^>]*\/?>/i', $html, $m)) {
            $data['image'] = $m[1];
        }

        return $data;
    }

    private function parseFallback(string $html): array
    {
        $data = [];

        if (preg_match('/<title[^>]*>([^<]+)<\/title>/i', $html, $m)) {
            $data['title'] = trim($m[1]);
        }

        if (preg_match('/<meta\s+[^>]*name=["\']description["\'][^>]*content=["\']([^"\']*)["\'][^>]*\/?>/i', $html, $m)) {
            $data['description'] = $m[1];
        }

        if (preg_match('/<link\s+[^>]*rel=["\']image_src["\'][^>]*href=["\']([^"\']*)["\'][^>]*\/?>/i', $html, $m)) {
            $data['image'] = $m[1];
        }

        return $data;
    }

    private function extractFavicon(string $html, string $url): ?string
    {
        if (preg_match('/<link\s+[^>]*rel=["\'](?:shortcut\s+)?icon["\'][^>]*href=["\']([^"\']*)["\'][^>]*\/?>/i', $html, $m)) {
            $favicon = $m[1];

            if (! preg_match('#^https?://#i', $favicon)) {
                $favicon = $this->resolveUrl($favicon, $url);
            }

            return $favicon;
        }

        $host = parse_url($url, PHP_URL_HOST);

        return "https://{$host}/favicon.ico";
    }

    private function resolveUrl(string $path, string $baseUrl): string
    {
        $parsed = parse_url($baseUrl);

        if (str_starts_with($path, '//')) {
            return ($parsed['scheme'] ?? 'https').':'.$path;
        }

        if (str_starts_with($path, '/')) {
            $base = ($parsed['scheme'] ?? 'https').'://'.($parsed['host'] ?? '');

            return $base.$path;
        }

        $base = rtrim(dirname($baseUrl), '/');

        return $base.'/'.ltrim($path, '/');
    }
}
