<script setup lang="ts">
import { ref, onMounted, reactive } from 'vue';
import api from '../../services/api';
import { 
  Search, 
  Filter, 
  Loader2,
  Plus,
  X,
  Pencil,
  Trash2
} from 'lucide-vue-next';

const users = ref<any[]>([]);
const loading = ref(true);
const roleFilter = ref('');
const searchQuery = ref('');

// Modal State
const showModal = ref(false);
const modalMode = ref<'add' | 'edit'>('add');
const selectedUser = ref<any>(null);
const submitting = ref(false);

const form = reactive({
  name: '',
  email: '',
  password: '',
  role: 'customer'
});

const fetchUsers = async () => {
  loading.value = true;
  try {
    const response = await api.get('/admin/users', {
      params: { 
        role: roleFilter.value,
        search: searchQuery.value
      }
    });
    users.value = response.data.data;
  } catch (err) {
    console.error('Failed to fetch users', err);
  } finally {
    loading.value = false;
  }
};

const openAddModal = () => {
  modalMode.value = 'add';
  form.name = '';
  form.email = '';
  form.password = '';
  form.role = 'customer';
  showModal.value = true;
};

const openEditModal = (user: any) => {
  modalMode.value = 'edit';
  selectedUser.value = user;
  form.name = user.name;
  form.email = user.email;
  form.password = ''; // Don't show password
  form.role = user.roles[0]?.name || 'customer';
  showModal.value = true;
};

const handleSubmit = async () => {
  submitting.value = true;
  try {
    if (modalMode.value === 'add') {
      await api.post('/admin/users', form);
    } else {
      await api.put(`/admin/users/${selectedUser.value.id}`, form);
    }
    showModal.value = false;
    fetchUsers();
  } catch (err) {
    console.error('Failed to save user', err);
    alert('Error saving user. Please check if email is unique.');
  } finally {
    submitting.value = false;
  }
};

const deleteUser = async (id: number) => {
  if (!confirm('Are you sure you want to delete this user?')) return;
  
  try {
    await api.delete(`/admin/users/${id}`);
    fetchUsers();
  } catch (err) {
    console.error('Failed to delete user', err);
  }
};

onMounted(fetchUsers);
</script>

