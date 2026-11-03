<?php

namespace Tests\Unit;

use App\Helpers\RfqNoteHelper;
use App\Models\Rfq;
use App\Models\RfqItem;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

/**
 * RfqNoteAdversarialTest
 *
 * Empirical adversarial stress-testing harness for App\Helpers\RfqNoteHelper:
 * 1. 25+ Exotic XSS Payloads:
 *    - Polyglot vectors, data: URIs, base64 strings, nested malformed tags,
 *      obfuscated event handlers, prototype pollution vectors, SVG/MathML, CSS injection.
 * 2. Massive Inputs & Stress Harness:
 *    - 50KB+ payloads, 50-80 deeply nested tags, 1,000-element lists, ReDoS regex audit.
 * 3. Edge-Case WhatsApp Conversions:
 *    - Empty lists, unclosed bold/italic/strike, multi-line list items, nested lists,
 *      unicode emojis, Indonesian typography, Asian scripts, delimiter-touching rules.
 */
class RfqNoteAdversarialTest extends TestCase
{
    // =========================================================================
    // SECTION 1: 25+ EXOTIC XSS PAYLOADS
    // =========================================================================

    #[DataProvider('exoticXssPayloadProvider')]
    public function test_exotic_xss_payloads_are_strictly_neutralized(
        string $description,
        string $payload,
        array $dangerousTokens
    ): void {
        // 1. Sanitize direct execution
        $sanitized = RfqNoteHelper::sanitize($payload);

        // 2. RenderHtml execution (output intended for Blade {!! !!})
        $renderedHtml = RfqNoteHelper::renderHtml($payload);

        // 3. Eloquent Model Mutator execution (persistence pipeline)
        $rfq = new Rfq();
        $rfq->admin_note = $payload;
        $rfq->notes = $payload;

        $item = new RfqItem();
        $item->description = $payload;
        $item->detail_item = $payload;

        // VERIFICATION RULE 1: Neither sanitize() nor mutators must ever emit any non-whitelisted tag
        // Allowed tags: b, strong, i, em, u, s, ul, ol, li, p, br
        $this->assertDoesNotMatchRegularExpression(
            '/<(?!\/?(b|strong|i|em|u|s|ul|ol|li|p|br)\b)[a-z!]/i',
            $sanitized ?? '',
            "Failed sanitize() whitelist enforcement for [{$description}]: contains unwhitelisted raw tag in '{$sanitized}'"
        );
        $this->assertDoesNotMatchRegularExpression(
            '/<(?!\/?(b|strong|i|em|u|s|ul|ol|li|p|br)\b)[a-z!]/i',
            $rfq->admin_note ?? '',
            "Failed Rfq::admin_note mutator whitelist enforcement for [{$description}]: contains unwhitelisted raw tag in '{$rfq->admin_note}'"
        );

        // VERIFICATION RULE 2: In renderHtml(), any non-whitelisted tag must be strictly escaped by e() or stripped
        $this->assertDoesNotMatchRegularExpression(
            '/<(?!\/?(b|strong|i|em|u|s|ul|ol|li|p|br)\b)[a-z!]/i',
            $renderedHtml,
            "Failed renderHtml() XSS immunity for [{$description}]: contains unescaped raw tag in '{$renderedHtml}'"
        );

        // VERIFICATION RULE 3: Neither sanitize() nor mutator output must contain ANY attributes on any tag
        $this->assertDoesNotMatchRegularExpression(
            '/<[a-z0-9]+\s+[^>]*>/i',
            $sanitized ?? '',
            "Failed sanitize() attribute stripping for [{$description}]: attributes found in '{$sanitized}'"
        );
        $this->assertDoesNotMatchRegularExpression(
            '/<[a-z0-9]+\s+[^>]*>/i',
            $rfq->admin_note ?? '',
            "Failed Rfq::admin_note attribute stripping for [{$description}]: attributes found in '{$rfq->admin_note}'"
        );

        // VERIFICATION RULE 4: No executable event handlers or javascript: pseudoprotocol can exist unescaped
        $this->assertDoesNotMatchRegularExpression(
            '/<[^>]*\bon[a-z]+\s*=/i',
            $sanitized ?? '',
            "Failed sanitize(): raw inline event handler found for [{$description}]"
        );
        $this->assertDoesNotMatchRegularExpression(
            '/<[^>]*\bon[a-z]+\s*=/i',
            $renderedHtml,
            "Failed renderHtml(): raw inline event handler found for [{$description}]"
        );

        // VERIFICATION RULE 5: Specific dangerous raw tokens must not be present in sanitized or mutator output
        foreach ($dangerousTokens as $token) {
            $this->assertStringNotContainsStringIgnoringCase(
                $token,
                $sanitized ?? '',
                "Sanitized output contains dangerous token '{$token}' for [{$description}]"
            );
            $this->assertStringNotContainsStringIgnoringCase(
                $token,
                $rfq->admin_note ?? '',
                "Mutator output contains dangerous token '{$token}' for [{$description}]"
            );
        }
    }

