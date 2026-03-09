<script setup lang="ts">
import { ref, onMounted } from 'vue';
import api from '../../services/api';
import { 
  Store, 
  Search, 
  Filter, 
  Loader2,
  CheckCircle2,
  XCircle,
  Eye,
  Percent
} from 'lucide-vue-next';

const merchants = ref<any[]>([]);
const loading = ref(true);
const searchQuery = ref('');

const fetchMerchants = async () => {
  loading.value = true;
  try {
    const response = await api.get('/admin/merchants', {
      params: { search: searchQuery.value }
    });
    merchants.value = response.data.data;
  } catch (err) {
    console.error('Failed to fetch merchants', err);
  } finally {
    loading.value = false;
  }
};

const toggleVerification = async (merchant: any) => {
  try {
    await api.patch(`/admin/merchants/${merchant.id}/status`, {
      is_verified: !merchant.is_verified
    });
    fetchMerchants();
  } catch (err) {
    console.error('Failed to toggle verification', err);
  }
};

const toggleStatus = async (merchant: any) => {
  try {
    await api.patch(`/admin/merchants/${merchant.id}/status`, {
      is_active: !merchant.is_active
    });
    fetchMerchants();
  } catch (err) {
    console.error('Failed to toggle status', err);
  }
};

const updateCommission = async (merchant: any) => {
  const rate = prompt('Enter new commission rate (%)', merchant.commission_rate);
  if (rate === null) return;
  
  try {
    await api.patch(`/admin/merchants/${merchant.id}/status`, {
      commission_rate: parseFloat(rate)
    });
    fetchMerchants();
  } catch (err) {
    console.error('Failed to update commission', err);
  }
};

onMounted(fetchMerchants);
</script>

<template>
  <div class="space-y-10 animate-in fade-in duration-700">
    <div class="flex items-end justify-between">
      <div>
        <h1 class="text-3xl font-bold text-white tracking-tight">Merchant Management</h1>
        <p class="text-zinc-400 mt-1 font-medium">Approve and manage partner restaurants and shops.</p>
      </div>
      <button @click="fetchMerchants" class="p-3 bg-zinc-900 border border-zinc-800 rounded-xl text-zinc-400 hover:text-white transition-all shadow-lg shadow-zinc-950/50">
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
          @keyup.enter="fetchMerchants"
          type="text" 
          placeholder="Search by business name..." 
          class="w-full pl-12 pr-4 py-4 bg-zinc-900 border border-zinc-800 rounded-2xl text-white focus:outline-none focus:ring-2 focus:ring-blue-500 transition-all placeholder-zinc-600 shadow-lg shadow-zinc-950/50"
        />
      </div>
    </div>

    <!-- Merchants Table -->
    <div class="bg-zinc-900 border border-zinc-800 rounded-[2.5rem] shadow-2xl shadow-zinc-950/50 overflow-hidden">
      <div class="overflow-x-auto">
        <table class="w-full text-left min-w-[900px]">
        <thead class="bg-zinc-800/50 text-zinc-500 text-[10px] font-black uppercase tracking-[0.2em]">
          <tr>
            <th class="px-8 py-6">Business Info</th>
            <th class="px-8 py-6">Owner</th>
            <th class="px-8 py-6">Commission</th>
            <th class="px-8 py-6">Status</th>
            <th class="px-8 py-6 text-right">Operations</th>
          </tr>
        </thead>
        <tbody class="divide-y divide-zinc-800 text-white">
          <tr v-if="loading && merchants.length === 0">
            <td colspan="5" class="px-8 py-20 text-center">
              <Loader2 class="w-12 h-12 text-blue-500 animate-spin mx-auto" />
            </td>
          </tr>
          <tr v-else-if="merchants.length === 0">
            <td colspan="5" class="px-8 py-20 text-center text-zinc-500 font-black uppercase tracking-widest">
              No merchants found.
            </td>
          </tr>
          <tr v-for="merchant in merchants" :key="merchant.id" class="hover:bg-zinc-800/30 transition-all group">
            <td class="px-8 py-6">
              <div class="flex items-center gap-4">
                <div class="w-14 h-14 bg-zinc-800 rounded-2xl flex items-center justify-center text-zinc-400 font-black text-xl border border-zinc-700 overflow-hidden shadow-lg">
                  <img v-if="merchant.logo" :src="merchant.logo" class="w-full h-full object-cover" />
                  <Store v-else class="w-6 h-6" />
                </div>
                <div class="flex flex-col">
                  <span class="font-black text-white text-lg group-hover:text-blue-400 transition-colors">{{ merchant.business_name }}</span>
                  <span class="text-xs text-zinc-500 font-bold mt-0.5 tracking-tight">{{ merchant.address }}</span>
                </div>
              </div>
            </td>
            <td class="px-8 py-6">
              <div class="flex flex-col">
                <span class="font-bold text-white">{{ merchant.user.name }}</span>
                <span class="text-xs text-zinc-500">{{ merchant.user.email }}</span>
              </div>
            </td>
            <td class="px-8 py-6">
               <button @click="updateCommission(merchant)" class="flex items-center gap-2 px-3 py-1.5 bg-blue-500/10 text-blue-500 rounded-xl border border-blue-500/20 font-black text-sm">
                  <Percent class="w-4 h-4" /> {{ merchant.commission_rate }}%
               </button>
            </td>
            <td class="px-8 py-6">
              <div class="flex flex-col gap-2">
                 <div class="flex items-center gap-2">
                    <div :class="['w-2 h-2 rounded-full shadow-lg', merchant.is_verified ? 'bg-emerald-500 shadow-emerald-500/50' : 'bg-zinc-700']"></div>
                    <span :class="['text-[10px] font-black uppercase tracking-widest', merchant.is_verified ? 'text-emerald-500' : 'text-zinc-500']">
                       {{ merchant.is_verified ? 'Verified' : 'Unverified' }}
                    </span>
                 </div>
                 <div class="flex items-center gap-2">
                    <div :class="['w-2 h-2 rounded-full shadow-lg', merchant.is_active ? 'bg-blue-500 shadow-blue-500/50' : 'bg-red-500 shadow-red-500/50']"></div>
                    <span :class="['text-[10px] font-black uppercase tracking-widest', merchant.is_active ? 'text-blue-500' : 'text-red-500']">
                       {{ merchant.is_active ? 'Active' : 'Inactive' }}
                    </span>
                 </div>
              </div>
            </td>
            <td class="px-8 py-6">
              <div class="flex items-center justify-end gap-3 opacity-0 group-hover:opacity-100 transition-all translate-x-4 group-hover:translate-x-0">
                <button @click="toggleVerification(merchant)" :class="['p-3 rounded-xl transition-all shadow-xl', merchant.is_verified ? 'bg-emerald-500/10 text-emerald-500' : 'bg-zinc-800 text-zinc-400']" title="Toggle Verification">
                  <CheckCircle2 class="w-5 h-5" />
                </button>
                <button @click="toggleStatus(merchant)" :class="['p-3 rounded-xl transition-all shadow-xl', merchant.is_active ? 'bg-blue-500/10 text-blue-500' : 'bg-red-500/10 text-red-500']" title="Toggle Status">
                  <XCircle class="w-5 h-5" />
                </button>
                <button class="p-3 bg-zinc-800 text-zinc-400 hover:text-white rounded-xl transition-all shadow-xl">
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
