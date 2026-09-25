<script setup>
import { onMounted, ref } from 'vue'
import client from '../../api/client'
import AppShell from '../../components/AppShell.vue'

const records = ref([])
const loading = ref(true)

onMounted(async () => {
  const { data } = await client.get('/me/attendance')
  records.value = data.data
  loading.value = false
})
</script>

<template>
  <AppShell>
    <h1 style="font-size:1.2rem; margin-bottom:1rem;">My Attendance</h1>
    <div class="card">
      <table>
        <thead>
          <tr><th>Session</th><th>Date</th><th>Status</th><th>Late (min)</th></tr>
        </thead>
        <tbody>
          <tr v-for="r in records" :key="r.id">
            <td>{{ r.session?.title }}</td>
            <td>{{ r.checked_in_at ? new Date(r.checked_in_at).toLocaleString() : '—' }}</td>
            <td><span :class="'badge badge-' + (r.status === 'present' ? 'present' : r.status === 'late' ? 'late' : r.status === 'absent' ? 'absent' : 'other')">{{ r.status }}</span></td>
            <td>{{ r.late_minutes || '—' }}</td>
          </tr>
          <tr v-if="!loading && !records.length"><td colspan="4" style="color:var(--muted);">No attendance records yet.</td></tr>
        </tbody>
      </table>
    </div>
  </AppShell>
</template>