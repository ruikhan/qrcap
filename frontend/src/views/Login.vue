<script setup>
import { ref } from 'vue'
import { useRouter } from 'vue-router'
import { useAuthStore } from '../stores/auth'

const email = ref('')
const password = ref('')
const showPassword = ref(false)
const error = ref('')
const loading = ref(false)
const auth = useAuthStore()
const router = useRouter()
const year = new Date().getFullYear()

function friendlyError(e) {
  if (!e.response) return 'Cannot reach the server. Check your connection and try again.'
  const errs = e.response.data?.errors
  if (errs) return Object.values(errs).flat()[0]
  if (e.response.status === 429) return 'Too many sign-in attempts. Wait a minute, then try again.'
  return e.response.data?.message || 'Sign-in failed. Check your details and try again.'
}

async function submit() {
  error.value = ''
  loading.value = true
  try {
    await auth.login(email.value, password.value)
    router.push(auth.isStaffOrAbove ? '/admin' : '/scan')
  } catch (e) {
    error.value = friendlyError(e)
  } finally {
    loading.value = false
  }
}
</script>

<template>
  <div class="lg-root">
    <div class="lg-window" role="main">
      <div class="lg-titlebar" aria-hidden="true">
        <div class="lg-dots">
          <span class="lg-dot" style="background:#ff5f57"></span>
          <span class="lg-dot" style="background:#febc2e"></span>
          <span class="lg-dot" style="background:#28c840"></span>
        </div>
        <div class="lg-title">QRCAP</div>
      </div>

      <div class="lg-body">
        <!-- Brand panel -->
        <aside class="lg-aside">
          <div class="lg-mark" aria-hidden="true">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linejoin="round" stroke-linecap="round">
              <rect x="3" y="3" width="7" height="7" rx="1.5" />
              <rect x="14" y="3" width="7" height="7" rx="1.5" />
              <rect x="3" y="14" width="7" height="7" rx="1.5" />
              <path d="M14 14h3v3h-3zM20 14v1M14 20h1M19 19h2v2h-2z" />
            </svg>
          </div>
          <p class="lg-headline">Attendance, verified at the door.</p>
          <p class="lg-sub">One system for running sessions, checking people in, and reporting on who was there.</p>

          <ul class="lg-features">
            <li>
              <span class="lg-icon" aria-hidden="true">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M20 12a8 8 0 1 1-2.34-5.66" /><path d="M20 4v5h-5" /></svg>
              </span>
              <span>
                <span class="lg-f-title">Rotating QR codes</span>
                <span class="lg-f-text">Codes refresh automatically, so a shared screenshot stops working.</span>
              </span>
            </li>
            <li>
              <span class="lg-icon" aria-hidden="true">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M12 3l7 3v5c0 4.5-3 8-7 10-4-2-7-5.5-7-10V6z" /><path d="M9 12l2 2 4-4" /></svg>
              </span>
              <span>
                <span class="lg-f-title">Checked on the server</span>
                <span class="lg-f-text">Eligibility, timing, and duplicates are verified before anything is recorded.</span>
              </span>
            </li>
            <li>
              <span class="lg-icon" aria-hidden="true">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><rect x="5" y="3" width="14" height="18" rx="2" /><path d="M9 8h6M9 12h6M9 16h4" /></svg>
              </span>
              <span>
                <span class="lg-f-title">Full audit trail</span>
                <span class="lg-f-text">Every correction is logged with who made it, when, and why.</span>
              </span>
            </li>
          </ul>
        </aside>

        <!-- Sign-in panel -->
        <section class="lg-panel">
          <div class="lg-mobile-brand" aria-hidden="true">
            <div class="lg-mark lg-mark-sm">
              <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linejoin="round" stroke-linecap="round">
                <rect x="3" y="3" width="7" height="7" rx="1.5" />
                <rect x="14" y="3" width="7" height="7" rx="1.5" />
                <rect x="3" y="14" width="7" height="7" rx="1.5" />
                <path d="M14 14h3v3h-3zM20 14v1M14 20h1M19 19h2v2h-2z" />
              </svg>
            </div>
            <span>QRCAP</span>
          </div>

          <h1 class="lg-h1">Sign in</h1>
          <p class="lg-lead">Use the account your administrator created for you.</p>

          <form class="lg-form" :aria-busy="loading" @submit.prevent="submit">
            <div>
              <label class="lg-label" for="login-email">Email</label>
              <input
                id="login-email"
                class="lg-field"
                type="email"
                name="email"
                autocomplete="username"
                placeholder="name@organization.com"
                v-model="email"
                required
              />
            </div>

            <div>
              <label class="lg-label" for="login-password">Password</label>
              <div class="lg-pw">
                <input
                  id="login-password"
                  class="lg-field lg-field-pw"
                  :type="showPassword ? 'text' : 'password'"
                  name="password"
                  autocomplete="current-password"
                  placeholder="Enter your password"
                  v-model="password"
                  required
                />
                <button
                  type="button"
                  class="lg-eye"
                  :aria-label="showPassword ? 'Hide password' : 'Show password'"
                  :aria-pressed="showPassword"
                  @click="showPassword = !showPassword"
                >
                  <svg v-if="!showPassword" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M2 12s3.5-7 10-7 10 7 10 7-3.5 7-10 7S2 12 2 12z" /><circle cx="12" cy="12" r="3" /></svg>
                  <svg v-else viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M3 3l18 18" /><path d="M10.6 5.1A10 10 0 0 1 12 5c6.5 0 10 7 10 7a17 17 0 0 1-3.2 4.1M6.6 6.6A17 17 0 0 0 2 12s3.5 7 10 7a9.7 9.7 0 0 0 4.4-1" /><path d="M9.9 9.9a3 3 0 0 0 4.2 4.2" /></svg>
                </button>
              </div>
            </div>

            <p v-if="error" class="lg-error" role="alert">{{ error }}</p>

            <button class="lg-btn" type="submit" :disabled="loading">
              <span v-if="loading" class="lg-spinner" aria-hidden="true"></span>
              {{ loading ? 'Signing in…' : 'Sign in' }}
            </button>
          </form>

          <p class="lg-help">Can't sign in? Ask your administrator to check your account.</p>
        </section>
      </div>

      <footer class="lg-status">
        <span>Designed and Developed by <strong>Justine Villarosa</strong></span>
        <span class="lg-copy">© {{ year }} QRCAP</span>
      </footer>
    </div>
  </div>
