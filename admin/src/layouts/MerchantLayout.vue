<script setup lang="ts">
import { ref } from 'vue';
import Sidebar from '../components/Sidebar.vue';
import { Menu, Bell } from 'lucide-vue-next';
import { useAuthStore } from '../stores/auth';

const authStore = useAuthStore();
const isSidebarOpen = ref(false);
</script>

<template>
  <div class="flex h-screen bg-zinc-950 overflow-hidden">
    <!-- Shared Sidebar -->
    <Sidebar v-model="isSidebarOpen" />

    <!-- Mobile Overlay -->
    <div 
      v-if="isSidebarOpen" 
      @click="isSidebarOpen = false"
      class="fixed inset-0 bg-zinc-950/60 backdrop-blur-sm z-40 lg:hidden"
    ></div>

    <!-- Main Content -->
    <main class="flex-1 overflow-y-auto relative">
      <header class="h-16 border-b border-zinc-800 bg-zinc-950/50 backdrop-blur-md sticky top-0 z-30 flex items-center px-4 lg:px-8 justify-between">
        <div class="flex items-center gap-4">
          <!-- Mobile Menu Toggle -->
          <button 
            @click="isSidebarOpen = true"
            class="lg:hidden p-2 text-zinc-400 hover:text-white transition-colors"
          >
            <Menu class="w-6 h-6" />
          </button>
          
          <div class="flex items-center gap-2">
            <span class="text-zinc-500 font-medium hidden sm:inline">Store:</span>
            <span class="text-white font-bold truncate max-w-[150px] sm:max-w-none">{{ authStore.user?.merchant?.business_name || 'My Store' }}</span>
          </div>
        </div>
        
        <div class="flex items-center gap-4">
          <button class="p-2 text-zinc-400 hover:text-white transition-colors relative">
            <Bell class="w-6 h-6" />
            <div class="absolute top-2 right-2 w-2 h-2 bg-emerald-500 rounded-full"></div>
          </button>
          <div class="w-px h-6 bg-zinc-800 hidden sm:block"></div>
          <div class="flex items-center gap-3">
            <div class="w-8 h-8 rounded-full bg-emerald-600/20 flex items-center justify-center text-emerald-500 font-bold">
              {{ authStore.user?.name?.charAt(0) }}
            </div>
            <span class="text-sm font-medium text-white hidden sm:block">{{ authStore.user?.name }}</span>
          </div>
        </div>
      </header>
      
      <div class="p-4 lg:p-8">
        <router-view />
      </div>
    </main>
  </div>
</template>
