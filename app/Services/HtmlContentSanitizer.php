<?php

namespace App\Services;

use DOMDocument;
use DOMElement;
use DOMNode;

class HtmlContentSanitizer
{
    private const ALLOWED_TAGS = [
        'a', 'b', 'blockquote', 'br', 'em', 'h2', 'h3', 'h4', 'i',
        'li', 'ol', 'p', 'strong', 'u', 'ul',
    ];

    public function sanitize(?string $html): ?string
    {
        if ($html === null || trim($html) === '') {
            return $html;
        }

        if (! class_exists(DOMDocument::class)) {
            return strip_tags($html);
        }

        $document = new DOMDocument('1.0', 'UTF-8');
        $previous = libxml_use_internal_errors(true);
        $document->loadHTML(
            '<?xml encoding="UTF-8"><div>'.$html.'</div>',
            LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD,
        );
        libxml_clear_errors();
        libxml_use_internal_errors($previous);

        $wrapper = $document->getElementsByTagName('div')->item(0);
        if (! $wrapper) {
            return strip_tags($html);
        }

        foreach (iterator_to_array($wrapper->childNodes) as $child) {
            $this->sanitizeNode($child);
        }

        $clean = '';
        foreach ($wrapper->childNodes as $child) {
            $clean .= $document->saveHTML($child);
        }

        return $clean;
    }

    private function sanitizeNode(DOMNode $node): void
    {
        if ($node instanceof DOMElement && ! in_array(strtolower($node->tagName), self::ALLOWED_TAGS, true)) {
            $node->parentNode?->removeChild($node);

            return;
        }

        if ($node instanceof DOMElement) {
            $tag = strtolower($node->tagName);
            $href = $tag === 'a' ? $this->safeHref($node->getAttribute('href')) : null;

            while ($node->attributes->length > 0) {
                $node->removeAttributeNode($node->attributes->item(0));
            }

            if ($tag === 'a' && $href !== null) {
                $node->setAttribute('href', $href);
                $node->setAttribute('rel', 'noopener noreferrer');
            }
        }

        foreach (iterator_to_array($node->childNodes) as $child) {
            $this->sanitizeNode($child);
        }
    }

    private function safeHref(string $href): ?string
    {
        $scheme = strtolower((string) parse_url(trim($href), PHP_URL_SCHEME));

        return in_array($scheme, ['http', 'https', 'mailto', 'tel'], true) ? trim($href) : null;
    }
}
