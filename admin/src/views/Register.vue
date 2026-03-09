<script setup lang="ts">
import { ref, reactive } from 'vue';
import { useRouter } from 'vue-router';
import api from '../services/api';
import { 
  User, 
  Mail, 
  Lock, 
  Phone, 
  MapPin, 
  Bike, 
  Store, 
  ChevronRight, 
  ArrowLeft,
  Loader2,
  CheckCircle2
} from 'lucide-vue-next';

const router = useRouter();
const step = ref<'role' | 'details' | 'otp' | 'success'>('role');
const loading = ref(false);
const error = ref('');

const form = reactive({
  role: '' as 'customer' | 'merchant' | 'rider',
  name: '',
  email: '',
  password: '',
  password_confirmation: '',
  phone: '',
  address: '',
  business_name: '',
  vehicle_type: 'Motorcycle',
  vehicle_number: '',
  license_number: '',
  otp: ''
});

const selectRole = (role: 'customer' | 'merchant' | 'rider') => {
  form.role = role;
  step.value = 'details';
};

const handleSendOtp = async () => {
  loading.value = true;
  error.value = '';
  try {
    await api.post('/register/otp', { email: form.email });
    step.value = 'otp';
  } catch (err: any) {
    error.value = err.response?.data?.message || 'Failed to send OTP';
  } finally {
    loading.value = false;
  }
};

const handleRegister = async () => {
  loading.value = true;
  error.value = '';
  try {
    const endpoint = `/register/${form.role}`;
    const payload = { ...form };
    await api.post(endpoint, payload);
    step.value = 'success';
  } catch (err: any) {
    error.value = err.response?.data?.message || 'Registration failed';
  } finally {
    loading.value = false;
  }
};
</script>