    public static function exoticXssPayloadProvider(): array
    {
        return [
            // 1. Classic script tag
            '1. Script tag simple' => [
                'Script tag simple',
                '<script>alert("xss")</script>',
                ['<script']
            ],

            // 2. Polyglot XSS vector
            '2. Polyglot XSS vector' => [
                'Polyglot XSS vector',
                'jaVasCript:/*-/*`/*\`/*\'/*"/**/(/* */oNcliCk=alert() )//%0D%0A%0d%0a//</stYle/</titLe/</teXtarEa/</scRipt/--!>\x3csVg/<sVg/oNloAd=alert()//>\x3e',
                ['<svg', 'onload=']
            ],

            // 3. Data URI in <a> tag
            '3. Data URI in a tag' => [
                'Data URI in a tag',
                '<a href="data:text/html;base64,PHNjcmlwdD5hbGVydCgxKTwvc2NyaXB0Pg==">Click</a>',
                ['<a', 'href=', 'data:text/html']
            ],

            // 4. Data URI in <object> tag
            '4. Data URI in object tag' => [
                'Data URI in object tag',
                '<object data="data:text/html;base64,PHNjcmlwdD5hbGVydCgxKTwvc2NyaXB0Pg=="></object>',
                ['<object', 'data=']
            ],

            // 5. Data URI in <embed> tag
            '5. Data URI in embed tag' => [
                'Data URI in embed tag',
                '<embed src="data:text/html;base64,PHNjcmlwdD5hbGVydCgxKTwvc2NyaXB0Pg==">',
                ['<embed', 'src=']
            ],

            // 6. Data URI in <iframe> tag
            '6. Data URI in iframe tag' => [
                'Data URI in iframe tag',
                '<iframe src="data:text/html,<script>alert(1)</script>"></iframe>',
                ['<iframe', '<script']
            ],

            // 7. Data URI SVG in <img> tag
            '7. Data URI SVG in img tag' => [
                'Data URI SVG in img tag',
                '<img src="data:image/svg+xml;utf8,<svg xmlns=\'http://www.w3.org/2000/svg\' onload=\'alert(1)\'/>">',
                ['<img', 'onload']
            ],

            // 8. Base64 payload inside allowed tags (must render as safe inert text)
            '8. Base64 payload in bold tag' => [
                'Base64 payload in bold tag',
                '<b>PHNjcmlwdD5hbGVydCgxKTwvc2NyaXB0Pg==</b>',
                ['<script']
            ],

            // 9. Malformed nested script tags
            '9. Malformed nested script tags' => [
                'Malformed nested script tags',
                '<<SCRIPT>alert("XSS");//<</SCRIPT>',
                ['<script']
            ],

            // 10. Nested recursive script evasion
            '10. Nested recursive script evasion' => [
                'Nested recursive script evasion',
                '<scr<script>ipt>alert(1)</scr</script>ipt>',
                ['<script']
            ],

            // 11. Tag smuggling inside allowed tag
            '11. Tag smuggling inside allowed tag' => [
                'Tag smuggling inside allowed tag',
                '<b<script>alert(1)</script>>Bold Text</b>',
                ['<script']
            ],

            // 12. Obfuscated event handler with newline
            '12. Obfuscated event handler with newline' => [
                'Obfuscated event handler with newline',
                "<b on\nerror=\"alert(1)\">Bold</b>",
                ['onerror']
            ],

            // 13. Mixed case event handler
            '13. Mixed case event handler' => [
                'Mixed case event handler',
                '<p oNmOuSeOvEr="alert(1)">Paragraph</p>',
                ['onmouseover']
            ],

            // 14. Obfuscated event handler with spaces around equals
            '14. Obfuscated event handler with spaces' => [
                'Obfuscated event handler with spaces',
                '<b onmouseover = "alert(1)">Bold</b>',
                ['onmouseover']
            ],

            // 15. Focus autofocus payload
            '15. Focus autofocus payload' => [
                'Focus autofocus payload',
                '<input type="text" autofocus onfocus="alert(1)">',
                ['<input', 'onfocus']
            ],

            // 16. Animation event in SVG
            '16. Animation event in SVG' => [
                'Animation event in SVG',
                '<svg><animate onbegin="alert(1)" attributeName="x"/></svg>',
                ['<svg', '<animate', 'onbegin']
            ],

            // 17. Marquee event payload
            '17. Marquee event payload' => [
                'Marquee event payload',
                '<marquee onstart="alert(1)">Moving text</marquee>',
                ['<marquee', 'onstart']
            ],

            // 18. Form & prototype pollution DOM clobbering
            '18. Form prototype pollution' => [
                'Form prototype pollution',
                '<form id="__proto__"><input name="polluted" value="yes"></form>',
                ['<form', '<input']
            ],

            // 19. DOM clobbering via attributes on allowed tags
            '19. DOM clobbering on allowed tag' => [
                'DOM clobbering on allowed tag',
                '<p id="__proto__" name="__proto__" data-proto="polluted">Safe paragraph</p>',
                ['id=', 'name=', 'data-proto=']
            ],

            // 20. Javascript pseudoprotocol with hex entities
            '20. Javascript pseudoprotocol with hex entities' => [
                'Javascript pseudoprotocol with hex entities',
                '<a href="jav&#x09;ascript:alert(1)">Click here</a>',
                ['<a', 'javascript:']
            ],

            // 21. VBScript pseudoprotocol
            '21. VBScript pseudoprotocol' => [
                'VBScript pseudoprotocol',
                '<a href="vbscript:msgbox(1)">Click</a>',
                ['<a', 'vbscript:']
            ],

            // 22. MathML / foreignObject payload
            '22. MathML foreignObject payload' => [
                'MathML foreignObject payload',
                '<math><mtext><table><mglyph><style><img src=x onerror=alert(1)>',
                ['<math', '<mglyph', '<img']
            ],

            // 23. HTML5 template injection
            '23. HTML5 template injection' => [
                'HTML5 template injection',
                '<template><script>alert(1)</script></template>',
                ['<template', '<script']
            ],

            // 24. CDATA section evasion
            '24. CDATA section evasion' => [
                'CDATA section evasion',
                '<![CDATA[<script>alert(1)</script>]]>',
                ['<script>']
            ],

            // 25. Inline CSS expression injection
            '25. Inline CSS expression injection' => [
                'Inline CSS expression injection',
                '<b style="color:expression(alert(1));">Styled</b>',
                ['style=', 'expression']
            ],

            // 26. Audio media event
            '26. Audio media event' => [
                'Audio media event',
                '<audio src="x" onerror="alert(1)"></audio>',
                ['<audio', 'onerror']
            ],

            // 27. Meta refresh redirection
            '27. Meta refresh redirection' => [
                'Meta refresh redirection',
                '<meta http-equiv="refresh" content="0;url=javascript:alert(1)">',
                ['<meta', 'http-equiv']
            ],
        ];
    }

