<?php

namespace Tests\Unit;

use App\Helpers\RfqNoteHelper;
use App\Models\Customer;
use App\Models\Rfq;
use App\Models\RfqItem;
use PHPUnit\Framework\TestCase;

/**
 * RfqModelAndFormatChallengeTest
 *
 * Empirical Challenge Test Suite (Challenger 2):
 * Stress-tests Model Mutators, Legacy Detection Edge Cases, and WhatsApp Formatting.
 */
class RfqModelAndFormatChallengeTest extends TestCase
{
    // =========================================================================
    // 1. MODEL MUTATOR CHALLENGES (Rfq and RfqItem)
    // =========================================================================

    /**
     * Challenge: Mass assignment via constructor and fill()
     */
    public function test_mass_assignment_invokes_mutators_on_rfq_and_rfq_item(): void
    {
        // Rfq constructor mass assignment
        $rfq = new Rfq([
            'admin_note' => '<script>alert("admin")</script><b>Admin Note</b>',
            'notes'      => '<iframe src="evil.com"></iframe><i>Special Note</i>',
        ]);

        $this->assertSame('<b>Admin Note</b>', $rfq->admin_note);
        $this->assertSame('<i>Special Note</i>', $rfq->notes);

        // Rfq fill() mass assignment
        $rfq->fill([
            'admin_note' => '<img src=x onerror=alert(1)>Updated Admin',
            'notes'      => '<style>body{color:red}</style>Updated Notes',
        ]);

        $this->assertSame('Updated Admin', $rfq->admin_note);
        $this->assertSame('Updated Notes', $rfq->notes);

        // RfqItem constructor mass assignment
        $item = new RfqItem([
            'description' => '<script>evil()</script><u>Item Desc</u>',
            'detail_item' => '<svg onload=alert(1)><s>Item Detail</s>',
        ]);

        $this->assertSame('<u>Item Desc</u>', $item->description);
        $this->assertSame('<s>Item Detail</s>', $item->detail_item);

        // RfqItem fill() mass assignment
        $item->fill([
            'description' => '<a href="javascript:alert(1)">Re-filled Desc</a>',
            'detail_item' => '<object data="evil.swf"></object>Re-filled Detail',
        ]);

        $this->assertSame('Re-filled Desc', $item->description);
        $this->assertSame('Re-filled Detail', $item->detail_item);
    }

    /**
     * Challenge: Sequential re-assignments on the same model instance
     * Tests that mutators do not leak state, cache stale purified values, or fail on re-assignment.
     */
    public function test_multiple_sequential_reassignments_maintain_state_integrity(): void
    {
        $rfq = new Rfq();

        // 1. Valid rich HTML
        $rfq->admin_note = '<b>Step 1</b>';
        $this->assertSame('<b>Step 1</b>', $rfq->admin_note);

        // 2. Malicious HTML
        $rfq->admin_note = '<script>evil()</script>Step 2';
        $this->assertSame('Step 2', $rfq->admin_note);

        // 3. Plain text
        $rfq->admin_note = 'Step 3 plain text';
        $this->assertSame('Step 3 plain text', $rfq->admin_note);

        // 4. Null value
        $rfq->admin_note = null;
        $this->assertNull($rfq->admin_note);

        // 5. Empty string
        $rfq->admin_note = '';
        $this->assertSame('', $rfq->admin_note);

        // 6. Whitespace string
        $rfq->admin_note = "   \t\n   ";
        $this->assertSame('', $rfq->admin_note);

        // 7. Rich HTML again
        $rfq->admin_note = '<p><b>Step 7 Final</b></p>';
        $this->assertSame('<p><b>Step 7 Final</b></p>', $rfq->admin_note);

        // Repeat for RfqItem description
        $item = new RfqItem();
        $item->description = '<s>Old Spec</s>';
        $this->assertSame('<s>Old Spec</s>', $item->description);

        $item->description = null;
        $this->assertNull($item->description);

        $item->description = '<u>New Spec</u>';
        $this->assertSame('<u>New Spec</u>', $item->description);
    }