<template>
  <div class="min-h-screen bg-black flex items-center justify-center p-6 relative overflow-hidden">
    <!-- Decorative background -->
    <div class="absolute top-0 left-0 w-full h-full opacity-20 pointer-events-none">
       <div class="absolute top-[-10%] left-[-10%] w-[40%] h-[40%] bg-blue-600 rounded-full blur-[120px]"></div>
       <div class="absolute bottom-[-10%] right-[-10%] w-[40%] h-[40%] bg-emerald-600 rounded-full blur-[120px]"></div>
    </div>

    <div class="w-full max-w-xl relative z-10">
      <!-- Step 1: Role Selection -->
      <div v-if="step === 'role'" class="space-y-8 animate-in fade-in zoom-in-95 duration-500">
        <div class="text-center">
          <h1 class="text-5xl font-black text-white uppercase tracking-tighter mb-4">Join Movam</h1>
          <p class="text-zinc-500 font-bold">Choose how you want to use the platform</p>
        </div>

        <div class="grid grid-cols-1 gap-4">
          <button @click="selectRole('customer')" class="group flex items-center justify-between p-8 bg-zinc-900/50 border border-zinc-800 rounded-[2.5rem] hover:border-blue-500/50 hover:bg-zinc-900 transition-all text-left">
            <div class="flex items-center gap-6">
              <div class="w-16 h-16 bg-blue-600/10 text-blue-500 rounded-2xl flex items-center justify-center group-hover:scale-110 transition-transform">
                <User class="w-8 h-8" />
              </div>
              <div>
                <h3 class="text-xl font-black text-white">Customer</h3>
                <p class="text-zinc-500 text-sm">Order food and track deliveries</p>
              </div>
            </div>
            <ChevronRight class="w-6 h-6 text-zinc-700 group-hover:text-blue-500 transition-colors" />
          </button>

          <button @click="selectRole('merchant')" class="group flex items-center justify-between p-8 bg-zinc-900/50 border border-zinc-800 rounded-[2.5rem] hover:border-emerald-500/50 hover:bg-zinc-900 transition-all text-left">
            <div class="flex items-center gap-6">
              <div class="w-16 h-16 bg-emerald-600/10 text-emerald-500 rounded-2xl flex items-center justify-center group-hover:scale-110 transition-transform">
                <Store class="w-8 h-8" />
              </div>
              <div>
                <h3 class="text-xl font-black text-white">Merchant</h3>
                <p class="text-zinc-500 text-sm">Grow your business and reach more customers</p>
              </div>
            </div>
            <ChevronRight class="w-6 h-6 text-zinc-700 group-hover:text-emerald-500 transition-colors" />
          </button>

          <button @click="selectRole('rider')" class="group flex items-center justify-between p-8 bg-zinc-900/50 border border-zinc-800 rounded-[2.5rem] hover:border-amber-500/50 hover:bg-zinc-900 transition-all text-left">
            <div class="flex items-center gap-6">
              <div class="w-16 h-16 bg-amber-600/10 text-amber-500 rounded-2xl flex items-center justify-center group-hover:scale-110 transition-transform">
                <Bike class="w-8 h-8" />
              </div>
              <div>
                <h3 class="text-xl font-black text-white">Rider</h3>
                <p class="text-zinc-500 text-sm">Earn money by delivering orders</p>
              </div>
            </div>
            <ChevronRight class="w-6 h-6 text-zinc-700 group-hover:text-amber-500 transition-colors" />
          </button>
        </div>

        <p class="text-center text-zinc-600 text-sm">
          Already have an account? 
          <router-link to="/login" class="text-blue-500 font-bold hover:underline">Login here</router-link>
        </p>
      </div>

      <!-- Step 2: Details -->
      <div v-else-if="step === 'details'" class="space-y-8 animate-in slide-in-from-right-8 duration-500">
        <button @click="step = 'role'" class="flex items-center gap-2 text-zinc-500 hover:text-white transition-colors font-bold uppercase text-xs tracking-widest">
           <ArrowLeft class="w-4 h-4" /> Back to roles
        </button>

        <div>
          <h2 class="text-4xl font-black text-white uppercase tracking-tighter">Create Account</h2>
          <p class="text-zinc-500 font-bold mt-1">Fill in your information to get started as a <span class="text-white capitalize">{{ form.role }}</span></p>
        </div>

        <form @submit.prevent="handleSendOtp" class="space-y-6">
          <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <div class="space-y-2">
              <label class="text-[10px] font-black text-zinc-500 uppercase tracking-widest">Full Name</label>
              <div class="relative">
                <User class="absolute left-4 top-1/2 -translate-y-1/2 w-5 h-5 text-zinc-600" />
                <input v-model="form.name" type="text" required placeholder="John Doe" class="w-full pl-12 pr-4 py-4 bg-zinc-900/50 border border-zinc-800 rounded-2xl text-white focus:outline-none focus:ring-2 focus:ring-blue-500 transition-all" />
              </div>
            </div>

            <div class="space-y-2">
              <label class="text-[10px] font-black text-zinc-500 uppercase tracking-widest">Email Address</label>
              <div class="relative">
                <Mail class="absolute left-4 top-1/2 -translate-y-1/2 w-5 h-5 text-zinc-600" />
                <input v-model="form.email" type="email" required placeholder="john@example.com" class="w-full pl-12 pr-4 py-4 bg-zinc-900/50 border border-zinc-800 rounded-2xl text-white focus:outline-none focus:ring-2 focus:ring-blue-500 transition-all" />
              </div>
            </div>
          </div>

          <!-- Merchant Specific Fields -->
          <template v-if="form.role === 'merchant'">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
              <div class="space-y-2">
                <label class="text-[10px] font-black text-zinc-500 uppercase tracking-widest">Business Name</label>
                <div class="relative">
                  <Store class="absolute left-4 top-1/2 -translate-y-1/2 w-5 h-5 text-zinc-600" />
                  <input v-model="form.business_name" type="text" required placeholder="My Awesome Store" class="w-full pl-12 pr-4 py-4 bg-zinc-900/50 border border-zinc-800 rounded-2xl text-white focus:outline-none focus:ring-2 focus:ring-blue-500 transition-all" />
                </div>
              </div>
              <div class="space-y-2">
                <label class="text-[10px] font-black text-zinc-500 uppercase tracking-widest">Phone Number</label>
                <div class="relative">
                  <Phone class="absolute left-4 top-1/2 -translate-y-1/2 w-5 h-5 text-zinc-600" />
                  <input v-model="form.phone" type="text" required placeholder="08012345678" class="w-full pl-12 pr-4 py-4 bg-zinc-900/50 border border-zinc-800 rounded-2xl text-white focus:outline-none focus:ring-2 focus:ring-blue-500 transition-all" />
                </div>
              </div>
            </div>
            <div class="space-y-2">
              <label class="text-[10px] font-black text-zinc-500 uppercase tracking-widest">Business Address</label>
              <div class="relative">
                <MapPin class="absolute left-4 top-1/2 -translate-y-1/2 w-5 h-5 text-zinc-600" />
                <input v-model="form.address" type="text" required placeholder="123 Store Street, Lagos" class="w-full pl-12 pr-4 py-4 bg-zinc-900/50 border border-zinc-800 rounded-2xl text-white focus:outline-none focus:ring-2 focus:ring-blue-500 transition-all" />
              </div>
            </div>
          </template>

          <!-- Rider Specific Fields -->
          <template v-if="form.role === 'rider'">
            <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
              <div class="space-y-2">
                <label class="text-[10px] font-black text-zinc-500 uppercase tracking-widest">Vehicle Type</label>
                <select v-model="form.vehicle_type" class="w-full p-4 bg-zinc-900/50 border border-zinc-800 rounded-2xl text-white focus:outline-none focus:ring-2 focus:ring-blue-500 transition-all">
                  <option value="Motorcycle">Motorcycle</option>
                  <option value="Bicycle">Bicycle</option>
                  <option value="Car">Car</option>
                </select>
              </div>
              <div class="space-y-2">
                <label class="text-[10px] font-black text-zinc-500 uppercase tracking-widest">Vehicle Plate #</label>
                <input v-model="form.vehicle_number" type="text" required placeholder="LAG-123-XY" class="w-full p-4 bg-zinc-900/50 border border-zinc-800 rounded-2xl text-white focus:outline-none focus:ring-2 focus:ring-blue-500 transition-all" />
              </div>
              <div class="space-y-2">
                <label class="text-[10px] font-black text-zinc-500 uppercase tracking-widest">License #</label>
                <input v-model="form.license_number" type="text" required placeholder="ABC-987654" class="w-full p-4 bg-zinc-900/50 border border-zinc-800 rounded-2xl text-white focus:outline-none focus:ring-2 focus:ring-blue-500 transition-all" />
              </div>
            </div>
          </template>

          <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <div class="space-y-2">
              <label class="text-[10px] font-black text-zinc-500 uppercase tracking-widest">Password</label>
              <div class="relative">
                <Lock class="absolute left-4 top-1/2 -translate-y-1/2 w-5 h-5 text-zinc-600" />
                <input v-model="form.password" type="password" required placeholder="••••••••" class="w-full pl-12 pr-4 py-4 bg-zinc-900/50 border border-zinc-800 rounded-2xl text-white focus:outline-none focus:ring-2 focus:ring-blue-500 transition-all" />
              </div>
            </div>
            <div class="space-y-2">
              <label class="text-[10px] font-black text-zinc-500 uppercase tracking-widest">Confirm Password</label>
              <div class="relative">
                <Lock class="absolute left-4 top-1/2 -translate-y-1/2 w-5 h-5 text-zinc-600" />
                <input v-model="form.password_confirmation" type="password" required placeholder="••••••••" class="w-full pl-12 pr-4 py-4 bg-zinc-900/50 border border-zinc-800 rounded-2xl text-white focus:outline-none focus:ring-2 focus:ring-blue-500 transition-all" />
              </div>
            </div>
          </div>

          <div v-if="error" class="p-4 bg-red-500/10 border border-red-500/20 rounded-2xl text-red-500 text-sm font-bold flex items-center gap-3">
             <AlertCircle class="w-5 h-5" /> {{ error }}
          </div>

          <button :disabled="loading" type="submit" class="w-full py-5 bg-white text-black rounded-2xl font-black text-lg transition-all hover:scale-[1.02] active:scale-95 shadow-xl flex items-center justify-center gap-3 disabled:opacity-50">
            <Loader2 v-if="loading" class="w-6 h-6 animate-spin" />
            Create Account
          </button>
        </form>
      </div>

      <!-- Step 3: OTP Verification -->
      <div v-else-if="step === 'otp'" class="space-y-8 animate-in zoom-in-95 duration-500 text-center">
        <div class="w-20 h-20 bg-blue-600/10 text-blue-500 rounded-3xl flex items-center justify-center mx-auto">
           <Mail class="w-10 h-10" />
        </div>
        
        <div>
           <h2 class="text-4xl font-black text-white uppercase tracking-tighter">Verify Your Email</h2>
           <p class="text-zinc-500 font-bold mt-2">We've sent a 6-digit code to <span class="text-white">{{ form.email }}</span></p>
        </div>

        <form @submit.prevent="handleRegister" class="space-y-8">
           <input 
             v-model="form.otp"
             type="text" 
             required
             maxlength="6"
             placeholder="000000"
             class="w-full p-6 bg-zinc-900/50 border border-zinc-800 rounded-[2rem] text-white focus:outline-none focus:ring-2 focus:ring-blue-500 transition-all text-center text-5xl font-black tracking-[0.5em] placeholder-zinc-800"
           />

           <div v-if="error" class="p-4 bg-red-500/10 border border-red-500/20 rounded-2xl text-red-500 text-sm font-bold">
             {{ error }}
           </div>

           <div class="space-y-4">
              <button :disabled="loading" type="submit" class="w-full py-5 bg-blue-600 text-white rounded-2xl font-black text-lg transition-all hover:scale-[1.02] active:scale-95 shadow-xl shadow-blue-600/20 flex items-center justify-center gap-3 disabled:opacity-50">
                <Loader2 v-if="loading" class="w-6 h-6 animate-spin" />
                Complete Registration
              </button>
              
              <button @click="handleSendOtp" type="button" class="text-zinc-500 font-bold text-xs uppercase tracking-widest hover:text-white transition-all">
                 Resend Code
              </button>
           </div>
        </form>
      </div>

      <!-- Step 4: Success -->
      <div v-else-if="step === 'success'" class="space-y-8 animate-in fade-in duration-500 text-center">
        <div class="w-24 h-24 bg-emerald-500/10 text-emerald-500 rounded-full flex items-center justify-center mx-auto">
           <CheckCircle2 class="w-12 h-12" />
        </div>

        <div>
           <h2 class="text-4xl font-black text-white uppercase tracking-tighter">Welcome to Movam!</h2>
           <p class="text-zinc-500 font-bold mt-2">Your account has been successfully created. You can now login to your dashboard.</p>
        </div>

        <button @click="router.push('/login')" class="w-full py-5 bg-white text-black rounded-2xl font-black text-lg transition-all hover:scale-[1.02] active:scale-95 shadow-xl">
           Login to Dashboard
        </button>
      </div>
    </div>
  </div>
</template>

<style scoped>
input::placeholder {
  color: #27272a;
}
</style>
