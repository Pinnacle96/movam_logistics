<script setup lang="ts">
import { ref, reactive, onMounted } from 'vue';
import { useRouter } from 'vue-router';
import { useCartStore } from '../../stores/cart';
import { useAuthStore } from '../../stores/auth';
import api from '../../services/api';
import { 
  ArrowLeft, 
  MapPin, 
  CreditCard, 
  ShoppingBag, 
  Loader2,
  CheckCircle2,
  AlertCircle
} from 'lucide-vue-next';

const router = useRouter();
const cartStore = useCartStore();
const authStore = useAuthStore();

const loading = ref(false);
const error = ref('');
const success = ref(false);

const orderData = reactive({
  delivery_address: '',
  delivery_lat: 0,
  delivery_lng: 0,
  payment_method: 'wallet',
  items: cartStore.items.map(item => ({
    product_id: item.id,
    quantity: item.quantity
  }))
});

const handleCheckout = async () => {
  if (!orderData.delivery_address) {
    error.value = 'Please provide a delivery address.';
    return;
  }

  loading.value = true;
  error.value = '';

  try {
    // For now, let's use dummy lat/lng
    orderData.delivery_lat = 6.5244;
    orderData.delivery_lng = 3.3792;

    // 1. Create Order
    const response = await api.post('/orders', orderData);
    const order = response.data;

    if (orderData.payment_method === 'paystack') {
       // 2. Initialize Paystack Payment
       const initResponse = await api.post('/payments/initialize', {
         order_id: order.id,
         callback_url: window.location.origin + '/payment/callback'
       });

       // 3. Open Paystack Checkout
       const handler = (window as any).PaystackPop.setup({
         key: import.meta.env.VITE_PAYSTACK_PUBLIC_KEY,
         email: authStore.user?.email,
         amount: Math.round(order.total_amount * 100),
         currency: 'NGN',
         ref: initResponse.data.reference,
         callback: async (paystackResponse: any) => {
            // 4. Verify Payment
            await api.post('/payments/verify', { reference: paystackResponse.reference });
            await authStore.fetchUser();
            cartStore.clearCart();
            success.value = true;
            setTimeout(() => {
              router.push(`/orders/${order.id}/track`);
            }, 2000);
         },
         onClose: () => {
           loading.value = false;
         }
       });
       handler.openIframe();
    } else {
      // Handle other payment methods (e.g. Wallet)
      await authStore.fetchUser();
      success.value = true;
      cartStore.clearCart();
      setTimeout(() => {
        router.push(`/orders/${order.id}/track`);
      }, 2000);
    }
  } catch (err: any) {
    error.value = err.response?.data?.message || 'Checkout failed. Please check your balance.';
  } finally {
    if (orderData.payment_method !== 'paystack') {
      loading.value = false;
    }
  }
};

const formatCurrency = (amount: number) => {
  return new Intl.NumberFormat('en-NG', { style: 'currency', currency: 'NGN' }).format(amount);
};

onMounted(() => {
  if (cartStore.isEmpty) {
    router.push('/');
  }
});
</script>

