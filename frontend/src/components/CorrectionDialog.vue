<script setup>
import { ref } from 'vue'
import client from '../api/client'

const props = defineProps({ record: Object })
const emit = defineEmits(['close', 'saved'])

const newStatus = ref(props.record.status)
const reason = ref('')
const error = ref('')
const saving = ref(false)

const statuses = ['present', 'late', 'absent', 'excused', 'pending_review', 'rejected', 'cancelled']

async function save() {
  error.value = ''
  if (reason.value.trim().length < 5) {
    error.value = 'Please provide a reason (at least 5 characters).'
    return
  }
  saving.value = true
  try {
    await client.post(`/admin/attendance/${props.record.id}/adjust`, {
      new_status: newStatus.value,
      reason: reason.value,
    })
    emit('saved')
  } catch (e) {
    error.value = e.response?.data?.message || 'Could not save correction.'
  } finally {
    saving.value = false
  }
}
</script>

<template>
  <div style="position:fixed; inset:0; background:rgba(0,0,0,.5); display:flex; align-items:center; justify-content:center; z-index:50;">
    <div class="card" style="width:360px;">
      <h3 style="margin-top:0;">Correct Attendance</h3>
      <p style="color:var(--muted); font-size:0.9rem;">{{ record.user?.name }} — current status: <strong>{{ record.status }}</strong></p>
      <label style="display:block; margin:0.75rem 0 0.25rem;">New status</label>
      <select class="input" v-model="newStatus">
        <option v-for="s in statuses" :key="s" :value="s">{{ s }}</option>
      </select>
      <label style="display:block; margin:0.75rem 0 0.25rem;">Reason (required)</label>
      <textarea class="input" v-model="reason" rows="3" placeholder="e.g. Phone camera malfunction; identity verified by instructor"></textarea>
      <p v-if="error" style="color:#f87171; font-size:0.85rem;">{{ error }}</p>
      <div style="display:flex; gap:0.5rem; margin-top:1rem;">
        <button class="btn btn-primary" :disabled="saving" @click="save">{{ saving ? 'Saving…' : 'Save Correction' }}</button>
        <button class="btn" @click="emit('close')">Cancel</button>
      </div>
    </div>
  </div>
</template>