    // =========================================================================
    // SECTION 2: MASSIVE INPUTS & STRESS PERFORMANCE
    // =========================================================================

    /**
     * Massive 50KB payload stress test (53KB payload).
     * Verifies sanitization and WhatsApp conversion complete in < 250ms without memory exhaustion.
     */
    public function test_massive_50kb_payload_stress(): void
    {
        $chunk = '<p><b>Spesifikasi Item:</b> Server Enterprise <i>Xeon Platinum 8480+</i> dengan <s>garansi standar</s> <u>garansi 3 tahun 24x7</u>.</p><ul><li>64GB DDR5 ECC Reg</li><li>2x 1.92TB NVMe SSD U.2 Enterprise</li></ul>';
        $largeInput = str_repeat($chunk, 250); // ~53KB

        $this->assertGreaterThan(50000, strlen($largeInput), 'Input must exceed 50KB');

        $start = microtime(true);
        $clean = RfqNoteHelper::sanitize($largeInput);
        $sanitizeDuration = microtime(true) - $start;

        $this->assertNotNull($clean);
        $this->assertLessThan(0.25, $sanitizeDuration, "50KB sanitize() took {$sanitizeDuration}s (limit: 0.25s)");

        $startWa = microtime(true);
        $wa = RfqNoteHelper::toWhatsApp($clean);
        $waDuration = microtime(true) - $startWa;

        $this->assertNotEmpty($wa);
        $this->assertLessThan(0.30, $waDuration, "50KB toWhatsApp() took {$waDuration}s (limit: 0.30s)");
        $this->assertStringContainsString('*Spesifikasi Item:*', $wa);
        $this->assertStringContainsString('• 64GB DDR5 ECC Reg', $wa);
    }

