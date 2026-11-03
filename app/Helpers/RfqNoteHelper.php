<?php

namespace App\Helpers;

use DOMDocument;
use DOMNode;
use HTMLPurifier;
use HTMLPurifier_Config;

/**
 * RfqNoteHelper
 *
 * Centralized sanitization, legacy backward compatibility, and multi-channel
 * transformation engine for RFQ notes and item descriptions.
 */
class RfqNoteHelper
{
    /**
     * Singleton HTMLPurifier instance.
     */
    private static ?HTMLPurifier $purifier = null;

    /**
     * Get or initialize the configured HTMLPurifier instance.
     */
    public static function getPurifier(): HTMLPurifier
    {
        if (self::$purifier === null) {
            $config = HTMLPurifier_Config::createDefault();

            // Disable definition cache directory to ensure compatibility
            // across all CLI, containerized, and read-only environments.
            $config->set('Cache.DefinitionImpl', null);

            // Whitelist permitted formatting and structural elements.
            $config->set('HTML.AllowedElements', ['b', 'strong', 'i', 'em', 'u', 's', 'ul', 'ol', 'li', 'p', 'br']);

            // Disallow all attributes across all elements.
            $config->set('HTML.AllowedAttributes', []);

            // Ensure UTF-8 character encoding and consistent HTML doctype.
            $config->set('Core.Encoding', 'UTF-8');
            $config->set('HTML.Doctype', 'HTML 4.01 Transitional');

            self::$purifier = new HTMLPurifier($config);
        }

        return self::$purifier;
    }

    /**
     * Sanitize rich HTML input.
     *
     * - Whitelists: <b>, <strong>, <i>, <em>, <u>, <s>, <ul>, <ol>, <li>, <p>, <br>
     * - Strips all attributes (id, class, style, onclick, etc.)
     * - Strips scripts, iframes, styles, and dangerous tags
     * - Balances unclosed tags and fixes malformed nesting
     * - Returns null for null, empty string for whitespace/empty
     *
     * @param string|null $html
     * @return string|null
     */
    public static function sanitize(?string $html): ?string
    {
        if ($html === null) {
            return null;
        }

        $trimmed = trim($html);
        if ($trimmed === '') {
            return '';
        }

        // Normalize <del> and <strike> tags to standard <s>
        $normalized = preg_replace('/<(\/?)(strike|del)\b[^>]*>/i', '<$1s>', $trimmed);

        $purified = self::getPurifier()->purify($normalized);

        return trim($purified);
    }

    /**
     * Detect if a string is legacy plain-text (i.e. contains no HTML formatting tags).
     * Accurately ignores mathematical/relational comparisons (e.g. "RAM < 8GB & CPU > 2GHz").
     *
     * @param string|null $content
     * @return bool
     */
    public static function isLegacyPlainText(?string $content): bool
    {
        if ($content === null || trim($content) === '') {
            return true;
        }

        $tagPattern = '/<\/?(p|br|b|strong|i|em|u|s|strike|del|ul|ol|li|div|span|h[1-6]|table|thead|tbody|tr|td|th|blockquote|pre|code|hr|a|img|script|style)\b[^>]*>/i';

        return !preg_match($tagPattern, $content);
    }

    /**
     * Render safe HTML for Web views (show, price_form, preview_quotation).
     *
     * Automatically applies nl2br(e($content)) for legacy plain-text,
     * while preserving rich HTML tags without double-escaping.
     *
     * @param string|null $content
     * @return string
     */
    public static function renderHtml(?string $content): string
    {
        if ($content === null || trim($content) === '') {
            return '';
        }

        if (self::isLegacyPlainText($content)) {
            return nl2br(e($content));
        }

        return self::sanitize($content) ?? '';
    }

    /**
     * Format a WhatsApp delimiter string ensuring delimiters touch non-whitespace core text.
     *
     * @param string $delimiter
     * @param string $inner
     * @return string
     */
    private static function formatDelimiter(string $delimiter, string $inner): string
    {
        if (trim($inner) === '') {
            return $inner;
        }

        if (preg_match('/^(\s*)([\s\S]*?)(\s*)$/u', $inner, $matches)) {
            $prefix = $matches[1];
            $core   = $matches[2];
            $suffix = $matches[3];

            if ($core === '') {
                return $inner;
            }

            return $prefix . $delimiter . $core . $delimiter . $suffix;
        }

        return $delimiter . trim($inner) . $delimiter;
    }

