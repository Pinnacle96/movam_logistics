<script setup lang="ts">
import { ref, onMounted, onUnmounted } from 'vue';
import api from '../../services/api';
import { 
  Bike, 
  Map as MapIcon, 
  History, 
  CreditCard,
  ChevronRight,
  Loader2,
  CheckCircle2,
  Navigation
} from 'lucide-vue-next';

const data = ref<any>(null);
const loading = ref(true);
let locationInterval: any = null;

const updateRiderLocation = async () => {
  if (!data.value?.stats?.active_delivery) return;

  try {
    // Simulate slight movement for demo purposes
    const lat = parseFloat(data.value.rider.rider.current_lat) + (Math.random() - 0.5) * 0.001;
    const lng = parseFloat(data.value.rider.rider.current_lng) + (Math.random() - 0.5) * 0.001;
    
    await api.post('/rider/location', { lat, lng });
    
    // Update local state to reflect movement
    data.value.rider.rider.current_lat = lat;
    data.value.rider.rider.current_lng = lng;
  } catch (err) {
    console.error('Failed to update location', err);
  }
};

onMounted(async () => {
  try {
    const response = await api.get('/rider/dashboard');
    data.value = response.data;
    
    if (data.value.stats.active_delivery) {
      locationInterval = setInterval(updateRiderLocation, 5000);
    }
  } catch (err) {
    console.error('Failed to fetch rider dashboard', err);
  } finally {
    loading.value = false;
  }
});

onUnmounted(() => {
  if (locationInterval) clearInterval(locationInterval);
});

const formatCurrency = (amount: number) => {
  return new Intl.NumberFormat('en-NG', { style: 'currency', currency: 'NGN' }).format(amount);
};

const completeOrder = async (orderId: number) => {
  try {
    await api.patch(`/orders/${orderId}/status`, { status: 'delivered' });
    window.location.reload();
  } catch (err) {
    console.error('Failed to complete order', err);
  }
};
</script>

