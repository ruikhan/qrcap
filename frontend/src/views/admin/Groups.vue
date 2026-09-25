<script setup>
import { onMounted, reactive, ref } from 'vue'
import client from '../../api/client'
import AppShell from '../../components/AppShell.vue'

const groups = ref([])
const showForm = ref(false)
const error = ref('')
const form = reactive({ name: '', description: '' })

async function load() {
  const { data } = await client.get('/admin/groups')
  groups.value = data.data
}
onMounted(load)

async function createGroup() {
  error.value = ''
  try {
    await client.post('/admin/groups', form)
    showForm.value = false
    Object.assign(form, { name: '', description: '' })
    await load()
  } catch (e) {
    error.value = e.response?.data?.message || 'Could not create group.'
  }
}
</script>

<template>
  <AppShell>
    <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:1rem;">
      <h1 style="font-size:1.2rem;">Groups</h1>
      <button class="btn btn-primary" @click="showForm = !showForm">{{ showForm ? 'Cancel' : '+ New Group' }}</button>
    </div>

    <form v-if="showForm" class="card grid" style="max-width:400px; margin-bottom:1.5rem;" @submit.prevent="createGroup">
      <input class="input" placeholder="Group name" v-model="form.name" required />
      <textarea class="input" placeholder="Description" v-model="form.description"></textarea>
      <p v-if="error" style="color:#f87171;">{{ error }}</p>
      <button class="btn btn-primary" type="submit">Create</button>
    </form>

    <div class="card">
      <table>
        <thead><tr><th>Name</th><th>Description</th><th>Members</th></tr></thead>
        <tbody>
          <tr v-for="g in groups" :key="g.id">
            <td>{{ g.name }}</td>
            <td>{{ g.description }}</td>
            <td>{{ g.members_count }}</td>
          </tr>
        </tbody>
      </table>
    </div>
  </AppShell>
</template>