    /**
     * Challenge: Null vs Empty String vs Whitespace Distinction
     */
    public function test_null_vs_empty_vs_whitespace_on_models(): void
    {
        $rfq = new Rfq();
        $item = new RfqItem();

        // Explicit null must remain null
        $rfq->admin_note = null;
        $rfq->notes = null;
        $item->description = null;
        $item->detail_item = null;

        $this->assertNull($rfq->admin_note);
        $this->assertNull($rfq->notes);
        $this->assertNull($item->description);
        $this->assertNull($item->detail_item);
        $this->assertTrue(array_key_exists('admin_note', $rfq->getAttributes()));
        $this->assertNull($rfq->getAttributes()['admin_note']);
        $this->assertTrue(array_key_exists('notes', $rfq->getAttributes()));
        $this->assertNull($rfq->getAttributes()['notes']);

        // Explicit empty string must remain empty string
        $rfq->admin_note = '';
        $rfq->notes = '';
        $item->description = '';
        $item->detail_item = '';

        $this->assertSame('', $rfq->admin_note);
        $this->assertSame('', $rfq->notes);
        $this->assertSame('', $item->description);
        $this->assertSame('', $item->detail_item);

        // Whitespace-only string must normalize to empty string
        $rfq->admin_note = '   ';
        $rfq->notes = "\t\n  ";
        $item->description = '  ';
        $item->detail_item = " \n ";

        $this->assertSame('', $rfq->admin_note);
        $this->assertSame('', $rfq->notes);
        $this->assertSame('', $item->description);
        $this->assertSame('', $item->detail_item);
    }

    /**
     * Challenge: Integer and Numeric inputs to mutators
     */
    public function test_numeric_and_integer_inputs_to_mutators(): void
    {
        $rfq = new Rfq();

        // Integer 12345
        $rfq->admin_note = 12345;
        $this->assertSame('12345', $rfq->admin_note);

        // Integer 0 (must NOT be treated as empty/null)
        $rfq->admin_note = 0;
        $this->assertSame('0', $rfq->admin_note);

        // Float 99.99
        $rfq->notes = 99.99;
        $this->assertSame('99.99', $rfq->notes);

        // RfqItem with integer
        $item = new RfqItem();
        $item->description = 2026;
        $this->assertSame('2026', $item->description);
    }

    /**
     * Challenge: Stringable object input
     */
    public function test_stringable_object_input_to_mutators(): void
    {
        $rfq = new Rfq();

        $stringable = new class {
            public function __toString(): string
            {
                return '<b>Stringable Object Text</b>';
            }
        };

        // If Stringable is passed, it should either coerce to string or handle safely
        $rfq->admin_note = (string) $stringable;
        $this->assertSame('<b>Stringable Object Text</b>', $rfq->admin_note);
    }

    // =========================================================================
    // 2. isLegacyPlainText CHALLENGES (Edge cases & Technical Strings)
    // =========================================================================

    /**
     * Challenge: Technical Part Numbers with angle brackets
     */
    public function test_is_legacy_plain_text_with_technical_part_numbers(): void
    {
        // Part #ABC<DEF>123: DEF is not an HTML tag
        $this->assertTrue(RfqNoteHelper::isLegacyPlainText('Part #ABC<DEF>123'));

        // Dimensions: 10<20cm: mathematical comparison
        $this->assertTrue(RfqNoteHelper::isLegacyPlainText('Dimensions: 10<20cm'));

        // Stock <5pcs and >2pcs
        $this->assertTrue(RfqNoteHelper::isLegacyPlainText('Stock <5pcs and >2pcs'));

        // Cable Type <UTP> Cat6: UTP is not an HTML tag (word boundary check)
        $this->assertTrue(RfqNoteHelper::isLegacyPlainText('Cable Type <UTP> Cat6'));

        // Email in angle brackets: <sales@maju.id>
        $this->assertTrue(RfqNoteHelper::isLegacyPlainText('Contact: PT Maju <sales@maju.id>'));
        $this->assertTrue(RfqNoteHelper::isLegacyPlainText('Email <admin@domain.com>'));
        $this->assertTrue(RfqNoteHelper::isLegacyPlainText('Email <support@domain.com>'));
        $this->assertTrue(RfqNoteHelper::isLegacyPlainText('Email <info@domain.com>'));

        // Mathematical expressions without space
        $this->assertTrue(RfqNoteHelper::isLegacyPlainText('10<20 and 50>30'));
        $this->assertTrue(RfqNoteHelper::isLegacyPlainText('x<y & a>b'));

        // Negative numbers or relational comparisons
        $this->assertTrue(RfqNoteHelper::isLegacyPlainText('Temp <-5C to >40C'));
    }

