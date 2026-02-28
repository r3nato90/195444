import { defineStore } from "pinia";
import { ref } from "vue";
import axios from 'axios';
import router from '../Router';
import HttpApi from "@/Services/HttpApi.js";

export const useSettingStore = defineStore("settingData", () => {
    const setting = ref(JSON.parse(localStorage.getItem('setting')));
    const custom = ref(JSON.parse(localStorage.getItem('custom')));
    const favorites = ref([]);  // Inicializar favoritos como vazio, sem usar localStorage

    /**
     * Set Setting
     * @param settingValue
     */
    function setSetting(settingValue) {
        if (settingValue != null && settingValue.setting) {
            localStorage.setItem('setting', JSON.stringify(settingValue.setting));
            setting.value = settingValue.setting;
        }
        if (settingValue != null && settingValue.custom) {
            localStorage.setItem('custom', JSON.stringify(settingValue.custom));
            custom.value = settingValue.custom;
        }
        // Removido: Não estamos mais armazenando os favoritos no localStorage.
        if (settingValue != null && settingValue.favorites) {
            favorites.value = settingValue.favorites;  // Armazenar apenas na variável reativa
        }
    }

    function getSettingData() {
        return setting.value;
    }

    function getCustomData() {
        return custom.value;
    }

    function getFavoritesData() {
        return favorites.value;
    }

    /**
     * Fetch Settings and Custom Data
     * @returns {Promise<void>}
     */
    async function getSetting() {
        try {
            const { data } = await HttpApi.get('/settings/data');
            setSetting(data); // Atualiza tanto `setting` quanto `custom`
            return data;
        } catch (error) {
            console.log(error.response);
        }
    }

    return {
        setting,
        custom,
        favorites,
        setSetting,
        getSetting,
        getSettingData,
        getCustomData,
        getFavoritesData,
    };
});
