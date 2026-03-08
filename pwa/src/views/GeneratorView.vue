<script setup>
import { ref, computed, onMounted } from 'vue';
import { generate, strength } from '../utils/password-generator.js';

const length = ref(16);
const opts = ref({ lowercase: true, uppercase: true, numbers: true, symbols: true });
const password = ref('');
const copied = ref(false);

const s = computed(() => strength(password.value));

function generatePassword() {
    password.value = generate({ length: length.value, ...opts.value });
}

async function copy() {
    await navigator.clipboard.writeText(password.value);
    copied.value = true;
    setTimeout(() => { copied.value = false; }, 1500);
}

onMounted(generatePassword);
</script>

<template>
    <div class="view">
        <h2 class="title">Password Generator</h2>

        <div class="strength-block">
            <div class="row">
                <span class="label-sm">Strength</span>
                <span class="label-sm bold" :style="{ color: s.color }">{{ s.label }}</span>
            </div>
            <div class="strength-track">
                <div class="strength-fill" :style="{ width: s.percent + '%', background: s.color }"></div>
            </div>
        </div>

        <div class="field-block">
            <div class="row">
                <span class="label-sm">Password length</span>
                <span class="label-sm bold">{{ length }} characters</span>
            </div>
            <input type="range" v-model.number="length" min="8" max="64" @input="generatePassword" class="slider">
        </div>

        <div class="options-block">
            <label class="option">
                <input type="checkbox" v-model="opts.lowercase" @change="generatePassword">
                Lowercase (abc)
            </label>
            <label class="option">
                <input type="checkbox" v-model="opts.uppercase" @change="generatePassword">
                Uppercase (ABC)
            </label>
            <label class="option">
                <input type="checkbox" v-model="opts.numbers" @change="generatePassword">
                Numbers (123)
            </label>
            <label class="option">
                <input type="checkbox" v-model="opts.symbols" @change="generatePassword">
                Symbols (!#$)
            </label>
        </div>

        <div class="password-block">
            <span class="password-text">{{ password }}</span>
            <button @click="copy" class="copy-btn">{{ copied ? '&#10003;' : '&#10697;' }}</button>
        </div>

        <button @click="generatePassword" class="btn-primary">Generate</button>
    </div>
</template>

<style scoped>
.view { padding: 20px 16px; }
.title { font-size: 18px; font-weight: 700; color: #111827; margin-bottom: 20px; }
.strength-block, .field-block { margin-bottom: 16px; }
.row { display: flex; justify-content: space-between; margin-bottom: 6px; }
.label-sm { font-size: 13px; color: #6b7280; }
.bold { font-weight: 600; }
.strength-track { height: 8px; background: #e5e7eb; border-radius: 9999px; overflow: hidden; }
.strength-fill { height: 100%; border-radius: 9999px; transition: width .3s, background .3s; }
.slider { width: 100%; accent-color: #4f46e5; cursor: pointer; }
.options-block { display: flex; flex-direction: column; gap: 10px; margin-bottom: 16px; }
.option { display: flex; align-items: center; gap: 8px; font-size: 14px; cursor: pointer; }
input[type=checkbox] { accent-color: #4f46e5; width: 16px; height: 16px; cursor: pointer; }
.password-block { display: flex; align-items: center; gap: 10px; background: #f9fafb; border: 1px solid #e5e7eb; border-radius: 12px; padding: 12px 14px; margin-bottom: 12px; }
.password-text { flex: 1; font-family: monospace; font-size: 15px; word-break: break-all; color: #111827; }
.copy-btn { background: none; border: none; font-size: 20px; cursor: pointer; color: #6b7280; padding: 0; }
.copy-btn:hover { color: #4f46e5; }
.btn-primary { width: 100%; background: #4f46e5; color: white; border: none; border-radius: 12px; padding: 14px; font-size: 15px; font-weight: 600; cursor: pointer; }
.btn-primary:hover { background: #4338ca; }
</style>
