<script setup>
import { Html5Qrcode } from 'html5-qrcode'
import { onBeforeUnmount, onMounted, ref } from 'vue'
import client from '../../api/client'
import AppShell from '../../components/AppShell.vue'

const scannerId = 'qr-reader'
let html5Qr = null
const scanning = ref(false)
const step = ref('scan') // scan -> confirm -> done
const sessionInfo = ref(null)
const receipt = ref(null)
const error = ref('')
const busy = ref(false)
let lastPayload = ''

onMounted(startScanner)
onBeforeUnmount(stopScanner)

async function startScanner() {
  error.value = ''
  step.value = 'scan'
  sessionInfo.value = null
  receipt.value = null
  try {
    html5Qr = new Html5Qrcode(scannerId)
    await html5Qr.start(
      { facingMode: 'environment' },
      { fps: 10, qrbox: 240 },
      onScanSuccess,
      () => {} // ignore per-frame scan failures
    )
    scanning.value = true
  } catch (e) {
    error.value = 'Could not access camera. Check camera permission and try again.'
  }
}

async function stopScanner() {
  if (html5Qr && scanning.value) {
    try { await html5Qr.stop() } catch (e) { /* already stopped */ }
    scanning.value = false
  }
}

async function onScanSuccess(decodedText) {
  if (busy.value || decodedText === lastPayload) return
  lastPayload = decodedText
  busy.value = true
  try {
    const { data } = await client.post('/checkin/validate', { payload: decodedText })
    sessionInfo.value = { ...data.session, already_checked_in: data.already_checked_in, payload: decodedText }
    step.value = 'confirm'
    await stopScanner()
  } catch (e) {
    error.value = e.response?.data?.message || 'Invalid or expired QR code.'
    lastPayload = ''
  } finally {
    busy.value = false
  }
}

async function confirmCheckin() {
  busy.value = true
  error.value = ''
  try {
    const geo = await getLocation().catch(() => null)
    const { data } = await client.post('/checkin/confirm', {
      payload: sessionInfo.value.payload,
      lat: geo?.lat,
      lng: geo?.lng,
    })
    receipt.value = data.receipt
    step.value = 'done'
  } catch (e) {
    error.value = e.response?.data?.message || 'Check-in failed.'
  } finally {
    busy.value = false
  }
}

function getLocation() {
  return new Promise((resolve, reject) => {
    if (!navigator.geolocation) return reject()
    navigator.geolocation.getCurrentPosition(
      (pos) => resolve({ lat: pos.coords.latitude, lng: pos.coords.longitude }),
      () => reject(),
      { timeout: 4000 }
    )
  })
}

function scanAgain() {
  startScanner()
}
</script>

<template>
  <AppShell>
    <h1 style="font-size:1.2rem; margin-bottom:1rem;">Scan QR Code</h1>

    <div v-if="step === 'scan'" class="card" style="max-width:420px;">
      <div id="qr-reader" style="width:100%;"></div>
      <p v-if="error" style="color:#f87171; margin-top:0.75rem;">{{ error }}</p>
    </div>

    <div v-else-if="step === 'confirm'" class="card" style="max-width:420px;">
      <h2 style="font-size:1.05rem;">{{ sessionInfo.title }}</h2>
      <p style="color:var(--muted); font-size:0.9rem;">{{ sessionInfo.location }}</p>
      <p v-if="sessionInfo.already_checked_in" style="color:#fbbf24;">You've already checked in to this session.</p>
      <p v-if="error" style="color:#f87171;">{{ error }}</p>
      <div style="display:flex; gap:0.5rem; margin-top:1rem;">
        <button class="btn btn-primary" :disabled="busy" @click="confirmCheckin">
          {{ busy ? 'Confirming…' : 'Confirm Check-in' }}
        </button>
        <button class="btn" @click="scanAgain">Cancel</button>
      </div>
    </div>

    <div v-else-if="step === 'done'" class="card" style="max-width:420px; text-align:center;">
      <h2 style="font-size:1.4rem; margin-bottom:0.5rem;">
        <span :class="'badge badge-' + (receipt.status === 'present' ? 'present' : receipt.status === 'late' ? 'late' : 'other')">
          {{ receipt.status.toUpperCase() }}
        </span>
      </h2>
      <p style="color:var(--muted);">Checked in at {{ new Date(receipt.checked_in_at).toLocaleTimeString() }}</p>
      <p v-if="receipt.late_minutes > 0" style="color:#fbbf24;">{{ receipt.late_minutes }} minutes late</p>
      <button class="btn btn-primary" style="margin-top:1rem;" @click="scanAgain">Scan another</button>
    </div>
  </AppShell>
</template>