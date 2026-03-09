<script setup lang="ts">
import { ref, onMounted, computed, onUnmounted } from 'vue';
import { useRoute, useRouter } from 'vue-router';
import api from '../../services/api';
import echo from '../../services/echo';
import mapboxgl from 'mapbox-gl';
import 'mapbox-gl/dist/mapbox-gl.css';
import { 
  ArrowLeft, 
  Clock, 
  Bike, 
  Store, 
  CheckCircle2, 
  Loader2,
  Phone,
  MessageSquare,
  Package,
  Navigation,
  ExternalLink,
  Plus,
  Minus
} from 'lucide-vue-next';

mapboxgl.accessToken = import.meta.env.VITE_MAPBOX_ACCESS_TOKEN;

const route = useRoute();
const router = useRouter();
const order = ref<any>(null);
const loading = ref(true);
const error = ref<string | null>(null);
const mapContainer = ref<HTMLElement | null>(null);
const map = ref<any>(null);
const riderMarker = ref<any>(null);

const fetchOrder = async () => {
  loading.value = true;
  error.value = null;
  try {
    const response = await api.get(`/orders/${route.params.id}`);
    order.value = response.data;
    updateRiderPosition();
  } catch (err: any) {
    console.error('Failed to fetch order', err);
    error.value = err.response?.data?.message || 'Failed to load order tracking. Please try again.';
  } finally {
    loading.value = false;
  }
};

const statusTimeline = [
  { id: 'pending', label: 'Order Placed', icon: Package, description: 'The merchant is confirming your order.' },
  { id: 'accepted', label: 'Accepted', icon: Store, description: 'Merchant has accepted your order.' },
  { id: 'preparing', label: 'Preparing', icon: Clock, description: 'Your delicious food is being prepared.' },
  { id: 'ready', label: 'Ready', icon: CheckCircle2, description: 'Order is ready for pickup by a rider.' },
  { id: 'dispatched', label: 'On the Way', icon: Bike, description: 'The rider is heading to your location.' },
  { id: 'delivered', label: 'Delivered', icon: CheckCircle2, description: 'Order successfully delivered. Enjoy!' }
];

const currentStatusIndex = computed(() => {
  if (!order.value) return 0;
  return statusTimeline.findIndex(s => s.id === order.value.status);
});

const initMap = () => {
  if (!mapContainer.value || !order.value) return;

  map.value = new mapboxgl.Map({
    container: mapContainer.value,
    style: 'mapbox://styles/mapbox/dark-v11',
    center: [order.value.pickup_lng, order.value.pickup_lat],
    zoom: 13,
    pitch: 45
  });

  map.value.on('load', () => {
    if (!map.value || !order.value) return;

    // 1. Pickup Marker (Merchant)
    const pickupEl = document.createElement('div');
    pickupEl.className = 'w-8 h-8 bg-blue-600 rounded-full border-4 border-white shadow-lg flex items-center justify-center text-white';
    pickupEl.innerHTML = '<svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"><path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"></path><polyline points="9 22 9 12 15 12 15 22"></polyline></svg>';
    
    new mapboxgl.Marker(pickupEl)
      .setLngLat([order.value.pickup_lng, order.value.pickup_lat])
      .setPopup(new mapboxgl.Popup({ offset: 25 }).setHTML('<h3>Pickup Point</h3>'))
      .addTo(map.value);

    // 2. Delivery Marker (Customer)
    const deliveryEl = document.createElement('div');
    deliveryEl.className = 'w-8 h-8 bg-emerald-600 rounded-full border-4 border-white shadow-lg flex items-center justify-center text-white';
    deliveryEl.innerHTML = '<svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"><path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"></path><circle cx="12" cy="10" r="3"></circle></svg>';
    
    new mapboxgl.Marker(deliveryEl)
      .setLngLat([order.value.delivery_lng, order.value.delivery_lat])
      .setPopup(new mapboxgl.Popup({ offset: 25 }).setHTML('<h3>Your Location</h3>'))
      .addTo(map.value);

    // Fit bounds
    const bounds = new mapboxgl.LngLatBounds()
      .extend([order.value.pickup_lng, order.value.pickup_lat])
      .extend([order.value.delivery_lng, order.value.delivery_lat]);
    
    map.value.fitBounds(bounds, { padding: 100 });
    
    updateRiderPosition();
  });
};

