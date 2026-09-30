<script setup>
import { onMounted, reactive, ref } from 'vue'
import client from '../../api/client'
import AppShell from '../../components/AppShell.vue'

const groups = ref([])
const users = ref([])
const search = ref('')
const showForm = ref(false)
const editingId = ref(null)
const error = ref('')
const saving = ref(false)
const form = reactive({ name: '', description: '', member_ids: [] })

function errMsg(e, fallback) {
  const errs = e.response?.data?.errors
  if (errs) return Object.values(errs).flat()[0]
  return e.response?.data?.message || fallback
}

async function load() {
  const { data } = await client.get('/admin/groups', { params: { per_page: 100 } })
  groups.value = data.data
}

async function loadUsers() {
  const { data } = await client.get('/admin/users', { params: { search: search.value, per_page: 100 } })
  users.value = data.data
}

onMounted(() => { load(); loadUsers() })

function resetForm() {
  Object.assign(form, { name: '', description: '', member_ids: [] })
  editingId.value = null
  error.value = ''
}

function toggleForm() {
  if (showForm.value) { showForm.value = false; resetForm() } else { showForm.value = true }
}

async function startEdit(g) {
  error.value = ''
  try {
    const { data } = await client.get(`/admin/groups/${g.id}`)
    Object.assign(form, {
      name: data.name,
      description: data.description || '',
      member_ids: data.members.map((m) => m.id),
    })
    editingId.value = g.id
    showForm.value = true
  } catch (e) {
    error.value = errMsg(e, 'Could not load group.')
  }
}

async function save() {
  error.value = ''
  saving.value = true
  try {
    if (editingId.value) {
      await client.put(`/admin/groups/${editingId.value}`, form)
    } else {
      await client.post('/admin/groups', form)
    }
    showForm.value = false
    resetForm()
    await load()
  } catch (e) {
    error.value = errMsg(e, 'Could not save group.')
  } finally {
    saving.value = false
  }
}
</script>

<template>
  <AppShell>
    <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:1rem;">
      <h1 style="font-size:1.2rem;">Groups</h1>
      <button class="btn btn-primary" @click="toggleForm">{{ showForm ? 'Cancel' : '+ New Group' }}</button>
    </div>

    <form v-if="showForm" class="card grid" style="max-width:440px; margin-bottom:1.5rem;" @submit.prevent="save">
      <h3 style="margin:0;">{{ editingId ? 'Edit group' : 'New group' }}</h3>
      <input class="input" placeholder="Group name" v-model="form.name" required />
      <textarea class="input" placeholder="Description" v-model="form.description"></textarea>

      <div>
        <div style="display:flex; gap:0.5rem; margin-bottom:0.5rem;">
          <input class="input" placeholder="Search people…" v-model="search" @keydown.enter.prevent="loadUsers" />
          <button class="btn" type="button" @click="loadUsers">Search</button>
        </div>
        <div style="max-height:220px; overflow:auto; border:1px solid var(--border); border-radius:8px; padding:0.5rem 0.7rem;">
          <label v-for="u in users" :key="u.id" style="display:flex; align-items:center; gap:0.4rem; margin:0.2rem 0;">
            <input type="checkbox" :value="u.id" v-model="form.member_ids" />
            {{ u.name }} <span style="color:var(--muted); font-size:0.8rem;">{{ u.identifier || u.email }}</span>
          </label>
          <p v-if="!users.length" style="color:var(--muted); margin:0;">No people found.</p>
        </div>
        <p style="color:var(--muted); font-size:0.8rem; margin:0.4rem 0 0;">{{ form.member_ids.length }} selected</p>
      </div>

      <p v-if="error" style="color:#f87171;">{{ error }}</p>
      <button class="btn btn-primary" type="submit" :disabled="saving">{{ saving ? 'Saving…' : (editingId ? 'Save changes' : 'Create') }}</button>
    </form>
    <p v-else-if="error" style="color:#f87171;">{{ error }}</p>

    <div class="card">
      <table>
        <thead><tr><th>Name</th><th>Description</th><th>Members</th><th></th></tr></thead>
        <tbody>
          <tr v-for="g in groups" :key="g.id">
            <td>{{ g.name }}</td>
            <td>{{ g.description }}</td>
            <td>{{ g.members_count }}</td>
            <td><button class="btn" style="padding:0.2rem 0.6rem; font-size:0.8rem;" @click="startEdit(g)">Edit</button></td>
          </tr>
        </tbody>
      </table>
    </div>
  </AppShell>
</template>