<?php

namespace Tests\Unit;

use App\Helpers\RfqNoteHelper;
use App\Models\Rfq;
use App\Models\RfqItem;
use PHPUnit\Framework\TestCase;

/**
 * RfqNoteSecurityTest
 *
 * Comprehensive unit test suite for RFQ note security, HTMLPurifier sanitization,
 * backward-compatible legacy rendering, Eloquent mutators, and WhatsApp conversion.
 * Completely in-memory execution with zero database dependency.
 */
class RfqNoteSecurityTest extends TestCase
{
    public function test_xss_script_tags_are_stripped(): void
    {
        $payload = '<script>alert("XSS")</script><b>Hello</b>';
        $clean = RfqNoteHelper::sanitize($payload);
        $this->assertSame('<b>Hello</b>', $clean);

        $payload2 = '<p>Paragraph 1</p><script>evil()</script><p>Paragraph 2</p>';
        $clean2 = RfqNoteHelper::sanitize($payload2);
        $this->assertSame('<p>Paragraph 1</p><p>Paragraph 2</p>', $clean2);

        $payload3 = '<script src="https://evil.com/xss.js"></script>';
        $clean3 = RfqNoteHelper::sanitize($payload3);
        $this->assertSame('', $clean3);

        $payload4 = '<<SCRIPT>alert("XSS");//<</SCRIPT>';
        $clean4 = RfqNoteHelper::sanitize($payload4);
        $this->assertStringNotContainsStringIgnoringCase('<script', $clean4);
    }

    public function test_inline_event_handlers_are_stripped(): void
    {
        $payload1 = '<b onmouseover="alert(\'pwn\')">Bold hover</b>';
        $clean1 = RfqNoteHelper::sanitize($payload1);
        $this->assertSame('<b>Bold hover</b>', $clean1);

        $payload2 = '<img src="x" onerror="alert(1)">Text';
        $clean2 = RfqNoteHelper::sanitize($payload2);
        $this->assertSame('Text', $clean2);

        $payload3 = '<svg onload=alert(1)>';
        $clean3 = RfqNoteHelper::sanitize($payload3);
        $this->assertSame('', $clean3);

        $payload4 = '<p onclick="alert(1)" id="p1" class="intro">Paragraph</p>';
        $clean4 = RfqNoteHelper::sanitize($payload4);
        $this->assertSame('<p>Paragraph</p>', $clean4);
    }

    public function test_javascript_uris_and_unauthorized_tags_are_stripped(): void
    {
        $payload1 = '<a href="javascript:alert(1)">Click me</a>';
        $clean1 = RfqNoteHelper::sanitize($payload1);
        $this->assertSame('Click me', $clean1);

        $payload2 = '<iframe src="https://evil.com"></iframe>Safe text';
        $clean2 = RfqNoteHelper::sanitize($payload2);
        $this->assertSame('Safe text', $clean2);

        $payload3 = '<style>body{display:none;}</style>Visible';
        $clean3 = RfqNoteHelper::sanitize($payload3);
        $this->assertSame('Visible', $clean3);

        $payload4 = '<div>Div text</div><span>Span text</span><h1>Header</h1>';
        $clean4 = RfqNoteHelper::sanitize($payload4);
        $this->assertSame('Div textSpan textHeader', $clean4);
    }

    public function test_allowed_formatting_tags_are_preserved(): void
    {
        $input = '<b>Bold</b> <strong>Strong</strong> <i>Italic</i> <em>Em</em> <u>Underline</u> <s>Strike</s>';
        $clean = RfqNoteHelper::sanitize($input);
        $this->assertSame($input, $clean);

        $list = '<ul><li>Item 1</li><li>Item 2</li></ul>';
        $cleanList = RfqNoteHelper::sanitize($list);
        $this->assertSame($list, $cleanList);

        $ol = '<ol><li>First</li><li>Second</li></ol>';
        $cleanOl = RfqNoteHelper::sanitize($ol);
        $this->assertSame($ol, $cleanOl);

        $para = '<p>Line 1</p><br><p>Line 2</p>';
        $cleanPara = RfqNoteHelper::sanitize($para);
        $this->assertSame('<p>Line 1</p><br><p>Line 2</p>', $cleanPara);

        // Normalize <del> and <strike> to <s>
        $del = '<del>Old</del> and <strike>Discarded</strike>';
        $cleanDel = RfqNoteHelper::sanitize($del);
        $this->assertSame('<s>Old</s> and <s>Discarded</s>', $cleanDel);
    }

