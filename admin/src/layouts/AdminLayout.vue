<script setup lang="ts">
import { ref } from 'vue';
import Sidebar from '../components/Sidebar.vue';
import { useAuthStore } from '../stores/auth';
import { Menu, Bell, Wallet } from 'lucide-vue-next';

const isSidebarOpen = ref(false);
const authStore = useAuthStore();

const formatCurrency = (amount: number) => {
  return new Intl.NumberFormat('en-NG', { style: 'currency', currency: 'NGN' }).format(amount);
};
</script>

<template>
  <div class="flex h-screen bg-zinc-950 overflow-hidden">
    <!-- Sidebar -->
    <Sidebar v-model="isSidebarOpen" />

    <!-- Mobile Overlay -->
    <div 
      v-if="isSidebarOpen" 
      @click="isSidebarOpen = false"
      class="fixed inset-0 bg-zinc-950/60 backdrop-blur-sm z-40 lg:hidden"
    ></div>

    <main class="flex-1 overflow-y-auto relative">
      <header class="h-16 border-b border-zinc-800 bg-zinc-950/50 backdrop-blur-md sticky top-0 z-30 flex items-center px-4 lg:px-8 justify-between">
        <!-- Mobile Menu Toggle -->
        <button 
          @click="isSidebarOpen = true"
          class="lg:hidden p-2 text-zinc-400 hover:text-white transition-colors"
        >
          <Menu class="w-6 h-6" />
        </button>

        <div class="flex-1 lg:flex-none"></div>

        <div class="flex items-center gap-4 lg:gap-6">
          <!-- Wallet Balance (Logged In Customers) -->
          <div v-if="authStore.isAuthenticated && !authStore.isAdmin" class="hidden sm:flex items-center gap-2 px-4 py-1.5 bg-zinc-900 border border-zinc-800 rounded-full">
             <Wallet class="w-4 h-4 text-emerald-500" />
             <span class="text-sm font-black text-white">{{ formatCurrency(authStore.user?.wallet?.balance || 0) }}</span>
          </div>

          <template v-if="authStore.isAuthenticated">
            <button class="p-2 text-zinc-400 hover:text-white transition-colors">
              <span class="sr-only">Notifications</span>
              <div class="relative">
                <div class="absolute -top-1 -right-1 w-2 h-2 bg-blue-600 rounded-full"></div>
                <Bell class="w-6 h-6" />
              </div>
            </button>
            <div class="w-px h-6 bg-zinc-800 hidden sm:block"></div>
            <div class="flex items-center gap-3">
              <div class="w-8 h-8 rounded-full bg-zinc-800 flex items-center justify-center text-zinc-400 font-bold">
                {{ authStore.user?.name?.charAt(0) || 'U' }}
              </div>
              <span class="text-sm font-medium text-white hidden sm:block">{{ authStore.user?.name || 'User' }}</span>
            </div>
          </template>
          
          <template v-else>
            <router-link 
              to="/login" 
              class="text-sm font-bold text-zinc-400 hover:text-white transition-colors"
            >
              Login
            </router-link>
            <router-link 
              to="/register" 
              class="px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white text-sm font-black rounded-xl transition-all shadow-lg shadow-blue-600/20"
            >
              Join Movam
            </router-link>
          </template>
        </div>
      </header>
      
      <div class="p-4 lg:p-8">
        <router-view />
      </div>
    </main>
  </div>
</template>
