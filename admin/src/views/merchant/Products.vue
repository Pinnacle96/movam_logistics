<script setup lang="ts">
import { ref, onMounted, reactive, computed } from 'vue';
import api from '../../services/api';
import { 
  Plus, 
  Search, 
  Edit, 
  Trash2, 
  Image as ImageIcon,
  Loader2,
  X,
  Upload,
  Filter
} from 'lucide-vue-next';

const products = ref<any[]>([]);
const categories = ref<any[]>([]);
const loading = ref(true);
const submitting = ref(false);
const searchQuery = ref('');
const selectedCategoryId = ref('');

// Modal State
const showModal = ref(false);
const modalMode = ref<'add' | 'edit'>('add');
const selectedProduct = ref<any>(null);

const form = reactive({
  name: '',
  description: '',
  price: 0,
  category_id: '',
  stock: 0,
  is_available: true,
  image: null as File | null
});

const fetchProducts = async () => {
  loading.value = true;
  try {
    const response = await api.get('/products', {
      params: { 
        search: searchQuery.value,
        category_id: selectedCategoryId.value
      }
    });
    products.value = response.data;
  } catch (err) {
    console.error('Error fetching products:', err);
  } finally {
    loading.value = false;
  }
};

const fetchCategories = async () => {
  try {
    const response = await api.get('/products/categories');
    categories.value = response.data;
  } catch (err) {
    console.error('Error fetching categories:', err);
  }
};

const openAddModal = () => {
  modalMode.value = 'add';
  form.name = '';
  form.description = '';
  form.price = 0;
  form.category_id = categories.value[0]?.id || '';
  form.stock = 100;
  form.is_available = true;
  form.image = null;
  showModal.value = true;
};

const openEditModal = (product: any) => {
  modalMode.value = 'edit';
  selectedProduct.value = product;
  form.name = product.name;
  form.description = product.description;
  form.price = product.price;
  form.category_id = product.category_id;
  form.stock = product.stock;
  form.is_available = !!product.is_available;
  form.image = null;
  showModal.value = true;
};

const handleImageUpload = (event: any) => {
  const file = event.target.files[0];
  if (file) {
    form.image = file;
  }
};

const handleSubmit = async () => {
  submitting.value = true;
  const formData = new FormData();
  formData.append('name', form.name);
  formData.append('description', form.description);
  formData.append('price', form.price.toString());
  formData.append('category_id', form.category_id.toString());
  formData.append('stock', form.stock.toString());
  formData.append('is_available', form.is_available ? '1' : '0');
  
  if (form.image) {
    formData.append('image', form.image);
  }

  try {
    if (modalMode.value === 'add') {
      await api.post('/products', formData, {
        headers: { 'Content-Type': 'multipart/form-data' }
      });
    } else {
      // Laravel PATCH/PUT doesn't handle FormData well, so we use POST with _method spoofing
      formData.append('_method', 'PUT');
      await api.post(`/products/${selectedProduct.value.id}`, formData, {
        headers: { 'Content-Type': 'multipart/form-data' }
      });
    }
    showModal.value = false;
    fetchProducts();
  } catch (err) {
    console.error('Failed to save product', err);
    alert('Error saving product. Please try again.');
  } finally {
    submitting.value = false;
  }
};

const deleteProduct = async (id: number) => {
  if (!confirm('Are you sure you want to delete this product?')) return;
  try {
    await api.delete(`/products/${id}`);
    fetchProducts();
  } catch (err) {
    console.error('Failed to delete product', err);
  }
};

const toggleAvailability = async (product: any) => {
  try {
    await api.patch(`/products/${product.id}/toggle-availability`);
    product.is_available = !product.is_available;
  } catch (err) {
    console.error('Failed to toggle availability', err);
  }
};

const formatCurrency = (amount: number) => {
  return new Intl.NumberFormat('en-NG', { style: 'currency', currency: 'NGN' }).format(amount);
};

onMounted(() => {
  fetchProducts();
  fetchCategories();
});
</script>

