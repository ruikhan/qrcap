import { createRouter, createWebHistory } from 'vue-router'
import { useAuthStore } from '../stores/auth'

import Login from '../views/Login.vue'
import AuditLogs from '../views/admin/AuditLogs.vue'
import DashboardOverview from '../views/admin/DashboardOverview.vue'
import Groups from '../views/admin/Groups.vue'
import People from '../views/admin/People.vue'
import Reports from '../views/admin/Reports.vue'
import SessionDetail from '../views/admin/SessionDetail.vue'
import Sessions from '../views/admin/Sessions.vue'
import History from '../views/attendee/History.vue'
import Scanner from '../views/attendee/Scanner.vue'

const routes = [
  { path: '/login', component: Login, meta: { public: true } },
  { path: '/scan', component: Scanner },
  { path: '/history', component: History },
  { path: '/admin', component: DashboardOverview, meta: { staffOnly: true } },
  { path: '/admin/sessions', component: Sessions, meta: { staffOnly: true } },
  { path: '/admin/sessions/:id', component: SessionDetail, meta: { staffOnly: true } },
  { path: '/admin/people', component: People, meta: { adminOnly: true } },
  { path: '/admin/groups', component: Groups, meta: { adminOnly: true } },
  { path: '/admin/reports', component: Reports, meta: { adminOnly: true } },
  { path: '/admin/audit-logs', component: AuditLogs, meta: { adminOnly: true } },
  { path: '/', redirect: '/scan' },
]

const router = createRouter({ history: createWebHistory(), routes })

router.beforeEach((to) => {
  const auth = useAuthStore()

  if (to.meta.public) return true
  if (!auth.isAuthenticated) return '/login'
  if (to.meta.adminOnly && !auth.isAdmin) return '/scan'
  if (to.meta.staffOnly && !auth.isStaffOrAbove) return '/scan'
  return true
})

export default router