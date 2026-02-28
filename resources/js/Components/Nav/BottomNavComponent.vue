<template>
  <div class="flex">
    <div class="fixed btn z-40 w-full max-w-[490px] h-16 -translate-x-1/2 bottom-0 left-1/2 custom-border-top background-bottom-navigation"
         :style="{ 
            backgroundImage: `url(/storage/${setting?.icon_bottom_left}), url(/storage/${setting?.icon_bottom_right})`,
            backgroundRepeat: 'no-repeat, no-repeat',
            backgroundPosition: 'left, right',
            backgroundSize: 'contain, contain'
         }" style="box-shadow: rgba(0, 0, 0, 0.4) 0px -2.53333vw 5.2vw;">
      <div class="grid h-full grid-cols-5 mx-auto flex-grow">
        <button v-click-sound @click="navigateTo('home', '/home/game?gameCategoryId=0')"
                type="button"
                class="flex-1 inline-flex flex-col items-center justify-center group"
                style="color:var(--navb_icon_color);">
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="size-6">
                  <path stroke-linecap="round" stroke-linejoin="round" d="m2.25 12 8.954-8.955c.44-.439 1.152-.439 1.591 0L21.75 12M4.5 9.75v10.125c0 .621.504 1.125 1.125 1.125H9.75v-4.875c0-.621.504-1.125 1.125-1.125h2.25c.621 0 1.125.504 1.125 1.125V21h4.125c.621 0 1.125-.504 1.125-1.125V9.75M8.25 21h8.25" />
                </svg>
 <span class="text text-color opacity-bottom-nav">{{ $t('Home') }}</span>
        </button>

        <button v-click-sound @click="navigateTo('cassino', null); openModal('login')"
                type="button"
                class="flex-1 inline-flex flex-col items-center justify-center group" 
                style="color:var(--navb_icon_color);"
                >
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="size-6">
                  <path stroke-linecap="round" stroke-linejoin="round" d="M8.25 9V5.25A2.25 2.25 0 0 1 10.5 3h6a2.25 2.25 0 0 1 2.25 2.25v13.5A2.25 2.25 0 0 1 16.5 21h-6a2.25 2.25 0 0 1-2.25-2.25V15m-3 0-3-3m0 0 3-3m-3 3H15" />
                </svg>
<span class="text text-color opacity-bottom-nav">{{ authButtonText }}</span>
        </button>

        
          <button v-click-sound @click="navigateTo('deposito', '/profile/financas')"
                  type="button"
                  class="flex-1 inline-flex flex-col items-center justify-center group" 
                  style="color:var(--navb_icon_color);">
                  <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="size-6">
                  <path stroke-linecap="round" stroke-linejoin="round" d="M21 12a2.25 2.25 0 0 0-2.25-2.25H15a3 3 0 1 1-6 0H5.25A2.25 2.25 0 0 0 3 12m18 0v6a2.25 2.25 0 0 1-2.25 2.25H5.25A2.25 2.25 0 0 1 3 18v-6m18 0V9M3 12V9m18 0a2.25 2.25 0 0 0-2.25-2.25H5.25A2.25 2.25 0 0 0 3 9m18 0V6a2.25 2.25 0 0 0-2.25-2.25H5.25A2.25 2.25 0 0 0 3 6v3" />
                </svg>

<span class="text text-color opacity-bottom-nav">{{ $t('Depósito') }}</span>
          </button>
        

        <button v-click-sound @click="navigateTo('affiliate', '/profile/promocoes')"
                type="button"
                class="flex-1 inline-flex flex-col items-center justify-center group" 
                style="color:var(--navb_icon_color);">
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="size-6">
                  <path stroke-linecap="round" stroke-linejoin="round" d="M21 11.25v8.25a1.5 1.5 0 0 1-1.5 1.5H5.25a1.5 1.5 0 0 1-1.5-1.5v-8.25M12 4.875A2.625 2.625 0 1 0 9.375 7.5H12m0-2.625V7.5m0-2.625A2.625 2.625 0 1 1 14.625 7.5H12m0 0V21m-8.625-9.75h18c.621 0 1.125-.504 1.125-1.125v-1.5c0-.621-.504-1.125-1.125-1.125h-18c-.621 0-1.125.504-1.125 1.125v1.5c0 .621.504 1.125 1.125 1.125Z" />
                </svg>

                <span class="text text-color opacity-bottom-nav">Promoções</span>
        </button>

        <button v-click-sound @click="navigateTo('wallet', null); openProfileModal()"
                type="button"
                class="flex-1 inline-flex flex-col items-center justify-center group" 
                style="color:var(--navb_icon_color);">
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="size-6">
                <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 6a3.75 3.75 0 1 1-7.5 0 3.75 3.75 0 0 1 7.5 0ZM4.501 20.118a7.5 7.5 0 0 1 14.998 0A17.933 17.933 0 0 1 12 21.75c-2.676 0-5.216-.584-7.499-1.632Z" />
              </svg>

<span class="text text-color opacity-bottom-nav">{{ $t('Perfil') }}</span>
        </button>
      </div>
    </div>
  </div>

  <ModalAuth ref="modalAuth" />
