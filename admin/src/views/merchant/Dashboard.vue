<script setup lang="ts">
import { ref, onMounted } from 'vue';
import api from '../../services/api';
import { 
  ShoppingBag, 
  Package, 
  TrendingUp, 
  Clock,
  ChevronRight,
  Loader2
} from 'lucide-vue-next';

const data = ref<any>(null);
const loading = ref(true);

onMounted(async () => {
  try {
    const response = await api.get('/merchant/dashboard');
    data.value = response.data;
  } catch (err) {
    console.error('Failed to fetch merchant dashboard', err);
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
    <div class="flex items-end justify-between">
      <div>
        <h1 class="text-3xl font-bold text-white">{{ data.merchant.business_name }} Dashboard</h1>
        <p class="text-zinc-400 mt-1">Monitor your business performance and manage orders.</p>
      </div>
    </div>

    <!-- Stats Grid -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
      <div class="bg-zinc-900 border border-zinc-800 p-6 rounded-2xl shadow-sm">
        <div class="flex items-center gap-4">
          <div class="p-3 bg-blue-500/10 rounded-xl text-blue-500">
            <ShoppingBag class="w-6 h-6" />
          </div>
          <div>
            <p class="text-zinc-400 text-sm font-medium">Total Orders</p>
            <h3 class="text-2xl font-bold text-white">{{ data.stats.total_orders }}</h3>
          </div>
        </div>
      </div>

      <div class="bg-zinc-900 border border-zinc-800 p-6 rounded-2xl shadow-sm">
        <div class="flex items-center gap-4">
          <div class="p-3 bg-amber-500/10 rounded-xl text-amber-500">
            <Clock class="w-6 h-6" />
          </div>
          <div>
            <p class="text-zinc-400 text-sm font-medium">Pending Orders</p>
            <h3 class="text-2xl font-bold text-white">{{ data.stats.pending_orders }}</h3>
          </div>
        </div>
      </div>

      <div class="bg-zinc-900 border border-zinc-800 p-6 rounded-2xl shadow-sm">
        <div class="flex items-center gap-4">
          <div class="p-3 bg-emerald-500/10 rounded-xl text-emerald-500">
            <TrendingUp class="w-6 h-6" />
          </div>
          <div>
            <p class="text-zinc-400 text-sm font-medium">Total Revenue</p>
            <h3 class="text-2xl font-bold text-white">{{ formatCurrency(data.stats.total_revenue) }}</h3>
          </div>
        </div>
      </div>

      <div class="bg-zinc-900 border border-zinc-800 p-6 rounded-2xl shadow-sm">
        <div class="flex items-center gap-4">
          <div class="p-3 bg-purple-500/10 rounded-xl text-purple-500">
            <Package class="w-6 h-6" />
          </div>
          <div>
            <p class="text-zinc-400 text-sm font-medium">Total Products</p>
            <h3 class="text-2xl font-bold text-white">{{ data.stats.total_products }}</h3>
          </div>
        </div>
      </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-2 gap-8">
      <!-- Recent Orders -->
      <div class="bg-zinc-900 border border-zinc-800 rounded-2xl shadow-sm overflow-hidden">
        <div class="p-6 border-b border-zinc-800 flex items-center justify-between">
          <h3 class="text-xl font-bold text-white">Recent Orders</h3>
          <router-link to="/merchant/orders" class="text-blue-500 hover:text-blue-400 text-sm font-medium flex items-center">
            View all <ChevronRight class="w-4 h-4" />
          </router-link>
        </div>
        <div class="divide-y divide-zinc-800">
          <div v-for="order in data.recent_orders" :key="order.id" class="p-4 hover:bg-zinc-800/50 transition-colors">
            <div class="flex items-center justify-between mb-2">
              <span class="font-bold text-white">#{{ order.order_number }}</span>
              <span :class="[
                'px-3 py-1 rounded-full text-xs font-bold uppercase tracking-wider',
                order.status === 'pending' ? 'bg-amber-500/10 text-amber-500' : 
                order.status === 'delivered' ? 'bg-emerald-500/10 text-emerald-500' : 
                'bg-blue-500/10 text-blue-500'
              ]">
                {{ order.status }}
              </span>
            </div>
            <div class="flex items-center justify-between text-sm text-zinc-400">
              <span>{{ order.customer.name }}</span>
              <span>{{ formatCurrency(order.total_amount) }}</span>
            </div>
          </div>
          <div v-if="data.recent_orders.length === 0" class="p-8 text-center text-zinc-500">
            No orders found yet.
          </div>
        </div>
      </div>

      <!-- Revenue Chart Placeholder -->
      <div class="bg-zinc-900 border border-zinc-800 rounded-2xl shadow-sm p-6">
        <h3 class="text-xl font-bold text-white mb-6">Revenue Trend (Last 7 Days)</h3>
        <div class="h-64 flex items-end justify-between gap-2 px-4">
          <div v-for="item in data.revenue_data" :key="item.date" class="flex-1 flex flex-col items-center group">
            <div 
              class="w-full bg-blue-600/20 group-hover:bg-blue-600/40 rounded-t-lg transition-all"
              :style="{ height: `${(item.revenue / data.stats.total_revenue) * 100}%` }"
            ></div>
            <span class="text-[10px] text-zinc-500 mt-2 rotate-45 origin-left whitespace-nowrap">{{ item.date }}</span>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>
