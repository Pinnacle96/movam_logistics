<script setup lang="ts">
import { ref, reactive } from 'vue';
import { useAuthStore } from '../../stores/auth';
import { 
  User, 
  Mail, 
  Phone, 
  MapPin, 
  Shield, 
  Loader2,
  CheckCircle2,
  Camera
} from 'lucide-vue-next';

const authStore = useAuthStore();
const loading = ref(false);
const success = ref(false);

const form = reactive({
  name: authStore.user?.name || '',
  email: authStore.user?.email || '',
  phone: authStore.user?.phone || '',
  address: authStore.user?.address || ''
});

const handleUpdate = async () => {
  loading.value = true;
  try {
    // In a real app, call API to update profile
    // await api.put('/user/profile', form);
    setTimeout(() => {
      success.value = true;
      loading.value = false;
      setTimeout(() => success.value = false, 3000);
    }, 1000);
  } catch (err) {
    console.error(err);
    loading.value = false;
  }
};
</script>

<template>
  <div class="max-w-2xl mx-auto space-y-8 animate-in fade-in duration-700">
    <div>
      <h1 class="text-3xl font-bold text-white tracking-tight">My Profile</h1>
      <p class="text-zinc-400 mt-1 font-medium">Manage your personal information and security settings.</p>
    </div>

    <div class="bg-zinc-900 border border-zinc-800 rounded-[2.5rem] p-8 lg:p-12 shadow-2xl relative overflow-hidden">
      <div class="flex flex-col items-center mb-12">
         <div class="relative group">
            <div class="w-32 h-32 bg-zinc-800 rounded-[2.5rem] flex items-center justify-center text-4xl font-black text-zinc-600 border-4 border-zinc-900 shadow-xl group-hover:scale-105 transition-transform duration-500">
               {{ form.name.charAt(0) }}
            </div>
            <button class="absolute -bottom-2 -right-2 p-3 bg-blue-600 text-white rounded-2xl shadow-lg shadow-blue-600/30 hover:bg-blue-700 transition-all">
               <Camera class="w-5 h-5" />
            </button>
         </div>
         <h2 class="text-2xl font-black text-white mt-6">{{ form.name }}</h2>
         <p class="text-zinc-500 font-bold uppercase text-[10px] tracking-widest mt-1">Premium Member</p>
      </div>

      <form @submit.prevent="handleUpdate" class="space-y-8">
        <div class="grid grid-cols-1 gap-6">
          <div class="space-y-2">
            <label class="text-[10px] font-black text-zinc-500 uppercase tracking-widest">Full Name</label>
            <div class="relative">
              <User class="absolute left-4 top-1/2 -translate-y-1/2 w-5 h-5 text-zinc-600" />
              <input v-model="form.name" type="text" class="w-full pl-12 pr-4 py-4 bg-zinc-950 border border-zinc-800 rounded-2xl text-white focus:outline-none focus:ring-2 focus:ring-blue-500 transition-all" />
            </div>
          </div>

          <div class="space-y-2">
            <label class="text-[10px] font-black text-zinc-500 uppercase tracking-widest">Email Address</label>
            <div class="relative">
              <Mail class="absolute left-4 top-1/2 -translate-y-1/2 w-5 h-5 text-zinc-600" />
              <input v-model="form.email" type="email" disabled class="w-full pl-12 pr-4 py-4 bg-zinc-950 border border-zinc-800 rounded-2xl text-zinc-500 cursor-not-allowed" />
            </div>
          </div>

          <div class="space-y-2">
            <label class="text-[10px] font-black text-zinc-500 uppercase tracking-widest">Phone Number</label>
            <div class="relative">
              <Phone class="absolute left-4 top-1/2 -translate-y-1/2 w-5 h-5 text-zinc-600" />
              <input v-model="form.phone" type="text" class="w-full pl-12 pr-4 py-4 bg-zinc-950 border border-zinc-800 rounded-2xl text-white focus:outline-none focus:ring-2 focus:ring-blue-500 transition-all" />
            </div>
          </div>

          <div class="space-y-2">
            <label class="text-[10px] font-black text-zinc-500 uppercase tracking-widest">Delivery Address</label>
            <div class="relative">
              <MapPin class="absolute left-4 top-1/2 -translate-y-1/2 w-5 h-5 text-zinc-600" />
              <input v-model="form.address" type="text" class="w-full pl-12 pr-4 py-4 bg-zinc-950 border border-zinc-800 rounded-2xl text-white focus:outline-none focus:ring-2 focus:ring-blue-500 transition-all" />
            </div>
          </div>
        </div>

        <div class="pt-6">
          <button 
            type="submit" 
            :disabled="loading"
            class="w-full py-5 bg-white text-black rounded-2xl font-black text-lg transition-all hover:scale-[1.02] active:scale-95 shadow-xl flex items-center justify-center gap-3 disabled:opacity-50"
          >
            <Loader2 v-if="loading" class="w-6 h-6 animate-spin" />
            <CheckCircle2 v-else-if="success" class="w-6 h-6 text-emerald-600" />
            {{ success ? 'Profile Updated!' : 'Save Changes' }}
          </button>
        </div>
      </form>
    </div>

    <!-- Security Section -->
    <div class="bg-zinc-900 border border-zinc-800 rounded-[2.5rem] p-8 lg:p-12 shadow-2xl">
       <div class="flex items-center gap-4 mb-8">
          <div class="p-3 bg-amber-500/10 text-amber-500 rounded-2xl">
             <Shield class="w-6 h-6" />
          </div>
          <h3 class="text-xl font-black text-white uppercase tracking-tight">Security & Password</h3>
       </div>
       
       <button class="w-full py-4 border-2 border-zinc-800 hover:border-zinc-700 text-zinc-400 hover:text-white rounded-2xl font-bold transition-all text-sm uppercase tracking-widest">
          Change Password
       </button>
    </div>
  </div>
</template>