    /**
     * Convert rich HTML or plain text to WhatsApp Markdown.
     *
     * Converts:
     * - <b>, <strong> -> *text*
     * - <i>, <em> -> _text_
     * - <s>, <strike>, <del> -> ~text~
     * - <u> -> unwrapped text
     * - <ul><li> -> • text
     * - <ol><li> -> 1. text
     * - <p>, <br> -> newlines
     *
     * @param string|null $content
     * @return string
     */
    public static function toWhatsApp(?string $content): string
    {
        if ($content === null || trim($content) === '') {
            return '';
        }

        // Normalize Windows/Mac linebreaks to Unix newlines
        $normalized = str_replace(["\r\n", "\r"], "\n", $content);

        // Replace non-breaking space entities with regular space
        $html = str_replace('&nbsp;', ' ', $normalized);

        // Normalize <br> variants to newline
        $html = preg_replace('/<br\s*\/?>/i', "\n", $html);

        // Parse HTML DOM with explicit UTF-8 encoding
        $dom = new DOMDocument();
        libxml_use_internal_errors(true);
        $dom->loadHTML('<?xml encoding="utf-8" ?><div>' . $html . '</div>', LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD);
        libxml_clear_errors();

        $walk = function (DOMNode $node, callable $walk) {
            $out = '';
            foreach ($node->childNodes as $child) {
                if ($child->nodeType === XML_TEXT_NODE) {
                    $out .= $child->textContent;
                } elseif ($child->nodeType === XML_ELEMENT_NODE) {
                    $tag = strtolower($child->nodeName);
                    switch ($tag) {
                        case 'b':
                        case 'strong':
                            $inner = $walk($child, $walk);
                            $out .= self::formatDelimiter('*', $inner);
                            break;

                        case 'i':
                        case 'em':
                            $inner = $walk($child, $walk);
                            $out .= self::formatDelimiter('_', $inner);
                            break;

                        case 's':
                        case 'strike':
                        case 'del':
                            $inner = $walk($child, $walk);
                            $out .= self::formatDelimiter('~', $inner);
                            break;

                        case 'u':
                            // WhatsApp has no underline syntax; unwrap inner text
                            $out .= $walk($child, $walk);
                            break;

                        case 'p':
                            $inner = $walk($child, $walk);
                            $trimmed = trim($inner);
                            if ($trimmed !== '') {
                                if ($out !== '' && !str_ends_with($out, "\n")) {
                                    $out .= "\n";
                                }
                                $out .= $trimmed . "\n";
                            } elseif ($out !== '' && !str_ends_with($out, "\n\n")) {
                                $out .= "\n";
                            }
                            break;

                        case 'ul':
                            if ($out !== '' && !str_ends_with($out, "\n")) {
                                $out .= "\n";
                            }
                            foreach ($child->childNodes as $li) {
                                if ($li->nodeType === XML_ELEMENT_NODE && strtolower($li->nodeName) === 'li') {
                                    $liText = trim($walk($li, $walk));
                                    if ($liText !== '') {
                                        $out .= "• " . $liText . "\n";
                                    }
                                }
                            }
                            break;

                        case 'ol':
                            if ($out !== '' && !str_ends_with($out, "\n")) {
                                $out .= "\n";
                            }
                            $idx = 1;
                            foreach ($child->childNodes as $li) {
                                if ($li->nodeType === XML_ELEMENT_NODE && strtolower($li->nodeName) === 'li') {
                                    $liText = trim($walk($li, $walk));
                                    if ($liText !== '') {
                                        $out .= $idx . ". " . $liText . "\n";
                                        $idx++;
                                    }
                                }
                            }
                            break;

                        default:
                            // Container tags like div, span, etc.
                            $out .= $walk($child, $walk);
                            break;
                    }
                }
            }
            return $out;
        };

        $result = $walk($dom->documentElement, $walk);

        // Decode HTML entities (e.g. &amp; -> &, &lt; -> <, &gt; -> >)
        $result = html_entity_decode($result, ENT_QUOTES | ENT_HTML5, 'UTF-8');

        // Replace non-breaking spaces if any decoded (\xc2\xa0)
        $result = str_replace("\xc2\xa0", ' ', $result);

        // Strip trailing horizontal spaces before newlines
        $result = preg_replace('/[^\S\r\n]+\n/', "\n", $result);

        // Collapse multiple horizontal spaces to single space
        $result = preg_replace('/[^\S\r\n]{2,}/u', ' ', $result);

        // Collapse 3+ newlines to max 2 newlines
        $result = preg_replace("/\n{3,}/", "\n\n", $result);

        return trim($result);
    }

    /**
     * Render HTML formatted specifically for DomPDF inside the .pdf-rich-text container.
     *
     * @param string|null $content
     * @return string
     */
    public static function renderPdfHtml(?string $content): string
    {
        if ($content === null || trim($content) === '') {
            return '';
        }

        $rendered = self::renderHtml($content);
        if ($rendered === '') {
            return '';
        }

        return '<div class="pdf-rich-text">' . $rendered . '</div>';
    }
}