    /**
     * Challenge: Technical text containing whitelisted tag letters (e.g. <B>, <I>, <P>)
     * Demonstrates boundary behavior of regex word-boundary matching.
     */
    public function test_is_legacy_plain_text_with_tag_colliding_letters(): void
    {
        // Single letter <B> is treated as HTML bold tag because "b" is in the tag list
        $this->assertFalse(RfqNoteHelper::isLegacyPlainText('Grade <B> unit'));

        // Single letter <I> is treated as HTML italic tag
        $this->assertFalse(RfqNoteHelper::isLegacyPlainText('Option <I> selected'));

        // Single letter <P> is treated as paragraph tag
        $this->assertFalse(RfqNoteHelper::isLegacyPlainText('Class <P> material'));

        // Multi-letter non-tag words starting with tag letters: <BETA>, <PRO>, <INFO>, <SEC>
        $this->assertTrue(RfqNoteHelper::isLegacyPlainText('Hardware <BETA> release'));
        $this->assertTrue(RfqNoteHelper::isLegacyPlainText('Version <PRO> edition'));
        $this->assertTrue(RfqNoteHelper::isLegacyPlainText('Status <INFO> notice'));
        $this->assertTrue(RfqNoteHelper::isLegacyPlainText('Timeout <SEC> 30'));
    }

    /**
     * Challenge: Mixed HTML and Legacy Plain-text
     */
    public function test_mixed_html_and_legacy_plain_text(): void
    {
        // Mixed text containing real HTML tags must be detected as rich HTML (not legacy)
        $mixed = "Line 1 legacy text\n<b>Line 2 bold</b>\nLine 3 legacy text";
        $this->assertFalse(RfqNoteHelper::isLegacyPlainText($mixed));

        // renderHtml on mixed text should sanitize and preserve HTML without double-escaping
        $rendered = RfqNoteHelper::renderHtml($mixed);
        $this->assertStringContainsString('<b>Line 2 bold</b>', $rendered);
        $this->assertStringNotContainsString('&lt;b&gt;', $rendered);

        // WhatsApp conversion on mixed text converts tags while preserving newlines
        $wa = RfqNoteHelper::toWhatsApp($mixed);
        $this->assertStringContainsString('*Line 2 bold*', $wa);
        $this->assertStringContainsString("Line 1 legacy text", $wa);
        $this->assertStringContainsString("Line 3 legacy text", $wa);
    }

    /**
     * Challenge: Real-world Indonesian Quotation Terms (Complex strings)
     */
    public function test_real_world_complex_quotation_text(): void
    {
        // Legacy multi-line quotation notes
        $complexLegacy = "SYARAT & KETENTUAN PENAWARAN:\n"
            . "1. Harga belum termasuk PPN 11% (kecuali disebutkan Include PPN).\n"
            . "2. Franco pengiriman: Jabodetabek (Luar kota biaya ekspedisi ditanggung pembeli).\n"
            . "3. Pembayaran: TOP 30 Hari kalender setelah Invoice & Faktur Pajak diterima lengkap.\n"
            . "4. Garansi unit 1 tahun (tidak mencakup kelalaian pemakaian/force majeure).\n"
            . "5. Validitas penawaran berlaku 14 hari sejak tanggal diterbitkan.";

        $this->assertTrue(RfqNoteHelper::isLegacyPlainText($complexLegacy));

        $rendered = RfqNoteHelper::renderHtml($complexLegacy);
        $this->assertStringContainsString('SYARAT &amp; KETENTUAN PENAWARAN:<br />', $rendered);
        $this->assertStringContainsString('PPN 11%', $rendered);

        $wa = RfqNoteHelper::toWhatsApp($complexLegacy);
        $this->assertSame($complexLegacy, $wa);

        // Rich HTML quotation notes
        $complexRich = "<p><strong>SYARAT &amp; KETENTUAN:</strong></p>"
            . "<ol>"
            . "<li>Harga <b>belum termasuk PPN 11%</b></li>"
            . "<li>Franco <i>Jakarta &amp; Tangerang</i></li>"
            . "<li>Garansi resmi <u>1 tahun</u></li>"
            . "<li><s>Harga lama Rp 15.000.000</s> <b>Harga promo Rp 13.500.000</b></li>"
            . "</ol>";

        $this->assertFalse(RfqNoteHelper::isLegacyPlainText($complexRich));

        $waRich = RfqNoteHelper::toWhatsApp($complexRich);
        $this->assertStringContainsString('*SYARAT & KETENTUAN:*', $waRich);
        $this->assertStringContainsString('1. Harga *belum termasuk PPN 11%*', $waRich);
        $this->assertStringContainsString('2. Franco _Jakarta & Tangerang_', $waRich);
        $this->assertStringContainsString('3. Garansi resmi 1 tahun', $waRich);
        $this->assertStringContainsString('4. ~Harga lama Rp 15.000.000~ *Harga promo Rp 13.500.000*', $waRich);
    }

