@props([
    'id' => 'editor-' . uniqid(),
    'name' => 'content',
    'label' => null,
    'value' => '',
    'placeholder' => 'Ketik catatan rapat di sini...',
    'hint' => null,
    'minHeight' => '220px',
])

<div class="word-editor-wrapper space-y-1.5" data-editor-id="{{ $id }}">
    @if($label)
        <label for="{{ $id }}-content" class="text-xs font-bold text-slate-900 block">{{ $label }}</label>
    @endif

    <div class="word-editor-card">
        <!-- Word Ribbon Toolbar -->
        <div class="word-editor-ribbon" role="toolbar" aria-label="Toolbar Pengolah Kata">
            <!-- Group 1: Paragraph / Heading Format Dropdown -->
            <div class="flex items-center">
                <select class="word-select" data-command="formatBlock" title="Gaya Paragraf">
                    <option value="p">Normal (Paragraf)</option>
                    <option value="h2">Sub-Topik (H2)</option>
                    <option value="h3">Poin Pembahasan (H3)</option>
                    <option value="blockquote">Kutipan Arahan</option>
                </select>
            </div>

            <div class="h-5 w-px bg-slate-300 mx-0.5 hidden sm:block"></div>

            <!-- Group 2: Font Styles (Bold, Italic, Underline, Strikethrough) -->
            <div class="word-btn-group">
                <button type="button" class="word-btn" data-command="bold" title="Tebal / Bold (Ctrl+B)">
                    <svg viewBox="0 0 24 24" width="14" height="14" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M6 4h8a4 4 0 0 1 4 4 4 4 0 0 1-4 4H6z"/><path d="M6 12h9a4 4 0 0 1 4 4 4 4 0 0 1-4 4H6z"/></svg>
                </button>
                <button type="button" class="word-btn" data-command="italic" title="Miring / Italic (Ctrl+I)">
                    <svg viewBox="0 0 24 24" width="14" height="14" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><line x1="19" x2="10" y1="4" y2="4"/><line x1="14" x2="5" y1="20" y2="20"/><line x1="15" x2="9" y1="4" y2="20"/></svg>
                </button>
                <button type="button" class="word-btn" data-command="underline" title="Garis Bawah / Underline (Ctrl+U)">
                    <svg viewBox="0 0 24 24" width="14" height="14" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M6 4v6a6 6 0 0 0 12 0V4"/><line x1="4" x2="20" y1="20" y2="20"/></svg>
                </button>
                <button type="button" class="word-btn" data-command="strikeThrough" title="Coret / Strikethrough">
                    <svg viewBox="0 0 24 24" width="14" height="14" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M16 4H9a3 3 0 0 0-2.83 4"/><path d="M14 12a4 4 0 0 1 0 8H6"/><line x1="4" x2="20" y1="12" y2="12"/></svg>
                </button>
            </div>

            <div class="h-5 w-px bg-slate-300 mx-0.5 hidden sm:block"></div>

            <!-- Group 3: Text Alignment (Left, Center, Right, Justify) -->
            <div class="word-btn-group">
                <button type="button" class="word-btn" data-command="justifyLeft" title="Rata Kiri">
                    <svg viewBox="0 0 24 24" width="14" height="14" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><line x1="21" x2="3" y1="6" y2="6"/><line x1="15" x2="3" y1="12" y2="12"/><line x1="17" x2="3" y1="18" y2="18"/></svg>
                </button>
                <button type="button" class="word-btn" data-command="justifyCenter" title="Rata Tengah">
                    <svg viewBox="0 0 24 24" width="14" height="14" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><line x1="21" x2="3" y1="6" y2="6"/><line x1="17" x2="7" y1="12" y2="12"/><line x1="19" x2="5" y1="18" y2="18"/></svg>
                </button>
                <button type="button" class="word-btn" data-command="justifyRight" title="Rata Kanan">
                    <svg viewBox="0 0 24 24" width="14" height="14" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><line x1="21" x2="3" y1="6" y2="6"/><line x1="21" x2="9" y1="12" y2="12"/><line x1="21" x2="7" y1="18" y2="18"/></svg>
                </button>
                <button type="button" class="word-btn" data-command="justifyFull" title="Rata Kanan-Kiri / Justify">
                    <svg viewBox="0 0 24 24" width="14" height="14" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><line x1="21" x2="3" y1="6" y2="6"/><line x1="21" x2="3" y1="12" y2="12"/><line x1="21" x2="3" y1="18" y2="18"/></svg>
                </button>
            </div>

            <div class="h-5 w-px bg-slate-300 mx-0.5 hidden sm:block"></div>

            <!-- Group 4: Lists (Bullets, Numbers) -->
            <div class="word-btn-group">
                <button type="button" class="word-btn" data-command="insertUnorderedList" title="Daftar Poin / Bullets">
                    <svg viewBox="0 0 24 24" width="14" height="14" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><line x1="21" x2="9" y1="6" y2="6"/><line x1="21" x2="9" y1="12" y2="12"/><line x1="21" x2="9" y1="18" y2="18"/><circle cx="4" cy="6" r="1.5" fill="currentColor"/><circle cx="4" cy="12" r="1.5" fill="currentColor"/><circle cx="4" cy="18" r="1.5" fill="currentColor"/></svg>
                </button>
                <button type="button" class="word-btn" data-command="insertOrderedList" title="Daftar Nomor / Numbering">
                    <svg viewBox="0 0 24 24" width="14" height="14" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><line x1="21" x2="9" y1="6" y2="6"/><line x1="21" x2="9" y1="12" y2="12"/><line x1="21" x2="9" y1="18" y2="18"/><path d="M4 6h1v4"/><path d="M4 10h2"/><path d="M6 18H4c0-1 2-2 2-3s-1-1.5-2-1"/></svg>
                </button>
            </div>

            <div class="h-5 w-px bg-slate-300 mx-0.5 hidden sm:block"></div>

            <!-- Group 5: Insert (Quote, Horizontal Divider) -->
            <div class="word-btn-group">
                <button type="button" class="word-btn" data-command="blockquote" title="Kutipan Arahan Pimpinan">
                    <svg viewBox="0 0 24 24" width="14" height="14" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M3 21c3 0 7-1 7-8V5c0-1.25-.756-2.017-2-2H4c-1.25 0-2 .75-2 1.972V11c0 1.25.75 2 2 2 1 0 1 0 1 1v1c0 1-1 2-2 2s-1 .008-1 1.031V20c0 1 0 1 1 1z"/><path d="M15 21c3 0 7-1 7-8V5c0-1.25-.757-2.017-2-2h-4c-1.25 0-2 .75-2 1.972V11c0 1.25.75 2 2 2 1 0 1 0 1 1v1c0 1-1 2-2 2s-1 .008-1 1.031V20c0 1 0 1 1 1z"/></svg>
                </button>
                <button type="button" class="word-btn" data-command="insertHorizontalRule" title="Garis Pemisah Sesi">
                    <svg viewBox="0 0 24 24" width="14" height="14" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><line x1="2" x2="22" y1="12" y2="12"/></svg>
                </button>
            </div>

            <div class="h-5 w-px bg-slate-300 mx-0.5 hidden sm:block"></div>

            <!-- Group 6: History & Clear Format -->
            <div class="word-btn-group ml-auto">
                <button type="button" class="word-btn" data-command="undo" title="Batal / Undo (Ctrl+Z)">
                    <svg viewBox="0 0 24 24" width="14" height="14" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M3 7v6h6"/><path d="M21 17a9 9 0 0 0-9-9 9 9 0 0 0-6 2.3L3 13"/></svg>
                </button>
                <button type="button" class="word-btn" data-command="redo" title="Ulangi / Redo (Ctrl+Y)">
                    <svg viewBox="0 0 24 24" width="14" height="14" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M21 7v6h-6"/><path d="M3 17a9 9 0 0 1 9-9 9 9 0 0 1 6 2.3l3 2.7"/></svg>
                </button>
                <button type="button" class="word-btn text-rose-600 hover:text-rose-700 hover:bg-rose-50" data-command="removeFormat" title="Hapus Format Teks">
                    <svg viewBox="0 0 24 24" width="14" height="14" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><line x1="18" x2="6" y1="6" y2="18"/><path d="M4 6h16"/><path d="M10 6v2a4 4 0 0 0 4 4"/></svg>
                </button>
            </div>
        </div>

        <!-- Editable Document Page (Contenteditable Sheet) -->
        <div 
            id="{{ $id }}-content"
            class="word-editor-content prose-gov focus:ring-2 focus:ring-slate-900/15"
            contenteditable="true"
            data-placeholder="{{ $placeholder }}"
            style="min-height: {{ $minHeight }};"
        >{!! $value !!}</div>

        <!-- Bottom Document Status Bar -->
        <div class="bg-slate-50 border-t border-slate-200 px-4 py-1.5 flex items-center text-[11px] text-slate-500 font-medium">
            <div class="flex items-center gap-3">
                <span class="word-counter-words">0 Kata</span>
                <span>&bull;</span>
                <span class="word-counter-chars">0 Karakter</span>
            </div>
        </div>

        <!-- Hidden input for standard form submission -->
        <textarea id="{{ $id }}-hidden" name="{{ $name }}" class="hidden">{{ $value }}</textarea>
    </div>

    @if($hint)
        <p class="text-[11px] text-slate-600 font-medium">{{ $hint }}</p>
    @endif
    @error($name)
        <p class="text-xs text-rose-700 font-bold">{{ $message }}</p>
    @enderror
</div>