</template>

<style scoped>
.lg-root {
  --ink: #f5f5f7;
  --ink-2: rgba(245, 245, 247, 0.72);
  --ink-3: rgba(245, 245, 247, 0.5);
  --glass: rgba(28, 30, 44, 0.66);
  --glass-side: rgba(255, 255, 255, 0.04);
  --line: rgba(255, 255, 255, 0.12);
  --field: rgba(255, 255, 255, 0.07);
  --field-line: rgba(255, 255, 255, 0.16);
  --accent: #0071e3;
  --accent-hover: #0a84ff;
  --icon-bg: rgba(10, 132, 255, 0.2);
  --icon-fg: #6cb6ff;
  --danger: #ff8a84;
  --danger-bg: rgba(255, 69, 58, 0.16);
  --shadow: 0 40px 90px rgba(0, 0, 0, 0.55), 0 0 0 0.5px rgba(255, 255, 255, 0.08);
  --wall-base: #0a0e1f;
  --wall-a: #5b3cc4;
  --wall-b: #0a6fd6;
  --wall-c: #1fa7a0;

  position: relative;
  min-height: 100vh;
  min-height: 100dvh;
  display: flex;
  align-items: center;
  justify-content: center;
  padding: 32px 20px;
  overflow: hidden;
  background: var(--wall-base);
  color: var(--ink);
  color-scheme: dark light;
  font-family: -apple-system, BlinkMacSystemFont, 'SF Pro Display', 'SF Pro Text', 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif;
  -webkit-font-smoothing: antialiased;
  letter-spacing: -0.01em;
}

