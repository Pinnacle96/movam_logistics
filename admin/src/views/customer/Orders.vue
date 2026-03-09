<script setup lang="ts">
import { ref, onMounted } from 'vue';
import api from '../../services/api';
import { useRouter } from 'vue-router';
import { 
  Package, 
  MapPin, 
  Clock, 
  ChevronRight, 
  Loader2,
  Store
} from 'lucide-vue-next';

const orders = ref<any[]>([]);
const loading = ref(true);
const error = ref<string | null>(null);
const router = useRouter();

const fetchOrders = async () => {
  loading.value = true;
  error.value = null;
  try {
    const response = await api.get('/orders');
    // Handle paginated response or direct array
    const data = response.data.data || response.data;
    orders.value = Array.isArray(data) ? data : [];
  } catch (err: any) {
    console.error('Failed to fetch orders', err);
    error.value = err.response?.data?.message || 'Failed to load orders. Please try again.';
  } finally {
    loading.value = false;
  }
};

const formatCurrency = (amount: any) => {
  const val = parseFloat(amount) || 0;
  return new Intl.NumberFormat('en-NG', { style: 'currency', currency: 'NGN' }).format(val);
};

onMounted(fetchOrders);
</script>

<template>
  <div class="space-y-8 animate-in fade-in duration-700">
    <div>
      <h1 class="text-3xl font-bold text-white tracking-tight">My Orders</h1>
      <p class="text-zinc-400 mt-1 font-medium">Track and manage your delivery history.</p>
    </div>

    <div v-if="loading" class="flex justify-center py-20">
      <Loader2 class="w-12 h-12 text-blue-500 animate-spin" />
    </div>

    <div v-else-if="error" class="bg-red-500/10 border border-red-500/20 rounded-3xl p-12 text-center">
      <p class="text-red-500 font-bold mb-4">{{ error }}</p>
      <button @click="fetchOrders" class="bg-zinc-800 text-white px-6 py-2 rounded-xl font-bold hover:bg-zinc-700 transition-all">Retry</button>
    </div>

    <div v-else-if="orders.length === 0" class="bg-zinc-900 border border-zinc-800 rounded-[2.5rem] p-20 text-center shadow-sm">
      <div class="w-24 h-24 bg-zinc-800 rounded-full flex items-center justify-center mx-auto mb-6 text-zinc-500">
        <Package class="w-12 h-12" />
      </div>
      <h3 class="text-2xl font-bold text-white mb-2">No orders yet</h3>
      <p class="text-zinc-500 mb-8 max-w-sm mx-auto">Hungry? Browse our partner merchants and place your first order.</p>
      <button @click="router.push('/')" class="bg-blue-600 text-white px-8 py-3 rounded-xl font-bold">Browse Food</button>
    </div>

    <div v-else class="grid grid-cols-1 gap-6">
      <div 
        v-for="order in orders" 
        :key="order.id" 
        class="bg-zinc-900 border border-zinc-800 rounded-3xl p-6 hover:border-blue-500/50 transition-all shadow-xl group"
      >
        <div class="flex flex-col md:flex-row justify-between gap-6">
          <div class="flex gap-6">
            <div class="w-20 h-20 bg-zinc-800 rounded-2xl flex items-center justify-center text-zinc-500">
               <Store v-if="order.merchant" class="w-10 h-10" />
               <Package v-else class="w-10 h-10" />
            </div>
            <div>
              <div class="flex items-center gap-3 mb-1">
                <h3 class="text-xl font-black text-white group-hover:text-blue-400 transition-colors">{{ order.merchant?.business_name || 'Merchant' }}</h3>
                <span :class="[
                  'px-3 py-1 rounded-full text-[10px] font-black uppercase tracking-widest border',
                  order.status === 'pending' ? 'bg-amber-500/10 text-amber-500 border-amber-500/20' : 
                  order.status === 'delivered' ? 'bg-emerald-500/10 text-emerald-500 border-emerald-500/20' : 
                  'bg-blue-500/10 text-blue-500 border-blue-500/20'
                ]">
                  {{ order.status }}
                </span>
              </div>
              <p class="text-zinc-500 text-sm font-medium mb-4 flex items-center gap-2">
                <Clock class="w-4 h-4" /> {{ new Date(order.created_at).toLocaleString() }}
              </p>
              <div class="flex items-center gap-4 text-xs font-bold uppercase tracking-widest text-zinc-400">
                 <span>{{ order.items?.length || 0 }} Items</span>
                 <span class="w-1 h-1 bg-zinc-700 rounded-full"></span>
                 <span class="text-white">{{ formatCurrency(order.total_amount) }}</span>
              </div>
            </div>
          </div>

          <div class="flex items-center gap-4">
            <button 
              @click="router.push(`/orders/${order.id}/track`)"
              class="flex-1 md:flex-none px-6 py-3 bg-zinc-800 hover:bg-zinc-700 text-white rounded-xl font-bold transition-all flex items-center justify-center gap-2"
            >
              <MapPin class="w-4 h-4" /> Track
            </button>
            <button class="p-3 bg-zinc-800 hover:bg-zinc-700 text-zinc-400 hover:text-white rounded-xl transition-all">
               <ChevronRight class="w-5 h-5" />
            </button>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>
