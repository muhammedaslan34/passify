<script setup>
import { ref } from 'vue';
import { api } from '../api.js';

const query = ref('');
const results = ref([]);
const loading = ref(false);
const copiedId = ref(null);
let timer;

function onInput() {
    clearTimeout(timer);
    if (!query.value.trim()) { results.value = []; return; }
    timer = setTimeout(async () => {
        loading.value = true;
        const res = await api.search(query.value);
        results.value = res?.data || [];
        loading.value = false;
    }, 400);
}

async function copy(text, id) {
    await navigator.clipboard.writeText(text);
    copiedId.value = id;
    setTimeout(() => { copiedId.value = null; }, 1500);
}
</script>

<template>
    <div class="view">
        <h2 class="title">Search</h2>
        <input v-model="query" @input="onInput" type="text" placeholder="Search credentials..." class="search-input" autocomplete="off">
        <div v-if="loading" class="empty">Searching...</div>
        <div v-else-if="query && !results.length" class="empty">No results found.</div>
        <div v-else class="list">
            <div v-for="c in results" :key="c.id" class="card">
                <div class="card-name">{{ c.name }}</div>
                <div class="card-meta">{{ c.organization_name }} &middot; {{ c.email || 'no email' }}</div>
                <div class="card-actions">
                    <button @click="copy(c.email || '', c.id + '-email')" class="action-btn">
                        {{ copiedId === c.id + '-email' ? 'Copied!' : 'Copy email' }}
                    </button>
                    <button @click="copy(c.password, c.id + '-pwd')" class="action-btn">
                        {{ copiedId === c.id + '-pwd' ? 'Copied!' : 'Copy password' }}
                    </button>
                </div>
            </div>
        </div>
    </div>
</template>

<style scoped>
.view { padding: 20px 16px; }
.title { font-size: 18px; font-weight: 700; color: #111827; margin-bottom: 16px; }
.search-input { width: 100%; padding: 12px 14px; border: 1px solid #d1d5db; border-radius: 12px; font-size: 15px; outline: none; margin-bottom: 16px; }
.search-input:focus { border-color: #4f46e5; box-shadow: 0 0 0 3px rgba(79,70,229,.1); }
.empty { color: #6b7280; font-size: 14px; text-align: center; padding: 20px 0; }
.list { display: flex; flex-direction: column; gap: 10px; }
.card { background: white; border: 1px solid #e5e7eb; border-radius: 12px; padding: 14px 16px; }
.card-name { font-weight: 600; font-size: 15px; color: #111827; }
.card-meta { font-size: 12px; color: #6b7280; margin-top: 2px; margin-bottom: 10px; }
.card-actions { display: flex; gap: 8px; }
.action-btn { flex: 1; background: #f3f4f6; border: none; border-radius: 8px; padding: 6px 12px; font-size: 12px; cursor: pointer; color: #374151; }
.action-btn:hover { background: #e5e7eb; }
</style>