@media (prefers-color-scheme: light) {
  .lg-root {
    --ink: #1d1d1f;
    --ink-2: rgba(29, 29, 31, 0.72);
    --ink-3: rgba(29, 29, 31, 0.55);
    --glass: rgba(255, 255, 255, 0.7);
    --glass-side: rgba(255, 255, 255, 0.4);
    --line: rgba(0, 0, 0, 0.1);
    --field: rgba(255, 255, 255, 0.85);
    --field-line: rgba(0, 0, 0, 0.16);
    --icon-bg: rgba(0, 113, 227, 0.12);
    --icon-fg: #0071e3;
    --danger: #b3261e;
    --danger-bg: rgba(255, 69, 58, 0.12);
    --shadow: 0 30px 70px rgba(40, 60, 120, 0.22), 0 0 0 0.5px rgba(0, 0, 0, 0.06);
    --wall-base: #e8edfa;
    --wall-a: #c9bcff;
    --wall-b: #9fcbff;
    --wall-c: #b4efe8;
  }
}

/* Soft wallpaper behind the window */
.lg-root::before {
  content: '';
  position: absolute;
  inset: -20%;
  background:
    radial-gradient(42% 40% at 16% 20%, var(--wall-a) 0%, transparent 70%),
    radial-gradient(38% 42% at 88% 30%, var(--wall-b) 0%, transparent 70%),
    radial-gradient(36% 36% at 60% 95%, var(--wall-c) 0%, transparent 70%);
  opacity: 0.6;
  filter: blur(30px);
}

.lg-window {
  position: relative;
  width: min(880px, 100%);
  border-radius: 14px;
  background: var(--glass);
  -webkit-backdrop-filter: blur(40px) saturate(180%);
  backdrop-filter: blur(40px) saturate(180%);
  border: 1px solid var(--line);
  box-shadow: var(--shadow);
  overflow: hidden;
  animation: lg-in 0.5s cubic-bezier(0.2, 0.8, 0.2, 1) both;
}

@keyframes lg-in {
  from { opacity: 0; transform: translateY(10px) scale(0.985); }
  to { opacity: 1; transform: none; }
}

.lg-titlebar {
  position: relative;
  display: flex;
  align-items: center;
  height: 44px;
  padding: 0 16px;
  border-bottom: 1px solid var(--line);
}
.lg-dots { display: flex; gap: 8px; }
.lg-dot { width: 12px; height: 12px; border-radius: 50%; box-shadow: inset 0 0 0 0.5px rgba(0, 0, 0, 0.25); }
.lg-title {
  position: absolute;
  left: 0;
  right: 0;
  text-align: center;
  font-size: 13px;
  font-weight: 600;
  color: var(--ink-2);
  pointer-events: none;
}

.lg-body { display: grid; grid-template-columns: 1.05fr 1fr; }

