<template>
  <div class="container-fluid navtop-color rounded-lg mt-1">
    <div class="row">
      <div class="col-12">
        <div class="gains-slider-container">
          <div class="gains-section">
            <div class="icon-trofeu ml-2">
              <img src="/assets/images/trofeu.webp" alt="">
            </div>
            <div class="title-gains">
              <h1>ÚLTIMOS <br> GANHADORES</h1>
            </div>
          </div>
          <div class="gains-con rounded-lg">
            <div class="gains-slider">
              <div class="logos-slide">
                <div class="slide-gains" v-for="(gain, index) in gains" :key="index">
                  <img :src="gain.image" :alt="gain.game">
                  <div class="status-gains">
                    <p class="player">{{ gain.player }}</p>
                    <p class="game">{{ gain.game }}</p>
                    <p class="valor">{{ gain.amount }}</p>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script>
export default {
  data() {
    return {
      names: [
        'Camila', 'Guilherme', 'Rafaela', 'Hugo', 'Fernando', 'Isabela', 'Gabriela', 'Thales', 'Maria', 'Vitor', 
        'Vinícius', 'Aline', 'Priscila', 'Mariana', 'Rodrigo', 'Carla', 'Renato', 'Fernanda', 'Lucas', 'Ana',
        'Pedro', 'Juliana', 'Matheus', 'Gabriel', 'Isabella', 'Amanda', 'Patrícia', 'Thiago', 'Larissa', 'Eduarda',
        'André', 'Bianca', 'Rafael', 'Luana', 'Felipe', 'Sofia', 'Júlio', 'Clara', 'João', 'Verônica', 
        'Henrique', 'Bárbara', 'César', 'Michele', 'Ricardo', 'Natália', 'Cássio', 'Nicole', 'Bruno', 'Tatiane',
        'Caio', 'Helena', 'Daniel', 'Laura', 'Diego', 'Melissa', 'Marcelo', 'Olívia', 'Leandro', 'Carolina'
      ],
      lastNames: [
        'Silv***', 'Sant***', 'Olivei***', 'Lim***', 'Pereir***', 'Fernand***', 'Almeid***', 'Souz***', 'Cost***', 'Rodrigu***',
        'Marti***', 'Roch***', 'Alv***', 'Mend***', 'Cardos***', 'Gom***', 'Araúj***', 'Freit***', 'Barbos***', 'Ribeir***',
        'Cast***', 'Nascim***', 'Carvalh***', 'Di***', 'Monta***', 'Cru***', 'Corre***', 'Figueired***', 'Teixeir***', 'Vieir***',
        'Camar***', 'Fari***', 'Amar***', 'Pachec***', 'Morai***', 'Moraes***', 'Campo***', 'Duart***', 'Ram***', 'Batis***',
        'Mou***', 'Cavalcant***', 'Borg***', 'Pinhei***', 'Neve***', 'Rezend***', 'Ferreir***', 'Marqu***', 'Tavare***', 'Azeved***',
        'Cuelh***', 'Nun***', 'Leit***', 'Barros***', 'Mel***', 'Antun***', 'Aguiar***', 'Prad***', 'Assi***', 'Mac***'
      ],
      games: [
  { name: 'Fortune Tiger', image: new URL('/assets/images/games/tigre.webp', import.meta.url).href },
  { name: 'Big Bass', image: new URL('/assets/images/games/big.png', import.meta.url).href },
  { name: 'The Dog House', image: new URL('/assets/images/games/dog.png', import.meta.url).href },
  { name: 'Fruit Party', image: new URL('/assets/images/games/fruit.png', import.meta.url).href },
  { name: 'Ganesha Gold', image: new URL('/assets/images/games/gold.webp', import.meta.url).href },
  { name: 'Gates of Olympus', image: new URL('/assets/images/games/gates.webp', import.meta.url).href },
  { name: 'Ganesha Fortune', image: new URL('/assets/images/games/ganesha.webp', import.meta.url).href },
  { name: 'Fortune Mouse', image: new URL('/assets/images/games/mouse.webp', import.meta.url).href },
  { name: 'Fortune Ox', image: new URL('/assets/images/games/ox.webp', import.meta.url).href },
  { name: 'Fortune Rabbit', image: new URL('/assets/images/games/rabbit.webp', import.meta.url).href },
  { name: 'Prosperity Fortune', image: new URL('/assets/images/games/tree.webp', import.meta.url).href },
  { name: 'Sugar Rush', image: new URL('/assets/images/games/sugar.png', import.meta.url).href },
],

      gains: [], // Lista vazia para armazenar os ganhos gerados aleatoriamente
    };
  },
  methods: {
    generateRandomName() {
      const randomName = this.names[Math.floor(Math.random() * this.names.length)];
      const randomLastName = this.lastNames[Math.floor(Math.random() * this.lastNames.length)];
      return `${randomName} ${randomLastName}`;
    },
    generateRandomGains() {
      const gains = [];
      for (let i = 0; i < 25; i++) {
        const randomGame = this.games[Math.floor(Math.random() * this.games.length)];
        const randomAmount = (Math.random() * (500 - 10) + 10).toFixed(2); // Gera valores entre R$ 10,00 e R$ 500,00
        gains.push({
          player: this.generateRandomName(),
          game: randomGame.name,
          amount: `R$ ${randomAmount}`,
          image: randomGame.image,
        });
      }
      return gains;
    },
  },
  created() {
    // Gerar ganhos aleatórios no momento de criação do componente
    this.gains = this.generateRandomGains();
  },
};
</script>
  
 
<style scoped>

