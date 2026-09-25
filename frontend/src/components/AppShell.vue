<script setup>
import { useRouter } from 'vue-router'
import { useAuthStore } from '../stores/auth'

const auth = useAuthStore()
const router = useRouter()

async function handleLogout() {
  await auth.logout()
  router.push('/login')
}
</script>

<template>
  <div style="display:flex; min-height:100vh;">
    <aside style="width:220px; border-right:1px solid var(--border); padding:1rem; flex-shrink:0;">
      <h2 style="font-size:1.1rem; margin-bottom:1.5rem;">QRCAP</h2>
      <nav style="display:flex; flex-direction:column; gap:0.4rem;">
        <router-link v-if="auth.isStaffOrAbove" to="/admin" class="btn" style="text-align:left; border:none; background:transparent;">Dashboard</router-link>
        <router-link v-if="auth.isStaffOrAbove" to="/admin/sessions" class="btn" style="text-align:left; border:none; background:transparent;">Sessions</router-link>
        <router-link v-if="auth.isAdmin" to="/admin/people" class="btn" style="text-align:left; border:none; background:transparent;">People</router-link>
        <router-link v-if="auth.isAdmin" to="/admin/groups" class="btn" style="text-align:left; border:none; background:transparent;">Groups</router-link>
        <router-link v-if="auth.isAdmin" to="/admin/reports" class="btn" style="text-align:left; border:none; background:transparent;">Reports</router-link>
        <router-link v-if="auth.isAdmin" to="/admin/audit-logs" class="btn" style="text-align:left; border:none; background:transparent;">Audit Logs</router-link>
        <div style="height:1px; background:var(--border); margin:0.5rem 0;"></div>
        <router-link to="/scan" class="btn" style="text-align:left; border:none; background:transparent;">Scan QR</router-link>
        <router-link to="/history" class="btn" style="text-align:left; border:none; background:transparent;">My Attendance</router-link>
      </nav>
      <div style="margin-top:2rem; font-size:0.85rem; color:var(--muted);">
        {{ auth.user?.name }}<br />
        <span style="text-transform:capitalize;">{{ auth.roles.join(', ') }}</span>
      </div>
      <button class="btn" style="margin-top:1rem; width:100%;" @click="handleLogout">Log out</button>
    </aside>
    <main style="flex:1; padding:1.5rem; overflow:auto;">
      <slot />
    </main>
  </div>
</template>