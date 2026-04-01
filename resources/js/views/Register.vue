<template>
  <div class="m-auto w-full max-w-md bg-white rounded-xl shadow-lg p-8">
    <h2 class="text-3xl font-bold text-center text-gray-800 mb-8">Inscription</h2>
    
    <form @submit.prevent="register" class="space-y-4">
      <div>
        <label class="block text-sm font-medium text-gray-700">Nom</label>
        <input 
          v-model="form.name"
          type="text" 
          required 
          class="mt-1 w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 outline-none transition"
        />
      </div>

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

      <div>
        <label class="block text-sm font-medium text-gray-700">Confirmer mot de passe</label>
        <input 
          v-model="form.password_confirmation"
          type="password" 
          required 
          class="mt-1 w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 outline-none transition"
        />
      </div>
      
      <p v-if="error" class="text-sm text-red-500 text-center">{{ error }}</p>

      <button 
        type="submit" 
        :disabled="loading"
        class="w-full bg-indigo-600 hover:bg-indigo-700 text-white font-semibold py-3 rounded-lg transition mt-4"
      >
        <span v-if="loading">Création...</span>
        <span v-else>S'inscrire</span>
      </button>
    </form>
    
    <div class="mt-6 text-center text-sm text-gray-600">
      Déjà un compte ? 
      <router-link to="/login" class="text-indigo-600 font-semibold hover:underline">Se connecter</router-link>
    </div>
  </div>
</template>

<script setup>
import { ref } from 'vue';
import { useRouter } from 'vue-router';
import axios from 'axios';

const router = useRouter();
const form = ref({ name: '', email: '', password: '', password_confirmation: '' });
const error = ref('');
const loading = ref(false);

const register = async () => {
  error.value = '';
  loading.value = true;
  
  try {
    const response = await axios.post('/api/auth/register', form.value);
    localStorage.setItem('auth_token', response.data.access_token);
    localStorage.setItem('user', JSON.stringify(response.data.user));
    
    // Configurer le header pour les requêtes futures
    axios.defaults.headers.common['Authorization'] = `Bearer ${response.data.access_token}`;
    
    router.push({ name: 'Chat' });
  } catch (err) {
    if (err.response && err.response.data.errors) {
      error.value = Object.values(err.response.data.errors)[0][0];
    } else {
      error.value = 'Erreur lors de l\'inscription. Veuillez réessayer.';
    }
  } finally {
    loading.value = false;
  }
};
</script>
