import { createRouter, createWebHistory } from 'vue-router';
import { useAuthStore } from '../stores/auth';
import Login from '../views/Login.vue';
import Register from '../views/Register.vue';

const router = createRouter({
  history: createWebHistory(),
  routes: [
    {
      path: '/login',
      name: 'login',
      component: Login,
      meta: { guest: true }
    },
    {
      path: '/register',
      name: 'register',
      component: Register,
      meta: { guest: true }
    },
    {
      path: '/admin',
      component: () => import('../layouts/AdminLayout.vue'),
      meta: { requiresAuth: true, role: 'admin' },
      children: [
        {
          path: 'dashboard',
          name: 'admin-dashboard',
          component: () => import('../views/admin/Dashboard.vue')
        },
        {
          path: 'users',
          name: 'admin-users',
          component: () => import('../views/admin/Users.vue')
        },
        {
          path: 'merchants',
          name: 'admin-merchants',
          component: () => import('../views/admin/Merchants.vue')
        },
        {
          path: 'riders',
          name: 'admin-riders',
          component: () => import('../views/admin/Riders.vue')
        },
        {
          path: 'orders',
          name: 'admin-orders',
          component: () => import('../views/admin/Orders.vue')
        },
        {
          path: 'settings',
          name: 'admin-settings',
          component: () => import('../views/admin/Settings.vue')
        }
      ]
    },
    {
      path: '/merchant',
      component: () => import('../layouts/MerchantLayout.vue'),
      meta: { requiresAuth: true, role: 'merchant' },
      children: [
        {
          path: 'dashboard',
          name: 'merchant-dashboard',
          component: () => import('../views/merchant/Dashboard.vue')
        },
        {
          path: 'products',
          name: 'merchant-products',
          component: () => import('../views/merchant/Products.vue')
        },
        {
          path: 'orders',
          name: 'merchant-orders',
          component: () => import('../views/merchant/Orders.vue')
        },
        {
          path: 'wallet',
          name: 'merchant-wallet',
          component: () => import('../views/shared/Wallet.vue')
        }
      ]
    },
    {
      path: '/rider',
      component: () => import('../layouts/AdminLayout.vue'),
      meta: { requiresAuth: true, role: 'rider' },
      children: [
        {
          path: 'dashboard',
          name: 'rider-dashboard',
          component: () => import('../views/rider/Dashboard.vue')
        },
        {
          path: 'available-orders',
          name: 'rider-available-orders',
          component: () => import('../views/rider/AvailableDeliveries.vue')
        },
        {
          path: 'wallet',
          name: 'rider-wallet',
          component: () => import('../views/shared/Wallet.vue')
        }
      ]
    },
    {
      path: '/customer',
      component: () => import('../layouts/AdminLayout.vue'),
      children: [
        {
          path: 'home',
          name: 'customer-home',
          component: () => import('../views/customer/Home.vue')
        },
        {
          path: 'merchant/:slug',
          name: 'customer-merchant-details',
          component: () => import('../views/customer/MerchantDetails.vue')
        },
        {
          path: 'orders',
          name: 'customer-orders',
          component: () => import('../views/customer/Orders.vue'),
          meta: { requiresAuth: true }
        },
        {
          path: 'profile',
          name: 'customer-profile',
          component: () => import('../views/customer/Profile.vue'),
          meta: { requiresAuth: true }
        },
        {
          path: 'wallet',
          name: 'customer-wallet',
          component: () => import('../views/shared/Wallet.vue'),
          meta: { requiresAuth: true }
        }
      ]
    },
    {
      path: '/cart',
      name: 'cart',
      component: () => import('../views/customer/Cart.vue')
    },
    {
      path: '/checkout',
      name: 'checkout',
      component: () => import('../views/customer/Checkout.vue'),
      meta: { requiresAuth: true }
    },
    {
      path: '/orders/:id/track',
      name: 'order-track',
      component: () => import('../views/customer/OrderTracking.vue'),
      meta: { requiresAuth: true }
    },
    {
      path: '/',
      redirect: '/customer/home'
    }
  ]
});

router.beforeEach((to, _, next) => {
  const authStore = useAuthStore();
  const isAuthenticated = authStore.isAuthenticated;

  if (to.meta.requiresAuth && !isAuthenticated) {
    next({ path: '/login', query: { redirect: to.fullPath } });
  } else if (to.meta.guest && isAuthenticated) {
    // Redirect already logged in users
    if (authStore.isAdmin) next('/admin/dashboard');
    else if (authStore.isMerchant) next('/merchant/dashboard');
    else next('/');
  } else if (to.meta.role && !authStore.user?.roles?.some(r => r.name === to.meta.role)) {
    // Role check
    next('/login');
  } else {
    next();
  }
});

export default router;
