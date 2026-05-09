import { createRouter, createWebHashHistory } from 'vue-router'

const routes = [
  {
    path: '/',
    redirect: '/license'
  },
  {
    path: '/license',
    name: 'License',
    component: () => import('../views/License.vue')
  },
  {
    path: '/environment',
    name: 'Environment',
    component: () => import('../views/Environment.vue')
  },
  {
    path: '/config',
    name: 'Config',
    component: () => import('../views/Config.vue')
  },
  {
    path: '/install',
    name: 'Install',
    component: () => import('../views/Install.vue')
  },
  {
    path: '/complete',
    name: 'Complete',
    component: () => import('../views/Complete.vue')
  }
]

const router = createRouter({
  history: createWebHashHistory(),
  routes
})

export default router 