<script setup lang="ts">
import { useRouter } from 'vue-router';
import { useCartStore } from '../../stores/cart';
import { 
  ShoppingBag, 
  Trash2, 
  Plus, 
  Minus, 
  ArrowLeft,
  ChevronRight,
  CreditCard,
  MapPin
} from 'lucide-vue-next';

const router = useRouter();
const cartStore = useCartStore();

const formatCurrency = (amount: number) => {
  return new Intl.NumberFormat('en-NG', { style: 'currency', currency: 'NGN' }).format(amount);
};
</script>

<template>
  <div class="max-w-4xl mx-auto space-y-8 animate-in fade-in slide-in-from-bottom-4 duration-700">
    <div class="flex items-center gap-4">
      <button @click="router.back()" class="p-2 hover:bg-zinc-900 rounded-xl text-zinc-400 hover:text-white transition-all">
        <ArrowLeft class="w-6 h-6" />
      </button>
      <h1 class="text-3xl font-bold text-white">Your Shopping Cart</h1>
    </div>

    <div v-if="cartStore.isEmpty" class="bg-zinc-900 border border-zinc-800 rounded-3xl p-16 text-center">
      <div class="w-24 h-24 bg-zinc-800 rounded-full flex items-center justify-center mx-auto mb-6 text-zinc-500">
        <ShoppingBag class="w-12 h-12" />
      </div>
      <h3 class="text-2xl font-bold text-white mb-2">Your cart is empty</h3>
      <p class="text-zinc-500 mb-8 max-w-sm mx-auto">Looks like you haven't added anything to your cart yet. Explore our top merchants to find something delicious.</p>
      <router-link to="/" class="inline-flex items-center gap-2 py-4 px-8 bg-blue-600 hover:bg-blue-700 text-white rounded-2xl font-bold transition-all shadow-lg shadow-blue-600/20">
        Start Browsing
      </router-link>
    </div>

    <div v-else class="grid grid-cols-1 lg:grid-cols-3 gap-8">
      <!-- Items List -->
      <div class="lg:col-span-2 space-y-4">
        <div 
          v-for="item in cartStore.items" 
          :key="item.id"
          class="bg-zinc-900 border border-zinc-800 rounded-2xl p-3 sm:p-4 flex flex-col sm:flex-row items-start sm:items-center gap-4 group"
        >
          <div class="w-full sm:w-20 h-40 sm:h-20 bg-zinc-800 rounded-xl overflow-hidden flex-shrink-0">
             <img src="https://images.unsplash.com/photo-1546069901-ba9599a7e63c?auto=format&fit=crop&q=80&w=200" class="w-full h-full object-cover" />
          </div>
          <div class="flex-1 min-w-0 w-full sm:w-auto">
            <h3 class="text-lg font-bold text-white truncate">{{ item.name }}</h3>
            <p class="text-blue-500 font-bold">{{ formatCurrency(item.price) }}</p>
          </div>
          
          <div class="flex items-center justify-between w-full sm:w-auto gap-3">
            <div class="flex items-center gap-3 bg-zinc-950 rounded-xl p-1 border border-zinc-800">
              <button @click="cartStore.removeItem(item.id)" class="p-1.5 hover:bg-zinc-800 rounded-lg text-zinc-400 hover:text-white transition-all">
                <Minus class="w-4 h-4" />
              </button>
              <span class="text-white font-bold w-6 text-center">{{ item.quantity }}</span>
              <button @click="cartStore.addItem(item)" class="p-1.5 hover:bg-zinc-800 rounded-lg text-zinc-400 hover:text-white transition-all">
                <Plus class="w-4 h-4" />
              </button>
            </div>
            
            <button @click="cartStore.removeItem(item.id)" class="p-2 text-zinc-500 hover:text-red-500 transition-colors">
              <Trash2 class="w-5 h-5" />
            </button>
          </div>
        </div>

        <button @click="cartStore.clearCart()" class="text-zinc-500 hover:text-red-500 text-sm font-medium flex items-center gap-2 transition-colors">
          <Trash2 class="w-4 h-4" /> Clear entire cart
        </button>
      </div>

      <!-- Summary -->
      <div class="space-y-6">
        <div class="bg-zinc-900 border border-zinc-800 rounded-3xl p-6 sticky top-8">
          <h3 class="text-xl font-bold text-white mb-6">Order Summary</h3>
          
          <div class="space-y-4 mb-6">
            <div class="flex justify-between text-zinc-400">
              <span>Subtotal</span>
              <span class="text-white">{{ formatCurrency(cartStore.totalAmount) }}</span>
            </div>
            <div class="flex justify-between text-zinc-400">
              <span>Delivery Fee</span>
              <span class="text-emerald-500 font-bold">Free</span>
            </div>
            <div class="pt-4 border-t border-zinc-800 flex justify-between">
              <span class="text-lg font-bold text-white">Total</span>
              <span class="text-2xl font-black text-blue-500">{{ formatCurrency(cartStore.totalAmount) }}</span>
            </div>
          </div>

          <div class="space-y-3 mb-8">
             <div class="flex items-center gap-3 p-3 bg-zinc-950 border border-zinc-800 rounded-2xl">
                <MapPin class="w-5 h-5 text-zinc-500" />
                <div class="text-xs">
                   <p class="text-zinc-500 uppercase font-bold tracking-widest">Delivery to</p>
                   <p class="text-white font-medium">Select address in next step</p>
                </div>
             </div>
             <div class="flex items-center gap-3 p-3 bg-zinc-950 border border-zinc-800 rounded-2xl">
                <CreditCard class="w-5 h-5 text-zinc-500" />
                <div class="text-xs">
                   <p class="text-zinc-500 uppercase font-bold tracking-widest">Payment Method</p>
                   <p class="text-white font-medium">Wallet Balance</p>
                </div>
             </div>
          </div>

          <button 
            @click="router.push('/checkout')"
            class="w-full py-4 bg-blue-600 hover:bg-blue-700 text-white rounded-2xl font-black flex items-center justify-center gap-2 shadow-lg shadow-blue-600/20 transition-all active:scale-95"
          >
            Go to Checkout <ChevronRight class="w-5 h-5" />
          </button>
        </div>
      </div>
    </div>
  </div>
</template>
