<script setup lang="ts">
import { ref, onMounted, reactive } from 'vue';
import api from '../../services/api';
import { 
  Settings, 
  Loader2,
  Save,
  Globe,
  Bell,
  Shield,
  CreditCard,
  Mail,
  Zap
} from 'lucide-vue-next';

const loading = ref(true);
const saving = ref(false);
const settings = reactive({
  platform_name: '',
  delivery_fee_base: 0,
  delivery_fee_per_km: 0,
  commission_rate_default: 0,
  min_withdrawal_amount: 0,
  support_email: '',
  maintenance_mode: false
});

const fetchSettings = async () => {
  loading.value = true;
  try {
    const response = await api.get('/admin/settings');
    Object.assign(settings, response.data);
  } catch (err) {
    console.error('Failed to fetch settings', err);
  } finally {
    loading.value = false;
  }
};

const handleSave = async () => {
  saving.value = true;
  try {
    await api.post('/admin/settings', settings);
    alert('Settings saved successfully!');
  } catch (err) {
    console.error('Failed to save settings', err);
  } finally {
    saving.value = false;
  }
};

onMounted(fetchSettings);
</script>

<template>
  <div class="space-y-10 animate-in fade-in duration-700">
    <div class="flex flex-col lg:flex-row items-start lg:items-end justify-between gap-4">
      <div>
        <h1 class="text-3xl font-bold text-white tracking-tight">Platform Configuration</h1>
        <p class="text-zinc-400 mt-1 font-medium">Fine-tune platform parameters and system behavior.</p>
      </div>
      <button 
        @click="handleSave"
        :disabled="saving"
        class="w-full lg:w-auto bg-blue-600 hover:bg-blue-700 text-white px-8 py-4 rounded-2xl font-black transition-all shadow-lg shadow-blue-600/20 active:scale-95 flex items-center justify-center gap-3 disabled:opacity-50"
      >
        <Loader2 v-if="saving" class="w-5 h-5 animate-spin" />
        <Save v-else class="w-5 h-5" />
        Save Changes
      </button>
    </div>

    <div v-if="loading" class="flex justify-center py-20">
      <Loader2 class="w-12 h-12 text-blue-500 animate-spin" />
    </div>

    <div v-else class="grid grid-cols-1 lg:grid-cols-3 gap-10">
      <!-- General Settings -->
      <div class="lg:col-span-2 space-y-8">
        <div class="bg-zinc-900 border border-zinc-800 rounded-[2.5rem] p-8 space-y-8 shadow-2xl">
           <div class="flex items-center gap-4">
              <div class="w-12 h-12 bg-blue-600/10 text-blue-500 rounded-2xl flex items-center justify-center">
                 <Globe class="w-6 h-6" />
              </div>
              <h3 class="text-xl font-black text-white uppercase tracking-tight">General Information</h3>
           </div>

           <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
              <div class="space-y-2">
                 <label class="text-xs font-black text-zinc-500 uppercase tracking-[0.2em]">Platform Name</label>
                 <input 
                  v-model="settings.platform_name"
                  type="text" 
                  class="w-full p-4 bg-zinc-950 border border-zinc-800 rounded-2xl text-white focus:outline-none focus:ring-2 focus:ring-blue-500 transition-all"
                 />
              </div>
              <div class="space-y-2">
                 <label class="text-xs font-black text-zinc-500 uppercase tracking-[0.2em]">Support Email</label>
                 <input 
                  v-model="settings.support_email"
                  type="email" 
                  class="w-full p-4 bg-zinc-950 border border-zinc-800 rounded-2xl text-white focus:outline-none focus:ring-2 focus:ring-blue-500 transition-all"
                 />
              </div>
           </div>
        </div>

        <div class="bg-zinc-900 border border-zinc-800 rounded-[2.5rem] p-8 space-y-8 shadow-2xl">
           <div class="flex items-center gap-4">
              <div class="w-12 h-12 bg-emerald-600/10 text-emerald-500 rounded-2xl flex items-center justify-center">
                 <CreditCard class="w-6 h-6" />
              </div>
              <h3 class="text-xl font-black text-white uppercase tracking-tight">Financial Parameters</h3>
           </div>

           <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
              <div class="space-y-2">
                 <label class="text-xs font-black text-zinc-500 uppercase tracking-[0.2em]">Base Delivery Fee (₦)</label>
                 <input 
                  v-model="settings.delivery_fee_base"
                  type="number" 
                  class="w-full p-4 bg-zinc-950 border border-zinc-800 rounded-2xl text-white focus:outline-none focus:ring-2 focus:ring-blue-500 transition-all"
                 />
              </div>
              <div class="space-y-2">
                 <label class="text-xs font-black text-zinc-500 uppercase tracking-[0.2em]">Delivery Fee Per KM (₦)</label>
                 <input 
                  v-model="settings.delivery_fee_per_km"
                  type="number" 
                  class="w-full p-4 bg-zinc-950 border border-zinc-800 rounded-2xl text-white focus:outline-none focus:ring-2 focus:ring-blue-500 transition-all"
                 />
              </div>
              <div class="space-y-2">
                 <label class="text-xs font-black text-zinc-500 uppercase tracking-[0.2em]">Platform Commission (%)</label>
                 <input 
                  v-model="settings.commission_rate_default"
                  type="number" 
                  class="w-full p-4 bg-zinc-950 border border-zinc-800 rounded-2xl text-white focus:outline-none focus:ring-2 focus:ring-blue-500 transition-all"
                 />
              </div>
              <div class="space-y-2">
                 <label class="text-xs font-black text-zinc-500 uppercase tracking-[0.2em]">Min. Withdrawal (₦)</label>
                 <input 
                  v-model="settings.min_withdrawal_amount"
                  type="number" 
                  class="w-full p-4 bg-zinc-950 border border-zinc-800 rounded-2xl text-white focus:outline-none focus:ring-2 focus:ring-blue-500 transition-all"
                 />
              </div>
           </div>
        </div>
      </div>

      <!-- Sidebar Settings -->
      <div class="space-y-8">
        <div class="bg-zinc-900 border border-zinc-800 rounded-[2.5rem] p-8 space-y-8 shadow-2xl">
           <div class="flex items-center gap-4">
              <div class="w-12 h-12 bg-amber-600/10 text-amber-500 rounded-2xl flex items-center justify-center">
                 <Zap class="w-6 h-6" />
              </div>
              <h3 class="text-xl font-black text-white uppercase tracking-tight">System Status</h3>
           </div>

           <div class="space-y-6">
              <div class="flex items-center justify-between p-4 bg-zinc-950 rounded-2xl border border-zinc-800">
                 <div>
                    <p class="text-white font-bold">Maintenance Mode</p>
                    <p class="text-zinc-500 text-[10px] font-medium">Disable platform for users</p>
                 </div>
                 <button 
                  @click="settings.maintenance_mode = !settings.maintenance_mode"
                  :class="['w-14 h-8 rounded-full transition-all flex items-center p-1', settings.maintenance_mode ? 'bg-red-600' : 'bg-zinc-800']"
                 >
                    <div :class="['w-6 h-6 bg-white rounded-full transition-all shadow-lg', settings.maintenance_mode ? 'translate-x-6' : 'translate-x-0']"></div>
                 </button>
              </div>

              <div class="p-6 bg-blue-600/5 border border-blue-500/20 rounded-2xl">
                 <div class="flex items-center gap-3 mb-4">
                    <Shield class="w-5 h-5 text-blue-500" />
                    <span class="text-blue-500 font-black text-xs uppercase tracking-widest">System Security</span>
                 </div>
                 <p class="text-zinc-400 text-xs leading-relaxed">
                    All financial parameters are encrypted at rest. Changes to commission rates will take effect on new orders immediately.
                 </p>
              </div>
           </div>
        </div>
      </div>
    </div>
  </div>
</template>
