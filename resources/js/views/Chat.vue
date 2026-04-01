<template>
  <div class="flex h-screen w-full bg-white overflow-hidden">
    <!-- Sidebar / Liste des conversations -->
    <div class="w-1/3 border-r flex flex-col bg-gray-50">
      <div class="p-4 bg-white border-b flex justify-between items-center shadow-sm z-10">
        <div class="flex items-center gap-3">
          <img :src="currentUser.avatar_url" class="w-10 h-10 rounded-full border border-gray-200 shadow-sm" alt="Avatar"/>
          <h2 class="text-xl font-bold text-gray-800">Discussions</h2>
        </div>
        <button @click="logout" class="text-sm px-3 py-1 bg-red-50 text-red-600 hover:bg-red-100 rounded-lg transition font-semibold">Déconnexion</button>
      </div>
      
      <!-- Liste des utilisateurs pour un nouveau chat -->
      <div class="p-4 border-b bg-white shadow-sm z-0">
        <h3 class="text-xs font-semibold text-gray-500 uppercase mb-2 ml-1">Nouvelle discussion</h3>
        <select v-model="selectedNewUser" @change="startConversation" class="w-full p-2.5 bg-gray-50 border border-gray-200 rounded-lg text-sm focus:ring-2 focus:ring-indigo-500 focus:outline-none transition">
          <option value="">Sélectionnez un utilisateur...</option>
          <option v-for="user in availableUsers" :key="user.id" :value="user.id">
            {{ user.name }}
          </option>
        </select>
      </div>

      <!-- Conversations actives -->
      <div class="flex-1 overflow-y-auto">
        <button 
          v-for="conv in conversations" :key="conv.id"
          @click="selectConversation(conv)"
          :class="['w-full text-left p-4 border-b hover:bg-gray-100 transition duration-150 flex items-center gap-4', selectedConversation?.id === conv.id ? 'bg-indigo-50 border-l-4 border-indigo-500' : '']"
        >
          <div class="relative">
            <div class="w-12 h-12 rounded-full bg-indigo-200 text-indigo-700 flex items-center justify-center font-bold text-lg shadow-inner">
              {{ getConversationName(conv).charAt(0).toUpperCase() }}
            </div>
            <!-- Pastille de notification "non lu" -->
            <div v-if="conv.unread_count > 0" class="absolute -top-1 -right-1 bg-red-500 text-white text-xs font-bold w-5 h-5 rounded-full flex items-center justify-center border-2 border-white">
              {{ conv.unread_count }}
            </div>
          </div>
          <div class="flex-1 min-w-0">
            <h3 class="font-semibold text-gray-800 truncate text-base">{{ getConversationName(conv) }}</h3>
            <p class="text-sm text-gray-500 truncate" v-if="conv.last_message && conv.last_message.length">
              {{ conv.last_message[0].body || '📎 Pièce jointe' }}
            </p>
          </div>
        </button>
      </div>
    </div>

    <!-- Zone de messages -->
    <div class="flex-1 flex flex-col bg-slate-50 relative" v-if="selectedConversation">
      <!-- Header de la conversation -->
      <div class="p-4 bg-white border-b shadow-sm z-10 flex items-center gap-3">
        <div class="w-10 h-10 rounded-full bg-indigo-200 text-indigo-700 flex items-center justify-center font-bold">
          {{ getConversationName(selectedConversation).charAt(0).toUpperCase() }}
        </div>
        <h2 class="text-xl font-bold flex items-center gap-2 text-gray-800">
          {{ getConversationName(selectedConversation) }}
        </h2>
      </div>

      <!-- Messages -->
      <div class="flex-1 overflow-y-auto p-6 space-y-6" id="messages-container">
        <div 
          v-for="msg in messages" :key="msg.id"
          :class="['flex w-full', msg.user_id === currentUser.id ? 'justify-end' : 'justify-start']"
        >
          <div 
            :class="['max-w-[70%] rounded-2xl p-4 shadow-md text-[15px] leading-relaxed relative', msg.user_id === currentUser.id ? 'bg-indigo-600 text-white rounded-tr-none' : 'bg-white text-gray-800 border border-gray-100 rounded-tl-none']"
          >
            <!-- Nom de l'expéditeur si c'est un groupe -->
            <div v-if="selectedConversation.type === 'group' && msg.user_id !== currentUser.id" class="text-xs font-bold text-indigo-500 mb-1">
              {{ msg.sender?.name }}
            </div>
            
            <p v-if="msg.body">{{ msg.body }}</p>

            <!-- Pièce jointe -->
            <div v-if="msg.file_path" class="mt-2 text-sm">
              <a :href="'/storage/' + msg.file_path" target="_blank" :class="['flex items-center gap-2 underline underline-offset-2', msg.user_id === currentUser.id ? 'text-indigo-200 hover:text-white' : 'text-indigo-600 hover:text-indigo-800']">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.172 7l-6.586 6.586a2 2 0 102.828 2.828l6.414-6.586a4 4 0 00-5.656-5.656l-6.415 6.585a6 6 0 108.486 8.486L20.5 13"></path></svg>
                Voir le fichier joint
              </a>
            </div>

            <!-- Date/Heure -->
            <div :class="['text-[10px] mt-2 text-right opacity-70', msg.user_id === currentUser.id ? 'text-indigo-100' : 'text-gray-500']">
              {{ new Date(msg.created_at).toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' }) }}
            </div>
          </div>
        </div>
      </div>

      <!-- Champ de saisie -->
      <div class="p-4 bg-white border-t rounded-tl-3xl shadow-[0_-4px_6px_-1px_rgba(0,0,0,0.02)]">
        <form @submit.prevent="sendMessage" class="flex gap-3 items-center max-w-4xl mx-auto">
          <input 
            v-model="newMessage"
            type="text" 
            placeholder="Écrivez un message..." 
            class="flex-1 p-3.5 bg-gray-50 border border-transparent rounded-full focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:bg-white transition shadow-inner"
          />
          <button 
            type="submit" 
            :disabled="!newMessage.trim() || sending"
            class="bg-indigo-600 hover:bg-indigo-700 disabled:bg-gray-400 text-white rounded-full p-3 w-12 h-12 flex items-center justify-center shadow-md transition transform hover:scale-105 active:scale-95 flex-shrink-0"
          >
            <svg class="w-5 h-5 rotate-90 ml-1" fill="currentColor" viewBox="0 0 20 20"><path d="M10.894 2.553a1 1 0 00-1.788 0l-7 14a1 1 0 001.169 1.409l5-1.429A1 1 0 009 15.571V11a1 1 0 112 0v4.571a1 1 0 00.725.962l5 1.428a1 1 0 001.17-1.408l-7-14z"></path></svg>
          </button>
        </form>
      </div>
    </div>
    
    <!-- Écran d'accueil au chargement ou sans conversation -->
    <div class="flex-1 flex flex-col items-center justify-center bg-gradient-to-br from-indigo-50 to-white" v-else>
      <div class="bg-white p-8 rounded-full shadow-lg mb-6">
        <svg class="w-20 h-20 text-indigo-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z"></path></svg>
      </div>
      <h2 class="text-3xl font-bold text-gray-800 mb-2">Bienvenue sur le Chat</h2>
      <p class="text-lg text-gray-500 font-medium">Sélectionnez une discussion pour démarrer</p>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted, onUnmounted, nextTick } from 'vue';
import { useRouter } from 'vue-router';
import axios from 'axios';

const router = useRouter();
const currentUser = ref(JSON.parse(localStorage.getItem('user') || '{}'));

const conversations = ref([]);
const availableUsers = ref([]);
const selectedNewUser = ref('');

const selectedConversation = ref(null);
const messages = ref([]);
const newMessage = ref('');
const sending = ref(false);

let pollingInterval = null;

onMounted(async () => {
  await fetchConversations();
  await fetchUsers();
  
  // HTTP POLLING: toutes les 3 secondes
  pollingInterval = setInterval(() => {
    if (selectedConversation.value) {
      pollMessages();
    }
    fetchConversations();
  }, 3000);
});

onUnmounted(() => {
  if (pollingInterval) clearInterval(pollingInterval);
});

const getConversationName = (conv) => {
  if (conv.type === 'group') return conv.name;
  const otherUser = conv.users?.find(u => u.id !== currentUser.value.id);
  return otherUser ? otherUser.name : 'Utilisateur inconnu';
};

const fetchConversations = async () => {
  try {
    const res = await axios.get('/api/conversations');
    conversations.value = res.data;
  } catch (err) {
    if (err.response?.status === 401) logout();
  }
};

const fetchUsers = async () => {
  try {
    const res = await axios.get('/api/users');
    availableUsers.value = res.data;
  } catch (err) {
    console.error(err);
  }
};

const startConversation = async () => {
  if (!selectedNewUser.value) return;
  
  try {
    const res = await axios.post('/api/conversations', {
      user_id: selectedNewUser.value,
      is_group: false
    });
    
    await fetchConversations();
    selectConversation(res.data);
    selectedNewUser.value = '';
  } catch (err) {
    console.error(err);
  }
};

const selectConversation = async (conv) => {
  selectedConversation.value = conv;
  await pollMessages(true);
};

const pollMessages = async (forceScroll = false) => {
  if (!selectedConversation.value) return;
  
  try {
    const res = await axios.get(`/api/conversations/${selectedConversation.value.id}/messages`);
    const newMessages = res.data.data.reverse();
    
    if (newMessages.length !== messages.value.length) {
      messages.value = newMessages;
      if (forceScroll || isScrolledToBottom()) {
        scrollToBottom();
      }
    }
  } catch (err) {
    console.error(err);
  }
};

const sendMessage = async () => {
  if (!newMessage.value.trim() || !selectedConversation.value) return;
  
  sending.value = true;
  const body = newMessage.value;
  
  // Optimistic UI update
  const tempMsg = {
    id: Date.now(),
    body: body,
    user_id: currentUser.value.id,
    created_at: new Date().toISOString()
  };
  messages.value.push(tempMsg);
  newMessage.value = '';
  scrollToBottom();

  try {
    await axios.post(`/api/conversations/${selectedConversation.value.id}/messages`, {
      body: body
    });
    await fetchConversations();
  } catch (err) {
    messages.value = messages.value.filter(m => m.id !== tempMsg.id); 
  } finally {
    sending.value = false;
  }
};

const isScrolledToBottom = () => {
  const container = document.getElementById('messages-container');
  if (!container) return false;
  return container.scrollHeight - container.clientHeight <= container.scrollTop + 50;
};

const scrollToBottom = () => {
  nextTick(() => {
    const container = document.getElementById('messages-container');
    if (container) {
      container.scrollTop = container.scrollHeight;
    }
  });
};

const logout = async () => {
  try {
    await axios.post('/api/auth/logout');
  } catch (e) {
  }
  localStorage.removeItem('auth_token');
  localStorage.removeItem('user');
  router.push({ name: 'Login' });
};
</script>
