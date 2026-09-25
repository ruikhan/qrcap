import { defineStore } from 'pinia'
import client from '../api/client'

export const useAuthStore = defineStore('auth', {
  state: () => ({
    token: localStorage.getItem('qrcap_token') || null,
    user: JSON.parse(localStorage.getItem('qrcap_user') || 'null'),
  }),
  getters: {
    isAuthenticated: (state) => !!state.token,
    roles: (state) => state.user?.roles || [],
    isStaffOrAbove: (state) => (state.user?.roles || []).some(r => ['staff', 'admin', 'super_admin'].includes(r)),
    isAdmin: (state) => (state.user?.roles || []).some(r => ['admin', 'super_admin'].includes(r)),
  },
  actions: {
    async login(email, password) {
      const { data } = await client.post('/auth/login', { email, password, device_name: 'web-dashboard' })
      this.token = data.token
      this.user = data.user
      localStorage.setItem('qrcap_token', data.token)
      localStorage.setItem('qrcap_user', JSON.stringify(data.user))
    },
    async logout() {
      try { await client.post('/auth/logout') } catch (e) { /* ignore */ }
      this.clear()
    },
    clear() {
      this.token = null
      this.user = null
      localStorage.removeItem('qrcap_token')
      localStorage.removeItem('qrcap_user')
    },
  },
})