    // =========================================================================
    // 3. Rfq::getWhatsAppFormattedNote() EDGE CONDITIONS
    // =========================================================================

    /**
     * Challenge: Missing customer edge conditions
     */
    public function test_whatsapp_formatted_note_missing_customer(): void
    {
        // 1. Both customer relation and customer_name are null
        $rfq1 = new Rfq();
        $rfq1->customer_name = null;
        $rfq1->type = 'Hardware';
        $rfq1->admin_note = 'Test note';
        $wa1 = $rfq1->getWhatsAppFormattedNote();

        $lines1 = explode("\n", $wa1);
        $this->assertSame('-, Hardware', $lines1[0]);

        // 2. Customer name is empty string
        $rfq2 = new Rfq();
        $rfq2->customer_name = '';
        $rfq2->type = 'Hardware';
        $rfq2->admin_note = 'Test note';
        $wa2 = $rfq2->getWhatsAppFormattedNote();

        $lines2 = explode("\n", $wa2);
        // Note: Customer::formatTitleCase('') is ''
        // In Rfq::getWhatsAppFormattedNote(): $customerName ?: ($this->customer_name ?? '-')
        // If customer_name is '', check what it produces:
        $this->assertTrue(
            $lines2[0] === '-, Hardware' || $lines2[0] === ', Hardware',
            "First line should be '-, Hardware' or ', Hardware', got '{$lines2[0]}'"
        );

        // 3. Customer relation loaded with company_name
        $customer = new Customer();
        $customer->company_name = 'PT Sentosa Abadi';
        $customer->name = 'Budi Santoso';

        $rfq3 = new Rfq();
        $rfq3->setRelation('customer', $customer);
        $rfq3->type = 'Jasa Pemasangan';
        $rfq3->admin_note = 'Jasa instalasi server';
        $wa3 = $rfq3->getWhatsAppFormattedNote();

        $lines3 = explode("\n", $wa3);
        $this->assertSame('PT Sentosa Abadi, Jasa Pemasangan', $lines3[0]);

        // 4. Customer relation loaded with only name (no company_name)
        $customer4 = new Customer();
        $customer4->company_name = null;
        $customer4->name = 'Budi Santoso';

        $rfq4 = new Rfq();
        $rfq4->setRelation('customer', $customer4);
        $rfq4->admin_note = 'Catatan';
        $wa4 = $rfq4->getWhatsAppFormattedNote();

        $lines4 = explode("\n", $wa4);
        $this->assertSame('Budi Santoso, Hardware', $lines4[0]);
    }

    /**
     * Challenge: Null and empty admin_note
     */
    public function test_whatsapp_formatted_note_null_and_empty_note(): void
    {
        // 1. admin_note is null
        $rfq = new Rfq();
        $rfq->customer_name = 'PT Surya Perkasa';
        $rfq->type = 'Hardware';
        $rfq->admin_note = null;

        $wa = $rfq->getWhatsAppFormattedNote();
        $expected = "Pt Surya Perkasa, Hardware\n"
            . "note:\n"
            . "file sudah dimasukan ke folder sales";

        $this->assertSame($expected, $wa);

        // 2. admin_note is empty string
        $rfq->admin_note = '';
        $this->assertSame($expected, $rfq->getWhatsAppFormattedNote());

        // 3. admin_note is whitespace
        $rfq->admin_note = "   \n\t  ";
        $this->assertSame($expected, $rfq->getWhatsAppFormattedNote());
    }

    /**
     * Challenge: Special characters, emojis, and HTML entities in WhatsApp note
     */
    public function test_whatsapp_formatted_note_special_characters_and_emojis(): void
    {
        $rfq = new Rfq();
        $rfq->customer_name = 'PT Delta Global';
        $rfq->type = 'Material Support';
        $rfq->admin_note = '<p>⚠️ <b>PERHATIAN:</b> Diskon &amp; Promo khusus &lt;5%&gt;!</p>'
            . '<ul><li>Kabel UTP Cat6</li><li>Patch Panel 24-Port</li></ul>';

        $wa = $rfq->getWhatsAppFormattedNote();

        $this->assertStringContainsString('Pt Delta Global, Material Support', $wa);
        $this->assertStringContainsString('⚠️ *PERHATIAN:* Diskon & Promo khusus <5%>!', $wa);
        $this->assertStringContainsString('• Kabel UTP Cat6', $wa);
        $this->assertStringContainsString('• Patch Panel 24-Port', $wa);
        $this->assertStringContainsString('file sudah dimasukan ke folder sales', $wa);
    }