<template>
  <div class="max-w-4xl mx-auto space-y-8 animate-in fade-in duration-700">
    <div class="flex items-center gap-4">
      <button @click="router.back()" class="p-2 hover:bg-zinc-900 rounded-xl text-zinc-400 hover:text-white transition-all">
        <ArrowLeft class="w-6 h-6" />
      </button>
      <h1 class="text-3xl font-bold text-white">Checkout</h1>
    </div>

    <div v-if="success" class="bg-zinc-900 border border-emerald-500/50 rounded-3xl p-8 sm:p-16 text-center animate-in zoom-in-95 duration-500 shadow-2xl shadow-emerald-500/10">
      <div class="w-20 h-20 sm:w-24 sm:h-24 bg-emerald-500 rounded-full flex items-center justify-center mx-auto mb-6 text-white shadow-lg shadow-emerald-500/30">
        <CheckCircle2 class="w-10 h-10 sm:w-12 sm:h-12" />
      </div>
      <h3 class="text-2xl sm:text-3xl font-bold text-white mb-2">Order Placed Successfully!</h3>
      <p class="text-zinc-500 mb-8 max-w-sm mx-auto">Your order is being prepared by the merchant. You'll be redirected to tracking shortly.</p>
      <div class="flex items-center justify-center gap-2 text-emerald-500 font-bold">
        <Loader2 class="w-5 h-5 animate-spin" /> Redirecting...
      </div>
    </div>

    <div v-else class="grid grid-cols-1 lg:grid-cols-2 gap-8 lg:gap-12">
      <!-- Checkout Form -->
      <div class="space-y-10">
        <!-- Delivery Address -->
        <section class="space-y-6">
          <div class="flex items-center gap-3">
             <div class="w-10 h-10 bg-blue-600/10 text-blue-500 rounded-xl flex items-center justify-center">
                <MapPin class="w-5 h-5" />
             </div>
             <h2 class="text-xl font-bold text-white">Delivery Address</h2>
          </div>
          
          <div class="space-y-4">
            <div class="relative">
              <textarea 
                v-model="orderData.delivery_address"
                placeholder="Enter your detailed delivery address..." 
                class="w-full h-32 pl-4 pr-4 py-3 bg-zinc-900 border border-zinc-800 rounded-2xl text-white focus:outline-none focus:ring-2 focus:ring-blue-500 transition-all placeholder-zinc-600"
              ></textarea>
            </div>
            
            <!-- Map Placeholder -->
            <div class="bg-zinc-950 rounded-2xl border border-zinc-800 h-48 flex items-center justify-center overflow-hidden group relative">
               <img src="https://images.unsplash.com/photo-1526778548025-fa2f459cd5c1?auto=format&fit=crop&q=80&w=1000" class="absolute inset-0 w-full h-full object-cover opacity-20 grayscale group-hover:grayscale-0 transition-all duration-700" />
               <div class="relative text-center p-6">
                  <MapPin class="w-8 h-8 text-zinc-700 mx-auto mb-2" />
                  <p class="text-zinc-500 text-sm font-medium">Interactive Mapbox Integration</p>
                  <p class="text-zinc-700 text-xs mt-1">Select location on map for precise delivery.</p>
               </div>
            </div>
          </div>
        </section>

        <!-- Payment Method -->
        <section class="space-y-6">
          <div class="flex items-center gap-3">
             <div class="w-10 h-10 bg-blue-600/10 text-blue-500 rounded-xl flex items-center justify-center">
                <CreditCard class="w-5 h-5" />
             </div>
             <h2 class="text-xl font-bold text-white">Payment Method</h2>
          </div>
          
          <div class="grid grid-cols-1 gap-4">
             <!-- Paystack Option -->
             <label 
               @click="orderData.payment_method = 'paystack'"
               :class="[
                 'flex items-center justify-between p-5 bg-zinc-900 border-2 rounded-2xl cursor-pointer transition-all shadow-lg',
                 orderData.payment_method === 'paystack' ? 'border-blue-600 shadow-blue-600/5' : 'border-zinc-800'
               ]"
             >
                <div class="flex items-center gap-4">
                   <div class="w-12 h-12 bg-white/5 rounded-xl flex items-center justify-center p-2">
                      <img src="https://paystack.com/assets/img/login/paystack-logo.png" class="w-full object-contain" alt="Paystack" />
                   </div>
                   <div>
                      <p class="text-white font-bold">Paystack</p>
                      <p class="text-zinc-500 text-xs">Secure Card & Bank Payment</p>
                   </div>
                </div>
                <div :class="[
                  'w-6 h-6 rounded-full border-2 flex items-center justify-center',
                  orderData.payment_method === 'paystack' ? 'border-blue-600' : 'border-zinc-800'
                ]">
                   <div v-if="orderData.payment_method === 'paystack'" class="w-3 h-3 bg-blue-600 rounded-full"></div>
                </div>
             </label>

             <!-- Wallet Option -->
             <label 
               @click="orderData.payment_method = 'wallet'"
               :class="[
                 'flex items-center justify-between p-5 bg-zinc-900 border-2 rounded-2xl cursor-pointer transition-all shadow-lg',
                 orderData.payment_method === 'wallet' ? 'border-blue-600 shadow-blue-600/5' : 'border-zinc-800'
               ]"
             >
                <div class="flex items-center gap-4">
                   <div class="w-12 h-12 bg-blue-600/10 text-blue-500 rounded-xl flex items-center justify-center font-black">₦</div>
                   <div>
                      <p class="text-white font-bold">Movam Wallet</p>
                      <p class="text-zinc-500 text-xs">Balance: {{ formatCurrency(authStore.user?.wallet?.balance || 0) }}</p>
                   </div>
                </div>
                <div :class="[
                  'w-6 h-6 rounded-full border-2 flex items-center justify-center',
                  orderData.payment_method === 'wallet' ? 'border-blue-600' : 'border-zinc-800'
                ]">
                   <div v-if="orderData.payment_method === 'wallet'" class="w-3 h-3 bg-blue-600 rounded-full"></div>
                </div>
             </label>
          </div>
        </section>
      </div>

      <!-- Order Summary Column -->
      <div class="space-y-8">
        <div class="bg-zinc-900 border border-zinc-800 rounded-3xl p-8 sticky top-8 shadow-2xl shadow-zinc-950/50">
           <h3 class="text-2xl font-bold text-white mb-8">Order Details</h3>
           
           <div class="space-y-6 mb-10 max-h-60 overflow-y-auto pr-2 custom-scrollbar">
              <div v-for="item in cartStore.items" :key="item.id" class="flex items-center justify-between group">
                 <div class="flex items-center gap-4">
                    <div class="w-12 h-12 bg-zinc-800 rounded-xl flex items-center justify-center text-zinc-500 font-bold">
                       {{ item.quantity }}x
                    </div>
                    <span class="text-white font-medium truncate max-w-[120px]">{{ item.name }}</span>
                 </div>
                 <span class="text-zinc-400 font-medium">{{ formatCurrency(item.price * item.quantity) }}</span>
              </div>
           </div>

           <div class="space-y-4 pt-6 sm:pt-8 border-t border-zinc-800">
              <div class="flex justify-between text-zinc-400 font-medium text-sm sm:text-base">
                 <span>Subtotal</span>
                 <span class="text-white">{{ formatCurrency(cartStore.totalAmount) }}</span>
              </div>
              <div class="flex justify-between text-zinc-400 font-medium text-sm sm:text-base">
                 <span>Delivery Fee</span>
                 <span class="text-emerald-500 font-black">FREE</span>
              </div>
              <div class="pt-4 sm:pt-6 border-t-2 border-zinc-800 border-dashed flex flex-col sm:flex-row justify-between items-start sm:items-end gap-4">
                 <div>
                    <p class="text-zinc-500 text-[10px] font-black uppercase tracking-widest mb-1">Total to Pay</p>
                    <span class="text-2xl sm:text-3xl font-black text-white">{{ formatCurrency(cartStore.totalAmount) }}</span>
                 </div>
                 <div class="text-right hidden sm:block">
                    <ShoppingBag class="w-8 h-8 text-blue-600 ml-auto mb-1" />
                    <p class="text-zinc-500 text-[10px] italic">Tax included</p>
                 </div>
              </div>
           </div>

           <div v-if="error" class="mt-6 sm:mt-8 p-4 bg-red-500/10 border border-red-500/20 rounded-2xl flex items-start gap-3 text-red-500 text-sm animate-in shake-1 duration-500">
              <AlertCircle class="w-5 h-5 flex-shrink-0" />
              <p>{{ error }}</p>
           </div>

           <button 
             @click="handleCheckout"
             :disabled="loading"
             class="w-full mt-6 sm:mt-10 py-4 sm:py-5 bg-blue-600 hover:bg-blue-700 text-white rounded-2xl font-black text-base sm:text-lg flex items-center justify-center gap-3 shadow-xl shadow-blue-600/30 transition-all hover:scale-[1.02] active:scale-95 disabled:opacity-50 disabled:cursor-not-allowed group"
           >
              <template v-if="loading">
                <Loader2 class="w-6 h-6 animate-spin" />
                Processing Payment...
              </template>
              <template v-else>
                Pay & Place Order
                <ArrowLeft class="w-6 h-6 rotate-180 group-hover:translate-x-1 transition-transform" />
              </template>
           </button>
           
           <p class="text-center text-zinc-600 text-xs mt-6 px-4">By placing your order, you agree to Movam's <span class="text-zinc-500 underline">Terms of Service</span> and <span class="text-zinc-500 underline">Privacy Policy</span>.</p>
        </div>
      </div>
    </div>
  </div>
</template>

<style scoped>
.custom-scrollbar::-webkit-scrollbar {
  width: 4px;
}
.custom-scrollbar::-webkit-scrollbar-track {
  background: transparent;
}
.custom-scrollbar::-webkit-scrollbar-thumb {
  background: #27272a;
  border-radius: 10px;
}
.custom-scrollbar::-webkit-scrollbar-thumb:hover {
  background: #3f3f46;
}
</style>
