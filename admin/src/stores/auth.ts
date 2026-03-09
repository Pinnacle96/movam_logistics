import { defineStore } from 'pinia';
import api from '../services/api';
import type { User, LoginCredentials, AuthResponse } from '../types/auth';

export const useAuthStore = defineStore('auth', {
  state: () => ({
    user: JSON.parse(localStorage.getItem('user') || 'null') as User | null,
    token: localStorage.getItem('auth_token'),
    loading: false,
    error: null as string | null,
  }),

  getters: {
    isAuthenticated: (state) => !!state.token,
    isAdmin: (state) => state.user?.roles?.some(r => r.name === 'admin') || false,
    isMerchant: (state) => state.user?.roles?.some(r => r.name === 'merchant') || false,
    isRider: (state) => state.user?.roles?.some(r => r.name === 'rider') || false,
  },

  actions: {
    async login(credentials: LoginCredentials) {
      this.loading = true;
      this.error = null;
      try {
        const response = await api.post<AuthResponse>('/login', credentials);
        this.user = response.data.user;
        this.token = response.data.access_token;
        
        localStorage.setItem('auth_token', this.token);
        localStorage.setItem('user', JSON.stringify(this.user));
        
        return response.data;
      } catch (err: any) {
        this.error = err.response?.data?.message || 'Login failed';
        throw err;
      } finally {
        this.loading = false;
      }
    },

    async logout() {
      try {
        await api.post('/logout');
      } catch (err) {
        console.error('Logout error:', err);
      } finally {
        this.user = null;
        this.token = null;
        localStorage.removeItem('auth_token');
        localStorage.removeItem('user');
      }
    },

    async fetchUser() {
      try {
        const response = await api.get('/user');
        this.user = response.data;
        localStorage.setItem('user', JSON.stringify(this.user));
      } catch (err) {
        this.logout();
      }
    }
  },
});
