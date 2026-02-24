<template>
  <div
    v-if="shouldDisplayGame"
    class="item-game text-white rounded-[15px] overflow-hidden relative"
  >
    <!-- Verificação do tipo de jogo para o link correto -->
    <RouterLink
      v-if="game.distribution === 'kagaming'"
      :to="{
        name: 'casinoPlayPage',
        params: { id: game.id, slug: game.game_code },
      }"
    >
      <img
        :src="game.cover"
        alt=""
        class="w-full h-full rounded-lg object-cover"
      />
    </RouterLink>
    <RouterLink
      v-else
      :to="{
        name: 'casinoPlayPage',
        params: { id: game.id, slug: game.game_code },
      }"
    >
      <img
        :src="`/storage/` + game.cover"
        alt=""
        class="w-full h-full rounded-lg object-cover"
      />
    </RouterLink>

    <!-- Botão de Favoritar com troca de imagem -->
    <button
      @click="toggleFavorite(game.id)"
      class="favorite-button absolute top-1 right-2"
    >
      <img
        :src="isFavorited(game.id) ? starenable : star"
        alt="favorite icon"
        class="w-6 h-6"
      />
    </button>
  </div>
</template>


<script>
import { RouterLink } from "vue-router";
import { useAuthStore } from "@/Stores/Auth.js";
import HttpApi from "@/Services/HttpApi.js";
import { useSettingStore } from "@/Stores/SettingStore.js";

export default {
  props: {
    index: Number,
    game: {
      props: {
    index: Number,
    title: String,
    cover: String,
    gamecode: String,
    type: String,
    game: Object
}
    },
  },
  components: { RouterLink },
  setup() {
    const authStore = useAuthStore();

    // Funções para verificar autenticação e dados do usuário
    const isAuthenticated = () => {
      return authStore.isAuth;
    };

    const userData = () => {
      return authStore.user;
    };

    return {
      isAuthenticated,
      userData,
    };
  },
  computed: {
    // Verifica se o jogo deve ser exibido
    shouldDisplayGame() {
      const user = this.userData();

      // Se o usuário não estiver autenticado
      if (!this.isAuthenticated()) {
        return this.game.is_influencer_mode === 0; // Exibe apenas jogos com is_influencer_mode 0
      }

      // Se o usuário estiver autenticado
      if (this.game.is_influencer_mode === 0) {
        return true; // Exibe para todos
      } else if (
        this.game.is_influencer_mode === 1 &&
        user &&
        user.influencer_mode === 1
      ) {
        return true; // Exibe apenas para usuários com influencer_mode 1
      }
      return false; // Não exibe para outros usuários
    },
  },
  data() {
    return {
      favorites: null, // Inicialmente vazio, será preenchido pelo backend
      star: "data:image/png;base64,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",
      starenable:
        "data:image/png;base64,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",
    };
  },

  mounted() {
    //this.loadFavorites(); // Carrega os favoritos ao montar o componente
  },

  methods: {
    async getSetting() {
      const settingStore = useSettingStore();
      if (settingStore) {
        try {
          // Faz a chamada à API para obter os dados de configuração e favoritos
          const { data } = await HttpApi.get("/settings/data"); // Refaça a requisição à API

          // Atualiza a store com os novos dados de configuração e favoritos
          settingStore.setSetting(data); // Atualiza tanto os favoritos quanto as configurações

          // Atualiza a variável local de favoritos (opcional, mas bom para garantir que a UI tenha os dados corretos)
          this.favorites = settingStore.favorites || [];
        } catch (error) { }
      }
    },

    // Carrega os favoritos do backend
    async loadFavorites() {
      try {
        const response = await HttpApi.get("/profile/favorites");
        // Atualiza o estado local com os IDs dos jogos favoritados
        this.favorites = response.data.map(
          (favorite) => favorite.game_id
        );
      } catch (error) { }
    },

    isFavorited(gameId) {
      if (Array.isArray(this.favorites)) {
        return this.favorites.some(
          (favorite) => Number(favorite.game_id) === Number(gameId)
        );
      }
      return false; // Caso favorites não seja um array, retorna false
    },

    async toggleFavorite(gameId) {
      try {
        if (this.isFavorited(gameId)) {
          // Remove dos favoritos
          await HttpApi.get(`/profile/favorites/${gameId}`);
          console.log("Jogo removido dos favoritos");
        } else {
          // Adiciona aos favoritos
          const response = await HttpApi.post(
            "/profile/favorites/store",
            { gameId }
          );
          console.log("Jogo adicionado aos favoritos");
        }

        // Após a operação de favoritar ou remover, recarregue as configurações e favoritos
        await this.getSetting(); // Refaça a requisição e atualize tudo
      } catch (error) {
        console.error(
          "Erro ao alternar favoritos:",
          error.response || error
        );
        alert(
          "Não foi possível atualizar os favoritos. Tente novamente mais tarde."
        );
      }
    },
  },

  async created() {
    await this.getSetting();
  },
};
</script>

<style scoped>
.item-game-wrapper {
  position: absolute;
  bottom: 0;
  left: 0;
  width: 100%;
  padding: 0px;
  text-align: center;
}

.item-game-wrapper span {
  font-family: "Helvetica", sans-serif;
  font-style: normal;
  line-height: normal;
  font-size: clamp(0.7rem, 1vw, 0.8rem);
  /* Responsivo */
  font-weight: 400;
}

.favorite-button {
  background: none;
  border: none;
  cursor: pointer;
}

.favorite-button img {
  width: 25px;
  height: 25px;
}

@import url("https://fonts.googleapis.com/css2?family=Roboto:wght@400;700&display=swap");

body {
  font-family: "Helvetica", sans-serif;
}

.custom-font {
  font-family: "Helvetica", sans-serif;
  font-style: normal;
  line-height: normal;
  font-weight: 400;
}
</style>
