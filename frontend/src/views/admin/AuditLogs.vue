<script setup>
import { onMounted, ref } from 'vue'
import client from '../../api/client'
import AppShell from '../../components/AppShell.vue'

const logs = ref([])
onMounted(async () => {
  const { data } = await client.get('/admin/audit-logs')
  logs.value = data.data
})
</script>

<template>
  <AppShell>
    <h1 style="font-size:1.2rem; margin-bottom:1rem;">Audit Logs</h1>
    <div class="card">
      <table>
        <thead><tr><th>Action</th><th>Entity</th><th>By</th><th>When</th></tr></thead>
        <tbody>
          <tr v-for="l in logs" :key="l.id">
            <td>{{ l.action }}</td>
            <td>{{ l.entity_type }} #{{ l.entity_id }}</td>
            <td>{{ l.actor?.name || 'System' }}</td>
            <td>{{ new Date(l.created_at).toLocaleString() }}</td>
          </tr>
        </tbody>
      </table>
    </div>
  </AppShell>
</template>