</template>



<script>
import { useSettingStore } from "@/Stores/SettingStore.js";
import { useAuthStore } from "@/Stores/Auth.js";
import { useRouter, watch } from 'vue-router';
import { useToast } from "vue-toastification";
import HttpApi from "@/Services/HttpApi.js";
import ModalAuth from "@/Components/ModalAuth/ModalAuth.vue";

export default {
  components: { ModalAuth },
  data() {
    return {
      setting: null,
      selectedButton: '', // Armazena o botão atualmente selecionado
    };
  },
  setup() {
    const router = useRouter();
    return {
      router,
    };
  },
  computed: {
    isAuthenticated() {
      const authStore = useAuthStore();
      return authStore.isAuth;
    },
    authButtonText() {
      return this.isAuthenticated ? this.$t('Sair') : this.$t('Login');
    },
    authAltText() {
      return this.isAuthenticated ? 'Logout' : 'Login';
    },
  },
  mounted() {
    this.getSetting();
    this.updateSelectedButton();
  },
  watch: {
    '$route': 'updateSelectedButton', // Observa a rota atual
  },
  methods: {
    selectButton(button) {
      this.selectedButton = button;
    },
    navigateTo(button, path) {
      this.selectButton(button); // Atualiza o botão selecionado
      if (path) {
        this.$router.push(path); // Navega para a nova rota
      }
    },
    openModal(type) {
      if (this.isAuthenticated) {
        this.logout();
      } else {
        this.$refs.modalAuth.openModal(type);
      }
    },
    openProfileModal() {
      if (this.isAuthenticated) {
        this.$router.push('/profile/profile');
      } else {
        this.$refs.modalAuth.openModal('register');
      }
    },
    getSetting() {
      const settingStore = useSettingStore();
      this.setting = settingStore.setting;
      this.$emit('hide-bottom-nav');
    },
    updateSelectedButton() {
      // Atualiza selectedButton com base na rota atual
      const path = this.$route.path;
      const query = this.$route.query;

      if (path === '/' || (path === '/home/game' && query.gameCategoryId === '0')) {
        this.selectedButton = 'home';
      } else if (path === '/profile/financas') {
        this.selectedButton = 'deposito';
      } else if (path === '/profile/promocoes') {
        this.selectedButton = 'affiliate';
      } else if (path === '/profile/profile') {
        this.selectedButton = 'wallet';
      } else {
        this.selectedButton = ''; // Reseta para padrão
      }
    },
    togglePassword() {
      this.typeInputPassword = this.typeInputPassword === 'password' ? 'text' : 'password';
    },
    logout() {
      const authStore = useAuthStore();
      const toast = useToast();
      
      HttpApi.post('auth/logout', {})
          .then(response => {
              authStore.logout();
              this.$router.push('/');
             // toast.success(this.$t('You have been successfully disconnected'), {
              //    position: 'top-center',
              //    timeout: 3000,
              //    className: 'logout-toast',
             // });
          })
          .catch(error => {
              console.error('Error logging out:', error);
          });
    },
    getImagePath(image) {
      return `/storage/${image}`;
    },
  },
  async created() {
    if (this.isAuthenticated) {
      await this.getSetting();
    }
  },
};
</script>

<style scoped>
.grid {
  display: flex; /* Adiciona flexbox ao contêiner */
}

button {
  flex: 1; /* Faz com que todos os botões cresçam igualmente */
  min-width: 0; /* Impede que o conteúdo ultrapasse o espaço disponível */
}
/* Estilos para telas maiores que 450px */
@media (min-width: 451px) {
  .icon {
    width: 30px; /* Tamanho fixo para a imagem */
    height: 30px; /* Tamanho fixo para a imagem */
  }

  .text {
    font-size: 0.80rem; /* Tamanho fixo do texto */
  }
}

/* Estilos para telas menores ou iguais a 450px */
@media (max-width: 450px) {
  .icon {
    width: 30px; /* Tamanho fixo para a imagem */
    height: 30px; /* Tamanho fixo para a imagem */
  }

  .text {
    font-size: 0.80rem; /* Tamanho fixo do texto */
  }
}
/* Estilos para telas menores ou iguais a 450px */
@media (max-width: 360px) {
  .icon {
    width: 25px; /* Tamanho fixo para a imagem */
    height: 25px; /* Tamanho fixo para a imagem */
  }

  .text {
    font-size: 0.70rem; /* Tamanho fixo do texto */
  }
}

.opacity-bottom-nav {
  opacity: var(--opacity-bottom-nav);
}

.btn {
  transition: background-color 0.3s ease;
  border-top-width: 1px; 
  border-top-width: solid; 
  border-top-color: var(--background-bottom-navigation);
}

@import url('https://fonts.googleapis.com/css2?family=Roboto:wght@400;700&display=swap');

body {
  font-family: 'Helvetica', sans-serif; /* Define a fonte Helvetica para todo o documento */
}

.custom-font {
  font-family: 'Helvetica', sans-serif;
  font-size: 12px;
}
</style>