    public function test_malformed_html_is_autoclosed(): void
    {
        $unclosed = 'Unclosed <b>bold text';
        $clean = RfqNoteHelper::sanitize($unclosed);
        $this->assertSame('Unclosed <b>bold text</b>', $clean);

        $unclosedList = '<ul><li>Unclosed list';
        $cleanList = RfqNoteHelper::sanitize($unclosedList);
        $this->assertSame('<ul><li>Unclosed list</li></ul>', $cleanList);

        $mismatched = '<b>Bold <i>Nested</b> Italic</i>';
        $cleanMismatched = RfqNoteHelper::sanitize($mismatched);
        $this->assertSame('<b>Bold <i>Nested</i></b> Italic', $cleanMismatched);
    }

    public function test_legacy_plain_text_detection(): void
    {
        $this->assertTrue(RfqNoteHelper::isLegacyPlainText(null));
        $this->assertTrue(RfqNoteHelper::isLegacyPlainText(''));
        $this->assertTrue(RfqNoteHelper::isLegacyPlainText('   '));
        $this->assertTrue(RfqNoteHelper::isLegacyPlainText('Simple plain text note'));
        $this->assertTrue(RfqNoteHelper::isLegacyPlainText("Line 1\nLine 2\r\nLine 3"));
        $this->assertTrue(RfqNoteHelper::isLegacyPlainText('RAM < 8GB & CPU > 2GHz'));
        $this->assertTrue(RfqNoteHelper::isLegacyPlainText('Harga <Rp 10.000 dan >Rp 5.000'));
        $this->assertTrue(RfqNoteHelper::isLegacyPlainText('Catatan - Item 1: Diskon < 10%'));

        $this->assertFalse(RfqNoteHelper::isLegacyPlainText('<b>Bold</b>'));
        $this->assertFalse(RfqNoteHelper::isLegacyPlainText('Line 1<br>Line 2'));
        $this->assertFalse(RfqNoteHelper::isLegacyPlainText('<p>Paragraph</p>'));
        $this->assertFalse(RfqNoteHelper::isLegacyPlainText('<ul><li>Item</li></ul>'));
        $this->assertFalse(RfqNoteHelper::isLegacyPlainText('<i>Italic</i>'));
        $this->assertFalse(RfqNoteHelper::isLegacyPlainText('<u>Underline</u>'));
        $this->assertFalse(RfqNoteHelper::isLegacyPlainText('<s>Strike</s>'));
        $this->assertFalse(RfqNoteHelper::isLegacyPlainText('<script>alert(1)</script>'));
    }

    public function test_render_html_backward_compatibility(): void
    {
        // Legacy plain text: gets nl2br(e($content))
        $legacy = "Barang ready di gudang.\nGaransi resmi 1 tahun.";
        $renderedLegacy = RfqNoteHelper::renderHtml($legacy);
        $this->assertSame("Barang ready di gudang.<br />\nGaransi resmi 1 tahun.", $renderedLegacy);

        // Legacy text with special characters is escaped safely
        $legacySpecial = "PT. Maju & Jaya <sales@maju.id>\nDiskon 10%";
        $renderedSpecial = RfqNoteHelper::renderHtml($legacySpecial);
        $this->assertSame("PT. Maju &amp; Jaya &lt;sales@maju.id&gt;<br />\nDiskon 10%", $renderedSpecial);

        // Math symbols in plain text are preserved and escaped safely
        $legacyMath = "RAM < 8GB & CPU > 2GHz";
        $renderedMath = RfqNoteHelper::renderHtml($legacyMath);
        $this->assertSame("RAM &lt; 8GB &amp; CPU &gt; 2GHz", $renderedMath);

        // Rich HTML text: sanitized and preserved without double-escaping
        $rich = '<p><strong>Barang ready</strong> di gudang.</p><p>Garansi resmi 1 tahun.</p>';
        $renderedRich = RfqNoteHelper::renderHtml($rich);
        $this->assertSame($rich, $renderedRich);

        // Null and empty string handling
        $this->assertSame('', RfqNoteHelper::renderHtml(null));
        $this->assertSame('', RfqNoteHelper::renderHtml('   '));
    }