    /**
     * Deeply nested tags stress test (50 to 80 nested tags).
     * HTMLPurifier and toWhatsApp must properly balance without stack overflow.
     */
    public function test_deeply_nested_50_tags_stress(): void
    {
        $nested50 = str_repeat('<b>', 50) . 'Deeply Nested Content' . str_repeat('</b>', 50);

        $clean = RfqNoteHelper::sanitize($nested50);
        $this->assertNotNull($clean);
        $this->assertStringContainsString('Deeply Nested Content', $clean);

        // WhatsApp conversion handles deeply nested formatting without crash
        $wa = RfqNoteHelper::toWhatsApp($clean);
        $this->assertStringContainsString('*Deeply Nested Content*', $wa);

        // Test mixed nesting (b > i > u > s repeated 20 times = 80 levels)
        $mixedOpen = str_repeat('<b><i><u><s>', 20);
        $mixedClose = str_repeat('</s></u></i></b>', 20);
        $mixedDeep = $mixedOpen . 'Extreme Depth' . $mixedClose;

        $cleanMixed = RfqNoteHelper::sanitize($mixedDeep);
        $this->assertNotNull($cleanMixed);
        $this->assertStringContainsString('Extreme Depth', $cleanMixed);

        $waMixed = RfqNoteHelper::toWhatsApp($cleanMixed);
        $this->assertStringContainsString('Extreme Depth', $waMixed);
    }

    /**
     * Massive 1,000 items list stress test.
     */
    public function test_massive_1000_items_list(): void
    {
        $items = '';
        for ($i = 1; $i <= 1000; $i++) {
            $items .= "<li>Item nomor <b>#{$i}</b></li>";
        }
        $listHtml = "<ul>{$items}</ul>";

        $clean = RfqNoteHelper::sanitize($listHtml);
        $this->assertNotNull($clean);
        $this->assertStringContainsString('Item nomor <b>#1000</b>', $clean);

        $wa = RfqNoteHelper::toWhatsApp($clean);
        $this->assertStringContainsString('• Item nomor *#1*', $wa);
        $this->assertStringContainsString('• Item nomor *#1000*', $wa);
    }

    /**
     * ReDoS vulnerability audit on isLegacyPlainText.
     * Evaluates regex performance against adversarial inputs with repeated unclosed tags or brackets.
     */
    public function test_is_legacy_plain_text_redos_resilience(): void
    {
        // 1. Long string of unclosed brackets (50,000 characters)
        $pathological1 = str_repeat('<', 50000);
        $start = microtime(true);
        $result1 = RfqNoteHelper::isLegacyPlainText($pathological1);
        $duration1 = microtime(true) - $start;

        $this->assertTrue($result1);
        $this->assertLessThan(0.05, $duration1, "isLegacyPlainText ReDoS check 1 took {$duration1}s");

        // 2. Long string of tag-like prefixes without closing bracket
        $pathological2 = str_repeat('<p class="test" ', 5000);
        $start = microtime(true);
        $result2 = RfqNoteHelper::isLegacyPlainText($pathological2);
        $duration2 = microtime(true) - $start;

        $this->assertTrue($result2);
        $this->assertLessThan(0.05, $duration2, "isLegacyPlainText ReDoS check 2 took {$duration2}s");

        // 3. Mathematical comparisons repeated 10,000 times
        $mathComparisons = str_repeat('CPU < 3.5GHz & RAM > 16GB; ', 2000);
        $start = microtime(true);
        $result3 = RfqNoteHelper::isLegacyPlainText($mathComparisons);
        $duration3 = microtime(true) - $start;

        $this->assertTrue($result3);
        $this->assertLessThan(0.05, $duration3, "isLegacyPlainText ReDoS check 3 took {$duration3}s");
    }

