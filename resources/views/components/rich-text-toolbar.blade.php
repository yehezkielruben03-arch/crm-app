{{--
    Rich Text Toolbar Component
    ===========================
    Usage:
        <x-rich-text-toolbar
            :name="'admin_note'"
            :id="'rfq-admin-note'"
            :value="old('admin_note', $rfq->admin_note)"
            :rows="4"
            :placeholder="'Tulis catatan...'"
            :class="'w-full text-xs ...'"
        />

    Props:
        name        - textarea/input name attribute (required)
        id          - element id (required)
        value       - initial HTML content
        rows        - textarea rows (default: 4)
        placeholder - textarea placeholder text
        class       - extra classes for the contenteditable div (optional)
        toolbarClass - extra classes for the toolbar wrapper (optional)
        required    - whether the field is required (default: false)
--}}
@props([
    'name',
    'id',
    'value'       => '',
    'rows'        => 4,
    'placeholder' => '',
    'class'       => '',
    'toolbarClass'=> '',
    'required'    => false,
])

@php
    // Determine the min-height from rows. Each row ≈ 1.6rem (20px line-height + 4px).
    $minHeightPx = max(80, (int)$rows * 24);
    // Sanitize initial value for safe insertion into contenteditable
    $safeValue = $value ? \App\Helpers\RfqNoteHelper::renderHtml($value) : '';
@endphp

<div
    x-data="richTextEditor({
        initialHtml: {{ Js::from($safeValue) }},
        inputId: {{ Js::from($id . '_hidden') }}
    })"
    class="rich-text-editor-wrapper"
