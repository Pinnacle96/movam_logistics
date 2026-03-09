<script setup lang="ts">
import { ref, onMounted } from 'vue';
import { useRouter } from 'vue-router';
import api from '../../services/api';
import { useCartStore } from '../../stores/cart';
import { useAuthStore } from '../../stores/auth';
import { 
  Search, 
  ShoppingBag, 
  Plus, 
  Minus,
  Star,
  MapPin,
  Clock,
  Loader2,
  ChevronRight
} from 'lucide-vue-next';

const cartStore = useCartStore();
const authStore = useAuthStore();
const router = useRouter();
const merchants = ref<any[]>([]);
const products = ref<any[]>([]);
const categories = ref<any[]>([]);
const loading = ref(true);
const searchQuery = ref('');
const selectedCategory = ref('');

const fetchData = async () => {
  loading.value = true;
  try {
    const [merchantsRes, productsRes, categoriesRes] = await Promise.all([
      api.get('/merchants'),
      api.get('/products/public', { params: { search: searchQuery.value, category_id: selectedCategory.value } }),
      api.get('/categories/public')
    ]);
    merchants.value = merchantsRes.data;
    products.value = productsRes.data;
    categories.value = categoriesRes.data;
  } catch (err) {
    console.error('Failed to fetch data', err);
  } finally {
    loading.value = false;
  }
};

onMounted(async () => {
  if (authStore.isAuthenticated) {
    if (authStore.isAdmin) return router.replace('/admin/dashboard');
    if (authStore.isMerchant) return router.replace('/merchant/dashboard');
    if (authStore.isRider) return router.replace('/rider/dashboard');
  }
  
  await fetchData();
});

const formatCurrency = (amount: number) => {
  return new Intl.NumberFormat('en-NG', { style: 'currency', currency: 'NGN' }).format(amount);
};

const addToCart = (product: any) => {
  cartStore.addItem(product);
};

const getItemQuantity = (productId: number) => {
  return cartStore.items.find(item => item.id === productId)?.quantity || 0;
};
</script>