    // =========================================================================
    // SECTION 3: EDGE-CASE WHATSAPP CONVERSIONS
    // =========================================================================

    /**
     * Edge Case: Empty and Whitespace-Only Lists.
     */
    public function test_whatsapp_empty_lists_handling(): void
    {
        // Empty ul / ol should return empty string
        $this->assertSame('', RfqNoteHelper::toWhatsApp('<ul></ul>'));
        $this->assertSame('', RfqNoteHelper::toWhatsApp('<ol></ol>'));
        $this->assertSame('', RfqNoteHelper::toWhatsApp('<ul><li></li></ul>'));
        $this->assertSame('', RfqNoteHelper::toWhatsApp("<ul><li>   </li><li>\n</li></ul>"));
        $this->assertSame('', RfqNoteHelper::toWhatsApp('<ol><li></li><li> </li></ol>'));
    }

    /**
     * Edge Case: Unclosed formatting tags.
     */
    public function test_whatsapp_unclosed_tags_handling(): void
    {
        $unclosedBold = '<b>Unclosed bold text';
        $waBold = RfqNoteHelper::toWhatsApp(RfqNoteHelper::sanitize($unclosedBold));
        $this->assertSame('*Unclosed bold text*', $waBold);

        $unclosedItalic = '<i>Unclosed italic text';
        $waItalic = RfqNoteHelper::toWhatsApp(RfqNoteHelper::sanitize($unclosedItalic));
        $this->assertSame('_Unclosed italic text_', $waItalic);

        $unclosedStrike = '<s>Unclosed strike text';
        $waStrike = RfqNoteHelper::toWhatsApp(RfqNoteHelper::sanitize($unclosedStrike));
        $this->assertSame('~Unclosed strike text~', $waStrike);
    }

    /**
     * Edge Case: Multi-line List Items.
     */
    public function test_whatsapp_multiline_list_items(): void
    {
        $input = '<ul><li>Line 1<br>Line 2<br>Line 3</li><li>Second Item</li></ul>';
        $wa = RfqNoteHelper::toWhatsApp($input);

        $this->assertStringContainsString('• Line 1', $wa);
        $this->assertStringContainsString('Line 2', $wa);
        $this->assertStringContainsString('Line 3', $wa);
        $this->assertStringContainsString('• Second Item', $wa);
    }

    /**
     * Edge Case: Nested lists (bullet inside numbered or vice versa).
     */
    public function test_whatsapp_nested_lists(): void
    {
        $input = '<ol><li>Step 1<ul><li>Sub-item A</li><li>Sub-item B</li></ul></li><li>Step 2</li></ol>';
        $wa = RfqNoteHelper::toWhatsApp($input);

        $this->assertStringContainsString('1. Step 1', $wa);
        $this->assertStringContainsString('• Sub-item A', $wa);
        $this->assertStringContainsString('• Sub-item B', $wa);
        $this->assertStringContainsString('2. Step 2', $wa);
    }

