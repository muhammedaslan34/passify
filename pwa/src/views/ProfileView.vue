<script setup>
import { api } from '../api.js';

const emit = defineEmits(['logout']);

async function logout() {
    await api.logout();
    api.clearToken();
    emit('logout');
}
</script>

<template>
    <div class="view">
        <h2 class="title">Profile</h2>
        <div class="card">
            <p class="info">You are signed in to Passify.</p>
            <button @click="logout" class="btn-logout">Sign Out</button>
        </div>
    </div>
</template>

<style scoped>
.view { padding: 20px 16px; }
.title { font-size: 18px; font-weight: 700; color: #111827; margin-bottom: 16px; }
.card { background: white; border: 1px solid #e5e7eb; border-radius: 12px; padding: 20px; }
.info { color: #6b7280; font-size: 14px; margin-bottom: 16px; }
.btn-logout { background: #fee2e2; color: #dc2626; border: none; border-radius: 10px; padding: 12px 20px; font-size: 14px; font-weight: 600; cursor: pointer; width: 100%; }
.btn-logout:hover { background: #fecaca; }
</style>
