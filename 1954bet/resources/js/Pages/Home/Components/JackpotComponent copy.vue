<template>
  <div class="w-full p-2 h-28 flex flex-between items-center max-w-[490px] mx-auto bgcategoria">
    <!-- Condição para exibir Jackpot Um ou Jackpot Dois -->
    <div v-if="isJackpotOneActive" class="flex grid grid-cols-2 gap-1 w-full">
      <!-- JACKPOT UM-->
      <div class="flex w-full relative">
        <img src="/public/assets/images/jackpot.png" class="h-22 w-auto p-2">
        <div class="absolute bottom-2 left-0 w-full flex justify-center items-end p-2">
          {{ formatNumber(currentJackpotValue.toFixed(2)) }}
        </div>
      </div>
      <!-- Botoes do jackpot um-->
      <div class="flex grid grid-cols-2 gap-1 w-full justify-center items-center">
        <div v-if="custom.bt_1_link" class="h-12 w-full">
          <a :href="custom.bt_1_link" target="_blank" class="h-12 w-full bghj rounded flex justify-start items-center">
            <img :src="`/storage/`+setting.icon_bt_1" class="left-0 w-5 h-5 mr-1 ml-2" alt="Ícone">
            <span style="font-size: 10px; color: var(--text-color);">{{ setting.name_bt_1 }}</span>
          </a>
        </div>
        <div v-if="custom.bt_2_link" class="h-12 w-full">
          <a :href="custom.bt_2_link" target="_blank" class="h-12 w-full bghj-1 rounded flex justify-start items-center">
            <img :src="`/storage/`+setting.icon_bt_2" class="left-0 w-5 h-5 mr-1 ml-2" alt="Ícone">
            <span style="font-size: 10px; color: var(--text-color);">{{ setting.name_bt_2 }}</span>
          </a>
        </div>
        <div v-if="custom.bt_3_link" class="h-12 w-full">
          <a :href="custom.bt_3_link" target="_blank" class="h-12 w-full bghj-2 rounded flex justify-start items-center">
            <img :src="`/storage/`+setting.icon_bt_3" class="left-0 w-5 h-5 mr-1 ml-2" alt="Ícone">
            <span style="font-size: 10px; color: var(--text-color);">{{ setting.name_bt_3 }}</span>
          </a>
        </div>
        <div v-if="custom.bt_4_link" class="h-12 w-full">
          <a :href="custom.bt_4_link" target="_blank" class="h-12 w-full bghj-3 rounded flex justify-start items-center">
            <img :src="`/storage/`+setting.icon_bt_4" class="left-0 w-5 h-5 mr-1 ml-2" alt="Ícone">
            <span style="font-size: 10px; color: var(--text-color);">{{ setting.name_bt_4 }}</span>
          </a>
        </div>
      </div>
      <!-- FIM DOS Botoes do jackpot um-->
    </div>
    <!-- FIM DO JACKPOT UM-->
    
    <!-- JACKPOT DOIS-->
    <div v-else class="flex gap-1 w-full">
      <div class="flex w-full relative">
        <img src="/public/assets/images/jackpot2.png" class="h-22 w-auto p-2">
        <div class="absolute-2 bottom-2 left-0 w-full flex justify-center items-end p-2 jackpot-value">
          {{ formatNumber(currentJackpotValue.toFixed(2)) }}
        </div>
      </div>
    </div>
  </div>
</template>

<script>
import { useSettingStore } from "@/Stores/SettingStore.js";
import { useRouter } from "vue-router";

export default {
  data() {
    return {
      isLoading: false,
      setting: null,
      custom: {},
      jackpotValue: 10714981, // valor inicial do jackpot
      currentJackpotValue: 10714981, // valor exibido do jackpot
    };
  },
  setup() {
    const router = useRouter();
    return { router };
  },
  computed: {
    isJackpotOneActive() {
      return !!this.custom.bt_1_link || !!this.custom.bt_2_link || !!this.custom.bt_3_link || !!this.custom.bt_4_link;
    }
  },
  methods: {
    getSetting() {
      const settingStore = useSettingStore();
      if (settingStore && settingStore.setting) {
        this.setting = settingStore.setting;
        this.custom = this.setting.custom || {}; // Certifique-se de que custom está definido
      }
    },
    incrementJackpot() {
      this.jackpotValue *= 1.001; // aumenta 0,10%
      this.animateJackpot();
    },
    animateJackpot() {
      const duration = 1000; // duração da animação em milissegundos
      const startValue = this.currentJackpotValue;
      const endValue = this.jackpotValue;
      const startTime = performance.now();

      const animate = (currentTime) => {
        const elapsedTime = currentTime - startTime;
        const progress = Math.min(elapsedTime / duration, 1);
        this.currentJackpotValue = startValue + (endValue - startValue) * progress;
        if (progress < 1) {
          requestAnimationFrame(animate);
        }
      };

      requestAnimationFrame(animate);
    },
    formatNumber(number) {
      return number.toString().replace(/\B(?=(\d{3})+(?!\d))/g, ",");
    }
  },
  created() {
    this.getSetting();
    this.custom = custom;
    setInterval(this.incrementJackpot, 2000); // chama incrementJackpot a cada 2 segundos
  }
};
</script>

<style scoped>

@import url('https://fonts.googleapis.com/css2?family=Shrikhand&display=swap');

.relative {
  position: relative;
}
.absolute {
  font-size: 13px; /* Usar unidades relativas para flexibilidade */
  color: var(--value-color-jackpot);
  position: absolute;
  display: flex;
  align-items: flex-end; /* Alinha os itens na parte inferior */
  padding-bottom: 11px; /* Ajuste conforme necessário */
  font-family: 'Shrikhand', cursive; /* Aplica a fonte Shrikhand */
  letter-spacing: 0.05em; /* Adiciona espaçamento entre letras */
  text-shadow: 
    -1px -1px 0 #FF0000, 
    1px -1px 0 #FF0000, 
    -1px 1px 0 #FF0000, 
    1px 1px 0 #FF0000; /* Adiciona contorno vermelho */
}


.absolute-2 {
  font-size: 24px;
  color: var(--value-color-jackpot);
  position: absolute;
  display: flex;
  align-items: flex-end; /* Alinha os itens na parte inferior */
  padding-bottom: 16px; /* Ajuste conforme necessário */
  font-family: 'Shrikhand', cursive; /* Aplica a fonte Shrikhand */
  letter-spacing: 0.05em; /* Adiciona espaçamento entre letras */
  text-shadow: 
    -1px -1px 0 #FF0000, 
    1px -1px 0 #FF0000, 
    -1px 1px 0 #FF0000, 
    1px 1px 0 #FF0000; /* Adiciona contorno vermelho */
}

.bghj {
  background-color: var(--color-bt-1);
  border-radius: 10px;
}
.bghj-1 {
  background-color: var(--color-bt-2);
  border-radius: 10px;
}
.bghj-2 {
  background-color: var(--color-bt-3);
  border-radius: 10px;
}
.bghj-3 {
  background-color: var(--color-bt-4);
  border-radius: 10px;
}
.bgcategoria{
  background-color: var(--background-color-jackpot);
}

</style>
