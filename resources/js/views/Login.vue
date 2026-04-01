<template>
  <div class="m-auto w-full max-w-md bg-white rounded-xl shadow-lg p-8">
    <h2 class="text-3xl font-bold text-center text-gray-800 mb-8">Connexion</h2>
    
    <form @submit.prevent="login" class="space-y-6">
      <div>
        <label class="block text-sm font-medium text-gray-700">Email</label>
        <input 
          v-model="form.email"
          type="email" 
          required 
          class="mt-1 w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 outline-none transition"
        />
      </div>
      
      <div>
        <label class="block text-sm font-medium text-gray-700">Mot de passe</label>
        <input 
          v-model="form.password"
          type="password" 
          required 
          class="mt-1 w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 outline-none transition"
        />
      </div>
      
      <p v-if="error" class="text-sm text-red-500 text-center">{{ error }}</p>

      <button 
        type="submit" 
        :disabled="loading"
        class="w-full bg-indigo-600 hover:bg-indigo-700 text-white font-semibold py-3 rounded-lg transition"
      >
        <span v-if="loading">Connexion...</span>
        <span v-else>Se connecter</span>
      </button>
    </form>
    
    <div class="mt-6 text-center text-sm text-gray-600">
      Pas encore de compte ? 
      <router-link to="/register" class="text-indigo-600 font-semibold hover:underline">S'inscrire</router-link>
    </div>
  </div>
</template>

<script setup>
import { ref } from 'vue';
import { useRouter } from 'vue-router';
import axios from 'axios';

const router = useRouter();
const form = ref({ email: '', password: '' });
const error = ref('');
const loading = ref(false);

const login = async () => {
  error.value = '';
  loading.value = true;
  
  try {
    const response = await axios.post('/api/auth/login', form.value);
    localStorage.setItem('auth_token', response.data.access_token);
    localStorage.setItem('user', JSON.stringify(response.data.user));
    
    // Configurer le header pour les requêtes futures
    axios.defaults.headers.common['Authorization'] = `Bearer ${response.data.access_token}`;
    
    router.push({ name: 'Chat' });
  } catch (err) {
    if (err.response && err.response.data.errors) {
      error.value = Object.values(err.response.data.errors)[0][0];
    } else if (err.response && err.response.data.message) {
      error.value = err.response.data.message;
    } else {
      error.value = 'Erreur lors de la connexion. Veuillez réessayer.';
    }
  } finally {
    loading.value = false;
  }
};
</script>
