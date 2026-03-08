{{-- Usage: <x-password-generator-modal /> --}}
{{-- Parent must handle @use-generated-password.window event to capture the password --}}

<div
    x-data="{
        open: false,
        length: 16,
        lowercase: true,
        uppercase: true,
        numbers: true,
        symbols: true,
        generated: '',
        strengthLabel: '',
        strengthColor: '#e5e7eb',
        strengthPercent: 0,
        generate() {
            this.generated = window.passifyGenerator.generate({
                length: this.length,
                lowercase: this.lowercase,
                uppercase: this.uppercase,
                numbers: this.numbers,
                symbols: this.symbols,
            });
            const s = window.passifyGenerator.strength(this.generated);
            this.strengthLabel = s.label;
            this.strengthColor = s.color;
            this.strengthPercent = s.percent;
        },
        use() {
            this.$dispatch('use-generated-password', { password: this.generated });
            this.open = false;
        },
        copy() {
            navigator.clipboard.writeText(this.generated);
        },
    }"
    x-init="generate()"
>
    <button type="button" @click="open = true"
        class="text-xs text-indigo-600 hover:text-indigo-800 font-medium transition">
        Generate password
    </button>

    <div x-show="open" x-cloak class="fixed inset-0 z-50 flex items-center justify-center p-4"
         @keydown.escape.window="open = false">
        <div class="absolute inset-0 bg-black/40" @click="open = false"></div>
        <div class="relative bg-white rounded-2xl shadow-xl w-full max-w-sm p-6" @click.stop>
            <h3 class="text-base font-semibold text-gray-900 mb-4">Password Generator</h3>

            {{-- Strength bar --}}
            <div class="mb-4">
                <div class="flex justify-between text-xs mb-1">
                    <span class="text-gray-500">Strength</span>
                    <span class="font-medium" x-bind:style="'color:' + strengthColor" x-text="strengthLabel"></span>
                </div>
                <div class="h-2 bg-gray-100 rounded-full overflow-hidden">
                    <div class="h-full rounded-full transition-all duration-300"
                         x-bind:style="'width:' + strengthPercent + '%; background:' + strengthColor"></div>
                </div>
            </div>

            {{-- Length slider --}}
            <div class="mb-4">
                <div class="flex justify-between text-xs text-gray-600 mb-1">
                    <span>Password length</span>
                    <span class="font-mono font-semibold" x-text="length + ' characters'"></span>
                </div>
                <input type="range" min="8" max="64" x-model.number="length" @input="generate()"
                    class="w-full accent-indigo-600">
            </div>

            {{-- Options --}}
            <div class="space-y-2 mb-4 text-sm text-gray-700">
                <label class="flex items-center gap-2 cursor-pointer">
                    <input type="checkbox" x-model="lowercase" @change="generate()" class="accent-indigo-600">
                    Lowercase (abc)
                </label>
                <label class="flex items-center gap-2 cursor-pointer">
                    <input type="checkbox" x-model="uppercase" @change="generate()" class="accent-indigo-600">
                    Uppercase (ABC)
                </label>
                <label class="flex items-center gap-2 cursor-pointer">
                    <input type="checkbox" x-model="numbers" @change="generate()" class="accent-indigo-600">
                    Numbers (123)
                </label>
                <label class="flex items-center gap-2 cursor-pointer">
                    <input type="checkbox" x-model="symbols" @change="generate()" class="accent-indigo-600">
                    Randomized symbols (!#$)
                </label>
            </div>

            {{-- Generated password display --}}
            <div class="flex items-center gap-2 mb-4 bg-gray-50 border border-gray-200 rounded-lg px-3 py-2">
                <span class="flex-1 font-mono text-sm text-gray-800 break-all" x-text="generated"></span>
                <button type="button" @click="copy()" title="Copy"
                    class="text-gray-400 hover:text-indigo-600 transition shrink-0">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round"
                            d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z"/>
                    </svg>
                </button>
            </div>

            <div class="flex gap-3">
                <button type="button" @click="generate()"
                    class="flex-1 border border-gray-300 text-gray-700 rounded-lg py-2 text-sm font-medium hover:bg-gray-50 transition">
                    Regenerate
                </button>
                <button type="button" @click="use()"
                    class="flex-1 bg-indigo-600 text-white rounded-lg py-2 text-sm font-medium hover:bg-indigo-700 transition">
                    Use Password
                </button>
            </div>
        </div>
    </div>
</div>
