<script setup lang="ts">
import { 
  LayoutDashboard, 
  Users, 
  Store, 
  Bike, 
  Settings, 
  LogOut,
  ChevronLeft,
  ChevronRight,
  Package,
  ShoppingBag,
  Map,
  History,
  CreditCard
} from 'lucide-vue-next';
import { ref, computed } from 'vue';
import { useAuthStore } from '../stores/auth';
import { useRouter } from 'vue-router';

const props = defineProps<{
  modelValue?: boolean
}>();

const emit = defineEmits(['update:modelValue']);

const authStore = useAuthStore();
const router = useRouter();
const collapsed = ref(false);

const isMobileOpen = computed({
  get: () => props.modelValue,
  set: (val) => emit('update:modelValue', val)
});

const menuItems = computed(() => {
  if (authStore.isAdmin) {
    return [
      { name: 'Admin Dashboard', icon: LayoutDashboard, path: '/admin/dashboard' },
      { name: 'User Management', icon: Users, path: '/admin/users' },
      { name: 'Merchants', icon: Store, path: '/admin/merchants' },
      { name: 'Riders', icon: Bike, path: '/admin/riders' },
      { name: 'All Orders', icon: Package, path: '/admin/orders' },
      { name: 'Platform Settings', icon: Settings, path: '/admin/settings' },
    ];
  } else if (authStore.isMerchant) {
    return [
      { name: 'Dashboard', icon: LayoutDashboard, path: '/merchant/dashboard' },
      { name: 'My Products', icon: Package, path: '/merchant/products' },
      { name: 'Manage Orders', icon: ShoppingBag, path: '/merchant/orders' },
      { name: 'My Wallet', icon: CreditCard, path: '/merchant/wallet' },
    ];
  } else if (authStore.isRider) {
    return [
      { name: 'Dashboard', icon: LayoutDashboard, path: '/rider/dashboard' },
      { name: 'Available Orders', icon: Map, path: '/rider/available-orders' },
      { name: 'My Wallet', icon: CreditCard, path: '/rider/wallet' },
    ];
  } else if (authStore.isAuthenticated) {
    return [
      { name: 'Browse Food', icon: LayoutDashboard, path: '/customer/home' },
      { name: 'My Orders', icon: History, path: '/customer/orders' },
      { name: 'My Wallet', icon: CreditCard, path: '/customer/wallet' },
      { name: 'My Profile', icon: Users, path: '/customer/profile' },
    ];
  }
  return [
    { name: 'Browse Food', icon: LayoutDashboard, path: '/customer/home' },
    { name: 'My Cart', icon: ShoppingBag, path: '/cart' },
  ];
});

const handleLogout = async () => {
  await authStore.logout();
  router.push('/login');
};
</script>

<template>
  <aside 
    :class="[
      'fixed inset-y-0 left-0 z-50 lg:relative bg-zinc-900 border-r border-zinc-800 transition-all duration-300 flex flex-col',
      collapsed ? 'lg:w-20' : 'lg:w-64',
      isMobileOpen ? 'translate-x-0 w-64' : '-translate-x-full lg:translate-x-0'
    ]"
  >
    <div class="p-6 flex items-center justify-between border-b border-zinc-800">
      <div v-if="!collapsed || isMobileOpen" class="flex items-center gap-2">
        <div class="w-8 h-8 bg-blue-600 rounded-lg flex items-center justify-center font-bold text-white">M</div>
        <span class="font-bold text-xl tracking-tight text-white">MOVAM</span>
      </div>
      <button 
        @click="collapsed = !collapsed"
        class="hidden lg:block p-2 hover:bg-zinc-800 rounded-lg text-zinc-400 transition-colors"
      >
        <ChevronLeft v-if="!collapsed" class="w-5 h-5" />
        <ChevronRight v-else class="w-5 h-5" />
      </button>
      <!-- Mobile Close Button -->
      <button 
        @click="isMobileOpen = false"
        class="lg:hidden p-2 hover:bg-zinc-800 rounded-lg text-zinc-400"
      >
        <X class="w-6 h-6" />
      </button>
    </div>

    <nav class="flex-1 p-4 space-y-2 overflow-y-auto">
      <router-link
        v-for="item in menuItems"
        :key="item.path"
        :to="item.path"
        @click="isMobileOpen = false"
        class="flex items-center gap-3 p-3 rounded-xl text-zinc-400 hover:text-white hover:bg-zinc-800 transition-all group relative"
        active-class="bg-blue-600/10 text-blue-500 !hover:bg-blue-600/20"
      >
        <component :is="item.icon" class="w-5 h-5 flex-shrink-0" />
        <span v-if="!collapsed || isMobileOpen" class="font-medium">{{ item.name }}</span>
        <div v-if="collapsed && !isMobileOpen" class="absolute left-full ml-2 px-2 py-1 bg-zinc-800 text-white text-xs rounded opacity-0 group-hover:opacity-100 transition-opacity pointer-events-none whitespace-nowrap z-50">
          {{ item.name }}
        </div>
      </router-link>
    </nav>

    <div v-if="authStore.isAuthenticated" class="p-4 border-t border-zinc-800">
      <button 
        @click="handleLogout"
        class="w-full flex items-center gap-3 p-3 rounded-xl text-zinc-400 hover:text-red-400 hover:bg-red-400/10 transition-all group"
      >
        <LogOut class="w-5 h-5" />
        <span v-if="!collapsed" class="font-medium">Logout</span>
      </button>
    </div>
  </aside>
</template>