    /**
     * Challenge: Deduplication of sales folder footer
     */
    public function test_whatsapp_formatted_note_does_not_duplicate_folder_sales_footer(): void
    {
        $rfq = new Rfq();
        $rfq->customer_name = 'PT Pelita Jaya';

        // 1. Note already contains lowercase phrase
        $rfq->admin_note = 'PO sudah rilis dan file sudah dimasukan ke folder sales hari ini.';
        $wa1 = $rfq->getWhatsAppFormattedNote();
        $this->assertSame(1, substr_count(strtolower($wa1), 'file sudah dimasukan ke folder sales'));

        // 2. Note contains UPPERCASE phrase
        $rfq->admin_note = 'CATATAN: FILE SUDAH DIMASUKAN KE FOLDER SALES.';
        $wa2 = $rfq->getWhatsAppFormattedNote();
        $this->assertSame(1, substr_count(strtolower($wa2), 'file sudah dimasukan ke folder sales'));

        // 3. Note contains Title Case phrase
        $rfq->admin_note = 'Catatan: File Sudah Dimasukan Ke Folder Sales.';
        $wa3 = $rfq->getWhatsAppFormattedNote();
        $this->assertSame(1, substr_count(strtolower($wa3), 'file sudah dimasukan ke folder sales'));

        // 4. Note does NOT contain phrase -> footer must be appended exactly once
        $rfq->admin_note = 'Barang ready stok di gudang.';
        $wa4 = $rfq->getWhatsAppFormattedNote();
        $this->assertSame(1, substr_count(strtolower($wa4), 'file sudah dimasukan ke folder sales'));
    }

    /**
     * Challenge: Type defaulting
     */
    public function test_whatsapp_formatted_note_type_defaulting(): void
    {
        $rfq = new Rfq();
        $rfq->customer_name = 'PT Prima';

        // Null type defaults to Hardware
        $rfq->type = null;
        $wa1 = $rfq->getWhatsAppFormattedNote();
        $this->assertStringStartsWith('Pt Prima, Hardware', $wa1);

        // Empty string type defaults to Hardware
        $rfq->type = '';
        $wa2 = $rfq->getWhatsAppFormattedNote();
        $this->assertStringStartsWith('Pt Prima, Hardware', $wa2);

        // Explicit custom type is preserved
        $rfq->type = 'Pengadaan PC';
        $wa3 = $rfq->getWhatsAppFormattedNote();
        $this->assertStringStartsWith('Pt Prima, Pengadaan PC', $wa3);
    }

    /**
     * Challenge: Array inputs to mutators throw TypeError
     */
    public function test_array_input_to_mutators_throws_type_error(): void
    {
        $this->expectException(\TypeError::class);
        $rfq = new Rfq();
        // Passing array to a method expecting ?string must raise TypeError
        $rfq->admin_note = ['nested' => 'array'];
    }

    /**
     * Challenge: Empty string customer_name is normalized to null by Customer::formatTitleCase,
     * ensuring that getWhatsAppFormattedNote() cleanly produces '-, Hardware'.
     */
    public function test_customer_name_empty_string_edge_behavior(): void
    {
        $rfq = new Rfq();
        $rfq->customer_name = '';
        $rfq->type = 'Hardware';
        $rfq->admin_note = 'Test';
        $wa = $rfq->getWhatsAppFormattedNote();

        $lines = explode("\n", $wa);
        // Customer::formatTitleCase('') returns null, so customer_name is set to null,
        // and $this->customer_name ?? '-' correctly evaluates to '-'.
        $this->assertSame('-, Hardware', $lines[0]);
    }

