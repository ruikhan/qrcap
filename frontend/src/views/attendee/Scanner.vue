<script setup>
import { Html5Qrcode, Html5QrcodeSupportedFormats } from 'html5-qrcode'
import { nextTick, onBeforeUnmount, onMounted, ref } from 'vue'
import client from '../../api/client'
import AppShell from '../../components/AppShell.vue'

const scannerId = 'qr-reader'
let html5Qr = null
let cooldownTimer = null
let lastPayload = ''

const scanning = ref(false)
const step = ref('scan') // scan -> confirm -> done
const sessionInfo = ref(null)
const receipt = ref(null)
const alreadyCheckedIn = ref(false)
const error = ref('')
const busy = ref(false)

onMounted(startScanner)
onBeforeUnmount(() => {
  clearTimeout(cooldownTimer)
  stopScanner()
})

function friendlyError(e, fallback) {
  if (!e.response) return 'Cannot reach the server. Check your internet connection.'
  if (e.response.status === 429) return 'Too many attempts. Wait a few seconds and try again.'
  if (e.response.status === 401) return 'Your login expired. Please sign in again.'
  const errs = e.response.data?.errors
  if (errs) return Object.values(errs).flat()[0]
  return e.response.data?.message || fallback
}

async function startScanner() {
  error.value = ''
  sessionInfo.value = null
  receipt.value = null
  alreadyCheckedIn.value = false
  lastPayload = ''
  step.value = 'scan'
  // #qr-reader only exists while step === 'scan', so wait for Vue to render it first.
  await nextTick()
  try {
    html5Qr = new Html5Qrcode(scannerId, {
      formatsToSupport: [Html5QrcodeSupportedFormats.QR_CODE],
      experimentalFeatures: { useBarCodeDetectorIfSupported: true },
      verbose: false,
    })
    await html5Qr.start(
      { facingMode: 'environment' },
      {
        fps: 10,
        // Scan area scales with the screen; the rotating QR payload is dense and needs room.
        qrbox: (w, h) => {
          const s = Math.floor(Math.min(w, h) * 0.8)
          return { width: s, height: s }
        },
      },
      onScanSuccess,
      () => {} // ignore per-frame decode failures
    )
    scanning.value = true
  } catch (e) {
    error.value = 'Could not access the camera. Allow camera permission for this site and use HTTPS.'
  }
}

async function stopScanner() {
  if (html5Qr && scanning.value) {
    try { await html5Qr.stop() } catch (e) { /* already stopped */ }
    try { html5Qr.clear() } catch (e) { /* nothing to clear */ }
  }
  scanning.value = false
}

async function onScanSuccess(decodedText) {
  if (busy.value || decodedText === lastPayload) return
  lastPayload = decodedText
  busy.value = true
  error.value = ''
  try {
    const { data } = await client.post('/checkin/validate', { payload: decodedText })
    await stopScanner()
    sessionInfo.value = { ...data.session, already_checked_in: data.already_checked_in, payload: decodedText }
    step.value = 'confirm'
  } catch (e) {
    error.value = friendlyError(e, 'Invalid or expired QR code.')
    // The camera decodes the same code ~10x per second. Without this pause a single
    // rejected code floods /checkin/validate and trips the rate limit.
    clearTimeout(cooldownTimer)
    cooldownTimer = setTimeout(() => { lastPayload = '' }, 4000)
  } finally {
    busy.value = false
  }
}

async function confirmCheckin() {
  busy.value = true
  error.value = ''
  try {
    let geo = null
    // Only ask for location when the session needs it; otherwise the permission
    // prompt can leave the button stuck on "Confirming…".
    if (sessionInfo.value.require_location) {
      geo = await getLocation().catch(() => null)
      if (!geo) {
        error.value = 'This session requires your location. Allow location access and try again.'
        return
      }
    }
    const { data } = await client.post('/checkin/confirm', {
      payload: sessionInfo.value.payload,
      lat: geo?.lat,
      lng: geo?.lng,
    })
    receipt.value = data.receipt
    alreadyCheckedIn.value = !!data.already_checked_in
    step.value = 'done'
  } catch (e) {
    error.value = friendlyError(e, 'Check-in failed.') + ' If the code expired, tap Cancel and scan again.'
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
      { timeout: 8000, enableHighAccuracy: true }
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
      <p v-if="!error" style="color:var(--muted); font-size:0.85rem; margin-top:0.75rem;">
        Hold your phone 20–30 cm from the screen and keep the whole QR code inside the frame.
      </p>
      <p v-if="error" style="color:#f87171; margin-top:0.75rem;">{{ error }}</p>
      <button v-if="error && !scanning" class="btn" style="margin-top:0.5rem;" @click="scanAgain">Try camera again</button>
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
      <p v-if="alreadyCheckedIn" style="color:#fbbf24;">You were already checked in earlier.</p>
      <p style="color:var(--muted);">Checked in at {{ new Date(receipt.checked_in_at).toLocaleTimeString() }}</p>
      <p v-if="receipt.late_minutes > 0" style="color:#fbbf24;">{{ receipt.late_minutes }} minutes late</p>
      <button class="btn btn-primary" style="margin-top:1rem;" @click="scanAgain">Scan another</button>
    </div>
  </AppShell>
</template>