/* Brand panel */
.lg-aside {
  padding: 40px 36px;
  background: var(--glass-side);
  border-right: 1px solid var(--line);
}
.lg-mark {
  width: 48px;
  height: 48px;
  display: grid;
  place-items: center;
  border-radius: 12px;
  color: #fff;
  background: linear-gradient(145deg, #0a84ff, #5e5ce6);
  box-shadow: 0 8px 20px rgba(10, 132, 255, 0.35), inset 0 1px 0 rgba(255, 255, 255, 0.3);
}
.lg-mark svg { width: 26px; height: 26px; }
.lg-headline {
  margin: 24px 0 8px;
  font-size: 30px;
  line-height: 1.12;
  font-weight: 700;
  letter-spacing: -0.022em;
}
.lg-sub { margin: 0; font-size: 15px; line-height: 1.5; color: var(--ink-2); max-width: 34ch; }

.lg-features { list-style: none; margin: 32px 0 0; padding: 0; display: grid; gap: 18px; }
.lg-features li { display: flex; gap: 12px; align-items: flex-start; }
.lg-icon {
  flex: none;
  width: 34px;
  height: 34px;
  display: grid;
  place-items: center;
  border-radius: 9px;
  background: var(--icon-bg);
  color: var(--icon-fg);
}
.lg-icon svg { width: 18px; height: 18px; }
.lg-f-title { display: block; font-size: 14px; font-weight: 600; }
.lg-f-text { display: block; margin-top: 2px; font-size: 13px; line-height: 1.45; color: var(--ink-2); }

/* Sign-in panel */
.lg-panel { padding: 40px 40px 32px; display: flex; flex-direction: column; justify-content: center; }
.lg-mobile-brand { display: none; align-items: center; gap: 10px; margin-bottom: 20px; font-weight: 700; font-size: 17px; }
.lg-mark-sm { width: 36px; height: 36px; border-radius: 9px; }
.lg-mark-sm svg { width: 20px; height: 20px; }

.lg-h1 { margin: 0; font-size: 26px; font-weight: 700; letter-spacing: -0.022em; }
.lg-lead { margin: 6px 0 24px; font-size: 14px; color: var(--ink-2); }

.lg-form { display: grid; gap: 16px; }
.lg-label { display: block; margin: 0 0 6px; font-size: 13px; font-weight: 500; color: var(--ink-2); }
.lg-field {
  width: 100%;
  height: 42px;
  padding: 0 12px;
  border-radius: 9px;
  border: 1px solid var(--field-line);
  background: var(--field);
  color: var(--ink);
  font: inherit;
  font-size: 15px;
  outline: none;
  transition: border-color 0.15s, box-shadow 0.15s;
}
.lg-field::placeholder { color: var(--ink-3); }
.lg-field:focus-visible { border-color: var(--accent-hover); box-shadow: 0 0 0 4px rgba(10, 132, 255, 0.32); }

.lg-pw { position: relative; }
.lg-field-pw { padding-right: 44px; }
.lg-eye {
  position: absolute;
  top: 6px;
  right: 6px;
  width: 30px;
  height: 30px;
  display: grid;
  place-items: center;
  border: 0;
  border-radius: 7px;
  background: transparent;
  color: var(--ink-2);
  cursor: pointer;
}
.lg-eye:hover { background: var(--field); color: var(--ink); }
.lg-eye:focus-visible { outline: 2px solid var(--accent-hover); outline-offset: 1px; }
.lg-eye svg { width: 18px; height: 18px; }

.lg-error {
  margin: 0;
  padding: 10px 12px;
  border-radius: 9px;
  background: var(--danger-bg);
  color: var(--danger);
  font-size: 13px;
  line-height: 1.4;
}

.lg-btn {
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 8px;
  height: 42px;
  border: 0;
  border-radius: 9px;
  background: var(--accent);
  color: #fff;
  font: inherit;
  font-size: 15px;
  font-weight: 600;
  cursor: pointer;
  transition: background 0.15s, transform 0.1s;
}
.lg-btn:hover:not(:disabled) { background: var(--accent-hover); }
.lg-btn:active:not(:disabled) { transform: scale(0.99); }
.lg-btn:focus-visible { outline: 3px solid rgba(10, 132, 255, 0.5); outline-offset: 2px; }
.lg-btn:disabled { opacity: 0.7; cursor: default; }

.lg-spinner {
  width: 16px;
  height: 16px;
  border-radius: 50%;
  border: 2px solid rgba(255, 255, 255, 0.4);
  border-top-color: #fff;
  animation: lg-spin 0.7s linear infinite;
}
@keyframes lg-spin { to { transform: rotate(360deg); } }

.lg-help { margin: 20px 0 0; font-size: 13px; color: var(--ink-3); }

/* Window status bar with developer credit */
.lg-status {
  display: flex;
  justify-content: space-between;
  gap: 12px;
  flex-wrap: wrap;
  padding: 12px 20px;
  border-top: 1px solid var(--line);
  font-size: 12.5px;
  color: var(--ink-2);
}
.lg-status strong { color: var(--ink); font-weight: 600; }
.lg-copy { color: var(--ink-3); }

@media (max-width: 760px) {
  .lg-body { grid-template-columns: 1fr; }
  .lg-aside { display: none; }
  .lg-panel { padding: 28px 22px 24px; }
  .lg-mobile-brand { display: flex; }
  .lg-status { justify-content: center; text-align: center; }
}

@media (prefers-reduced-motion: reduce) {
  .lg-window { animation: none; }
  .lg-field, .lg-btn { transition: none; }
}
</style>