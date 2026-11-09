<?php

namespace Tests\Unit;

use App\Helpers\RfqNoteHelper;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

class RfqNoteSpacingTest extends TestCase
{
    #[Test]
    public function it_collapses_multiple_consecutive_blank_lines_in_plain_text(): void
    {
        $input = "Hasil pengecekan unit:\n- Kendala laptop tidak nyala\n\n\n\n\nPerbaikan service:\n- Motherboard";
        $rendered = RfqNoteHelper::renderHtml($input);

        // Should only have <br />\n<br /> (1 blank line), not 5 linebreaks
        $this->assertStringNotContainsString("<br />\n<br />\n<br />\n<br />\n<br />", $rendered);
        $this->assertStringContainsString("tidak nyala<br />\n<br />\nPerbaikan service:", $rendered);
    }

    #[Test]
    public function it_collapses_excessive_br_tags_in_rich_text(): void
    {
        $input = '<u>Hasil pengecekan unit:</u><br>- Kendala laptop tidak nyala<br><br><br><br><br><br><span style="color: #dc2626;"><u>Perbaikan service:</u></span>';
        $rendered = RfqNoteHelper::renderHtml($input);

        // Maximum 2 consecutive <br>
        $this->assertStringNotContainsString('<br><br><br>', $rendered);
        $this->assertStringContainsString('tidak nyala<br><br><span style="color:#dc2626;"><u>Perbaikan service:</u></span>', $rendered);
    }

    #[Test]
    public function it_collapses_breaks_with_literal_nbsp_and_empty_spans(): void
    {
        $input = '<u>Hasil pengecekan unit:</u><br>- Hardware lain belum bisa dicek terlebih dahulu<br>&nbsp;<br>&nbsp;<br><span>&nbsp;</span><br><span style="color: #dc2626;"><u>Perbaikan service:</u></span>';
        $rendered = RfqNoteHelper::renderPdfHtml($input);

        $this->assertStringNotContainsString('<br><br><br>', $rendered);
        $this->assertStringContainsString('terlebih dahulu<br><br><span style="color:#dc2626;"><u>Perbaikan service:</u></span>', $rendered);
    }

    #[Test]
    public function it_normalizes_empty_div_blocks_into_compact_spacing(): void
    {
        $input = '<div>Hasil pengecekan unit:</div><div>- Kendala laptop tidak nyala</div><div><br></div><div><br></div><div><br></div><div><u>Perbaikan service:</u></div>';
        $rendered = RfqNoteHelper::renderHtml($input);

        $this->assertStringNotContainsString('<br><br><br>', $rendered);
        $this->assertStringContainsString('tidak nyala<br><br><u>Perbaikan service:</u>', $rendered);
    }

    #[Test]
    public function it_preserves_single_blank_line_for_intended_paragraph_separation(): void
    {
        $input = "Hasil pengecekan unit:\n- Kendala laptop tidak nyala\n- Hardware lain belum bisa dicek\n\nPerbaikan service:\n- Motherboard i5";
        $rendered = RfqNoteHelper::renderHtml($input);

        // Exactly one blank line (2 linebreaks) between the sections
        $this->assertStringContainsString("belum bisa dicek<br />\n<br />\nPerbaikan service:", $rendered);
        $this->assertStringNotContainsString("<br />\n<br />\n<br />", $rendered);
    }

    #[Test]
    public function it_renders_compact_pdf_html_matching_image_3(): void
    {
        $input = '<u>hasil pengecekan unit:</u><br>- Laptop kondisi mati total<br>- Keyboard berfungsi<br><br><br><br><br><span style="color: #dc2626;"><u>Perbaikan service:</u></span><br><span style="color: #dc2626;">- Garansi 1 Bulan</span>';
        $pdfHtml = RfqNoteHelper::renderPdfHtml($input);

        $this->assertStringStartsWith('<div class="pdf-rich-text">', $pdfHtml);
        $this->assertStringNotContainsString('<br><br><br>', $pdfHtml);
        $this->assertStringContainsString('Keyboard berfungsi<br><br><span style="color:#dc2626;"><u>Perbaikan service:</u></span>', $pdfHtml);
    }
}