    /**
     * Edge Case: Unicode emojis, non-ASCII Indonesian typography, Asian alphabets.
     */
    public function test_whatsapp_unicode_emojis_and_multibyte(): void
    {
        // Emojis and Indonesian pricing typography
        $input = '<p><b>Promo Spesial 🔥:</b> Diskon hingga <i>50%</i> 🚀</p><ul><li>Unit siap kirim 📦</li><li>Garansi resmi 100% aman ✨</li></ul>';
        $wa = RfqNoteHelper::toWhatsApp($input);

        $this->assertStringContainsString('*Promo Spesial 🔥:* Diskon hingga _50%_ 🚀', $wa);
        $this->assertStringContainsString('• Unit siap kirim 📦', $wa);
        $this->assertStringContainsString('• Garansi resmi 100% aman ✨', $wa);

        // Asian multibyte scripts
        $asian = '<p><b>日本語テスト</b> <i>한국어</i> <s>中文</s></p>';
        $waAsian = RfqNoteHelper::toWhatsApp($asian);
        $this->assertSame('*日本語テスト* _한국어_ ~中文~', $waAsian);

        // Mathematical and currency symbols
        $symbols = '<p><b>Kapasitas:</b> ≥ 100TB &amp; ≤ 500TB ± 5% (Harga: Rp 15.000.000 / unit)</p>';
        $waSymbols = RfqNoteHelper::toWhatsApp($symbols);
        $this->assertSame('*Kapasitas:* ≥ 100TB & ≤ 500TB ± 5% (Harga: Rp 15.000.000 / unit)', $waSymbols);
    }

    /**
     * Edge Case: Delimiter touches text boundaries and punctuation.
     */
    public function test_whatsapp_delimiter_touch_boundary_rules(): void
    {
        // Punctuation outside
        $wa1 = RfqNoteHelper::toWhatsApp('Pemberitahuan (<b>Penting</b>): Harap dicek.');
        $this->assertSame('Pemberitahuan (*Penting*): Harap dicek.', $wa1);

        // Punctuation inside
        $wa2 = RfqNoteHelper::toWhatsApp('<b>"Penawaran Khusus!"</b>');
        $this->assertSame('*"Penawaran Khusus!"*', $wa2);

        // Space inside tag must be stripped from delimiter boundary
        $wa3 = RfqNoteHelper::toWhatsApp('<b>   Teks dengan spasi internal   </b>');
        $this->assertSame('*Teks dengan spasi internal*', $wa3);

        // Empty tags must not produce orphaned delimiters
        $this->assertSame('', RfqNoteHelper::toWhatsApp('<b></b>'));
        $this->assertSame('', RfqNoteHelper::toWhatsApp('<b>   </b>'));
        $this->assertSame('', RfqNoteHelper::toWhatsApp('<i></i>'));
        $this->assertSame('', RfqNoteHelper::toWhatsApp('<s></s>'));
        $this->assertSame('', RfqNoteHelper::toWhatsApp('<u></u>'));

        // Consecutive delimiters
        $wa4 = RfqNoteHelper::toWhatsApp('<b>Bold1</b> <b>Bold2</b>');
        $this->assertSame('*Bold1* *Bold2*', $wa4);
    }

    /**
     * Edge Case: Paragraph and newline normalization.
     */
    public function test_whatsapp_paragraph_and_newline_normalization(): void
    {
        // Paragraphs produce compact newline (\n) in WhatsApp; blank line (\n\n) when separated by empty paragraph
        $html1 = '<p>Teks Pertama</p><p>Teks Kedua</p>';
        $wa1 = RfqNoteHelper::toWhatsApp($html1);
        $this->assertSame("Teks Pertama\nTeks Kedua", $wa1);

        $htmlEmptyP = '<p>Teks Pertama</p><p></p><p>Teks Kedua</p>';
        $waEmptyP = RfqNoteHelper::toWhatsApp($htmlEmptyP);
        $this->assertSame("Teks Pertama\n\nTeks Kedua", $waEmptyP);

        // Multiple consecutive <br> tags collapse to maximum 2 newlines
        $html2 = '<p>Baris A<br><br><br><br><br>Baris B</p>';
        $wa2 = RfqNoteHelper::toWhatsApp($html2);
        $this->assertSame("Baris A\n\nBaris B", $wa2);

        // Windows CRLF and Mac CR normalization
        $html3 = "Baris 1\r\nBaris 2\rBaris 3";
        $wa3 = RfqNoteHelper::toWhatsApp($html3);
        $this->assertSame("Baris 1\nBaris 2\nBaris 3", $wa3);
    }
}