.container {
  display: flex;
  justify-content: center;
}

.gains-slider-container {
  width: 100%;
  height: auto;
  display: flex;
  justify-content: space-between;
}

.gains-section {
  width: 220px;
  height: 100px;
  background: linear-gradient(90deg, var(--background-color-category) 0%, var(--background-color-cassino) 50%, var(--background-color-category) 100%);
  z-index: 10;
  display: flex;
  align-items: center;
  gap: 15px;
  border-radius: 6px;
  padding-right: 10px;
}

.icon-trofeu img {
  width: 90px;
}

.title-gains {
  font-size: calc(min(100vw, 490px)* .032) !important;
  color: #fff;
  font-weight: 800;
  font-size: 25px;
}

.gains-con {
  width: calc(100% - 220px);
  overflow: hidden;
}

.gains-slider {
  overflow: hidden;
  padding-left: 220px;
  white-space: nowrap;
  position: relative;
  display: flex;
}

.gains-slider:before,
.gains-slider:after {
  position: absolute;
  top: 0;
  width: 220px;
  height: 100%;
  content: "";
  z-index: 2;
}

.gains-slider:before {
  left: 0;
}

.gains-slider:after {
  right: 0;
}

.gains-slider:hover .logos-slide {
  animation-play-state: paused;
}

.logos-slide {
  display: flex;
  animation: slide 35s infinite linear;
}

.slide-gains {
  background: linear-gradient(90deg, var(--background-color-category) 0%, var(--background-color-cassino) 50%, var(--background-color-category) 100%);
  margin-right: 15px;
  width: 200px;
  overflow: hidden;
  border-radius: 6px;
  display: flex;
  align-items: center;
  gap: 10px;
  height: 100%;
}

.slide-gains .player {
  font-family: Arial, sans-serif;
  color: #fff;
  font-weight: 500;
  font-size: 14px;
}

.slide-gains .game {
  font-family: Arial, sans-serif;
  color: #fff;
  font-size: 14px;
  margin: 0;
}

.slide-gains img {
  height: 100%;
  border-radius: 6px;
}

.slide-gains .valor {
  font-size: 19px;
  color: #ff0;
}

.logos-slide img {
  height: 100px;
}
@keyframes slide {
  0% {
    transform: translate(0);
  }
  to {
    transform: translate(-100%);
  }
}

@media only screen and (max-width: 600px) {
  .gains-section {
    width: 170px;
    height: 85px;
  }

  .icon-trofeu img {
    width: 60px;
  }


  .gains-slider {
    padding-left: 170px;
  }

  .slide-gains {
    height: 85px;
  }

  .gains-con {
    width: calc(100% - 170px);
    overflow: hidden;
  }
}
</style>