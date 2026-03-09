<script setup lang="ts">
import { ref, onMounted } from 'vue';
import api from '../../services/api';
import { 
  Package, 
  Search, 
  Filter, 
  Loader2,
  Eye,
  User,
  Store
} from 'lucide-vue-next';

const orders = ref<any[]>([]);
const loading = ref(true);
const statusFilter = ref('');
const searchQuery = ref('');

const fetchOrders = async () => {
  loading.value = true;
  try {
    const response = await api.get('/admin/orders', {
      params: { 
        status: statusFilter.value,
        search: searchQuery.value
      }
    });
    orders.value = response.data.data;
  } catch (err) {
    console.error('Failed to fetch orders', err);
  } finally {
    loading.value = false;
  }
};

const formatCurrency = (amount: number) => {
  return new Intl.NumberFormat('en-NG', { style: 'currency', currency: 'NGN' }).format(amount);
};

onMounted(fetchOrders);
</script>

<template>
  <div class="space-y-10 animate-in fade-in duration-700">
    <div class="flex items-end justify-between">
      <div>
        <h1 class="text-3xl font-bold text-white tracking-tight">Order Oversight</h1>
        <p class="text-zinc-400 mt-1 font-medium">Monitor all platform transactions and delivery statuses.</p>
      </div>
      <button @click="fetchOrders" class="p-3 bg-zinc-900 border border-zinc-800 rounded-xl text-zinc-400 hover:text-white transition-all shadow-lg shadow-zinc-950/50">
         <Loader2 v-if="loading" class="w-5 h-5 animate-spin" />
         <Filter v-else class="w-5 h-5" />
      </button>
    </div>

    <!-- Filters Bar -->
    <div class="flex flex-col md:flex-row gap-6">
      <div class="relative flex-1">
        <Search class="absolute left-4 top-1/2 -translate-y-1/2 w-5 h-5 text-zinc-500" />
        <input 
          v-model="searchQuery"
          @keyup.enter="fetchOrders"
          type="text" 
          placeholder="Search by order number or customer..." 
          class="w-full pl-12 pr-4 py-4 bg-zinc-900 border border-zinc-800 rounded-2xl text-white focus:outline-none focus:ring-2 focus:ring-blue-500 transition-all placeholder-zinc-600 shadow-lg shadow-zinc-950/50"
        />
      </div>
      <div class="flex gap-4">
        <select 
          v-model="statusFilter"
          @change="fetchOrders"
          class="bg-zinc-900 border border-zinc-800 rounded-2xl px-6 py-4 text-white focus:outline-none focus:ring-2 focus:ring-blue-500 transition-all font-bold tracking-tight shadow-lg shadow-zinc-950/50"
        >
          <option value="">All Statuses</option>
          <option value="pending">Pending</option>
          <option value="accepted">Accepted</option>
          <option value="preparing">Preparing</option>
          <option value="ready">Ready</option>
          <option value="dispatched">Dispatched</option>
          <option value="delivered">Delivered</option>
          <option value="cancelled">Cancelled</option>
        </select>
      </div>
    </div>

    <!-- Orders Table -->
    <div class="bg-zinc-900 border border-zinc-800 rounded-[2.5rem] shadow-2xl shadow-zinc-950/50 overflow-hidden">
      <div class="overflow-x-auto">
        <table class="w-full text-left min-w-[900px]">
        <thead class="bg-zinc-800/50 text-zinc-500 text-[10px] font-black uppercase tracking-[0.2em]">
          <tr>
            <th class="px-8 py-6">Order Details</th>
            <th class="px-8 py-6">Participants</th>
            <th class="px-8 py-6">Financials</th>
            <th class="px-8 py-6">Current Status</th>
            <th class="px-8 py-6 text-right">Actions</th>
          </tr>
        </thead>
        <tbody class="divide-y divide-zinc-800 text-white">
          <tr v-if="loading && orders.length === 0">
            <td colspan="5" class="px-8 py-20 text-center">
              <Loader2 class="w-12 h-12 text-blue-500 animate-spin mx-auto" />
            </td>
          </tr>
          <tr v-else-if="orders.length === 0">
            <td colspan="5" class="px-8 py-20 text-center text-zinc-500 font-black uppercase tracking-widest">
              No orders found.
            </td>
          </tr>
          <tr v-for="order in orders" :key="order.id" class="hover:bg-zinc-800/30 transition-all group">
            <td class="px-8 py-6">
              <div class="flex items-center gap-4">
                <div class="w-12 h-12 bg-zinc-800 rounded-xl flex items-center justify-center text-blue-500 border border-zinc-700 shadow-lg group-hover:scale-110 transition-all">
                  <Package class="w-6 h-6" />
                </div>
                <div class="flex flex-col">
                  <span class="font-black text-white text-lg tracking-tight">#{{ order.order_number }}</span>
                  <span class="text-[10px] text-zinc-500 font-bold uppercase tracking-widest mt-0.5">
                    {{ new Date(order.created_at).toLocaleString() }}
                  </span>
                </div>
              </div>
            </td>
            <td class="px-8 py-6">
              <div class="space-y-3">
                 <div class="flex items-center gap-2">
                    <User class="w-3 h-3 text-zinc-500" />
                    <span class="text-xs text-zinc-300 font-bold">{{ order.customer.name }}</span>
                 </div>
                 <div class="flex items-center gap-2">
                    <Store class="w-3 h-3 text-zinc-500" />
                    <span class="text-xs text-zinc-300 font-bold">{{ order.merchant.business_name }}</span>
                 </div>
              </div>
            </td>
            <td class="px-8 py-6">
              <div class="flex flex-col">
                 <span class="text-white font-black">{{ formatCurrency(order.total_amount) }}</span>
                 <span class="text-[10px] text-emerald-500 font-bold">Paid via {{ order.payment_method }}</span>
              </div>
            </td>
            <td class="px-8 py-6">
               <span :class="[
                'px-3 py-1.5 rounded-xl text-[10px] font-black uppercase tracking-widest border',
                order.status === 'pending' ? 'bg-amber-500/10 text-amber-500 border-amber-500/20' : 
                order.status === 'delivered' ? 'bg-emerald-500/10 text-emerald-500 border-emerald-500/20' : 
                'bg-blue-500/10 text-blue-500 border-blue-500/20'
               ]">
                {{ order.status }}
               </span>
            </td>
            <td class="px-8 py-6">
              <div class="flex items-center justify-end">
                <button class="p-3 bg-zinc-800 text-zinc-400 hover:text-white rounded-xl transition-all shadow-xl group-hover:scale-110">
                  <Eye class="w-5 h-5" />
                </button>
              </div>
            </td>
          </tr>
        </tbody>
      </table>
    </div>
  </div>
</div>
</template>