<template>
  <div v-if="loading" class="flex items-center justify-center h-full">
    <Loader2 class="w-12 h-12 text-blue-500 animate-spin" />
  </div>

  <div v-else-if="data" class="space-y-8 animate-in fade-in duration-500">
    <div class="flex items-end justify-between">
      <div>
        <h1 class="text-3xl font-bold text-white">Rider Dashboard</h1>
        <p class="text-zinc-400 mt-1">Manage your active deliveries and track your earnings.</p>
      </div>
    </div>

    <!-- Stats Grid -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
      <div class="bg-zinc-900 border border-zinc-800 p-6 rounded-2xl shadow-sm">
        <div class="flex items-center gap-4">
          <div class="p-3 bg-blue-500/10 rounded-xl text-blue-500">
            <History class="w-6 h-6" />
          </div>
          <div>
            <p class="text-zinc-400 text-sm font-medium">Completed Deliveries</p>
            <h3 class="text-2xl font-bold text-white">{{ data.stats.total_deliveries }}</h3>
          </div>
        </div>
      </div>

      <div class="bg-zinc-900 border border-zinc-800 p-6 rounded-2xl shadow-sm">
        <div class="flex items-center gap-4">
          <div class="p-3 bg-emerald-500/10 rounded-xl text-emerald-500">
            <CreditCard class="w-6 h-6" />
          </div>
          <div>
            <p class="text-zinc-400 text-sm font-medium">Total Earnings</p>
            <h3 class="text-2xl font-bold text-white">{{ formatCurrency(data.stats.total_earnings) }}</h3>
          </div>
        </div>
      </div>

      <div class="bg-zinc-900 border border-zinc-800 p-6 rounded-2xl shadow-sm">
        <div class="flex items-center gap-4">
          <div class="p-3 bg-amber-500/10 rounded-xl text-amber-500">
            <MapIcon class="w-6 h-6" />
          </div>
          <div>
            <p class="text-zinc-400 text-sm font-medium">Active Status</p>
            <h3 class="text-2xl font-bold text-white">Online</h3>
          </div>
        </div>
      </div>
    </div>

    <!-- Active Delivery -->
    <div v-if="data.stats.active_delivery" class="bg-zinc-900 border border-blue-500/50 rounded-2xl shadow-lg shadow-blue-500/5 overflow-hidden">
      <div class="p-6 border-b border-zinc-800 bg-blue-600/5 flex items-center justify-between">
        <div class="flex items-center gap-3">
          <div class="w-10 h-10 bg-blue-600 rounded-full flex items-center justify-center text-white">
            <Navigation class="w-5 h-5 animate-pulse" />
          </div>
          <div>
            <h3 class="text-xl font-bold text-white">Active Delivery</h3>
            <p class="text-sm text-zinc-400">Order #{{ data.stats.active_delivery.order_number }}</p>
          </div>
        </div>
        <span class="px-4 py-1.5 bg-blue-500/20 text-blue-400 rounded-full text-xs font-bold uppercase tracking-wider border border-blue-500/30">
          {{ data.stats.active_delivery.status }}
        </span>
      </div>
      <div class="p-6 grid grid-cols-1 md:grid-cols-2 gap-8">
        <div class="space-y-6">
          <div class="flex items-start gap-4">
            <div class="w-2 h-2 mt-2 rounded-full bg-blue-500 shadow-lg shadow-blue-500/50"></div>
            <div>
              <p class="text-xs text-zinc-500 uppercase font-bold tracking-widest mb-1">Pickup Address</p>
              <p class="text-white font-medium">{{ data.stats.active_delivery.pickup_address }}</p>
            </div>
          </div>
          <div class="flex items-start gap-4">
            <div class="w-2 h-2 mt-2 rounded-full bg-emerald-500 shadow-lg shadow-emerald-500/50"></div>
            <div>
              <p class="text-xs text-zinc-500 uppercase font-bold tracking-widest mb-1">Delivery Address</p>
              <p class="text-white font-medium">{{ data.stats.active_delivery.delivery_address }}</p>
            </div>
          </div>
          <div class="pt-4 border-t border-zinc-800">
             <div class="flex items-center justify-between text-sm text-zinc-400 mb-4">
                <span>Rider Share</span>
                <span class="text-emerald-500 font-bold text-lg">{{ formatCurrency(data.stats.active_delivery.rider_share) }}</span>
             </div>
             <button 
              @click="completeOrder(data.stats.active_delivery.id)"
              class="w-full flex items-center justify-center gap-2 py-3 px-6 bg-emerald-600 hover:bg-emerald-700 text-white rounded-xl font-bold transition-all shadow-lg shadow-emerald-600/20"
             >
               <CheckCircle2 class="w-5 h-5" />
               Mark as Delivered
             </button>
          </div>
        </div>
        
        <!-- Placeholder for Mapbox integration -->
        <div class="bg-zinc-950 rounded-xl border border-zinc-800 flex items-center justify-center min-h-[300px]">
           <div class="text-center p-8">
              <MapIcon class="w-12 h-12 text-zinc-700 mx-auto mb-4" />
              <p class="text-zinc-500 font-medium">Map Navigation Integration Coming Soon</p>
              <p class="text-zinc-700 text-xs mt-2">Real-time GPS tracking between pickup and delivery.</p>
           </div>
        </div>
      </div>
    </div>

    <!-- No Active Delivery State -->
    <div v-else class="bg-zinc-900 border border-zinc-800 rounded-2xl p-12 text-center shadow-sm">
      <div class="w-20 h-20 bg-zinc-800 rounded-full flex items-center justify-center mx-auto mb-6 text-zinc-500">
        <Bike class="w-10 h-10" />
      </div>
      <h3 class="text-2xl font-bold text-white mb-2">You're Online and Ready!</h3>
      <p class="text-zinc-400 mb-8 max-w-md mx-auto">You don't have any active deliveries at the moment. Browse available orders to start earning.</p>
      <router-link to="/rider/available-orders" class="inline-flex items-center gap-2 py-3 px-8 bg-blue-600 hover:bg-blue-700 text-white rounded-xl font-bold transition-all shadow-lg shadow-blue-600/20">
        Browse Available Orders <ChevronRight class="w-5 h-5" />
      </router-link>
    </div>
  </div>
</template>