<template>
  <div class="space-y-10 animate-in fade-in duration-700">
    <div class="flex items-end justify-between">
      <div>
        <h1 class="text-3xl font-bold text-white tracking-tight">User Management</h1>
        <p class="text-zinc-400 mt-1 font-medium">Manage and monitor all platform participants.</p>
      </div>
      <button @click="fetchUsers" class="p-3 bg-zinc-900 border border-zinc-800 rounded-xl text-zinc-400 hover:text-white transition-all shadow-lg shadow-zinc-950/50">
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
          @keyup.enter="fetchUsers"
          type="text" 
          placeholder="Search by name or email..." 
          class="w-full pl-12 pr-4 py-4 bg-zinc-900 border border-zinc-800 rounded-2xl text-white focus:outline-none focus:ring-2 focus:ring-blue-500 transition-all placeholder-zinc-600 shadow-lg shadow-zinc-950/50"
        />
      </div>
      <div class="flex gap-4">
        <select 
          v-model="roleFilter"
          @change="fetchUsers"
          class="bg-zinc-900 border border-zinc-800 rounded-2xl px-6 py-4 text-white focus:outline-none focus:ring-2 focus:ring-blue-500 transition-all font-bold tracking-tight shadow-lg shadow-zinc-950/50"
        >
          <option value="">All Roles</option>
          <option value="admin">Administrators</option>
          <option value="merchant">Merchants</option>
          <option value="rider">Riders</option>
          <option value="customer">Customers</option>
        </select>
        
        <button 
          @click="openAddModal"
          class="bg-blue-600 hover:bg-blue-700 text-white px-8 py-4 rounded-2xl font-black transition-all shadow-lg shadow-blue-600/20 active:scale-95 flex items-center gap-2"
        >
          <Plus class="w-5 h-5" /> Add User
        </button>
      </div>
    </div>

    <!-- Users Table -->
    <div class="bg-zinc-900 border border-zinc-800 rounded-[2.5rem] shadow-2xl shadow-zinc-950/50 overflow-hidden">
      <div class="overflow-x-auto">
        <table class="w-full text-left min-w-[800px]">
        <thead class="bg-zinc-800/50 text-zinc-500 text-[10px] font-black uppercase tracking-[0.2em]">
          <tr>
            <th class="px-8 py-6">Member Info</th>
            <th class="px-8 py-6">Role</th>
            <th class="px-8 py-6">Joined Date</th>
            <th class="px-8 py-6 text-right">Operations</th>
          </tr>
        </thead>
        <tbody class="divide-y divide-zinc-800 text-white">
          <tr v-if="loading && users.length === 0">
            <td colspan="4" class="px-8 py-20 text-center">
              <Loader2 class="w-12 h-12 text-blue-500 animate-spin mx-auto" />
            </td>
          </tr>
          <tr v-else-if="users.length === 0">
            <td colspan="4" class="px-8 py-20 text-center text-zinc-500 font-black uppercase tracking-widest">
              No platform members found.
            </td>
          </tr>
          <tr v-for="user in users" :key="user.id" class="hover:bg-zinc-800/30 transition-all group">
            <td class="px-8 py-6">
              <div class="flex items-center gap-4">
                <div class="w-12 h-12 bg-zinc-800 rounded-xl flex items-center justify-center text-zinc-400 font-black text-lg border border-zinc-700 shadow-lg group-hover:bg-blue-600 group-hover:text-white transition-all">
                  {{ user.name.charAt(0) }}
                </div>
                <div class="flex flex-col">
                  <span class="font-black text-white text-lg group-hover:text-blue-400 transition-colors">{{ user.name }}</span>
                  <span class="text-xs text-zinc-500 font-bold mt-0.5 tracking-tight">{{ user.email }}</span>
                </div>
              </div>
            </td>
            <td class="px-8 py-6">
               <span :class="[
                'px-3 py-1.5 rounded-xl text-[10px] font-black uppercase tracking-widest w-fit border',
                user.roles[0]?.name === 'admin' ? 'bg-purple-500/10 text-purple-500 border-purple-500/20' : 
                user.roles[0]?.name === 'merchant' ? 'bg-blue-500/10 text-blue-500 border-blue-500/20' : 
                user.roles[0]?.name === 'rider' ? 'bg-amber-500/10 text-amber-500 border-amber-500/20' : 
                'bg-emerald-500/10 text-emerald-500 border-emerald-500/20'
               ]">
                {{ user.roles[0]?.name }}
               </span>
            </td>
            <td class="px-8 py-6 text-sm text-zinc-500 font-bold">
               {{ new Date(user.created_at).toLocaleDateString() }}
            </td>
            <td class="px-8 py-6">
              <div class="flex items-center justify-end gap-3 opacity-0 group-hover:opacity-100 transition-all">
                <button @click="openEditModal(user)" class="p-2 bg-zinc-800 text-zinc-400 hover:text-white hover:bg-zinc-700 rounded-xl transition-all shadow-xl">
                  <Pencil class="w-5 h-5" />
                </button>
                <button @click="deleteUser(user.id)" class="p-2 bg-zinc-800 text-zinc-400 hover:text-red-500 hover:bg-red-500/10 rounded-xl transition-all shadow-xl">
                  <Trash2 class="w-5 h-5" />
                </button>
              </div>
            </td>
          </tr>
        </tbody>
      </table>
    </div>
  </div>

    <!-- Add/Edit Modal -->
    <div v-if="showModal" class="fixed inset-0 z-50 flex items-center justify-center p-4">
      <div class="absolute inset-0 bg-zinc-950/80 backdrop-blur-sm" @click="showModal = false"></div>
      <div class="relative w-full max-w-md bg-zinc-900 border border-zinc-800 rounded-3xl p-8 shadow-2xl animate-in zoom-in-95 duration-300">
        <div class="flex items-center justify-between mb-8">
          <h3 class="text-2xl font-black text-white uppercase tracking-tight">
            {{ modalMode === 'add' ? 'Add New User' : 'Edit User' }}
          </h3>
          <button @click="showModal = false" class="p-2 text-zinc-500 hover:text-white transition-colors">
            <X class="w-6 h-6" />
          </button>
        </div>

        <form @submit.prevent="handleSubmit" class="space-y-6">
          <div class="space-y-2">
            <label class="text-xs font-black text-zinc-500 uppercase tracking-widest">Full Name</label>
            <input 
              v-model="form.name"
              type="text" 
              required
              class="w-full p-4 bg-zinc-950 border border-zinc-800 rounded-2xl text-white focus:outline-none focus:ring-2 focus:ring-blue-500 transition-all"
            />
          </div>

          <div class="space-y-2">
            <label class="text-xs font-black text-zinc-500 uppercase tracking-widest">Email Address</label>
            <input 
              v-model="form.email"
              type="email" 
              required
              class="w-full p-4 bg-zinc-950 border border-zinc-800 rounded-2xl text-white focus:outline-none focus:ring-2 focus:ring-blue-500 transition-all"
            />
          </div>

          <div class="space-y-2">
            <label class="text-xs font-black text-zinc-500 uppercase tracking-widest">
              {{ modalMode === 'add' ? 'Password' : 'New Password (Leave blank to keep current)' }}
            </label>
            <input 
              v-model="form.password"
              type="password" 
              :required="modalMode === 'add'"
              class="w-full p-4 bg-zinc-950 border border-zinc-800 rounded-2xl text-white focus:outline-none focus:ring-2 focus:ring-blue-500 transition-all"
            />
          </div>

          <div class="space-y-2">
            <label class="text-xs font-black text-zinc-500 uppercase tracking-widest">Role</label>
            <select 
              v-model="form.role"
              class="w-full p-4 bg-zinc-950 border border-zinc-800 rounded-2xl text-white focus:outline-none focus:ring-2 focus:ring-blue-500 transition-all"
            >
              <option value="admin">Admin</option>
              <option value="merchant">Merchant</option>
              <option value="rider">Rider</option>
              <option value="customer">Customer</option>
            </select>
          </div>

          <button 
            type="submit"
            :disabled="submitting"
            class="w-full py-4 bg-blue-600 hover:bg-blue-700 text-white rounded-2xl font-black transition-all shadow-lg shadow-blue-600/20 flex items-center justify-center gap-2"
          >
            <Loader2 v-if="submitting" class="w-5 h-5 animate-spin" />
            {{ modalMode === 'add' ? 'Create User' : 'Save Changes' }}
          </button>
        </form>
      </div>
    </div>
  </div>
</template>
