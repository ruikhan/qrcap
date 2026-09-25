<script setup>
import { ref } from 'vue'
import { useRouter } from 'vue-router'
import { useAuthStore } from '../stores/auth'

const email = ref('')
const password = ref('')
const error = ref('')
const loading = ref(false)
const auth = useAuthStore()
const router = useRouter()

async function submit() {
  error.value = ''
  loading.value = true
  try {
    await auth.login(email.value, password.value)
    router.push(auth.isStaffOrAbove ? '/admin' : '/scan')
  } catch (e) {
    error.value = e.response?.data?.message || 'Login failed.'
  } finally {
    loading.value = false
  }
}
</script>

<template>
  <div style="display:flex; align-items:center; justify-content:center; min-height:100vh;">
    <form class="card" style="width:320px;" @submit.prevent="submit">
      <h1 style="font-size:1.3rem; margin-bottom:1rem;">QRCAP Login</h1>
      <div class="grid" style="gap:0.75rem;">
        <input class="input" type="email" placeholder="Email" v-model="email" required />
        <input class="input" type="password" placeholder="Password" v-model="password" required />
        <p v-if="error" style="color:#f87171; font-size:0.85rem; margin:0;">{{ error }}</p>
        <button class="btn btn-primary" type="submit" :disabled="loading">
          {{ loading ? 'Signing in…' : 'Sign in' }}
        </button>
      </div>
    </form>
  </div>
</template>