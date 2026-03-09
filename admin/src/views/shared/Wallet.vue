<script setup lang="ts">
import { ref, onMounted, reactive, computed } from 'vue';
import api from '../../services/api';
import { useAuthStore } from '../../stores/auth';
import { 
  Wallet, 
  ArrowUpRight, 
  ArrowDownLeft, 
  Clock, 
  CheckCircle2, 
  XCircle,
  Loader2,
  Plus,
  CreditCard,
  Building2,
  History,
  X,
  AlertCircle
} from 'lucide-vue-next';

const authStore = useAuthStore();
const data = ref<any>(null);
const loading = ref(true);
const processing = ref(false);
const banks = ref<any[]>([]);

// Modals
const showWithdrawModal = ref(false);
const showAddFundsModal = ref(false);
const withdrawStep = ref<'details' | 'otp'>('details');

const withdrawForm = reactive({
  amount: 0,
  bank_code: '',
  account_number: '',
  account_name: '',
  otp: ''
});

const addFundsForm = reactive({
  amount: 0
});

const isCustomer = computed(() => authStore.user?.roles?.some((r: any) => r.name === 'customer'));

const fetchWalletData = async () => {
  loading.value = true;
  try {
    const response = await api.get('/wallet');
    data.value = response.data;
  } catch (err) {
    console.error('Failed to fetch wallet data', err);
  } finally {
    loading.value = false;
  }
};

const fetchBanks = async () => {
  try {
    const response = await api.get('/banks');
    banks.value = response.data;
  } catch (err) {
    console.error('Failed to fetch banks', err);
  }
};

const handleWithdrawRequest = async () => {
  processing.value = true;
  try {
    await api.post('/wallet/withdraw/request', {
      amount: withdrawForm.amount,
      bank_code: withdrawForm.bank_code,
      account_number: withdrawForm.account_number,
      account_name: withdrawForm.account_name
    });
    withdrawStep.value = 'otp';
  } catch (err: any) {
    alert(err.response?.data?.message || 'Withdrawal request failed');
  } finally {
    processing.value = false;
  }
};

const handleWithdrawConfirm = async () => {
  processing.value = true;
  try {
    await api.post('/wallet/withdraw/confirm', {
      otp: withdrawForm.otp,
      amount: withdrawForm.amount,
      bank_code: withdrawForm.bank_code,
      account_number: withdrawForm.account_number,
      account_name: withdrawForm.account_name
    });
    alert('Withdrawal initiated successfully!');
    showWithdrawModal.value = false;
    withdrawStep.value = 'details';
    await fetchWalletData();
    await authStore.fetchUser();
  } catch (err: any) {
    alert(err.response?.data?.message || 'Withdrawal confirmation failed');
  } finally {
    processing.value = false;
  }
};

const handleAddFunds = async () => {
  processing.value = true;
  try {
    const response = await api.post('/wallet/add-funds', { amount: addFundsForm.amount });
    
    // Paystack Inline
    const handler = (window as any).PaystackPop.setup({
      key: import.meta.env.VITE_PAYSTACK_PUBLIC_KEY,
      email: authStore.user?.email,
      amount: addFundsForm.amount * 100,
      currency: 'NGN',
      ref: response.data.reference,
      callback: async (res: any) => {
        await api.post('/wallet/verify-funds', { reference: res.reference });
        showAddFundsModal.value = false;
        await fetchWalletData();
        await authStore.fetchUser();
        alert('Wallet topped up successfully!');
      },
      onClose: () => {
        processing.value = false;
      }
    });
    handler.openIframe();
  } catch (err: any) {
    alert(err.response?.data?.message || 'Top-up initialization failed');
    processing.value = false;
  }
};

const formatCurrency = (amount: number) => {
  return new Intl.NumberFormat('en-NG', { style: 'currency', currency: 'NGN' }).format(Math.abs(amount));
};

onMounted(() => {
  fetchWalletData();
  fetchBanks();
});
</script>