const updateRiderPosition = () => {
  if (!map.value || !order.value?.rider?.rider) return;

  const riderLng = parseFloat(order.value.rider.rider.current_lng) || 0;
  const riderLat = parseFloat(order.value.rider.rider.current_lat) || 0;

  if (riderLng === 0 || riderLat === 0) return;

  if (!riderMarker.value) {
    const riderEl = document.createElement('div');
    riderEl.className = 'w-10 h-10 bg-blue-500 rounded-full border-4 border-white shadow-2xl flex items-center justify-center text-white z-50';
    riderEl.innerHTML = '<svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round" class="animate-pulse"><rect x="1" y="3" width="15" height="13"></rect><polygon points="16 8 20 8 23 11 23 16 16 16 16 8"></polygon><circle cx="5.5" cy="18.5" r="2.5"></circle><circle cx="18.5" cy="18.5" r="2.5"></circle></svg>';
    
    riderMarker.value = new mapboxgl.Marker(riderEl)
      .setLngLat([riderLng, riderLat])
      .addTo(map.value);
  } else {
    riderMarker.value.setLngLat([riderLng, riderLat]);
  }
};

onMounted(async () => {
  await fetchOrder();
  initMap();
  
  // Real-time updates via WebSockets
  echo.private(`orders.${route.params.id}`)
    .listen('OrderStatusUpdated', (e: any) => {
      console.log('Order status updated:', e);
      order.value.status = e.status;
    })
    .listen('RiderLocationUpdated', (e: any) => {
      console.log('Rider location updated:', e);
      if (order.value.rider) {
        order.value.rider.rider.current_lat = e.lat;
        order.value.rider.rider.current_lng = e.lng;
        updateRiderPosition();
      }
    });
});

onUnmounted(() => {
  echo.leave(`orders.${route.params.id}`);
  if (map.value) map.value.remove();
});

const formatCurrency = (amount: any) => {
  const val = parseFloat(amount) || 0;
  return new Intl.NumberFormat('en-NG', { style: 'currency', currency: 'NGN' }).format(val);
};
</script>

