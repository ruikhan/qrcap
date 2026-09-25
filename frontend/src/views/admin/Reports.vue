<script setup>
import { onMounted, reactive, ref } from 'vue'
import client from '../../api/client'
import AppShell from '../../components/AppShell.vue'

const records = ref([])
const filters = reactive({ status: '', date_from: '', date_to: '' })

async function load() {
  const { data } = await client.get('/admin/reports/attendance', { params: filters })
  records.value = data.data
}
onMounted(load)

function exportCsv() {
  const params = new URLSearchParams({ ...filters, format: 'csv' }).toString()
  window.open(`${import.meta.env.VITE_API_BASE_URL}/admin/reports/attendance?${params}`, '_blank')
}
</script>

<template>
  <AppShell>
    <h1 style="font-size:1.2rem; margin-bottom:1rem;">Reports</h1>

    <div class="card" style="display:flex; gap:0.75rem; margin-bottom:1rem; align-items:end; flex-wrap:wrap;">
      <label>Status
        <select class="input" v-model="filters.status">
          <option value="">All</option>
          <option value="present">Present</option>
          <option value="late">Late</option>
          <option value="absent">Absent</option>
          <option value="excused">Excused</option>
        </select>
      </label>
      <label>From<input class="input" type="date" v-model="filters.date_from" /></label>
      <label>To<input class="input" type="date" v-model="filters.date_to" /></label>
      <button class="btn" @click="load">Apply</button>
      <button class="btn btn-primary" @click="exportCsv">Export CSV</button>
    </div>

    <div class="card">
      <table>
        <thead><tr><th>Session</th><th>User</th><th>Status</th><th>Checked in</th></tr></thead>
        <tbody>
          <tr v-for="r in records" :key="r.id">
            <td>{{ r.session?.title }}</td>
            <td>{{ r.user?.name }}</td>
            <td>{{ r.status }}</td>
            <td>{{ r.checked_in_at ? new Date(r.checked_in_at).toLocaleString() : '—' }}</td>
          </tr>
        </tbody>
      </table>
    </div>
  </AppShell>
</template>
EOF

cat > src/views/admin/AuditLogs.vue << 'EOF'
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