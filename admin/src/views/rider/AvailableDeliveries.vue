<script setup lang="ts">
import { ref, onMounted } from 'vue';
import api from '../../services/api';
import { 
  MapPin, 
  Clock, 
  ShoppingBag, 
  Loader2,
  CheckCircle2,
  Navigation,
  ChevronRight,
  Bike
} from 'lucide-vue-next';

const orders = ref<any[]>([]);
const loading = ref(true);

const fetchOrders = async () => {
  loading.value = true;
  try {
    const response = await api.get('/rider/available-orders');
    orders.value = response.data;
  } catch (err) {
    console.error('Failed to fetch available orders', err);
  } finally {
    loading.value = false;
  }
};

const acceptOrder = async (orderId: number) => {
  try {
    await api.post(`/rider/orders/${orderId}/accept`);
    window.location.href = '/rider/dashboard';
  } catch (err) {
    console.error('Failed to accept order', err);
  }
};

onMounted(fetchOrders);

const formatCurrency = (amount: number) => {
  return new Intl.NumberFormat('en-NG', { style: 'currency', currency: 'NGN' }).format(amount);
};
</script>

<template>
  <div class="space-y-10 animate-in fade-in duration-700">
    <div class="flex items-end justify-between">
      <div>
        <h1 class="text-3xl font-bold text-white tracking-tight">Available Deliveries</h1>
        <p class="text-zinc-400 mt-1 font-medium">Earn more by accepting nearby delivery requests.</p>
      </div>
      <button @click="fetchOrders" class="p-3 bg-zinc-900 border border-zinc-800 rounded-xl text-zinc-400 hover:text-white transition-all shadow-lg shadow-zinc-950/50">
         <Loader2 v-if="loading" class="w-5 h-5 animate-spin" />
         <Navigation v-else class="w-5 h-5" />
      </button>
    </div>

    <div v-if="loading" class="flex justify-center py-20">
      <Loader2 class="w-12 h-12 text-blue-500 animate-spin" />
    </div>

    <div v-else-if="orders.length === 0" class="bg-zinc-900 border border-zinc-800 rounded-3xl p-20 text-center shadow-sm">
      <div class="w-24 h-24 bg-zinc-800 rounded-full flex items-center justify-center mx-auto mb-6 text-zinc-500">
        <Bike class="w-12 h-12" />
      </div>
      <h3 class="text-2xl font-bold text-white mb-2">No available orders right now</h3>
      <p class="text-zinc-500 mb-8 max-w-sm mx-auto">Don't worry, new orders will appear soon. Keep your app online to receive notifications.</p>
    </div>

    <div v-else class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-8">
      <div 
        v-for="order in orders" 
        :key="order.id"
        class="bg-zinc-900 border border-zinc-800 rounded-[2.5rem] overflow-hidden hover:border-blue-500/50 transition-all group shadow-2xl shadow-zinc-950/50 flex flex-col"
      >
        <div class="p-8 space-y-8 flex-1">
          <div class="flex justify-between items-start">
            <div class="flex items-center gap-3">
               <div class="w-10 h-10 bg-blue-600/10 text-blue-500 rounded-xl flex items-center justify-center">
                  <ShoppingBag class="w-5 h-5" />
               </div>
               <span class="font-black text-white text-lg tracking-tight">#{{ order.order_number }}</span>
            </div>
            <div class="text-right">
               <p class="text-zinc-500 text-[10px] font-black uppercase tracking-widest mb-1">Earning Potential</p>
               <span class="text-2xl font-black text-emerald-500">{{ formatCurrency(order.rider_share) }}</span>
            </div>
          </div>

          <div class="space-y-6">
            <div class="flex items-start gap-4">
              <div class="w-3 h-3 mt-1.5 rounded-full bg-blue-500 shadow-lg shadow-blue-500/50 flex-shrink-0"></div>
              <div>
                <p class="text-xs text-zinc-500 uppercase font-black tracking-widest mb-1">Pickup from</p>
                <p class="text-white font-bold leading-tight truncate max-w-[200px]">{{ order.merchant.business_name }}</p>
                <p class="text-zinc-600 text-xs mt-1 truncate max-w-[200px]">{{ order.merchant.address }}</p>
              </div>
            </div>
            
            <div class="flex items-start gap-4">
              <div class="w-3 h-3 mt-1.5 rounded-full bg-emerald-500 shadow-lg shadow-emerald-500/50 flex-shrink-0"></div>
              <div>
                <p class="text-xs text-zinc-500 uppercase font-black tracking-widest mb-1">Deliver to</p>
                <p class="text-white font-bold leading-tight truncate max-w-[200px]">{{ order.delivery_address }}</p>
                <p class="text-zinc-600 text-xs mt-1 italic">3.2 km away • ~12 mins</p>
              </div>
            </div>
          </div>

          <div class="flex items-center gap-4 text-xs font-bold text-zinc-500">
             <div class="flex items-center gap-1.5 px-3 py-1.5 bg-zinc-950 rounded-xl border border-zinc-800">
                <Clock class="w-3.5 h-3.5" /> Ready for Pickup
             </div>
             <div class="flex items-center gap-1.5 px-3 py-1.5 bg-zinc-950 rounded-xl border border-zinc-800">
                <Package class="w-3.5 h-3.5" /> {{ order.items.length }} Items
             </div>
          </div>
        </div>

        <button 
          @click="acceptOrder(order.id)"
          class="w-full py-6 bg-blue-600 hover:bg-blue-700 text-white font-black uppercase tracking-[0.2em] text-xs flex items-center justify-center gap-3 transition-all active:scale-95 group shadow-xl"
        >
          Accept Delivery
          <ChevronRight class="w-5 h-5 group-hover:translate-x-1 transition-transform" />
        </button>
      </div>
    </div>
  </div>
</template>
