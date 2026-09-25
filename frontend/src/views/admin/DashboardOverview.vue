<script setup>
import { onMounted, ref } from 'vue'
import { useRouter } from 'vue-router'
import client from '../../api/client'
import AppShell from '../../components/AppShell.vue'

const sessions = ref([])
const loading = ref(true)
const router = useRouter()

onMounted(async () => {
  const { data } = await client.get('/admin/sessions', { params: { status: 'open' } })
  sessions.value = data.data
  loading.value = false
})
</script>

<template>
  <AppShell>
    <h1 style="font-size:1.2rem; margin-bottom:1rem;">Active Sessions</h1>
    <div class="grid" style="grid-template-columns: repeat(auto-fill, minmax(260px, 1fr));">
      <div v-for="s in sessions" :key="s.id" class="card" style="cursor:pointer;" @click="router.push(`/admin/sessions/${s.id}`)">
        <h3 style="margin:0 0 0.4rem;">{{ s.title }}</h3>
        <p style="color:var(--muted); font-size:0.85rem; margin:0;">{{ s.location || 'No location set' }}</p>
        <p style="color:var(--muted); font-size:0.85rem;">Starts {{ new Date(s.starts_at).toLocaleString() }}</p>
        <span class="badge badge-present">OPEN</span>
      </div>
      <p v-if="!loading && !sessions.length" style="color:var(--muted);">No open sessions right now. Create one under Sessions.</p>
    </div>
  </AppShell>
</template>