<template>
  <div class="space-y-12 animate-in fade-in duration-700">
    <!-- Hero Section -->
    <div class="relative min-h-[400px] lg:h-[400px] rounded-3xl overflow-hidden group">
      <img 
        src="https://images.unsplash.com/photo-1504674900247-0877df9cc836?auto=format&fit=crop&q=80&w=2070" 
        alt="Hero Food" 
        class="absolute inset-0 w-full h-full object-cover group-hover:scale-105 transition-transform duration-1000"
      />
      <div class="absolute inset-0 bg-gradient-to-r from-zinc-950 via-zinc-950/80 to-transparent flex flex-col justify-center px-6 lg:px-12 py-12 lg:py-0">
        <h1 class="text-3xl lg:text-5xl font-extrabold text-white max-w-lg leading-tight mb-4 lg:mb-6">
          Delicious Food, <span class="text-blue-500">Delivered</span> to Your Doorstep.
        </h1>
        <p class="text-zinc-300 text-base lg:text-lg max-w-md mb-8">Discover the best merchants in your area and enjoy fast delivery with real-time tracking.</p>
        
        <div class="flex flex-col sm:flex-row max-w-xl bg-zinc-900/90 backdrop-blur-md border border-zinc-800 p-2 rounded-2xl shadow-2xl gap-2">
          <div class="relative flex-1">
            <Search class="absolute left-4 top-1/2 -translate-y-1/2 w-5 h-5 text-zinc-500" />
            <input 
              v-model="searchQuery"
              @keyup.enter="fetchData"
              type="text" 
              placeholder="Search for restaurants..." 
              class="w-full pl-12 pr-4 py-3 bg-transparent text-white focus:outline-none placeholder-zinc-500"
            />
          </div>
          <button 
            @click="fetchData"
            class="bg-blue-600 hover:bg-blue-700 text-white px-8 py-3 rounded-xl font-bold transition-all shadow-lg shadow-blue-600/20"
          >
            Find Food
          </button>
        </div>
      </div>
    </div>

    <!-- Featured Merchants -->
    <section class="space-y-6">
      <div class="flex items-end justify-between">
        <div>
          <h2 class="text-3xl font-bold text-white">Popular Merchants</h2>
          <p class="text-zinc-400 mt-1">Hand-picked merchants with top ratings.</p>
        </div>
        <button class="text-blue-500 hover:text-blue-400 font-bold flex items-center gap-1 group">
          View All <ChevronRight class="w-4 h-4 group-hover:translate-x-1 transition-transform" />
        </button>
      </div>

      <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-8">
        <div 
          v-for="merchant in merchants" 
          :key="merchant.id"
          @click="router.push(`/customer/merchant/${merchant.slug}`)"
          class="bg-zinc-900 border border-zinc-800 rounded-3xl overflow-hidden hover:border-blue-500/50 transition-all group cursor-pointer"
        >
          <div class="h-48 relative overflow-hidden">
             <img 
              :src="merchant.cover_image_url || 'https://images.unsplash.com/photo-1552566626-52f8b828add9?auto=format&fit=crop&q=80&w=2070'" 
              :alt="merchant.business_name"
              class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-500"
             />
             <div class="absolute top-4 right-4 bg-zinc-950/80 backdrop-blur-md px-3 py-1.5 rounded-full flex items-center gap-1.5 text-xs font-bold text-white">
                <Star class="w-3.5 h-3.5 text-amber-500 fill-amber-500" />
                {{ merchant.rating || '4.5' }}
             </div>
          </div>
          <div class="p-6">
            <h3 class="text-xl font-bold text-white mb-2 group-hover:text-blue-400 transition-colors">{{ merchant.business_name }}</h3>
            <div class="flex items-center gap-4 text-sm text-zinc-500 mb-4">
               <div class="flex items-center gap-1.5">
                  <Clock class="w-4 h-4" /> 20-30 min
               </div>
               <div class="flex items-center gap-1.5">
                  <MapPin class="w-4 h-4" /> {{ merchant.address.split(',')[0] }}
               </div>
            </div>
            <div class="flex flex-wrap gap-2">
               <span class="px-3 py-1 bg-zinc-800 text-zinc-400 rounded-full text-xs font-medium">{{ merchant.category || 'Restaurant' }}</span>
            </div>
          </div>
        </div>
      </div>
    </section>

    <!-- Product Grid -->
    <section class="space-y-8">
      <div class="flex flex-col md:flex-row md:items-end justify-between gap-4">
        <div>
           <h2 class="text-3xl font-bold text-white">Best Sellers</h2>
           <p class="text-zinc-500 mt-1">Explore our most popular dishes.</p>
        </div>
        
        <div class="flex gap-4 overflow-x-auto pb-2 md:pb-0 no-scrollbar">
           <button 
             @click="selectedCategory = ''; fetchData()"
             :class="[
               'px-6 py-2.5 rounded-xl font-bold transition-all whitespace-nowrap border',
               selectedCategory === '' ? 'bg-blue-600 border-blue-600 text-white' : 'bg-zinc-900 border-zinc-800 text-zinc-400 hover:text-white'
             ]"
           >
              All
           </button>
           <button 
             v-for="cat in categories" 
             :key="cat.id"
             @click="selectedCategory = cat.id; fetchData()"
             :class="[
               'px-6 py-2.5 rounded-xl font-bold transition-all whitespace-nowrap border',
               selectedCategory === cat.id ? 'bg-blue-600 border-blue-600 text-white' : 'bg-zinc-900 border-zinc-800 text-zinc-400 hover:text-white'
             ]"
           >
              {{ cat.name }}
           </button>
        </div>
      </div>

      <div v-if="loading" class="flex justify-center py-20">
         <Loader2 class="w-10 h-10 text-blue-500 animate-spin" />
      </div>

      <div v-else class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6">
        <div 
          v-for="product in products" 
          :key="product.id"
          class="bg-zinc-900 border border-zinc-800 rounded-3xl overflow-hidden hover:shadow-2xl hover:shadow-blue-500/5 transition-all group"
        >
          <div class="h-44 relative">
             <img 
              :src="product.image || 'https://images.unsplash.com/photo-1546069901-ba9599a7e63c?auto=format&fit=crop&q=80&w=2080'" 
              :alt="product.name"
              class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-500"
             />
          </div>
          <div class="p-5">
            <p class="text-xs text-blue-500 font-bold uppercase tracking-widest mb-1">{{ product.category?.name || 'Food' }}</p>
            <h3 class="text-lg font-bold text-white mb-1 truncate">{{ product.name }}</h3>
            <p class="text-zinc-500 text-sm mb-4 line-clamp-2">{{ product.description }}</p>
            
            <div class="flex items-center justify-between">
              <span class="text-xl font-black text-white">{{ formatCurrency(product.price) }}</span>
              
              <div v-if="getItemQuantity(product.id) > 0" class="flex items-center gap-3 bg-zinc-800 rounded-xl p-1 px-2 border border-zinc-700">
                <button @click="cartStore.removeItem(product.id)" class="p-1.5 hover:bg-zinc-700 rounded-lg text-white transition-all">
                  <Minus class="w-4 h-4" />
                </button>
                <span class="text-white font-bold min-w-[1.5rem] text-center">{{ getItemQuantity(product.id) }}</span>
                <button @click="addToCart(product)" class="p-1.5 hover:bg-zinc-700 rounded-lg text-white transition-all">
                  <Plus class="w-4 h-4" />
                </button>
              </div>
              <button 
                v-else
                @click="addToCart(product)"
                class="p-3 bg-blue-600 hover:bg-blue-700 text-white rounded-xl shadow-lg shadow-blue-600/20 transition-all hover:scale-110 active:scale-95"
              >
                <Plus class="w-5 h-5" />
              </button>
            </div>
          </div>
        </div>
      </div>
    </section>

    <!-- Cart Bar (Floating) -->
    <div 
      v-if="!cartStore.isEmpty"
      class="fixed bottom-8 left-1/2 -translate-x-1/2 w-full max-w-lg px-4 animate-in slide-in-from-bottom-12 duration-500 z-50"
    >
      <div class="bg-blue-600 p-4 rounded-3xl shadow-2xl flex items-center justify-between group cursor-pointer" @click="router.push('/cart')">
        <div class="flex items-center gap-4">
          <div class="w-12 h-12 bg-white/20 rounded-2xl flex items-center justify-center text-white">
            <ShoppingBag class="w-6 h-6" />
          </div>
          <div>
            <p class="text-white font-bold">{{ cartStore.totalItems }} Items in Cart</p>
            <p class="text-blue-100 text-xs">{{ formatCurrency(cartStore.totalAmount) }} Total</p>
          </div>
        </div>
        <button class="bg-white text-blue-600 px-6 py-2.5 rounded-2xl font-black flex items-center gap-2 group-hover:scale-105 transition-transform">
          Checkout <ChevronRight class="w-5 h-5" />
        </button>
      </div>
    </div>
  </div>
</template>