>
    {{-- Hidden textarea that carries the value on form submit --}}
    <input type="hidden"
           :name="$el.querySelector('[data-rte-hidden]').name"
           id="{{ $id }}_hidden"
           name="{{ $name }}"
           data-rte-hidden
           x-ref="hiddenInput"
           value="{{ e($value) }}">

    {{-- Toolbar --}}
    <div class="flex items-center gap-0.5 px-2 py-1.5 bg-slate-50 border border-b-0 border-slate-200 rounded-t-xl {{ $toolbarClass }}"
         role="toolbar" aria-label="Text formatting">

        {{-- Bold --}}
        <button type="button"
                @click="exec('bold')"
                :class="active.bold ? 'bg-slate-200 text-slate-900' : 'text-slate-600 hover:bg-slate-200 hover:text-slate-900'"
                class="rte-btn px-1.5 py-0.5 rounded-md text-sm font-bold transition select-none"
                title="Bold (Ctrl+B)">
            <svg class="w-3.5 h-3.5" viewBox="0 0 24 24" fill="currentColor"><path d="M15.6 11.79c.97-.67 1.65-1.77 1.65-2.79 0-2.26-1.75-4-4-4H7v14h7.04c2.09 0 3.71-1.7 3.71-3.79 0-1.52-.86-2.82-2.15-3.42zM10 7.5h3c.83 0 1.5.67 1.5 1.5s-.67 1.5-1.5 1.5h-3v-3zm3.5 9H10v-3h3.5c.83 0 1.5.67 1.5 1.5s-.67 1.5-1.5 1.5z"/></svg>
        </button>

        {{-- Italic --}}
        <button type="button"
                @click="exec('italic')"
                :class="active.italic ? 'bg-slate-200 text-slate-900' : 'text-slate-600 hover:bg-slate-200 hover:text-slate-900'"
                class="rte-btn px-1.5 py-0.5 rounded-md text-sm italic transition select-none"
                title="Italic (Ctrl+I)">
            <svg class="w-3.5 h-3.5" viewBox="0 0 24 24" fill="currentColor"><path d="M10 4v3h2.21l-3.42 10H6v3h8v-3h-2.21l3.42-10H18V4z"/></svg>
        </button>

        {{-- Underline --}}
        <button type="button"
                @click="exec('underline')"
                :class="active.underline ? 'bg-slate-200 text-slate-900' : 'text-slate-600 hover:bg-slate-200 hover:text-slate-900'"
                class="rte-btn px-1.5 py-0.5 rounded-md text-sm underline transition select-none"
                title="Underline (Ctrl+U)">
            <svg class="w-3.5 h-3.5" viewBox="0 0 24 24" fill="currentColor"><path d="M12 17c3.31 0 6-2.69 6-6V3h-2.5v8c0 1.93-1.57 3.5-3.5 3.5S8.5 12.93 8.5 11V3H6v8c0 3.31 2.69 6 6 6zm-7 2v2h14v-2H5z"/></svg>
        </button>

        {{-- Strikethrough --}}
        <button type="button"
                @click="exec('strikeThrough')"
                :class="active.strikeThrough ? 'bg-slate-200 text-slate-900' : 'text-slate-600 hover:bg-slate-200 hover:text-slate-900'"
                class="rte-btn px-1.5 py-0.5 rounded-md text-sm line-through transition select-none"
                title="Strikethrough">
            <svg class="w-3.5 h-3.5" viewBox="0 0 24 24" fill="currentColor"><path d="M10 19h4v-3h-4v3zM5 4v3h6v3h2V7h6V4H5zM3 14h18v-2H3v2z"/></svg>
        </button>

        {{-- Separator --}}
        <span class="w-px h-4 bg-slate-300 mx-1 self-center"></span>

        {{-- Bullet List --}}
        <button type="button"
                @click="exec('insertUnorderedList')"
                :class="active.insertUnorderedList ? 'bg-slate-200 text-slate-900' : 'text-slate-600 hover:bg-slate-200 hover:text-slate-900'"
                class="rte-btn px-1.5 py-0.5 rounded-md transition select-none"
                title="Bullet List">
            <svg class="w-3.5 h-3.5" viewBox="0 0 24 24" fill="currentColor"><path d="M4 10.5c-.83 0-1.5.67-1.5 1.5s.67 1.5 1.5 1.5 1.5-.67 1.5-1.5-.67-1.5-1.5-1.5zm0-6c-.83 0-1.5.67-1.5 1.5S3.17 7.5 4 7.5 5.5 6.83 5.5 6 4.83 4.5 4 4.5zm0 12c-.83 0-1.5.68-1.5 1.5s.68 1.5 1.5 1.5 1.5-.68 1.5-1.5-.67-1.5-1.5-1.5zM7 19h14v-2H7v2zm0-6h14v-2H7v2zm0-8v2h14V5H7z"/></svg>
        </button>

        {{-- Numbered List --}}
        <button type="button"
                @click="exec('insertOrderedList')"
                :class="active.insertOrderedList ? 'bg-slate-200 text-slate-900' : 'text-slate-600 hover:bg-slate-200 hover:text-slate-900'"
                class="rte-btn px-1.5 py-0.5 rounded-md transition select-none"
                title="Numbered List">
            <svg class="w-3.5 h-3.5" viewBox="0 0 24 24" fill="currentColor"><path d="M2 17h2v.5H3v1h1v.5H2v1h3v-4H2v1zm1-9h1V4H2v1h1v3zm-1 3h1.8L2 13.1v.9h3v-1H3.2L5 10.9V10H2v1zm5-6v2h14V5H7zm0 14h14v-2H7v2zm0-6h14v-2H7v2z"/></svg>
        </button>

        {{-- Separator --}}
        <span class="w-px h-4 bg-slate-300 mx-1 self-center"></span>

        {{-- Clear Formatting --}}
        <button type="button"
                @click="exec('removeFormat')"
                class="rte-btn px-1.5 py-0.5 rounded-md text-slate-500 hover:bg-rose-50 hover:text-rose-600 transition select-none"
                title="Hapus Formatting">
            <svg class="w-3.5 h-3.5" viewBox="0 0 24 24" fill="currentColor"><path d="M3.27 5L2 6.27l6.97 6.97L6.5 19h3l1.57-3.66L16.73 21 18 19.73 3.27 5zM6 5v.18L8.82 8h2.4l-.72 1.68 2.1 2.1L14.21 8H20V5H6z"/></svg>
        </button>
    </div>

    {{-- Content Editable --}}
    <div
        x-ref="editor"
        contenteditable="true"
        @input="onInput()"
        @keydown="onKeydown($event)"
        @mouseup="updateActive()"
        @keyup="updateActive()"
        @focus="updateActive()"
        @paste.prevent="onPaste($event)"
        style="min-height: {{ $minHeightPx }}px;"
        class="rte-content w-full text-xs border border-slate-200 rounded-b-xl p-3 focus:outline-none focus:border-blue-500 focus:ring-1 focus:ring-blue-500 resize-y overflow-auto leading-relaxed {{ $class }}"
        data-placeholder="{{ $placeholder }}"
        spellcheck="true"
    ></div>
