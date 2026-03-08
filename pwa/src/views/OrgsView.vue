<script setup>
import { ref, onMounted } from 'vue';
import { api } from '../api.js';

const orgs = ref([]);
const selected = ref(null);
const credentials = ref([]);
const loading = ref(true);
const credLoading = ref(false);
const revealedId = ref(null);
const copiedId = ref(null);

onMounted(async () => {
    const res = await api.getOrgs();
    orgs.value = res?.data || [];
    loading.value = false;
});

async function selectOrg(org) {
    selected.value = org;
    credLoading.value = true;
    const res = await api.getCredentials(org.id);
    credentials.value = res?.data || [];
    credLoading.value = false;
}

function back() {
    selected.value = null;
    credentials.value = [];
    revealedId.value = null;
    copiedId.value = null;
}

function toggleReveal(id) {
    revealedId.value = revealedId.value === id ? null : id;
}

async function copyPassword(cred) {
    await navigator.clipboard.writeText(cred.password);
    copiedId.value = cred.id;
    setTimeout(() => { copiedId.value = null; }, 1500);
}
</script>

<template>
    <div class="view">
        <template v-if="!selected">
            <h2 class="title">Organizations</h2>
            <div v-if="loading" class="empty">Loading...</div>
            <div v-else-if="!orgs.length" class="empty">No organizations yet.</div>
            <div v-else class="list">
                <div v-for="org in orgs" :key="org.id" class="card clickable" @click="selectOrg(org)">
                    <div class="card-name">{{ org.name }}</div>
                    <div class="card-meta">{{ org.credentials_count }} credential{{ org.credentials_count !== 1 ? 's' : '' }} &middot; {{ org.role }}</div>
                </div>
            </div>
        </template>

        <template v-else>
            <div class="header-row">
                <button @click="back" class="back-btn">&larr; Back</button>
                <h2 class="title" style="margin-bottom:0">{{ selected.name }}</h2>
            </div>
            <div v-if="credLoading" class="empty">Loading...</div>
            <div v-else-if="!credentials.length" class="empty">No credentials in this org.</div>
            <div v-else class="list">
                <div v-for="cred in credentials" :key="cred.id" class="card">
                    <div class="cred-top">
                        <div class="cred-info">
                            <div class="card-name">{{ cred.name }}</div>
                            <div class="card-meta">{{ cred.service_type }} &middot; {{ cred.email || 'no email' }}</div>
                        </div>
                        <div class="cred-actions">
                            <button @click="toggleReveal(cred.id)" class="action-btn">
                                {{ revealedId === cred.id ? 'Hide' : 'Show' }}
                            </button>
                            <button @click="copyPassword(cred)" class="action-btn">
                                {{ copiedId === cred.id ? 'Copied!' : 'Copy' }}
                            </button>
                        </div>
                    </div>
                    <div v-if="revealedId === cred.id" class="password-reveal">{{ cred.password }}</div>
                </div>
            </div>
        </template>
    </div>
</template>

<style scoped>
.view { padding: 20px 16px; }
.title { font-size: 18px; font-weight: 700; color: #111827; margin-bottom: 16px; }
.header-row { display: flex; align-items: center; gap: 12px; margin-bottom: 16px; }
.back-btn { background: none; border: none; color: #4f46e5; font-size: 14px; cursor: pointer; padding: 0; font-weight: 500; white-space: nowrap; }
.empty { color: #6b7280; font-size: 14px; text-align: center; padding: 32px 0; }
.list { display: flex; flex-direction: column; gap: 10px; }
.card { background: white; border: 1px solid #e5e7eb; border-radius: 12px; padding: 14px 16px; }
.clickable { cursor: pointer; transition: border-color .15s; }
.clickable:hover { border-color: #a5b4fc; }
.card-name { font-weight: 600; font-size: 15px; color: #111827; }
.card-meta { font-size: 12px; color: #6b7280; margin-top: 2px; }
.cred-top { display: flex; align-items: flex-start; justify-content: space-between; gap: 8px; }
.cred-info { flex: 1; min-width: 0; }
.cred-actions { display: flex; gap: 6px; flex-shrink: 0; }
.action-btn { background: #f3f4f6; border: none; border-radius: 8px; padding: 5px 10px; font-size: 12px; cursor: pointer; color: #374151; }
.action-btn:hover { background: #e5e7eb; }
.password-reveal { margin-top: 10px; font-family: monospace; font-size: 13px; background: #f9fafb; border-radius: 8px; padding: 8px 10px; word-break: break-all; color: #111827; border: 1px solid #e5e7eb; }
</style>
