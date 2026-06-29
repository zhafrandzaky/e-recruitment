import { createRouter, createWebHistory, type RouteRecordRaw } from 'vue-router'
import { useAuthStore } from '../stores/auth'

const routes: RouteRecordRaw[] = [
  // Landing page — root URL
  {
    path: '/',
    name: 'landing',
    component: () => import('../pages/LandingPage.vue'),
  },

  // Auth routes (guest only)
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

  // Public job listing
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
  {
    path: '/jobs/:id/apply',
    name: 'job-apply',
    component: () => import('../pages/jobs/ApplyPage.vue'),
  },

  // Applicant-only routes
  {
    path: '/applications/me',
    name: 'my-applications',
    component: () => import('../pages/applications/MyApplicationsPage.vue'),
    meta: { requiresAuth: true },
  },
  {
    path: '/applications/:id',
    name: 'application-detail',
    component: () => import('../pages/applications/ApplicationDetailPage.vue'),
    meta: { requiresAuth: true },
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
  {
    path: '/hr/jobs/:id/applicants',
    name: 'hr-applicant-list',
    component: () => import('../pages/hr/HrApplicantListPage.vue'),
    meta: { requiresAuth: true, requiresRole: 'hr_admin' },
  },
  {
    path: '/hr/applicants/:id',
    name: 'hr-applicant-detail',
    component: () => import('../pages/hr/HrApplicantDetailPage.vue'),
    meta: { requiresAuth: true, requiresRole: 'hr_admin' },
  },

  // 404 — explicit, not a silent redirect
  {
    path: '/:pathMatch(.*)*',
    name: 'not-found',
    component: () => import('../pages/NotFoundPage.vue'),
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