</div>

@once
<style>
.rte-content:empty:before {
    content: attr(data-placeholder);
    color: #94a3b8;
    pointer-events: none;
}
.rte-content ul { list-style: disc; padding-left: 1.25rem; margin: 0.25rem 0; }
.rte-content ol { list-style: decimal; padding-left: 1.25rem; margin: 0.25rem 0; }
.rte-content li { margin: 0.125rem 0; }
.rte-content b, .rte-content strong { font-weight: 700; }
.rte-content i, .rte-content em { font-style: italic; }
.rte-content u { text-decoration: underline; }
.rte-content s, .rte-content strike { text-decoration: line-through; }
.rte-btn { display: inline-flex; align-items: center; justify-content: center; cursor: pointer; }
</style>

<script>
function richTextEditor({ initialHtml, inputId }) {
    return {
        active: { bold: false, italic: false, underline: false, strikeThrough: false, insertUnorderedList: false, insertOrderedList: false },

        init() {
            const editor = this.$refs.editor;
            // Set initial HTML content safely
            if (initialHtml && initialHtml.trim() !== '') {
                editor.innerHTML = initialHtml;
            }
            this.syncHidden();
            this.updateActive();
        },

        exec(command) {
            this.$refs.editor.focus();
            document.execCommand(command, false, null);
            this.syncHidden();
            this.updateActive();
        },

        onInput() {
            this.syncHidden();
            this.updateActive();
            // Trigger autosave if available (price_form compatibility)
            if (typeof triggerAutoSave === 'function') {
                triggerAutoSave();
            }
        },

        onKeydown(event) {
            // Intercept Ctrl+B/I/U shortcuts to keep them working
            if (event.ctrlKey || event.metaKey) {
                if (['b', 'i', 'u'].includes(event.key.toLowerCase())) {
                    // Let the browser handle it, then sync
                    this.$nextTick(() => {
                        this.syncHidden();
                        this.updateActive();
                    });
                }
            }
            // Tab → indent list items, prevent focus change
            if (event.key === 'Tab') {
                event.preventDefault();
                document.execCommand('indent', false, null);
                this.syncHidden();
            }
        },

        onPaste(event) {
            // Strip all formatting from pasted content — paste as plain text only
            const text = (event.clipboardData || window.clipboardData).getData('text/plain');
            document.execCommand('insertText', false, text);
            this.syncHidden();
        },

        syncHidden() {
            const hidden = this.$refs.hiddenInput;
            if (hidden) {
                hidden.value = this.$refs.editor.innerHTML;
            }
        },

        updateActive() {
            ['bold', 'italic', 'underline', 'strikeThrough', 'insertUnorderedList', 'insertOrderedList'].forEach(cmd => {
                try {
                    this.active[cmd] = document.queryCommandState(cmd);
                } catch (e) {
                    this.active[cmd] = false;
                }
            });
        },

        // Public API: allows Alpine snippets or external JS to set content
        setContent(html) {
            this.$refs.editor.innerHTML = html;
            this.syncHidden();
            this.updateActive();
        },

        appendText(text) {
            const editor = this.$refs.editor;
            const current = editor.innerHTML.trim();
            // If empty, set directly; otherwise append with line break
            if (current === '' || current === '<br>') {
                editor.innerHTML = text;
            } else {
                editor.innerHTML = current + '<br>' + text;
            }
            // Move cursor to end
            const range = document.createRange();
            range.selectNodeContents(editor);
            range.collapse(false);
            const sel = window.getSelection();
            sel.removeAllRanges();
            sel.addRange(range);
            this.syncHidden();
        }
    };
}
</script>
@endonce