    public function test_whatsapp_formatting_syntax_conversion(): void
    {
        // Bold, strong, italic, em, strike, del, underline
        $html = '<b>C9115AXI-X</b> : Cisco Catalyst > <i>EOL</i>, ganti type <strong>C9115AXI-F</strong> <s>Discontinue</s> <del>Old</del> <u>Underlined</u>';
        $wa = RfqNoteHelper::toWhatsApp($html);
        $this->assertSame('*C9115AXI-X* : Cisco Catalyst > _EOL_, ganti type *C9115AXI-F* ~Discontinue~ ~Old~ Underlined', $wa);

        // Delimiters touching text even with inner spaces
        $htmlSpaces = 'Text with <b> bold </b> and <i> italic </i> and <s> striked </s> tags.';
        $waSpaces = RfqNoteHelper::toWhatsApp($htmlSpaces);
        $this->assertSame('Text with *bold* and _italic_ and ~striked~ tags.', $waSpaces);

        // Nested formatting
        $htmlNested = '<b><i>Bold Italic</i></b> and <strong><s>Bold Strike</s></strong>';
        $waNested = RfqNoteHelper::toWhatsApp($htmlNested);
        $this->assertSame('*_Bold Italic_* and *~Bold Strike~*', $waNested);

        // Bullet list
        $htmlUl = '<ul><li>Item 1</li><li>Item 2 with <b>bold</b></li></ul>';
        $waUl = RfqNoteHelper::toWhatsApp($htmlUl);
        $this->assertSame("• Item 1\n• Item 2 with *bold*", $waUl);

        // Numbered list
        $htmlOl = '<ol><li>First step</li><li>Second step with <i>italic</i></li><li>Third step</li></ol>';
        $waOl = RfqNoteHelper::toWhatsApp($htmlOl);
        $this->assertSame("1. First step\n2. Second step with _italic_\n3. Third step", $waOl);

        // Paragraphs and line breaks
        $htmlPara = "<p>Line 1</p><p>Line 2<br>Line 2.5</p>";
        $waPara = RfqNoteHelper::toWhatsApp($htmlPara);
        $this->assertSame("Line 1\nLine 2\nLine 2.5", $waPara);

        // Entity decoding and excessive newline collapsing
        $htmlEntities = "<p>PT. Maju &amp; Jaya &lt;info@maju.id&gt;</p>\n\n\n\n<p>Catatan</p>";
        $waEntities = RfqNoteHelper::toWhatsApp($htmlEntities);
        $this->assertSame("PT. Maju & Jaya <info@maju.id>\n\nCatatan", $waEntities);

        // Legacy text preservation
        $legacyText = "Barang ready di gudang.\nGaransi resmi 1 tahun.";
        $this->assertSame($legacyText, RfqNoteHelper::toWhatsApp($legacyText));

        // Null and empty
        $this->assertSame('', RfqNoteHelper::toWhatsApp(null));
        $this->assertSame('', RfqNoteHelper::toWhatsApp('   '));
    }

    public function test_dompdf_render_pdf_html(): void
    {
        $sampleSpec = '<ul><li>16GB DDR5</li><li>1TB NVMe SSD</li></ul>';
        $pdfHtml = RfqNoteHelper::renderPdfHtml($sampleSpec);
        $this->assertSame('<div class="pdf-rich-text">' . $sampleSpec . '</div>', $pdfHtml);

        $this->assertSame('', RfqNoteHelper::renderPdfHtml(null));
        $this->assertSame('', RfqNoteHelper::renderPdfHtml(''));
        $this->assertSame('', RfqNoteHelper::renderPdfHtml('   '));
    }

    public function test_model_mutators_sanitize_on_assignment(): void
    {
        // 1. Rfq admin_note mutator
        $rfq = new Rfq();
        $rfq->admin_note = '<script>alert("admin")</script><b>Catatan Admin</b>';
        $this->assertSame('<b>Catatan Admin</b>', $rfq->admin_note);

        // 2. Rfq notes mutator
        $rfq->notes = '<iframe src="evil.com"></iframe><i>Catatan Khusus</i>';
        $this->assertSame('<i>Catatan Khusus</i>', $rfq->notes);

        // 3. RfqItem description mutator
        $item = new RfqItem();
        $item->description = '<img onerror=alert(1)><u>Spesifikasi Core i7</u>';
        $this->assertSame('<u>Spesifikasi Core i7</u>', $item->description);

        // 4. RfqItem detail_item mutator
        $item->detail_item = '<style>body{}</style><s>Detail Item EOL</s>';
        $this->assertSame('<s>Detail Item EOL</s>', $item->detail_item);

        // 5. Null values pass through safely
        $rfq->admin_note = null;
        $this->assertNull($rfq->admin_note);
        $rfq->notes = null;
        $this->assertNull($rfq->notes);
        $item->description = null;
        $this->assertNull($item->description);
        $item->detail_item = null;
        $this->assertNull($item->detail_item);

        // 6. WhatsApp formatted note on model
        $rfq->customer_name = 'PT Joyson Safety Systems';
        $rfq->type = 'Hardware';
        $rfq->admin_note = '<p><b>Perhatian:</b> <i>Stok terbatas</i></p>';
        $wa = $rfq->getWhatsAppFormattedNote();
        $this->assertStringContainsString('Pt Joyson Safety Systems, Hardware', $wa);
        $this->assertStringContainsString('*Perhatian:*', $wa);
        $this->assertStringContainsString('_Stok terbatas_', $wa);
        $this->assertStringContainsString('file sudah dimasukan ke folder sales', $wa);
    }
}
