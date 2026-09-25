<script setup>
import QrcodeVue from 'qrcode.vue'
import { onBeforeUnmount, onMounted, ref } from 'vue'
import { useRoute } from 'vue-router'
import client from '../../api/client'
import AppShell from '../../components/AppShell.vue'
import CorrectionDialog from '../../components/CorrectionDialog.vue'

const route = useRoute()
const sessionId = route.params.id

const session = ref(null)
const qrPayload = ref('')
const qrCountdown = ref(0)
const live = ref({ expected: 0, present: 0, late: 0, absent: 0, recent: [] })
const error = ref('')
const correctionTarget = ref(null)

let qrTimer = null
let countdownTimer = null
let liveTimer = null

async function loadSession() {
  const { data } = await client.get(`/admin/sessions/${sessionId}`)
  session.value = data
}

async function refreshLive() {
  const { data } = await client.get(`/admin/sessions/${sessionId}/live`)
  live.value = data
}

async function refreshQr() {
  if (session.value?.status !== 'open') return
  try {
    const { data } = await client.get(`/admin/sessions/${sessionId}/qr/current`)
    qrPayload.value = data.payload
    qrCountdown.value = data.rotates_in_seconds
    clearInterval(countdownTimer)
    countdownTimer = setInterval(() => {
      if (qrCountdown.value > 0) qrCountdown.value--
    }, 1000)
  } catch (e) {
    error.value = 'Could not refresh QR code.'
  }
}

async function doAction(action) {
  error.value = ''
  try {
    await client.post(`/admin/sessions/${sessionId}/${action}`)
    await loadSession()
    setupTimers()
  } catch (e) {
    error.value = e.response?.data?.message || 'Action failed.'
  }
}

function setupTimers() {
  clearInterval(qrTimer)
  clearInterval(liveTimer)
  if (session.value?.status === 'open') {
    refreshQr()
    qrTimer = setInterval(refreshQr, (session.value.qr_rotation_seconds || 30) * 1000)
  }
  refreshLive()
  liveTimer = setInterval(refreshLive, 5000)
}

onMounted(async () => {
  await loadSession()
  setupTimers()
})

onBeforeUnmount(() => {
  clearInterval(qrTimer)
  clearInterval(countdownTimer)
  clearInterval(liveTimer)
})

function badgeClass(status) {
  return 'badge badge-' + (status === 'present' ? 'present' : status === 'late' ? 'late' : status === 'absent' ? 'absent' : 'other')
}

async function onCorrected() {
  correctionTarget.value = null
  await refreshLive()
}
</script>

<template>
  <AppShell v-if="session">
    <div style="display:flex; justify-content:space-between; align-items:flex-start; margin-bottom:1rem;">
      <div>
        <h1 style="font-size:1.2rem; margin:0;">{{ session.title }}</h1>
        <p style="color:var(--muted); margin:0.2rem 0;">{{ session.location }} · {{ new Date(session.starts_at).toLocaleString() }}</p>
        <span :class="badgeClass(session.status === 'open' ? 'present' : 'other')">{{ session.status.toUpperCase() }}</span>
      </div>
      <div style="display:flex; gap:0.5rem;">
        <button v-if="['draft','paused'].includes(session.status)" class="btn btn-primary" @click="doAction('open')">Open</button>
        <button v-if="session.status === 'open'" class="btn" @click="doAction('pause')">Pause</button>
        <button v-if="['open','paused'].includes(session.status)" class="btn btn-danger" @click="doAction('close')">Close</button>
      </div>
    </div>
    <p v-if="error" style="color:#f87171;">{{ error }}</p>

    <div class="grid" style="grid-template-columns: 320px 1fr; align-items:start;">
      <div class="card" style="text-align:center;">
        <template v-if="session.status === 'open' && qrPayload">
          <QrcodeVue :value="qrPayload" :size="240" level="M" />
          <p style="color:var(--muted); margin-top:0.75rem;">Refreshes in {{ qrCountdown }}s</p>
        </template>
        <p v-else style="color:var(--muted);">QR code appears once the session is open.</p>
      </div>

      <div class="grid" style="gap:1rem;">
        <div class="grid" style="grid-template-columns: repeat(4, 1fr); gap:0.75rem;">
          <div class="card" style="text-align:center;"><div style="font-size:1.5rem;">{{ live.expected }}</div><div style="color:var(--muted); font-size:0.8rem;">Expected</div></div>
          <div class="card" style="text-align:center;"><div style="font-size:1.5rem; color:#4ade80;">{{ live.present }}</div><div style="color:var(--muted); font-size:0.8rem;">Present</div></div>
          <div class="card" style="text-align:center;"><div style="font-size:1.5rem; color:#fbbf24;">{{ live.late }}</div><div style="color:var(--muted); font-size:0.8rem;">Late</div></div>
          <div class="card" style="text-align:center;"><div style="font-size:1.5rem; color:#f87171;">{{ live.absent }}</div><div style="color:var(--muted); font-size:0.8rem;">Absent</div></div>
        </div>

        <div class="card">
          <h3 style="margin-top:0;">Recent Check-ins</h3>
          <table>
            <thead><tr><th>Name</th><th>Status</th><th>Time</th><th></th></tr></thead>
            <tbody>
              <tr v-for="r in live.recent" :key="r.id">
                <td>{{ r.user?.name }}</td>
                <td><span :class="badgeClass(r.status)">{{ r.status }}</span></td>
                <td>{{ r.checked_in_at ? new Date(r.checked_in_at).toLocaleTimeString() : '—' }}</td>
                <td><button class="btn" style="padding:0.2rem 0.6rem; font-size:0.8rem;" @click="correctionTarget = r">Correct</button></td>
              </tr>
              <tr v-if="!live.recent.length"><td colspan="4" style="color:var(--muted);">No check-ins yet.</td></tr>
            </tbody>
          </table>
        </div>
      </div>
    </div>

    <CorrectionDialog v-if="correctionTarget" :record="correctionTarget" @close="correctionTarget = null" @saved="onCorrected" />
  </AppShell>
</template>