    /**
     * Challenge: WhatsApp nested lists and empty list elements
     */
    public function test_whatsapp_nested_lists_and_empty_elements(): void
    {
        // Empty ul / ol should not emit bullets
        $emptyUl = '<ul></ul><ol></ol>';
        $waEmpty = RfqNoteHelper::toWhatsApp($emptyUl);
        $this->assertSame('', $waEmpty);

        // Empty li tags should be skipped
        $emptyLi = '<ul><li></li><li>   </li><li>Valid Item</li></ul>';
        $waEmptyLi = RfqNoteHelper::toWhatsApp($emptyLi);
        $this->assertSame('• Valid Item', $waEmptyLi);

        // Nested lists
        $nestedList = '<ul><li>Parent Item<ul><li>Child Item A</li><li>Child Item B</li></ul></li></ul>';
        $waNested = RfqNoteHelper::toWhatsApp($nestedList);
        $this->assertStringContainsString('• Parent Item', $waNested);
        $this->assertStringContainsString('• Child Item A', $waNested);
        $this->assertStringContainsString('• Child Item B', $waNested);
    }

    /**
     * Challenge: Whitespace-only formatting tags do NOT emit empty delimiters (e.g. ** or __)
     */
    public function test_whatsapp_whitespace_only_tags_do_not_emit_empty_delimiters(): void
    {
        $html = 'Text with <b>   </b> and <i> </i> and <s>  </s> tags.';
        $wa = RfqNoteHelper::toWhatsApp($html);

        // Delimiters should NOT appear without core text
        $this->assertStringNotContainsString('**', $wa);
        $this->assertStringNotContainsString('__', $wa);
        $this->assertStringNotContainsString('~~', $wa);
        $this->assertSame('Text with and and tags.', $wa);
    }

    /**
     * Challenge: Direct unsanitized input to toWhatsApp vs sanitized via Model Mutator
     */
    public function test_whatsapp_direct_unsanitized_vs_mutator_sanitized(): void
    {
        $dirty = '<script>alert(1)</script><b>Valid Text</b>';

        // 1. Both direct call and model mutator safely omit the <script> block and its inner text
        $waDirect = RfqNoteHelper::toWhatsApp($dirty);
        $this->assertStringNotContainsString('alert(1)', $waDirect);
        $this->assertSame('*Valid Text*', $waDirect);

        // 2. Setting it on Rfq model automatically invokes mutator which strips script tag completely
        $rfq = new Rfq();
        $rfq->admin_note = $dirty;
        $this->assertSame('<b>Valid Text</b>', $rfq->admin_note);

        $waModel = $rfq->getWhatsAppFormattedNote();
        $this->assertStringNotContainsString('alert(1)', $waModel);
        $this->assertStringContainsString('*Valid Text*', $waModel);
    }

    /**
     * Challenge: Strikethrough tag normalizations (<del> and <strike> to <s>)
     */
    public function test_strike_and_del_tag_normalizations(): void
    {
        $input = '<del>Deleted item</del> and <strike>Strikethrough item</strike>';
        $sanitized = RfqNoteHelper::sanitize($input);
        $this->assertSame('<s>Deleted item</s> and <s>Strikethrough item</s>', $sanitized);

        $wa = RfqNoteHelper::toWhatsApp($input);
        $this->assertSame('~Deleted item~ and ~Strikethrough item~', $wa);
    }

    /**
     * Test grand_total consistency between regular RFQ and Project RFQ with Material/Jasa ceiling.
     */
    public function test_rfq_grand_total_regular_vs_projek_with_ceiling(): void
    {
        // 1. Regular RFQ
        $rfqReg = new Rfq(['type' => 'Hardware']);
        $item1 = new RfqItem(['qty' => 2, 'price_after_margin' => 100000]);
        $item2 = new RfqItem(['qty' => 1, 'price_after_margin' => 350000]);
        $rfqReg->setRelation('items', collect([$item1, $item2]));

        $this->assertEquals(550000.0, $rfqReg->grand_total);

        // 2. Projek RFQ with Material Support ceiling rounding
        $rfqProj = new Rfq([
            'type' => 'Projek',
            'enable_material_ceiling' => true,
            'material_ceiling_step' => 500000,
        ]);
        $hwItem = new RfqItem(['product_name' => 'Switch Cisco', 'category' => 'Hardware', 'qty' => 1, 'price_after_margin' => 1000000]);
        // Material raw total is 700.000, with step 500.000 ceiling it becomes 1.000.000
        $matItem = new RfqItem(['product_name' => 'Kabel UTP Cat6', 'category' => 'Material Support', 'qty' => 1, 'price_after_margin' => 700000]);
        $rfqProj->setRelation('items', collect([$hwItem, $matItem]));

        // Expected grand total: 1.000.000 (hw) + 1.000.000 (mat ceiling) = 2.000.000
        $this->assertEquals(2000000.0, $rfqProj->grand_total);
    }
}
