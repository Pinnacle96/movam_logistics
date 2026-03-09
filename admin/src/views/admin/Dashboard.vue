<script setup lang="ts">
import { ref, onMounted } from 'vue';
import api from '../../services/api';
import { 
  Users, 
  Package, 
  TrendingUp, 
  CreditCard,
  Loader2,
  ArrowUpRight
} from 'lucide-vue-next';

const data = ref<any>(null);
const loading = ref(true);

onMounted(async () => {
  try {
    const response = await api.get('/admin/dashboard');
    data.value = response.data;
  } catch (err) {
    console.error('Failed to fetch admin dashboard', err);
  } finally {
    loading.value = false;
  }
});

const formatCurrency = (amount: number) => {
  return new Intl.NumberFormat('en-NG', { style: 'currency', currency: 'NGN' }).format(amount);
};
</script>

<template>
  <div v-if="loading" class="flex items-center justify-center h-full">
    <Loader2 class="w-12 h-12 text-blue-500 animate-spin" />
  </div>

  <div v-else-if="data" class="space-y-8 animate-in fade-in duration-500">
    <div>
      <h1 class="text-3xl font-bold text-white">System Overview</h1>
      <p class="text-zinc-400 mt-1">Platform-wide statistics and management.</p>
    </div>

    <!-- Stats Grid -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
      <div class="bg-zinc-900 border border-zinc-800 p-6 rounded-2xl shadow-sm">
        <div class="flex items-center justify-between mb-4">
          <div class="p-3 bg-blue-500/10 rounded-xl text-blue-500">
            <Users class="w-6 h-6" />
          </div>
          <span class="flex items-center text-xs font-bold text-emerald-500 bg-emerald-500/10 px-2 py-1 rounded-lg">
            <ArrowUpRight class="w-3 h-3 mr-1" /> 12%
          </span>
        </div>
        <div>
          <p class="text-zinc-400 text-sm font-medium">Total Users</p>
          <h3 class="text-2xl font-bold text-white">{{ data.stats.total_users }}</h3>
        </div>
      </div>

      <div class="bg-zinc-900 border border-zinc-800 p-6 rounded-2xl shadow-sm">
        <div class="flex items-center justify-between mb-4">
          <div class="p-3 bg-purple-500/10 rounded-xl text-purple-500">
            <Package class="w-6 h-6" />
          </div>
          <span class="flex items-center text-xs font-bold text-emerald-500 bg-emerald-500/10 px-2 py-1 rounded-lg">
            <ArrowUpRight class="w-3 h-3 mr-1" /> 8%
          </span>
        </div>
        <div>
          <p class="text-zinc-400 text-sm font-medium">Total Orders</p>
          <h3 class="text-2xl font-bold text-white">{{ data.stats.total_orders }}</h3>
        </div>
      </div>

      <div class="bg-zinc-900 border border-zinc-800 p-6 rounded-2xl shadow-sm">
        <div class="flex items-center justify-between mb-4">
          <div class="p-3 bg-emerald-500/10 rounded-xl text-emerald-500">
            <TrendingUp class="w-6 h-6" />
          </div>
          <span class="flex items-center text-xs font-bold text-emerald-500 bg-emerald-500/10 px-2 py-1 rounded-lg">
            <ArrowUpRight class="w-3 h-3 mr-1" /> 15%
          </span>
        </div>
        <div>
          <p class="text-zinc-400 text-sm font-medium">Gross Revenue</p>
          <h3 class="text-2xl font-bold text-white">{{ formatCurrency(data.stats.total_revenue) }}</h3>
        </div>
      </div>

      <div class="bg-zinc-900 border border-zinc-800 p-6 rounded-2xl shadow-sm">
        <div class="flex items-center justify-between mb-4">
          <div class="p-3 bg-amber-500/10 rounded-xl text-amber-500">
            <CreditCard class="w-6 h-6" />
          </div>
          <span class="flex items-center text-xs font-bold text-emerald-500 bg-emerald-500/10 px-2 py-1 rounded-lg">
            <ArrowUpRight class="w-3 h-3 mr-1" /> 10%
          </span>
        </div>
        <div>
          <p class="text-zinc-400 text-sm font-medium">Platform Margin</p>
          <h3 class="text-2xl font-bold text-white">{{ formatCurrency(data.stats.platform_commission) }}</h3>
        </div>
      </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-2 gap-8">
      <!-- Order Distribution -->
      <div class="bg-zinc-900 border border-zinc-800 rounded-2xl p-6">
        <h3 class="text-xl font-bold text-white mb-6">Order Status Distribution</h3>
        <div class="space-y-4">
          <div v-for="stat in data.order_stats" :key="stat.status" class="space-y-2">
            <div class="flex justify-between text-sm">
              <span class="text-zinc-400 capitalize font-medium">{{ stat.status }}</span>
              <span class="text-white font-bold">{{ stat.count }}</span>
            </div>
            <div class="w-full bg-zinc-800 h-2 rounded-full overflow-hidden">
              <div 
                class="bg-blue-500 h-full transition-all duration-1000"
                :style="{ width: `${(stat.count / data.stats.total_orders) * 100}%` }"
              ></div>
            </div>
          </div>
        </div>
      </div>

      <!-- Recent Users -->
      <div class="bg-zinc-900 border border-zinc-800 rounded-2xl overflow-hidden shadow-sm">
        <div class="p-6 border-b border-zinc-800 flex items-center justify-between">
          <h3 class="text-xl font-bold text-white">Recently Joined Users</h3>
          <router-link to="/admin/users" class="text-blue-500 hover:text-blue-400 text-sm font-medium">View all</router-link>
        </div>
        <div class="divide-y divide-zinc-800">
          <div v-for="user in data.stats.recent_users" :key="user.id" class="p-4 flex items-center justify-between hover:bg-zinc-800/50 transition-colors">
            <div class="flex items-center gap-3">
              <div class="w-10 h-10 bg-zinc-800 rounded-full flex items-center justify-center text-zinc-400 font-bold">
                {{ user.name.charAt(0) }}
              </div>
              <div>
                <p class="text-white font-medium">{{ user.name }}</p>
                <p class="text-xs text-zinc-500">{{ user.email }}</p>
              </div>
            </div>
            <span class="px-2 py-1 bg-zinc-800 text-zinc-400 rounded-lg text-[10px] font-bold uppercase tracking-wider">
              {{ user.roles[0]?.name }}
            </span>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>