<template>
  <div v-if="loading && !order" class="flex items-center justify-center h-full min-h-[400px]">
    <Loader2 class="w-12 h-12 text-blue-500 animate-spin" />
  </div>

  <div v-else-if="error" class="flex flex-col items-center justify-center h-full min-h-[400px] text-center p-8">
    <div class="w-16 h-16 bg-red-500/10 text-red-500 rounded-2xl flex items-center justify-center mb-4">
      <Package class="w-8 h-8" />
    </div>
    <h2 class="text-xl font-bold text-white mb-2">Something went wrong</h2>
    <p class="text-zinc-400 mb-6 max-w-md">{{ error }}</p>
    <button @click="fetchOrder" class="bg-blue-600 text-white px-8 py-3 rounded-2xl font-bold hover:bg-blue-700 transition-all">
      Retry Loading
    </button>
  </div>

  <div v-else-if="order" class="max-w-6xl mx-auto space-y-8 lg:space-y-12 animate-in fade-in duration-700">
    <div class="flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4">
       <div class="flex items-center gap-4">
          <button @click="router.back()" class="p-2 hover:bg-zinc-900 rounded-xl text-zinc-400 hover:text-white transition-all">
            <ArrowLeft class="w-6 h-6" />
          </button>
          <div>
             <h1 class="text-2xl sm:text-3xl font-bold text-white tracking-tight">Track Your Order</h1>
             <p class="text-zinc-500 font-medium text-sm">Order #{{ order.order_number }}</p>
          </div>
       </div>
       <div class="flex gap-2 w-full sm:w-auto">
          <button class="flex-1 sm:flex-none p-3 bg-zinc-900 border border-zinc-800 rounded-xl text-zinc-400 hover:text-white transition-all shadow-lg shadow-zinc-950/50 flex justify-center">
             <ExternalLink class="w-5 h-5" />
          </button>
       </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-8 lg:gap-12">
      <!-- Left Column: Status & Timeline -->
      <div class="space-y-10 lg:col-span-1 order-2 lg:order-1">
        <!-- Status Card -->
        <div class="bg-zinc-900 border border-zinc-800 rounded-3xl p-8 shadow-2xl shadow-zinc-950/50">
           <div class="flex items-center justify-between mb-8">
              <h3 class="text-xl font-black text-white uppercase tracking-widest">Status</h3>
              <span :class="[
                'px-4 py-1.5 rounded-full text-[10px] font-black uppercase tracking-widest border',
                order.status === 'pending' ? 'bg-amber-500/10 text-amber-500 border-amber-500/20' : 
                order.status === 'delivered' ? 'bg-emerald-500/10 text-emerald-500 border-emerald-500/20' : 
                'bg-blue-500/10 text-blue-500 border-blue-500/20'
              ]">
                {{ order.status }}
              </span>
           </div>

           <!-- Timeline -->
           <div class="space-y-10 relative">
              <div class="absolute left-6 top-2 bottom-2 w-0.5 bg-zinc-800"></div>
              
              <div 
                v-for="(status, index) in statusTimeline" 
                :key="status.id"
                class="relative pl-14 group"
              >
                <div 
                  :class="[
                    'absolute left-0 w-12 h-12 rounded-2xl flex items-center justify-center transition-all duration-500 z-10',
                    index <= currentStatusIndex ? 'bg-blue-600 text-white shadow-lg shadow-blue-600/30' : 'bg-zinc-950 text-zinc-700 border border-zinc-900'
                  ]"
                >
                   <component :is="status.icon" class="w-6 h-6" />
                </div>
                <div :class="[
                  'transition-all duration-500',
                  index <= currentStatusIndex ? 'opacity-100' : 'opacity-40'
                ]">
                   <h4 class="text-white font-bold">{{ status.label }}</h4>
                   <p class="text-xs text-zinc-500 mt-1 leading-relaxed">{{ status.description }}</p>
                </div>
              </div>
           </div>
        </div>

        <!-- Order Items Summary -->
        <div class="bg-zinc-900 border border-zinc-800 rounded-3xl p-8">
           <h3 class="text-xl font-bold text-white mb-6">Order Details</h3>
           <div class="space-y-4">
              <div v-for="item in order.items" :key="item.id" class="flex justify-between text-sm">
                 <span class="text-zinc-400">{{ item.quantity }}x <span class="text-zinc-300 ml-1">{{ item.product?.name || 'Item' }}</span></span>
                 <span class="text-white font-bold">{{ formatCurrency(item.price * item.quantity) }}</span>
              </div>
              <div class="pt-6 border-t border-zinc-800 flex justify-between items-center">
                 <span class="text-zinc-500 font-black uppercase tracking-widest text-xs">Total Paid</span>
                 <span class="text-2xl font-black text-white">{{ formatCurrency(order.total_amount) }}</span>
              </div>
           </div>
        </div>
      </div>

      <!-- Right Column: Map & Interaction -->
      <div class="lg:col-span-2 space-y-10 order-1 lg:order-2">
        <!-- Interactive Map Container -->
        <div class="bg-zinc-900 border border-zinc-800 rounded-[2.5rem] overflow-hidden min-h-[500px] lg:h-[600px] relative shadow-2xl shadow-zinc-950/50">
           <div ref="mapContainer" class="absolute inset-0 w-full h-full"></div>
           
           <!-- Overlay UI -->
           <div class="absolute inset-0 pointer-events-none flex flex-col justify-between p-6 lg:p-10">
              <div class="flex justify-between items-start">
                 <div class="bg-zinc-950/90 backdrop-blur-xl border border-zinc-800 p-4 lg:p-6 rounded-3xl shadow-2xl flex items-center gap-4 lg:gap-6 animate-in slide-in-from-top-8 duration-700 pointer-events-auto">
                    <div class="w-12 h-12 lg:w-16 lg:h-16 bg-blue-600 rounded-2xl flex items-center justify-center text-white shadow-xl shadow-blue-600/30">
                       <Navigation class="w-6 h-6 lg:w-8 lg:h-8 animate-pulse" />
                    </div>
                    <div>
                       <p class="text-zinc-500 text-[10px] font-black uppercase tracking-widest mb-1">Estimated Arrival</p>
                       <p class="text-2xl lg:text-3xl font-black text-white">24 <span class="text-blue-500 text-lg">MINS</span></p>
                    </div>
                 </div>
                 
                 <div class="flex flex-col gap-4 pointer-events-auto">
                    <button @click="map?.zoomIn()" class="p-3 lg:p-4 bg-zinc-950/90 backdrop-blur-md border border-zinc-800 rounded-2xl text-white hover:bg-zinc-900 transition-all shadow-xl">
                       <Plus class="w-5 h-5 lg:w-6 lg:h-6" />
                    </button>
                    <button @click="map?.zoomOut()" class="p-3 lg:p-4 bg-zinc-950/90 backdrop-blur-md border border-zinc-800 rounded-2xl text-white hover:bg-zinc-900 transition-all shadow-xl">
                       <Minus class="w-5 h-5 lg:w-6 lg:h-6" />
                    </button>
                 </div>
              </div>

              <!-- Rider Card Overlay -->
              <div v-if="order.rider" class="bg-zinc-950/90 backdrop-blur-xl border border-zinc-800 p-4 lg:p-6 rounded-[2rem] shadow-2xl flex items-center justify-between animate-in slide-in-from-bottom-8 duration-700 pointer-events-auto">
                 <div class="flex items-center gap-4 lg:gap-6">
                    <div class="relative">
                       <div class="w-12 h-12 lg:w-16 lg:h-16 bg-zinc-800 rounded-2xl flex items-center justify-center text-zinc-400 font-black text-lg border-2 border-zinc-700">
                          {{ order.rider.name.charAt(0) }}
                       </div>
                       <div class="absolute -bottom-1 -right-1 w-4 h-4 lg:w-6 lg:h-6 bg-emerald-500 border-4 border-zinc-950 rounded-full shadow-lg shadow-emerald-500/50"></div>
                    </div>
                    <div>
                       <h4 class="text-lg lg:text-xl font-black text-white">{{ order.rider.name }}</h4>
                       <div class="flex items-center gap-2 mt-1">
                          <Bike class="w-4 h-4 text-blue-500" />
                          <span class="text-zinc-400 text-xs lg:text-sm font-bold">{{ order.rider.rider?.vehicle_type || 'Rider' }} • <span class="text-white">{{ order.rider.rider?.vehicle_number || 'MOV-001' }}</span></span>
                       </div>
                    </div>
                 </div>
                 <div class="flex gap-2 lg:gap-4">
                    <button class="w-12 h-12 lg:w-14 lg:h-14 bg-zinc-900 border border-zinc-800 rounded-2xl flex items-center justify-center text-zinc-400 hover:text-emerald-500 hover:border-emerald-500/30 transition-all group">
                       <Phone class="w-5 h-5 lg:w-6 lg:h-6 group-hover:scale-110 transition-transform" />
                    </button>
                    <button class="w-12 h-12 lg:w-14 lg:h-14 bg-zinc-900 border border-zinc-800 rounded-2xl flex items-center justify-center text-zinc-400 hover:text-blue-500 hover:border-blue-500/30 transition-all group">
                       <MessageSquare class="w-5 h-5 lg:w-6 lg:h-6 group-hover:scale-110 transition-transform" />
                    </button>
                 </div>
              </div>
              
              <div v-else class="bg-zinc-950/90 backdrop-blur-xl border border-zinc-800 p-6 lg:p-8 rounded-[2rem] text-center shadow-2xl animate-in slide-in-from-bottom-8 duration-700 pointer-events-auto">
                 <div class="w-10 h-10 lg:w-12 lg:h-12 bg-amber-500/10 text-amber-500 rounded-2xl flex items-center justify-center mx-auto mb-4">
                    <Loader2 class="w-5 h-5 lg:w-6 lg:h-6 animate-spin" />
                 </div>
                 <h4 class="text-white font-black text-base lg:text-lg">Finding a Rider...</h4>
                 <p class="text-zinc-500 text-xs lg:text-sm mt-1">We're looking for the nearest rider to deliver your order.</p>
              </div>
           </div>
        </div>
      </div>
    </div>
  </div>
</template>

<style>
.mapboxgl-popup-content {
  background: #18181b !important;
  color: white !important;
  border: 1px solid #27272a !important;
  border-radius: 12px !important;
  padding: 10px !important;
  font-family: inherit !important;
}
.mapboxgl-popup-tip {
  border-top-color: #18181b !important;
}
</style>
