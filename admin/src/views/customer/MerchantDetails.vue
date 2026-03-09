<script setup lang="ts">
import { ref, onMounted } from 'vue';
import { useRoute, useRouter } from 'vue-router';
import api from '../../services/api';
import { useCartStore } from '../../stores/cart';
import { 
  ArrowLeft,
  Star,
  MapPin,
  Clock,
  Loader2,
  Plus,
  Minus,
  ShoppingBag,
  ChevronRight
} from 'lucide-vue-next';

const route = useRoute();
const router = useRouter();
const cartStore = useCartStore();

const merchant = ref<any>(null);
const products = ref<any[]>([]);
const loading = ref(true);
const merchantSlug = route.params.slug;

const fetchData = async () => {
  loading.value = true;
  try {
    const [merchantRes, productsRes] = await Promise.all([
      api.get(`/merchants/${merchantSlug}`),
      api.get('/products/public', { params: { merchant_id: merchantSlug } })
    ]);
    merchant.value = merchantRes.data;
    products.value = productsRes.data;
  } catch (err) {
    console.error('Failed to fetch merchant details', err);
    // router.push('/customer/home'); // Redirect if not found
  } finally {
    loading.value = false;
  }
};

onMounted(() => {
  fetchData();
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
  <div class="space-y-8 animate-in fade-in duration-700">
    <button @click="router.back()" class="flex items-center gap-2 text-zinc-400 hover:text-white transition-colors">
      <ArrowLeft class="w-5 h-5" /> Back
    </button>

    <div v-if="loading" class="flex justify-center py-20">
      <Loader2 class="w-10 h-10 text-blue-500 animate-spin" />
    </div>

    <div v-else-if="merchant" class="space-y-8">
      <!-- Merchant Header -->
      <div class="relative h-64 md:h-80 rounded-3xl overflow-hidden group">
        <img 
          :src="merchant.cover_image_url || 'https://images.unsplash.com/photo-1514933651103-005eec06c04b?auto=format&fit=crop&q=80&w=1974'" 
          :alt="merchant.business_name"
          class="absolute inset-0 w-full h-full object-cover group-hover:scale-105 transition-transform duration-1000"
        />
        <div class="absolute inset-0 bg-gradient-to-t from-zinc-950 via-zinc-950/60 to-transparent flex flex-col justify-end p-6 md:p-10">
          <div class="flex flex-col md:flex-row md:items-end justify-between gap-4">
            <div>
              <h1 class="text-3xl md:text-5xl font-extrabold text-white mb-2">{{ merchant.business_name }}</h1>
              <div class="flex flex-wrap items-center gap-4 text-zinc-300">
                <div class="flex items-center gap-1.5 bg-amber-500/10 px-2 py-1 rounded-lg border border-amber-500/20 text-amber-500 font-bold">
                  <Star class="w-4 h-4 fill-amber-500" /> {{ merchant.average_rating || 'New' }}
                </div>
                <div class="flex items-center gap-1.5">
                  <MapPin class="w-4 h-4" /> {{ merchant.address }}
                </div>
                <div class="flex items-center gap-1.5">
                  <Clock class="w-4 h-4" /> 20-30 min
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- Menu Grid -->
      <section>
        <h2 class="text-2xl font-bold text-white mb-6">Menu</h2>
        
        <div v-if="products.length === 0" class="text-center py-10 text-zinc-500">
          No items available yet.
        </div>

        <div v-else class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-6">
          <div 
            v-for="product in products" 
            :key="product.id"
            class="bg-zinc-900 border border-zinc-800 rounded-3xl overflow-hidden hover:shadow-xl hover:border-blue-500/30 transition-all group flex flex-col"
          >
            <div class="h-48 relative overflow-hidden">
              <img 
                :src="product.image_url || 'https://images.unsplash.com/photo-1546069901-ba9599a7e63c?auto=format&fit=crop&q=80&w=2080'" 
                :alt="product.name"
                class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-500"
              />
              <div v-if="!product.is_available" class="absolute inset-0 bg-black/60 flex items-center justify-center backdrop-blur-sm">
                <span class="bg-red-500 text-white px-3 py-1 rounded-full text-sm font-bold">Sold Out</span>
              </div>
            </div>
            
            <div class="p-5 flex-1 flex flex-col">
              <div class="flex-1">
                <p class="text-xs text-blue-500 font-bold uppercase tracking-widest mb-1">{{ product.category?.name || 'Food' }}</p>
                <h3 class="text-lg font-bold text-white mb-1">{{ product.name }}</h3>
                <p class="text-zinc-500 text-sm mb-4 line-clamp-2">{{ product.description }}</p>
              </div>
              
              <div class="flex items-center justify-between mt-4">
                <span class="text-xl font-black text-white">{{ formatCurrency(product.price) }}</span>
                
                <div v-if="product.is_available">
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
        </div>
      </section>
    </div>

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
