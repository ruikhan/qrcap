<script setup>
import { onMounted, reactive, ref } from 'vue'
import client from '../../api/client'
import AppShell from '../../components/AppShell.vue'

const records = ref([])
const filters = reactive({ status: '', date_from: '', date_to: '' })
const error = ref('')
const exporting = ref(false)

async function load() {
  error.value = ''
  try {
    const { data } = await client.get('/admin/reports/attendance', { params: filters })
    records.value = data.data
  } catch (e) {
    error.value = e.response?.data?.message || 'Could not load report.'
  }
}
onMounted(load)

// window.open() cannot send the Bearer token, so download through axios as a blob.
async function exportCsv() {
  error.value = ''
  exporting.value = true
  try {
    const res = await client.get('/admin/reports/attendance', {
      params: { ...filters, format: 'csv' },
      responseType: 'blob',
    })
    const url = URL.createObjectURL(res.data)
    const a = document.createElement('a')
    a.href = url
    a.download = 'attendance_report.csv'
    document.body.appendChild(a)
    a.click()
    a.remove()
    URL.revokeObjectURL(url)
  } catch (e) {
    error.value = 'Could not export CSV.'
  } finally {
    exporting.value = false
  }
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
      <button class="btn btn-primary" :disabled="exporting" @click="exportCsv">{{ exporting ? 'Exporting…' : 'Export CSV' }}</button>
    </div>
    <p v-if="error" style="color:#f87171;">{{ error }}</p>

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