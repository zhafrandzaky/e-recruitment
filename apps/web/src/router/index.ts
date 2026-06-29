import { createRouter, createWebHistory, type RouteRecordRaw } from 'vue-router'
import { useAuthStore } from '../stores/auth'

const routes: RouteRecordRaw[] = [
  // Public routes
  {
    path: '/',
    redirect: '/jobs',
  },
  {
    path: '/login',
    name: 'login',
    component: () => import('../pages/auth/LoginPage.vue'),
    meta: { requiresGuest: true },
  },
  {
    path: '/register',
    name: 'register',
    component: () => import('../pages/auth/RegisterPage.vue'),
    meta: { requiresGuest: true },
  },
  {
    path: '/forgot-password',
    name: 'forgot-password',
    component: () => import('../pages/auth/ForgotPasswordPage.vue'),
    meta: { requiresGuest: true },
  },
  {
    path: '/reset-password',
    name: 'reset-password',
    component: () => import('../pages/auth/ResetPasswordPage.vue'),
    meta: { requiresGuest: true },
  },
  {
    path: '/jobs',
    name: 'jobs',
    component: () => import('../pages/jobs/JobListPage.vue'),
  },
  {
    path: '/jobs/:id',
    name: 'job-detail',
    component: () => import('../pages/jobs/JobDetailPage.vue'),
  },

  // HR-only routes
  {
    path: '/hr/jobs',
    name: 'hr-jobs',
    component: () => import('../pages/hr/HrJobListPage.vue'),
    meta: { requiresAuth: true, requiresRole: 'hr_admin' },
  },
  {
    path: '/hr/jobs/create',
    name: 'hr-job-create',
    component: () => import('../pages/hr/HrJobFormPage.vue'),
    meta: { requiresAuth: true, requiresRole: 'hr_admin' },
  },
  {
    path: '/hr/jobs/:id/edit',
    name: 'hr-job-edit',
    component: () => import('../pages/hr/HrJobFormPage.vue'),
    meta: { requiresAuth: true, requiresRole: 'hr_admin' },
  },

  // Catch-all
  {
    path: '/:pathMatch(.*)*',
    redirect: '/jobs',
  },
]

const router = createRouter({
  history: createWebHistory(),
  routes,
  scrollBehavior: () => ({ top: 0 }),
})

router.beforeEach((to) => {
  const auth = useAuthStore()

  if (to.meta.requiresGuest && auth.isAuthenticated) {
    return auth.isHrAdmin ? { name: 'hr-jobs' } : { name: 'jobs' }
  }

  if (to.meta.requiresAuth && !auth.isAuthenticated) {
    return { name: 'login', query: { redirect: to.fullPath } }
  }

  if (to.meta.requiresRole && auth.user?.role !== to.meta.requiresRole) {
    return { name: 'jobs' }
  }
})

export default router
