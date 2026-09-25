<script setup>
import { onMounted, reactive, ref } from 'vue'
import { useRouter } from 'vue-router'
import client from '../../api/client'
import AppShell from '../../components/AppShell.vue'

const sessions = ref([])
const showForm = ref(false)
const router = useRouter()
const form = reactive({
  title: '', description: '', starts_at: '', ends_at: '', location: '',
  present_grace_minutes: 10, qr_rotation_seconds: 30, qr_expiry_seconds: 60,
})
const error = ref('')

async function load() {
  const { data } = await client.get('/admin/sessions')
  sessions.value = data.data
}
onMounted(load)

async function createSession() {
  error.value = ''
  try {
    await client.post('/admin/sessions', form)
    showForm.value = false
    Object.assign(form, { title: '', description: '', starts_at: '', ends_at: '', location: '' })
    await load()
  } catch (e) {
    error.value = e.response?.data?.message || 'Could not create session.'
  }
}
</script>

<template>
  <AppShell>
    <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:1rem;">
      <h1 style="font-size:1.2rem;">Sessions</h1>
      <button class="btn btn-primary" @click="showForm = !showForm">{{ showForm ? 'Cancel' : '+ New Session' }}</button>
    </div>

    <form v-if="showForm" class="card grid" style="max-width:480px; margin-bottom:1.5rem;" @submit.prevent="createSession">
      <input class="input" placeholder="Title" v-model="form.title" required />
      <textarea class="input" placeholder="Description" v-model="form.description"></textarea>
      <label>Starts at<input class="input" type="datetime-local" v-model="form.starts_at" required /></label>
      <label>Ends at<input class="input" type="datetime-local" v-model="form.ends_at" required /></label>
      <input class="input" placeholder="Location" v-model="form.location" />
      <label>Present grace period (minutes)<input class="input" type="number" v-model.number="form.present_grace_minutes" /></label>
      <label>QR rotation (seconds)<input class="input" type="number" v-model.number="form.qr_rotation_seconds" /></label>
      <label>QR expiry (seconds)<input class="input" type="number" v-model.number="form.qr_expiry_seconds" /></label>
      <p v-if="error" style="color:#f87171;">{{ error }}</p>
      <button class="btn btn-primary" type="submit">Create (draft)</button>
    </form>

    <div class="card">
      <table>
        <thead><tr><th>Title</th><th>Starts</th><th>Status</th><th></th></tr></thead>
        <tbody>
          <tr v-for="s in sessions" :key="s.id" style="cursor:pointer;" @click="router.push(`/admin/sessions/${s.id}`)">
            <td>{{ s.title }}</td>
            <td>{{ new Date(s.starts_at).toLocaleString() }}</td>
            <td><span :class="'badge badge-' + (s.status === 'open' ? 'present' : s.status === 'closed' ? 'other' : 'late')">{{ s.status }}</span></td>
            <td><router-link :to="`/admin/sessions/${s.id}`">Open →</router-link></td>
          </tr>
        </tbody>
      </table>
    </div>
  </AppShell>
</template>