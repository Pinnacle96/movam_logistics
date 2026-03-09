<script setup lang="ts">
import { ref, reactive, onMounted } from 'vue';
import { useRouter, useRoute } from 'vue-router';
import { useAuthStore } from '../stores/auth';
import { LogIn, Mail, Lock, Loader2 } from 'lucide-vue-next';

const router = useRouter();
const route = useRoute();
const authStore = useAuthStore();

const credentials = reactive({
  email: '',
  password: '',
});

const error = ref('');
const loading = ref(false);

const handleLogin = async () => {
  loading.value = true;
  error.value = '';
  try {
    await authStore.login(credentials);
    
    // Check for redirect query
    const redirectTo = route.query.redirect as string;
    if (redirectTo) {
      return router.push(redirectTo);
    }

    // Redirect based on role
    if (authStore.isAdmin) {
      router.push('/admin/dashboard');
    } else if (authStore.isMerchant) {
      router.push('/merchant/dashboard');
    } else if (authStore.isRider) {
      router.push('/rider/dashboard');
    } else {
      router.push('/');
    }
  } catch (err: any) {
    error.value = authStore.error || 'Invalid credentials';
  } finally {
    loading.value = false;
  }
};
</script>

<template>
  <div class="min-h-screen flex items-center justify-center bg-zinc-950 px-4">
    <div class="w-full max-w-md space-y-8 bg-zinc-900 p-8 rounded-2xl border border-zinc-800 shadow-2xl">
      <div class="text-center">
        <div class="inline-flex items-center justify-center w-16 h-16 rounded-xl bg-blue-600 mb-4 shadow-lg shadow-blue-500/20">
          <LogIn class="w-8 h-8 text-white" />
        </div>
        <h2 class="text-3xl font-bold tracking-tight text-white">Welcome Back</h2>
        <p class="mt-2 text-zinc-400">Sign in to your Movam account</p>
      </div>

      <form class="mt-8 space-y-6" @submit.prevent="handleLogin">
        <div class="space-y-4 rounded-md shadow-sm">
          <div class="relative">
            <label for="email" class="sr-only">Email address</label>
            <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
              <Mail class="h-5 w-5 text-zinc-500" />
            </div>
            <input
              id="email"
              v-model="credentials.email"
              type="email"
              required
              class="appearance-none block w-full pl-10 pr-3 py-3 border border-zinc-800 placeholder-zinc-500 text-white bg-zinc-950 rounded-xl focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent sm:text-sm transition-all"
              placeholder="Email address"
            />
          </div>
          <div class="relative">
            <label for="password" class="sr-only">Password</label>
            <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
              <Lock class="h-5 w-5 text-zinc-500" />
            </div>
            <input
              id="password"
              v-model="credentials.password"
              type="password"
              required
              class="appearance-none block w-full pl-10 pr-3 py-3 border border-zinc-800 placeholder-zinc-500 text-white bg-zinc-950 rounded-xl focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent sm:text-sm transition-all"
              placeholder="Password"
            />
          </div>
        </div>

        <div v-if="error" class="text-red-500 text-sm bg-red-500/10 p-3 rounded-lg border border-red-500/20">
          {{ error }}
        </div>

        <div>
          <button
            type="submit"
            :disabled="loading"
            class="group relative w-full flex justify-center py-3 px-4 border border-transparent text-sm font-semibold rounded-xl text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 disabled:opacity-50 disabled:cursor-not-allowed transition-all shadow-lg shadow-blue-500/25"
          >
            <span v-if="loading" class="flex items-center">
              <Loader2 class="animate-spin -ml-1 mr-2 h-4 w-4" />
              Signing in...
            </span>
            <span v-else>Sign in</span>
          </button>
        </div>
      </form>
      
      <div class="text-center text-sm text-zinc-500 space-y-2">
        <p>Don't have an account? <router-link to="/register" class="text-blue-500 font-bold hover:underline">Register here</router-link></p>
        <p>Admin: admin@movam.com / password</p>
      </div>
    </div>
  </div>
</template>