<template>
  <div v-if="loading" class="flex justify-center py-20">
    <Loader2 class="w-12 h-12 text-blue-500 animate-spin" />
  </div>

  <div v-else-if="data" class="space-y-8 animate-in fade-in duration-700">
    <div class="flex flex-col md:flex-row justify-between items-start md:items-center gap-4">
      <div>
        <h1 class="text-3xl font-bold text-white tracking-tight">Financial Wallet</h1>
        <p class="text-zinc-400 mt-1 font-medium">Manage your earnings, balance and transactions.</p>
      </div>
      
      <div v-if="isCustomer" class="flex gap-4 w-full md:w-auto">
        <button 
          @click="showAddFundsModal = true"
          class="flex-1 md:flex-none bg-emerald-600 hover:bg-emerald-700 text-white px-8 py-4 rounded-2xl font-black transition-all shadow-lg shadow-emerald-600/20 flex items-center justify-center gap-2 active:scale-95"
        >
          <Plus class="w-6 h-6" />
          Add Funds
        </button>
      </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
      <!-- Balance Card -->
      <div class="lg:col-span-1">
        <div class="bg-gradient-to-br from-blue-600 to-indigo-700 rounded-[2.5rem] p-8 text-white shadow-2xl shadow-blue-500/20 relative overflow-hidden group">
          <div class="absolute -right-10 -bottom-10 w-40 h-40 bg-white/10 rounded-full blur-3xl group-hover:scale-150 transition-transform duration-700"></div>
          
          <div class="flex justify-between items-start mb-12">
            <div class="p-3 bg-white/20 backdrop-blur-md rounded-2xl">
              <Wallet class="w-8 h-8" />
            </div>
            <span class="px-3 py-1 bg-white/20 backdrop-blur-md rounded-full text-[10px] font-black uppercase tracking-widest">Active Balance</span>
          </div>

          <p class="text-blue-100 text-sm font-bold uppercase tracking-widest mb-2">Total Balance</p>
          <h2 class="text-5xl font-black mb-12">{{ formatCurrency(data.wallet.balance) }}</h2>

          <button 
            @click="showWithdrawModal = true"
            class="w-full py-4 bg-white text-blue-600 rounded-2xl font-black transition-all hover:scale-[1.02] active:scale-95 shadow-xl flex items-center justify-center gap-2"
          >
            <ArrowUpRight class="w-5 h-5" />
            Withdraw Funds
          </button>
        </div>
      </div>

      <!-- Recent Transactions -->
      <div class="lg:col-span-2 space-y-6">
        <div class="flex items-center justify-between">
           <h3 class="text-xl font-bold text-white flex items-center gap-2">
              <History class="w-5 h-5 text-zinc-500" />
              Recent Activity
           </h3>
        </div>

        <div class="bg-zinc-900 border border-zinc-800 rounded-[2rem] overflow-hidden shadow-2xl">
          <div class="divide-y divide-zinc-800">
            <div v-for="tx in data.transactions.data" :key="tx.id" class="p-6 flex items-center justify-between hover:bg-zinc-800/30 transition-all">
              <div class="flex items-center gap-4">
                <div :class="[
                  'w-12 h-12 rounded-xl flex items-center justify-center',
                  tx.amount > 0 ? 'bg-emerald-500/10 text-emerald-500' : 'bg-amber-500/10 text-amber-500'
                ]">
                  <ArrowUpRight v-if="tx.amount < 0" class="w-6 h-6" />
                  <ArrowDownLeft v-else class="w-6 h-6" />
                </div>
                <div>
                  <p class="text-white font-bold">{{ tx.description }}</p>
                  <p class="text-zinc-500 text-xs mt-0.5">{{ new Date(tx.created_at).toLocaleString() }}</p>
                </div>
              </div>
              
              <div class="text-right">
                <p :class="['text-lg font-black', tx.amount > 0 ? 'text-emerald-500' : 'text-white']">
                  {{ tx.amount > 0 ? '+' : '-' }}{{ formatCurrency(tx.amount) }}
                </p>
                <div class="flex items-center justify-end gap-1.5 mt-1">
                   <component 
                    :is="tx.status === 'completed' ? CheckCircle2 : tx.status === 'pending' ? Clock : XCircle" 
                    :class="['w-3 h-3', tx.status === 'completed' ? 'text-emerald-500' : tx.status === 'pending' ? 'text-amber-500' : 'text-red-500']"
                   />
                   <span :class="['text-[10px] font-black uppercase tracking-widest', tx.status === 'completed' ? 'text-emerald-500' : tx.status === 'pending' ? 'text-amber-500' : 'text-red-500']">
                      {{ tx.status }}
                   </span>
                </div>
              </div>
            </div>

            <div v-if="data.transactions.data.length === 0" class="p-20 text-center text-zinc-500 font-black uppercase tracking-widest text-sm">
               No transaction history found.
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Withdrawal Modal -->
    <div v-if="showWithdrawModal" class="fixed inset-0 z-50 flex items-center justify-center p-4">
      <div class="absolute inset-0 bg-zinc-950/80 backdrop-blur-sm" @click="showWithdrawModal = false"></div>
      <div class="relative w-full max-w-md bg-zinc-900 border border-zinc-800 rounded-[2.5rem] p-8 lg:p-10 shadow-2xl animate-in zoom-in-95 duration-300">
        <div class="flex items-center justify-between mb-8">
           <h3 class="text-2xl font-black text-white uppercase tracking-tight">Withdraw Funds</h3>
           <button @click="showWithdrawModal = false" class="text-zinc-500 hover:text-white"><X class="w-6 h-6" /></button>
        </div>
        
        <form v-if="withdrawStep === 'details'" @submit.prevent="handleWithdrawRequest" class="space-y-6">
          <div class="space-y-2">
            <label class="text-xs font-black text-zinc-500 uppercase tracking-widest">Amount to Withdraw (₦)</label>
            <input 
              v-model="withdrawForm.amount"
              type="number" 
              required
              min="1000"
              class="w-full p-4 bg-zinc-950 border border-zinc-800 rounded-2xl text-white focus:outline-none focus:ring-2 focus:ring-blue-500 transition-all text-xl font-black"
            />
          </div>

          <div class="space-y-2">
            <label class="text-xs font-black text-zinc-500 uppercase tracking-widest">Select Bank</label>
            <select 
              v-model="withdrawForm.bank_code"
              required
              class="w-full p-4 bg-zinc-950 border border-zinc-800 rounded-2xl text-white focus:outline-none focus:ring-2 focus:ring-blue-500 transition-all"
            >
              <option value="">Select Bank</option>
              <option v-for="bank in banks" :key="bank.code" :value="bank.code">{{ bank.name }}</option>
            </select>
          </div>

          <div class="grid grid-cols-1 gap-6">
             <div class="space-y-2">
               <label class="text-xs font-black text-zinc-500 uppercase tracking-widest">Account Number</label>
               <input 
                v-model="withdrawForm.account_number"
                type="text" 
                required
                maxlength="10"
                placeholder="10-digit NUBAN"
                class="w-full p-4 bg-zinc-950 border border-zinc-800 rounded-2xl text-white focus:outline-none focus:ring-2 focus:ring-blue-500 transition-all font-mono tracking-widest"
               />
             </div>
             <div class="space-y-2">
               <label class="text-xs font-black text-zinc-500 uppercase tracking-widest">Account Name</label>
               <input 
                v-model="withdrawForm.account_name"
                type="text" 
                required
                placeholder="Name on account"
                class="w-full p-4 bg-zinc-950 border border-zinc-800 rounded-2xl text-white focus:outline-none focus:ring-2 focus:ring-blue-500 transition-all"
               />
             </div>
          </div>

          <button 
            type="submit"
            :disabled="processing"
            class="w-full py-5 bg-blue-600 hover:bg-blue-700 text-white rounded-2xl font-black transition-all shadow-xl shadow-blue-600/30 flex items-center justify-center gap-2 disabled:opacity-50"
          >
            <Loader2 v-if="processing" class="w-5 h-5 animate-spin" />
            Continue
          </button>
        </form>

        <!-- OTP Step -->
        <form v-else @submit.prevent="handleWithdrawConfirm" class="space-y-6">
           <div class="text-center p-6 bg-blue-600/5 border border-blue-500/20 rounded-3xl">
              <AlertCircle class="w-12 h-12 text-blue-500 mx-auto mb-4" />
              <h4 class="text-white font-bold mb-2">Verify Withdrawal</h4>
              <p class="text-zinc-500 text-xs">Enter the 6-digit code sent to your email to authorize this withdrawal of {{ formatCurrency(withdrawForm.amount) }}.</p>
           </div>

           <div class="space-y-2">
              <label class="text-xs font-black text-zinc-500 uppercase tracking-widest text-center block">Verification Code</label>
              <input 
                v-model="withdrawForm.otp"
                type="text" 
                required
                maxlength="6"
                placeholder="000000"
                class="w-full p-5 bg-zinc-950 border border-zinc-800 rounded-2xl text-white focus:outline-none focus:ring-2 focus:ring-blue-500 transition-all text-center text-3xl font-black tracking-[0.5em]"
              />
           </div>

           <button 
            type="submit"
            :disabled="processing"
            class="w-full py-5 bg-emerald-600 hover:bg-emerald-700 text-white rounded-2xl font-black transition-all shadow-xl shadow-emerald-600/30 flex items-center justify-center gap-2 disabled:opacity-50"
          >
            <Loader2 v-if="processing" class="w-5 h-5 animate-spin" />
            Verify & Withdraw
          </button>
          
          <button @click="withdrawStep = 'details'" type="button" class="w-full text-zinc-500 font-bold text-xs uppercase tracking-widest hover:text-white transition-all">
             Back to Details
          </button>
        </form>
      </div>
    </div>

    <!-- Add Funds Modal -->
    <div v-if="showAddFundsModal" class="fixed inset-0 z-50 flex items-center justify-center p-4">
      <div class="absolute inset-0 bg-zinc-950/80 backdrop-blur-sm" @click="showAddFundsModal = false"></div>
      <div class="relative w-full max-w-md bg-zinc-900 border border-zinc-800 rounded-[2.5rem] p-8 lg:p-10 shadow-2xl animate-in zoom-in-95 duration-300">
        <div class="flex items-center justify-between mb-8">
           <h3 class="text-2xl font-black text-white uppercase tracking-tight">Top Up Wallet</h3>
           <button @click="showAddFundsModal = false" class="text-zinc-500 hover:text-white"><X class="w-6 h-6" /></button>
        </div>
        
        <form @submit.prevent="handleAddFunds" class="space-y-6">
           <div class="space-y-2">
              <label class="text-xs font-black text-zinc-500 uppercase tracking-widest">Amount to Top Up (₦)</label>
              <input 
                v-model="addFundsForm.amount"
                type="number" 
                required
                min="100"
                class="w-full p-4 bg-zinc-950 border border-zinc-800 rounded-2xl text-white focus:outline-none focus:ring-2 focus:ring-emerald-500 transition-all text-xl font-black"
              />
           </div>

           <div class="p-6 bg-emerald-600/5 border border-emerald-500/20 rounded-3xl">
              <p class="text-zinc-400 text-xs leading-relaxed">
                 You will be redirected to Paystack's secure checkout to complete your transaction. Funds will be added instantly to your wallet.
              </p>
           </div>

           <button 
            type="submit"
            :disabled="processing"
            class="w-full py-5 bg-emerald-600 hover:bg-emerald-700 text-white rounded-2xl font-black transition-all shadow-xl shadow-emerald-600/30 flex items-center justify-center gap-2 disabled:opacity-50"
          >
            <Loader2 v-if="processing" class="w-5 h-5 animate-spin" />
            Proceed to Payment
          </button>
        </form>
      </div>
    </div>
  </div>
</template>
