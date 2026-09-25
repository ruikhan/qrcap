<script setup>
import { onMounted, reactive, ref } from 'vue'
import client from '../../api/client'
import AppShell from '../../components/AppShell.vue'

const users = ref([])
const showForm = ref(false)
const error = ref('')
const roleOptions = ['attendee', 'staff', 'admin', 'super_admin']
const form = reactive({ name: '', email: '', identifier: '', password: '', roles: ['attendee'] })

async function load() {
  const { data } = await client.get('/admin/users')
  users.value = data.data
}
onMounted(load)

async function createUser() {
  error.value = ''
  try {
    await client.post('/admin/users', form)
    showForm.value = false
    Object.assign(form, { name: '', email: '', identifier: '', password: '', roles: ['attendee'] })
    await load()
  } catch (e) {
    error.value = e.response?.data?.message || 'Could not create user.'
  }
}
</script>

<template>
  <AppShell>
    <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:1rem;">
      <h1 style="font-size:1.2rem;">People</h1>
      <button class="btn btn-primary" @click="showForm = !showForm">{{ showForm ? 'Cancel' : '+ New User' }}</button>
    </div>

    <form v-if="showForm" class="card grid" style="max-width:400px; margin-bottom:1.5rem;" @submit.prevent="createUser">
      <input class="input" placeholder="Full name" v-model="form.name" required />
      <input class="input" type="email" placeholder="Email" v-model="form.email" required />
      <input class="input" placeholder="Identifier (ID number, optional)" v-model="form.identifier" />
      <input class="input" type="password" placeholder="Temporary password" v-model="form.password" required />
      <div>
        <label v-for="r in roleOptions" :key="r" style="display:inline-flex; align-items:center; gap:0.3rem; margin-right:0.75rem;">
          <input type="checkbox" :value="r" v-model="form.roles" /> {{ r }}
        </label>
      </div>
      <p v-if="error" style="color:#f87171;">{{ error }}</p>
      <button class="btn btn-primary" type="submit">Create</button>
    </form>

    <div class="card">
      <table>
        <thead><tr><th>Name</th><th>Email</th><th>Roles</th><th>Status</th></tr></thead>
        <tbody>
          <tr v-for="u in users" :key="u.id">
            <td>{{ u.name }}</td>
            <td>{{ u.email }}</td>
            <td>{{ u.roles.map(r => r.name).join(', ') }}</td>
            <td>{{ u.account_status }}</td>
          </tr>
        </tbody>
      </table>
    </div>
  </AppShell>
</template>