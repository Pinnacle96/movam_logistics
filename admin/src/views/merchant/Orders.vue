<script setup lang="ts">
import { ref, onMounted } from 'vue';
import api from '../../services/api';
import { 
  ShoppingBag, 
  Loader2,
  Search,
  Filter,
  Eye,
  CheckCircle2,
  XCircle,
  Truck
} from 'lucide-vue-next';

const orders = ref<any[]>([]);
const loading = ref(true);
const statusFilter = ref('');

const fetchOrders = async () => {
  loading.value = true;
  try {
    const response = await api.get('/merchant/orders', {
      params: { status: statusFilter.value }
    });
    orders.value = response.data.data;
  } catch (err) {
    console.error('Failed to fetch orders', err);
  } finally {
    loading.value = false;
  }
};

const updateStatus = async (orderId: number, status: string) => {
  try {
    await api.patch(`/orders/${orderId}/status`, { status });
    fetchOrders();
  } catch (err) {
    console.error('Failed to update order status', err);
  }
};

onMounted(fetchOrders);

const formatCurrency = (amount: number) => {
  return new Intl.NumberFormat('en-NG', { style: 'currency', currency: 'NGN' }).format(amount);
};
</script>

<template>
  <div class="space-y-8 animate-in fade-in duration-500">
    <div class="flex items-end justify-between">
      <div>
        <h1 class="text-3xl font-bold text-white">Order Management</h1>
        <p class="text-zinc-400 mt-1">View and process your incoming orders.</p>
      </div>
    </div>

    <!-- Filters -->
    <div class="flex flex-col md:flex-row gap-4">
      <div class="relative flex-1">
        <Search class="absolute left-3 top-1/2 -translate-y-1/2 w-5 h-5 text-zinc-500" />
        <input 
          type="text" 
          placeholder="Search by order number or customer..." 
          class="w-full pl-10 pr-4 py-2 bg-zinc-900 border border-zinc-800 rounded-xl text-white focus:outline-none focus:ring-2 focus:ring-blue-500"
        />
      </div>
      <div class="flex gap-2">
        <select 
          v-model="statusFilter"
          @change="fetchOrders"
          class="bg-zinc-900 border border-zinc-800 rounded-xl px-4 py-2 text-white focus:outline-none focus:ring-2 focus:ring-blue-500"
        >
          <option value="">All Statuses</option>
          <option value="pending">Pending</option>
          <option value="accepted">Accepted</option>
          <option value="preparing">Preparing</option>
          <option value="ready">Ready for Pickup</option>
          <option value="dispatched">Dispatched</option>
          <option value="delivered">Delivered</option>
          <option value="cancelled">Cancelled</option>
        </select>
      </div>
    </div>

    <!-- Orders Table -->
    <div class="bg-zinc-900 border border-zinc-800 rounded-2xl shadow-sm overflow-hidden">
      <div class="overflow-x-auto">
        <table class="w-full text-left min-w-[800px]">
        <thead class="bg-zinc-800/50 text-zinc-400 text-sm font-medium uppercase tracking-wider">
          <tr>
            <th class="px-6 py-4">Order #</th>
            <th class="px-6 py-4">Customer</th>
            <th class="px-6 py-4">Items</th>
            <th class="px-6 py-4">Total</th>
            <th class="px-6 py-4">Status</th>
            <th class="px-6 py-4">Actions</th>
          </tr>
        </thead>
        <tbody class="divide-y divide-zinc-800 text-white">
          <tr v-if="loading">
            <td colspan="6" class="px-6 py-12 text-center">
              <Loader2 class="w-8 h-8 text-blue-500 animate-spin mx-auto" />
            </td>
          </tr>
          <tr v-else-if="orders.length === 0">
            <td colspan="6" class="px-6 py-12 text-center text-zinc-500">
              No orders found matching your criteria.
            </td>
          </tr>
          <tr v-for="order in orders" :key="order.id" class="hover:bg-zinc-800/30 transition-colors group">
            <td class="px-6 py-4 font-bold text-blue-500">#{{ order.order_number }}</td>
            <td class="px-6 py-4">
              <div class="flex flex-col">
                <span class="font-medium">{{ order.customer.name }}</span>
                <span class="text-xs text-zinc-500">{{ order.customer.email }}</span>
              </div>
            </td>
            <td class="px-6 py-4 text-sm text-zinc-400">
              {{ order.items.length }} items
            </td>
            <td class="px-6 py-4 font-medium">{{ formatCurrency(order.total_amount) }}</td>
            <td class="px-6 py-4">
              <span :class="[
                'px-3 py-1 rounded-full text-xs font-bold uppercase tracking-wider inline-block',
                order.status === 'pending' ? 'bg-amber-500/10 text-amber-500' : 
                order.status === 'delivered' ? 'bg-emerald-500/10 text-emerald-500' : 
                'bg-blue-500/10 text-blue-500'
              ]">
                {{ order.status }}
              </span>
            </td>
            <td class="px-6 py-4">
              <div class="flex items-center gap-2">
                <button 
                  v-if="order.status === 'pending'"
                  @click="updateStatus(order.id, 'accepted')"
                  class="p-2 bg-emerald-500/10 text-emerald-500 hover:bg-emerald-500 rounded-lg hover:text-white transition-all"
                  title="Accept Order"
                >
                  <CheckCircle2 class="w-5 h-5" />
                </button>
                <button 
                  v-if="order.status === 'accepted'"
                  @click="updateStatus(order.id, 'preparing')"
                  class="p-2 bg-blue-500/10 text-blue-500 hover:bg-blue-500 rounded-lg hover:text-white transition-all"
                  title="Start Preparing"
                >
                  <Clock class="w-5 h-5" />
                </button>
                <button 
                  v-if="order.status === 'preparing'"
                  @click="updateStatus(order.id, 'ready')"
                  class="p-2 bg-purple-500/10 text-purple-500 hover:bg-purple-500 rounded-lg hover:text-white transition-all"
                  title="Mark Ready for Pickup"
                >
                  <Package class="w-5 h-5" />
                </button>
                <button class="p-2 bg-zinc-800 text-zinc-400 hover:text-white rounded-lg transition-all">
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
