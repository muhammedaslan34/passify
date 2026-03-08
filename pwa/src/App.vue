<script setup>
import { ref } from 'vue';
import { api } from './api.js';
import LoginView from './views/LoginView.vue';
import OrgsView from './views/OrgsView.vue';
import GeneratorView from './views/GeneratorView.vue';
import SearchView from './views/SearchView.vue';
import ProfileView from './views/ProfileView.vue';

const route = ref(window.location.hash.replace('#/', '') || 'orgs');
const loggedIn = ref(api.isLoggedIn());

window.addEventListener('hashchange', () => {
    route.value = window.location.hash.replace('#/', '') || 'orgs';
});

function navigate(to) {
    window.location.hash = '#/' + to;
}

function onLogin() {
    loggedIn.value = true;
    navigate('orgs');
}

function onLogout() {
    loggedIn.value = false;
    navigate('login');
}
</script>

<template>
    <div class="app">
        <template v-if="!loggedIn || route === 'login'">
            <LoginView @login="onLogin" />
        </template>
        <template v-else>
            <main class="main-content">
                <OrgsView v-if="route === 'orgs' || route === ''" />
                <GeneratorView v-else-if="route === 'generator'" />
                <SearchView v-else-if="route === 'search'" />
                <ProfileView v-else-if="route === 'profile'" @logout="onLogout" />
            </main>
            <nav class="bottom-nav">
                <button @click="navigate('orgs')" :class="{ active: route === 'orgs' || route === '' }">
                    <span class="nav-icon">&#127962;</span>
                    <span class="nav-label">Orgs</span>
                </button>
                <button @click="navigate('generator')" :class="{ active: route === 'generator' }">
                    <span class="nav-icon">&#128273;</span>
                    <span class="nav-label">Generator</span>
                </button>
                <button @click="navigate('search')" :class="{ active: route === 'search' }">
                    <span class="nav-icon">&#128269;</span>
                    <span class="nav-label">Search</span>
                </button>
                <button @click="navigate('profile')" :class="{ active: route === 'profile' }">
                    <span class="nav-icon">&#128100;</span>
                    <span class="nav-label">Profile</span>
                </button>
            </nav>
        </template>
    </div>
</template>

<style>
*, *::before, *::after { box-sizing: border-box; }
body { margin: 0; font-family: system-ui, -apple-system, sans-serif; background: #f9fafb; }
.app { display: flex; flex-direction: column; min-height: 100vh; max-width: 480px; margin: 0 auto; position: relative; }
.main-content { flex: 1; overflow-y: auto; padding-bottom: 68px; }
.bottom-nav {
    position: fixed;
    bottom: 0;
    left: 50%;
    transform: translateX(-50%);
    width: 100%;
    max-width: 480px;
    display: flex;
    background: white;
    border-top: 1px solid #e5e7eb;
    height: 60px;
    z-index: 100;
}
.bottom-nav button {
    flex: 1;
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    gap: 2px;
    border: none;
    background: none;
    cursor: pointer;
    color: #6b7280;
    font-size: 11px;
    padding: 6px 0;
}
.bottom-nav button.active { color: #4f46e5; }
.nav-icon { font-size: 20px; line-height: 1; }
.nav-label { font-size: 10px; font-weight: 500; }
</style>
