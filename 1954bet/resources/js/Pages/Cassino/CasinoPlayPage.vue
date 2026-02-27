<template>
  <div>
    <GameLayout>
      <div class="w-full h-full flex justify-center items-center bg-black max-w-[490px]" v-if="isLoading">
        <div class="text-center">
          <img src="/assets/images/loading.png" class="w-[50px] h-auto" alt="Carregando informações dos jogos" />
        </div>
      </div>

      <div v-if="!isLoading && game" :class="{ 'w-full': modeMovie, 'lg:w-3/3': !modeMovie }" class="mx-auto relative">
        <div class="game-screen" id="game-screen">
          <fullscreen v-model="fullscreen" :page-only="pageOnly">
            
            <div class="absolute top-4 left-4 z-10 flex flex-col items-center">
              <router-link to="/" class="mr-2">
                <img src="/assets/images/lefts.png" alt="Voltar para Home" class="w-12 h-12 cursor-pointer" />
              </router-link>

              <button @click="toggleFullscreen" class="w-auto h-auto mr-2 mt-1 bg-black bg-opacity-50 p-2 rounded-full">
                <i v-if="fullscreen" class="fa-light fa-arrows-minimize fa-xl" style="color: #ffffff;"></i>
                <i v-else class="fa-light fa-arrows-maximize fa-xl" style="color: #ffffff;"></i>
              </button>
            </div>

            <div v-if="showButton || isExternalProvider(game.provider?.code)" class="game-full fullscreen-wrapper flex flex-col items-center justify-center bg-gray-900">
                <p class="text-white mb-4 text-center px-4">Seu navegador bloqueia cookies em iframes ou o provedor exige aba inteira.</p>
                <button @click.prevent="openModal(gameUrl)" type="button" class="px-6 py-3 rounded-md text-sm font-medium text-black focus:outline-none bg-white hover:bg-gray-200 transition">
                  🎮 CLIQUE AQUI PARA JOGAR
                </button>
            </div>
            
            <iframe v-else :src="gameUrl" class="game-full fullscreen-wrapper border-none" allowfullscreen></iframe>
          
          </fullscreen>
        </div>
      </div>

      <div v-if="undermaintenance" class="flex flex-col max-h-[100vh] items-center justify-center text-center py-24">
        <h1 class="text-2xl text-color mb-4">Jogo Em Manutenção, volte mais tarde!</h1>
        <img :src="`/assets/images/manutencao.png`" alt="" width="400">
        <div class="fixed bottom-16 flex items-center justify-center w-full p-4 h-20 max-w-[400px]">
          <router-link to="/" class="absolute rounded-lg w-auto py-4 pr-4 pl-4 items-center background-bottom-navigation text-white">
            Voltar para Tela inicial
          </router-link>
        </div>
      </div>
    </GameLayout>
  </div>
</template>

<script>
import { RouterLink, useRoute, useRouter } from "vue-router";
import { useAuthStore } from "@/Stores/Auth.js";
import { component } from 'vue-fullscreen';
import LoadingComponent from "@/Components/UI/LoadingComponent.vue";
import GameLayout from "@/Layouts/GameLayout.vue";
import HttpApi from "@/Services/HttpApi.js";
import { toRefs, reactive } from 'vue';

export default {
  components: {
    GameLayout,
    LoadingComponent,
    RouterLink,
    component
  },
  data() {
    return {
      isLoading: true,
      game: null,
      modeMovie: false,
      gameUrl: null,
      token: null,
      gameId: null,
      tabs: null,
      undermaintenance: false,
      showButton: false,
    };
  },
  setup() {
    const router = useRouter();
    const state = reactive({
      fullscreen: false,
      pageOnly: false,
    });

    function toggleFullscreen() {
      const elem = document.getElementById("game-screen");
      if (!elem) return;

      if (!state.fullscreen) {
        if (elem.requestFullscreen) {
          elem.requestFullscreen().then(() => state.fullscreen = true).catch(err => console.log(err));
        } else if (elem.webkitRequestFullscreen) {
          elem.webkitRequestFullscreen();
          state.fullscreen = true;
        } else if (elem.msRequestFullscreen) {
          elem.msRequestFullscreen();
          state.fullscreen = true;
        }
      } else {
        if (document.exitFullscreen) {
          document.exitFullscreen().then(() => state.fullscreen = false).catch(err => console.log(err));
        } else if (document.webkitExitFullscreen) {
          document.webkitExitFullscreen();
          state.fullscreen = false;
        } else if (document.msExitFullscreen) {
          document.msExitFullscreen();
          state.fullscreen = false;
        }
      }
    }

    return {
      ...toRefs(state),
      toggleFullscreen,
      router
    };
  },
  mounted() {
    setTimeout(() => {
      // Remover a chamada automática pois navegadores bloqueiam fullscreen sem clique do usuário
      // this.activateFullscreen(); 
    }, 500);

    const userAgent = navigator.userAgent.toLowerCase();
    const isSafari = userAgent.includes('safari') && !userAgent.includes('chrome');
    const isSamsungInternet = userAgent.includes('samsung') && userAgent.includes('safari');
    const isIOS = userAgent.includes('iphone') || userAgent.includes('ipad');

    // Força abertura via botão se o navegador for Safari/iOS
    if (isSafari || isSamsungInternet || isIOS) {
      this.showButton = true;
    }
  },
  computed: {
    isAuthenticated() {
      const authStore = useAuthStore();
      return authStore.isAuth;
    },
  },
  methods: {
    openModal(gameUrl) {
      window.open(gameUrl, '_self'); // '_self' mantém a navegação na mesma aba se possível
    },
    
    // Método para forçar certos provedores a abrirem via botão em vez de iFrame
    isExternalProvider(providerCode) {
      if(!providerCode) return false;
      const codes = ['maxapi', 'pgclone']; // Adicione aqui provedores que exigem abertura fora de iframe
      return codes.includes(providerCode.toLowerCase());
    },

    getGame: async function () {
      const _this = this;

      return await HttpApi.post('games/play/' + _this.gameId) // <-- A ROTA PODE PRECISAR DE AJUSTE AQUI. Antes era get('games/single/')..
        .then(async response => {
          
          if(response.data.status === false || response.data.error) {
            _this.isLoading = false;
            _this.undermaintenance = true;
            return;
          }

          _this.gameUrl = response.data.gameUrl;
          _this.game = { provider: { code: 'pgclone' }}; // Simulando objeto caso a API de play não retorne o obj game completo
          _this.isLoading = false;

        })
        .catch(error => {
          console.error(error);
          _this.isLoading = false;
          _this.undermaintenance = true;
        });
    },
  },
  async created() {
    if (this.isAuthenticated) {
      const route = useRoute();
      this.gameId = route.params.id;

      await this.getGame();
    } else {
      this.router.push({ name: 'login', params: { action: 'openlogin' } });
    }
  },
};
</script>

<style>
.game-screen {
  left: 0;
  right: 0;
  top: 0;
  bottom: 0;
  position: fixed;
  width: 100%;
  min-height: 100vh;
  z-index: 50;
  background-color: #000;
}

.fullscreen-overlay {
  position: absolute;
  top: 0;
  left: 0;
  right: 0;
  bottom: 0;
  background-color: rgba(0, 0, 0, 0.8);
  display: flex;
  justify-content: center;
  align-items: center;
  z-index: 1000;
}

.game-full {
  width: 100%;
  height: 100vh;
  border: none;
}
</style>