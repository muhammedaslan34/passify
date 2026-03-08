<script setup>
import { ref } from 'vue';
import { api } from '../api.js';

const emit = defineEmits(['login']);
const email = ref('');
const password = ref('');
const error = ref('');
const loading = ref(false);

async function login() {
    loading.value = true;
    error.value = '';
    try {
        const res = await api.login(email.value, password.value);
        if (res?.token) {
            api.saveToken(res.token);
            emit('login');
        } else {
            error.value = 'Invalid credentials. Please try again.';
        }
    } catch {
        error.value = 'Connection error. Check your network.';
    } finally {
        loading.value = false;
    }
}
</script>

<template>
    <div class="page">
        <div class="card">
            <h1 class="brand">Passify</h1>
            <p class="sub">Sign in to your vault</p>
            <div v-if="error" class="error-msg">{{ error }}</div>
            <form @submit.prevent="login">
                <div class="field">
                    <label>Email</label>
                    <input v-model="email" type="email" placeholder="you@example.com" required autocomplete="email">
                </div>
                <div class="field">
                    <label>Password</label>
                    <input v-model="password" type="password" placeholder="Password" required autocomplete="current-password">
                </div>
                <button type="submit" :disabled="loading" class="btn-login">
                    {{ loading ? 'Signing in...' : 'Sign In' }}
                </button>
            </form>
        </div>
    </div>
</template>

<style scoped>
.page { min-height: 100vh; display: flex; align-items: center; justify-content: center; background: #f9fafb; padding: 24px; }
.card { background: white; border-radius: 16px; padding: 32px 24px; width: 100%; max-width: 360px; box-shadow: 0 1px 4px rgba(0,0,0,.08); }
.brand { font-size: 28px; font-weight: 800; color: #4f46e5; text-align: center; margin: 0 0 4px; }
.sub { text-align: center; color: #6b7280; font-size: 14px; margin: 0 0 24px; }
.error-msg { background: #fef2f2; color: #dc2626; border-radius: 8px; padding: 10px 12px; font-size: 13px; margin-bottom: 16px; }
.field { margin-bottom: 14px; }
.field label { display: block; font-size: 13px; font-weight: 500; color: #374151; margin-bottom: 4px; }
.field input { width: 100%; padding: 10px 12px; border: 1px solid #d1d5db; border-radius: 10px; font-size: 15px; outline: none; }
.field input:focus { border-color: #4f46e5; box-shadow: 0 0 0 3px rgba(79,70,229,.1); }
.btn-login { width: 100%; background: #4f46e5; color: white; border: none; border-radius: 10px; padding: 12px; font-size: 15px; font-weight: 600; cursor: pointer; margin-top: 8px; }
.btn-login:disabled { opacity: .6; cursor: not-allowed; }
</style>
