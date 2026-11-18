@props(['value' => ''])

<div x-data="academicYearSuggest(@js($value))">
    <label class="label">Tahun Ajaran</label>
    <div class="relative">
        <input type="text" name="academic_year" x-ref="input"
               x-model="value"
               @@input="onInput()"
               @@keydown.enter.prevent="accept()"
               @@blur="onBlur()"
               value="{{ $value }}"
               class="input pr-36" placeholder="2026/2027" required>
        <button type="button" x-show="suggestion" x-cloak
                @@click="accept()"
                class="absolute right-2 top-1/2 -translate-y-1/2 text-xs font-medium text-pine-700 bg-pine-50 border border-pine-200 rounded-md px-2 py-1 hover:bg-pine-100 cursor-pointer whitespace-nowrap">
            Lengkapi: <span x-text="suggestion"></span>
        </button>
    </div>
    <p class="text-sm text-ink/60 mt-1">Kosongkan — saran <code>2025/2026</code> terisi otomatis.</p>
</div>

<script>
    function academicYearSuggest(initial) {
        return {
            value: initial || '',
            suggestion: '',
            onInput() {
                const match = String(this.value).trim().match(/^(\d{4})$/);

                this.suggestion = match ? match[1] + '/' + (parseInt(match[1], 10) + 1) : '';
            },
            accept() {
                if (this.suggestion) {
                    this.value = this.suggestion;
                    this.suggestion = '';
                    this.$refs.input.focus();
                }
            },
            onBlur() {
                this.accept();
            },
        };
    }
</script>
