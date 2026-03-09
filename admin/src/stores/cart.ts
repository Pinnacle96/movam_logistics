import { defineStore } from 'pinia';

interface CartItem {
  id: number;
  name: string;
  price: number;
  quantity: number;
  merchant_id: number;
}

export const useCartStore = defineStore('cart', {
  state: () => ({
    items: JSON.parse(localStorage.getItem('cart_items') || '[]') as CartItem[],
    merchantId: JSON.parse(localStorage.getItem('cart_merchant_id') || 'null') as number | null,
  }),

  getters: {
    totalItems: (state) => state.items.reduce((acc, item) => acc + item.quantity, 0),
    totalAmount: (state) => state.items.reduce((acc, item) => acc + (item.price * item.quantity), 0),
    isEmpty: (state) => state.items.length === 0,
  },

  actions: {
    addItem(product: any) {
      if (this.merchantId && this.merchantId !== product.merchant_id) {
        if (!confirm('Adding items from a different merchant will clear your current cart. Continue?')) {
          return;
        }
        this.clearCart();
      }

      this.merchantId = product.merchant_id;
      const existingItem = this.items.find(item => item.id === product.id);

      if (existingItem) {
        existingItem.quantity++;
      } else {
        this.items.push({
          id: product.id,
          name: product.name,
          price: product.price,
          quantity: 1,
          merchant_id: product.merchant_id
        });
      }

      this.saveToStorage();
    },

    removeItem(productId: number) {
      const index = this.items.findIndex(item => item.id === productId);
      if (index > -1) {
        if (this.items[index].quantity > 1) {
          this.items[index].quantity--;
        } else {
          this.items.splice(index, 1);
        }
      }

      if (this.items.length === 0) {
        this.merchantId = null;
      }

      this.saveToStorage();
    },

    clearCart() {
      this.items = [];
      this.merchantId = null;
      this.saveToStorage();
    },

    saveToStorage() {
      localStorage.setItem('cart_items', JSON.stringify(this.items));
      localStorage.setItem('cart_merchant_id', JSON.stringify(this.merchantId));
    }
  }
});
