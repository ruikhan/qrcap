<script setup>
import { onMounted, reactive, ref } from 'vue'
import { useRouter } from 'vue-router'
import client from '../../api/client'
import AppShell from '../../components/AppShell.vue'
import { useAuthStore } from '../../stores/auth'

const auth = useAuthStore()
const sessions = ref([])
const groups = ref([])
const showForm = ref(false)
const router = useRouter()
const error = ref('')

const blankForm = () => ({
  title: '', description: '', starts_at: '', ends_at: '', location: '',
  present_grace_minutes: 10, qr_rotation_seconds: 30, qr_expiry_seconds: 60,
  group_ids: [],
})
const form = reactive(blankForm())

function errMsg(e, fallback) {
  const errs = e.response?.data?.errors
  if (errs) return Object.values(errs).flat()[0]
  return e.response?.data?.message || fallback
}

async function load() {
  const { data } = await client.get('/admin/sessions')
  sessions.value = data.data
}

async function loadGroups() {
  if (!auth.isAdmin) return
  try {
    const { data } = await client.get('/admin/groups', { params: { per_page: 100 } })
    groups.value = data.data
  } catch (e) {
    error.value = errMsg(e, 'Could not load groups.')
  }
}

onMounted(() => { load(); loadGroups() })

async function createSession() {
  error.value = ''
  if (!form.group_ids.length) {
    error.value = 'Select at least one group so attendees are eligible to check in.'
    return
  }
  try {
    await client.post('/admin/sessions', form)
    showForm.value = false
    Object.assign(form, blankForm())
    await load()
  } catch (e) {
    error.value = errMsg(e, 'Could not create session.')
  }
}
</script>

<template>
  <AppShell>
    <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:1rem;">
      <h1 style="font-size:1.2rem;">Sessions</h1>
      <button v-if="auth.isAdmin" class="btn btn-primary" @click="showForm = !showForm">{{ showForm ? 'Cancel' : '+ New Session' }}</button>
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

      <fieldset style="border:1px solid var(--border); border-radius:8px; padding:0.6rem 0.8rem;">
        <legend style="color:var(--muted); font-size:0.85rem;">Eligible groups</legend>
        <p v-if="!groups.length" style="color:var(--muted); margin:0; font-size:0.85rem;">
          No groups yet. Create one under Groups first.
        </p>
        <label v-for="g in groups" :key="g.id" style="display:flex; align-items:center; gap:0.4rem; margin:0.2rem 0;">
          <input type="checkbox" :value="g.id" v-model="form.group_ids" />
          {{ g.name }} <span style="color:var(--muted);">({{ g.members_count }})</span>
        </label>
      </fieldset>

      <p v-if="error" style="color:#f87171;">{{ error }}</p>
      <button class="btn btn-primary" type="submit">Create (draft)</button>
    </form>
    <p v-else-if="error" style="color:#f87171;">{{ error }}</p>

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