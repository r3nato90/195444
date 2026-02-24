<template>
    <BaseLayout>
        <LoadingComponent :isLoading="isLoading">
            <div class="text-center">
                <span>{{ $t('Loading favorite games') }}</span>
            </div>
        </LoadingComponent>

        <div class="scroll-container w-full max-w-[490px] mx-auto scroll-hidden pr-2 pl-2">

            <div class="w-full flex justify-between mb-1 items-center pl-2 pr-2 pt-2">
                <div class="flex items-center">
                    <img src="/public/assets/images/populareshv.png" alt="Ícone" class="w-auto h-8 mr-1" />
                    <h2 class="title-custom title-color" style="font-family: 'Helvetica', sans-serif;">{{
                        $t('Favoritos') }}</h2>
                </div>
            </div>

            <div v-if="games.length > 0" :class="['grid', `grid-cols-4`, 'gap-2', 'mb-2']">

                <CassinoGameCard v-for="(game, gameIndex) in games" :key="gameIndex" :index="gameIndex"
                    :title="game.game.game_name" :cover="game.game.cover" :gamecode="game.game.game_code"
                    :type="game.game.distribution" :game="game.game" />

            </div>

            <div v-else class="empty-data flex flex-col justify-center items-center text-center my-36">
            <img :src="`/assets/images/img_none_sj.webp`" alt="" class="w-auto h-auto max-h-[140px]">
            <h3 class="text-color custom-font">{{ $t('Sem jogos disponíveis') }}</h3>
        </div>

        </div>


       


    </BaseLayout>
</template>


<script>

import BaseLayout from "@/Layouts/BaseLayout.vue";
import LoadingComponent from "@/Components/UI/LoadingComponent.vue";
import HeaderComponent from "@/Components/UI/HeaderComponent.vue";
import HttpApi from "@/Services/HttpApi.js";
import CassinoGameCard from "@/Pages/Cassino/Components/CassinoGameCard.vue";

export default {
    props: [],
    components: { CassinoGameCard, HeaderComponent, LoadingComponent, BaseLayout },
    data() {
        return {
            isLoading: true,
            games: [],
        }
    },
    setup(props) {


        return {};
    },
    computed: {

    },
    mounted() {

    },
    methods: {

        getFavoriteGame: async function (page = 1) {
            const _this = this;
            await HttpApi.get(`/profile/favorites`)
                .then(response => {
                    //console.log(response.data.games);
                    _this.games = response.data.games;
                    _this.isLoading = false;
                })
                .catch(error => {
                    _this.isLoading = false;
                });
        },
    },
    async created() {
        await this.getFavoriteGame();
    },
    watch: {

    },
};
</script>

<style scoped></style>