<template>
  <div class="space-y-8 animate-in fade-in duration-700">
    <div class="flex flex-col lg:flex-row items-start lg:items-center justify-between gap-4">
      <div>
        <h1 class="text-3xl font-bold text-white tracking-tight">Product Catalog</h1>
        <p class="text-zinc-400 mt-1 font-medium">Manage your menu items, prices and availability.</p>
      </div>
      <button 
        @click="openAddModal"
        class="w-full lg:w-auto bg-emerald-600 hover:bg-emerald-700 text-white px-8 py-4 rounded-2xl font-black transition-all shadow-lg shadow-emerald-600/20 flex items-center justify-center gap-2 active:scale-95"
      >
        <Plus class="w-6 h-6" />
        Add New Product
      </button>
    </div>

    <!-- Filters Bar -->
    <div class="flex flex-col md:flex-row gap-6">
      <div class="relative flex-1">
        <Search class="absolute left-4 top-1/2 -translate-y-1/2 w-5 h-5 text-zinc-500" />
        <input 
          v-model="searchQuery"
          @keyup.enter="fetchProducts"
          type="text" 
          placeholder="Search products by name..." 
          class="w-full pl-12 pr-4 py-4 bg-zinc-900 border border-zinc-800 rounded-2xl text-white focus:outline-none focus:ring-2 focus:ring-emerald-500 transition-all placeholder-zinc-600 shadow-lg shadow-zinc-950/50"
        />
      </div>
      <div class="flex gap-4">
        <select 
          v-model="selectedCategoryId"
          @change="fetchProducts"
          class="bg-zinc-900 border border-zinc-800 rounded-2xl px-6 py-4 text-white focus:outline-none focus:ring-2 focus:ring-emerald-500 transition-all font-bold tracking-tight shadow-lg shadow-zinc-950/50"
        >
          <option value="">All Categories</option>
          <option v-for="cat in categories" :key="cat.id" :value="cat.id">{{ cat.name }}</option>
        </select>
        
        <button @click="fetchProducts" class="p-4 bg-zinc-900 border border-zinc-800 rounded-2xl text-zinc-400 hover:text-white transition-all shadow-lg">
           <Filter class="w-6 h-6" />
        </button>
      </div>
    </div>

    <!-- Loading State -->
    <div v-if="loading && products.length === 0" class="flex justify-center py-20">
      <Loader2 class="w-12 h-12 text-emerald-500 animate-spin" />
    </div>

    <!-- Empty State -->
    <div v-else-if="products.length === 0" class="bg-zinc-900 border border-zinc-800 rounded-[2.5rem] p-20 text-center shadow-sm">
      <div class="w-24 h-24 bg-zinc-800 rounded-full flex items-center justify-center mx-auto mb-6 text-zinc-500">
        <ImageIcon class="w-12 h-12" />
      </div>
      <h3 class="text-2xl font-bold text-white mb-2">No products found</h3>
      <p class="text-zinc-500 mb-8 max-w-sm mx-auto">Start building your menu by adding your first product.</p>
      <button @click="openAddModal" class="bg-emerald-600 text-white px-8 py-3 rounded-xl font-bold">Add Product</button>
    </div>

    <!-- Product Grid -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-8">
      <div 
        v-for="product in products" 
        :key="product.id" 
        class="bg-zinc-900 border border-zinc-800 rounded-[2rem] overflow-hidden group hover:border-emerald-500/50 transition-all shadow-2xl shadow-zinc-950/50"
      >
        <div class="aspect-video bg-zinc-950 relative overflow-hidden">
          <img 
            v-if="product.image_url" 
            :src="product.image_url" 
            class="w-full h-full object-cover group-hover:scale-110 transition-transform duration-700" 
          />
          <div v-else class="w-full h-full flex items-center justify-center text-zinc-800">
             <ImageIcon class="w-12 h-12" />
          </div>
          
          <div class="absolute top-4 right-4 flex gap-2">
            <button 
              @click="openEditModal(product)"
              class="p-2.5 bg-zinc-900/90 backdrop-blur-xl border border-zinc-800 rounded-xl text-zinc-400 hover:text-white hover:border-emerald-500/30 transition-all shadow-xl"
            >
              <Edit class="w-4 h-4" />
            </button>
            <button 
              @click="deleteProduct(product.id)"
              class="p-2.5 bg-zinc-900/90 backdrop-blur-xl border border-zinc-800 rounded-xl text-zinc-400 hover:text-red-500 hover:border-red-500/30 transition-all shadow-xl"
            >
              <Trash2 class="w-4 h-4" />
            </button>
          </div>
          
          <div v-if="!product.is_available" class="absolute inset-0 bg-zinc-950/60 backdrop-blur-[2px] flex items-center justify-center">
            <span class="bg-red-500 text-white text-[10px] font-black uppercase tracking-[0.2em] px-4 py-1.5 rounded-full shadow-lg shadow-red-500/20">Out of Stock</span>
          </div>
        </div>
        
        <div class="p-6">
          <div class="flex justify-between items-start mb-4">
            <div>
              <span class="text-[10px] font-black text-emerald-500 bg-emerald-500/10 border border-emerald-500/20 px-3 py-1 rounded-lg uppercase tracking-widest mb-2 inline-block">
                {{ product.category?.name }}
              </span>
              <h3 class="text-xl font-black text-white group-hover:text-emerald-400 transition-colors">{{ product.name }}</h3>
            </div>
          </div>
          
          <div class="flex items-center justify-between mb-6">
             <p class="text-2xl font-black text-white">{{ formatCurrency(product.price) }}</p>
             <div class="text-right">
                <p class="text-[10px] text-zinc-500 font-black uppercase tracking-widest">Inventory</p>
                <p :class="['font-bold', product.stock < 10 ? 'text-amber-500' : 'text-zinc-300']">{{ product.stock }} units</p>
             </div>
          </div>
          
          <div class="flex items-center justify-between pt-6 border-t border-zinc-800/50">
            <div class="flex items-center gap-3">
              <button 
                @click="toggleAvailability(product)"
                :class="[
                  'w-12 h-6 rounded-full transition-all flex items-center p-1',
                  product.is_available ? 'bg-emerald-600' : 'bg-zinc-800'
                ]"
              >
                <div :class="['w-4 h-4 bg-white rounded-full transition-all shadow-md', product.is_available ? 'translate-x-6' : 'translate-x-0']"></div>
              </button>
              <span class="text-xs font-black uppercase tracking-widest text-zinc-500">
                {{ product.is_available ? 'Active' : 'Hidden' }}
              </span>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Add/Edit Modal -->
    <div v-if="showModal" class="fixed inset-0 z-50 flex items-center justify-center p-4">
      <div class="absolute inset-0 bg-zinc-950/80 backdrop-blur-sm" @click="showModal = false"></div>
      <div class="relative w-full max-w-2xl bg-zinc-900 border border-zinc-800 rounded-[2.5rem] p-8 lg:p-12 shadow-2xl animate-in zoom-in-95 duration-300 max-h-[90vh] overflow-y-auto">
        <div class="flex items-center justify-between mb-10">
          <h3 class="text-3xl font-black text-white uppercase tracking-tight">
            {{ modalMode === 'add' ? 'Create Product' : 'Edit Item' }}
          </h3>
          <button @click="showModal = false" class="p-3 text-zinc-500 hover:text-white transition-colors bg-zinc-950 rounded-2xl border border-zinc-800">
            <X class="w-6 h-6" />
          </button>
        </div>

        <form @submit.prevent="handleSubmit" class="space-y-8">
          <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
             <div class="space-y-6">
                <div class="space-y-2">
                  <label class="text-xs font-black text-zinc-500 uppercase tracking-widest">Product Name</label>
                  <input 
                    v-model="form.name"
                    type="text" 
                    required
                    class="w-full p-4 bg-zinc-950 border border-zinc-800 rounded-2xl text-white focus:outline-none focus:ring-2 focus:ring-emerald-500 transition-all"
                  />
                </div>

                <div class="space-y-2">
                  <label class="text-xs font-black text-zinc-500 uppercase tracking-widest">Category</label>
                  <select 
                    v-model="form.category_id"
                    required
                    class="w-full p-4 bg-zinc-950 border border-zinc-800 rounded-2xl text-white focus:outline-none focus:ring-2 focus:ring-emerald-500 transition-all"
                  >
                    <option v-for="cat in categories" :key="cat.id" :value="cat.id">{{ cat.name }}</option>
                  </select>
                </div>

                <div class="grid grid-cols-2 gap-4">
                   <div class="space-y-2">
                     <label class="text-xs font-black text-zinc-500 uppercase tracking-widest">Price (₦)</label>
                     <input 
                       v-model="form.price"
                       type="number" 
                       required
                       class="w-full p-4 bg-zinc-950 border border-zinc-800 rounded-2xl text-white focus:outline-none focus:ring-2 focus:ring-emerald-500 transition-all"
                     />
                   </div>
                   <div class="space-y-2">
                     <label class="text-xs font-black text-zinc-500 uppercase tracking-widest">Stock Level</label>
                     <input 
                       v-model="form.stock"
                       type="number" 
                       required
                       class="w-full p-4 bg-zinc-950 border border-zinc-800 rounded-2xl text-white focus:outline-none focus:ring-2 focus:ring-emerald-500 transition-all"
                     />
                   </div>
                </div>
             </div>

             <div class="space-y-6">
                <div class="space-y-2">
                  <label class="text-xs font-black text-zinc-500 uppercase tracking-widest">Product Image</label>
                  <div class="relative group">
                     <input 
                      type="file" 
                      @change="handleImageUpload"
                      accept="image/*"
                      class="absolute inset-0 w-full h-full opacity-0 cursor-pointer z-10"
                     />
                     <div class="w-full aspect-square bg-zinc-950 border-2 border-dashed border-zinc-800 rounded-3xl flex flex-col items-center justify-center gap-4 group-hover:border-emerald-500/50 transition-all overflow-hidden">
                        <img v-if="form.image" :src="URL.createObjectURL(form.image)" class="w-full h-full object-cover" />
                        <div v-else class="text-center p-6">
                           <Upload class="w-10 h-10 text-zinc-700 mx-auto mb-2" />
                           <p class="text-zinc-500 text-xs font-bold uppercase tracking-widest">Click to Upload</p>
                           <p class="text-zinc-700 text-[10px] mt-1">PNG, JPG up to 2MB</p>
                        </div>
                     </div>
                  </div>
                </div>
             </div>
          </div>

          <div class="space-y-2">
            <label class="text-xs font-black text-zinc-500 uppercase tracking-widest">Description</label>
            <textarea 
              v-model="form.description"
              rows="3"
              class="w-full p-4 bg-zinc-950 border border-zinc-800 rounded-2xl text-white focus:outline-none focus:ring-2 focus:ring-emerald-500 transition-all"
            ></textarea>
          </div>

          <div class="flex items-center gap-4 p-4 bg-zinc-950 rounded-2xl border border-zinc-800">
             <button 
              type="button"
              @click="form.is_available = !form.is_available"
              :class="['w-14 h-8 rounded-full transition-all flex items-center p-1', form.is_available ? 'bg-emerald-600' : 'bg-zinc-800']"
             >
                <div :class="['w-6 h-6 bg-white rounded-full transition-all shadow-lg', form.is_available ? 'translate-x-6' : 'translate-x-0']"></div>
             </button>
             <div>
                <p class="text-white font-bold text-sm">Active & Visible</p>
                <p class="text-zinc-500 text-[10px] font-black uppercase tracking-widest">Product will be visible to customers</p>
             </div>
          </div>

          <button 
            type="submit"
            :disabled="submitting"
            class="w-full py-5 bg-emerald-600 hover:bg-emerald-700 text-white rounded-2xl font-black text-lg transition-all shadow-xl shadow-emerald-600/30 flex items-center justify-center gap-3 active:scale-95 disabled:opacity-50"
          >
            <Loader2 v-if="submitting" class="w-6 h-6 animate-spin" />
            {{ modalMode === 'add' ? 'Publish Product' : 'Save Changes' }}
          </button>
        </form>
      </div>
    </div>
  </div>
</template>
