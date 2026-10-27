<?php

namespace App\Services;

use DOMDocument;
use DOMElement;
use DOMNode;

class PostContentSanitizer
{
    private const ALLOWED = ['p', 'br', 'div', 'h2', 'h3', 'h4', 'ul', 'ol', 'li', 'strong', 'b', 'em', 'i', 'u', 'blockquote', 'a', 'img', 'figure', 'figcaption', 'iframe'];

    public function clean(?string $html): string
    {
        if (! filled($html)) {
            return '';
        }

        $dom = new DOMDocument('1.0', 'UTF-8');
        $previous = libxml_use_internal_errors(true);
        $dom->loadHTML('<?xml encoding="utf-8" ?><div id="post-content-root">'.$html.'</div>', LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD);
        libxml_clear_errors();
        libxml_use_internal_errors($previous);
        $root = $dom->getElementById('post-content-root');
        if (! $root) {
            return e(strip_tags($html));
        }
        $this->sanitizeChildren($root);

        $result = '';
        foreach ($root->childNodes as $child) {
            $result .= $dom->saveHTML($child);
        }

        return $result;
    }

    private function sanitizeChildren(DOMNode $parent): void
    {
        foreach (iterator_to_array($parent->childNodes) as $node) {
            if (! $node instanceof DOMElement) {
                continue;
            }
            $tag = strtolower($node->tagName);
            if (! in_array($tag, self::ALLOWED, true)) {
                if (in_array($tag, ['script', 'object', 'embed', 'form', 'svg', 'math'], true)) {
                    $parent->removeChild($node);

                    continue;
                }
                while ($node->firstChild) {
                    $parent->insertBefore($node->firstChild, $node);
                }
                $parent->removeChild($node);

                continue;
            }

            $allowed = match ($tag) {
                'a' => ['href', 'title', 'target', 'rel'],
                'img' => ['src', 'alt', 'title', 'width', 'height', 'loading'],
                'iframe' => ['src', 'title', 'width', 'height', 'allow', 'allowfullscreen', 'loading'],
                default => [],
            };
            foreach (iterator_to_array($node->attributes) as $attribute) {
                if (! in_array(strtolower($attribute->name), $allowed, true) || str_starts_with(strtolower($attribute->value), 'javascript:')) {
                    $node->removeAttribute($attribute->name);
                }
            }
            if (in_array($tag, ['a', 'img'], true) && $node->hasAttribute('href')) {
                if (! $this->isSafeLink($node->getAttribute('href'))) {
                    $node->removeAttribute('href');
                }
            }
            if ($tag === 'img' && strtolower((string) parse_url($node->getAttribute('src'), PHP_URL_HOST)) === 'gknh.thuenhahcm.com') {
                $legacyPath = parse_url($node->getAttribute('src'), PHP_URL_PATH);
                if (is_string($legacyPath) && str_starts_with($legacyPath, '/')) {
                    $node->setAttribute('src', $legacyPath);
                }
            }
            if ($tag === 'img' && $node->hasAttribute('src') && ! $this->isSafeLink($node->getAttribute('src'), true)) {
                $parent->removeChild($node);

                continue;
            }
            if ($tag === 'iframe' && ! $this->isYoutubeUrl($node->getAttribute('src'))) {
                $parent->removeChild($node);

                continue;
            }
            if ($tag === 'a' && $node->getAttribute('target') === '_blank') {
                $node->setAttribute('rel', 'noopener noreferrer');
            }
            if ($tag === 'img') {
                $node->setAttribute('loading', 'lazy');
            }
            $this->sanitizeChildren($node);
        }
    }

    private function isYoutubeUrl(string $url): bool
    {
        $host = parse_url($url, PHP_URL_HOST);

        return in_array(strtolower((string) $host), ['www.youtube.com', 'youtube.com', 'www.youtube-nocookie.com', 'youtube-nocookie.com'], true)
            && str_starts_with($url, 'https://');
    }

    private function isSafeLink(string $url, bool $image = false): bool
    {
        $url = trim($url);
        if (str_starts_with($url, '/') || str_starts_with($url, '#')) {
            return true;
        }
        $scheme = strtolower((string) parse_url($url, PHP_URL_SCHEME));
        if ($scheme === '') {
            return true;
        }
        $allowed = $image ? ['http', 'https'] : ['http', 'https', 'mailto', 'tel'];

        return in_array($scheme, $allowed, true);
    }
}
