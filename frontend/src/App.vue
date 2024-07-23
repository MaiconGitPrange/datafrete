<template>
  <div id="app" class="container mt-5">
    <h1 class="mb-4">Cadastro de Distâncias</h1>
    <h2 class="mt-5">Upload de CSV</h2>
    <b-form @submit.prevent="uploadCsv">
      <div class="container-upload">
        <b-form-file class="col" v-model="csvFile" accept=".csv"></b-form-file>
        <b-button type="submit" variant="primary" class="col-2 float-right ml-2">Upload CSV
          <b-spinner small v-if="loadingCsv" label="Carregando..."></b-spinner>
        </b-button>
      </div>
    </b-form>

    <hr>
    <b-button variant="primary" class="float-right mb-2" @click="showAddModal">Adicionar Distância</b-button>
    <div class="mt-4">
      <div v-if="loading" class="text-center text-danger my-2 mt-5">
        <b-spinner class="align-middle"></b-spinner>
        <strong>Loading...</strong>
      </div>

      <b-table v-else :items="distances" :fields="fields">
        <template #cell(cep_origem)="row">
          {{ formatCep(row.item.cep_origem) }}
        </template>
        <template #cell(cep_destino)="row">
          {{ formatCep(row.item.cep_destino) }}
        </template>
        <template #cell(distancia)="row">
          {{ row.item.distancia + ` km` }}
        </template>
        <template #cell(edit)="row">
          <b-button class="float-right" size="sm" @click="editDistance(row.item)">Editar</b-button>
        </template>
      </b-table>
    </div>
    <b-modal v-model="isModalVisible" :title="modalTitle" hide-footer>
      <b-form @submit.prevent="handleSubmit">
        <b-form-group label="CEP Origem">
          <b-form-input v-model="cepOrigem" placeholder="CEP Origem" v-mask="'#####-###'"></b-form-input>
        </b-form-group>
        <b-form-group label="CEP Destino">
          <b-form-input v-model="cepDestino" placeholder="CEP Destino" v-mask="'#####-###'"></b-form-input>
        </b-form-group>
        <hr>
        <b-button type="submit" class="float-right" variant="primary">{{ modalButtonText }} <b-spinner small
            v-if="loadingInsert" label="Carregando..."></b-spinner></b-button>
      </b-form>
    </b-modal>
  </div>
</template>

<script>
import axios from 'axios';

export default {
  data() {
    return {
      cepOrigem: '',
      cepDestino: '',
      csvFile: null,
      distances: [],
      loading: false,
      loadingInsert: false,
      loadingCsv: false,
      isModalVisible: false,
      currentDistanceId: null,
      fields: [
        { key: 'cep_origem', label: 'CEP Origem' },
        { key: 'cep_destino', label: 'CEP Destino' },
        { key: 'distancia', label: 'Distância' },
        { key: 'edit', label: 'Editar' }
      ]
    };
  },
  computed: {
    modalTitle() {
      return this.currentDistanceId ? 'Editar Distância' : 'Adicionar Distância';
    },
    modalButtonText() {
      return this.currentDistanceId ? 'Salvar' : 'Adicionar';
    }
  },
  methods: {
    formatCep(cep) {
      return cep.replace(/(\d{5})(\d{3})/, '$1-$2');
    },
    showAddModal() {
      this.currentDistanceId = null;
      this.cepOrigem = '';
      this.cepDestino = '';
      this.isModalVisible = true;
    },
    async handleSubmit() {
      if (this.currentDistanceId) {
        this.updateDistance();
      } else {
        this.addDistance();
      }
    },
    async addDistance() {
      try {
        this.loadingInsert = true;
        await axios.post('http://localhost/calculate-distance', {
          cep_origem: this.cepOrigem.replace('-', ''),
          cep_destino: this.cepDestino.replace('-', '')
        });

        this.cepOrigem = '';
        this.cepDestino = '';
        this.fetchDistances();
        this.showToast('Distância adicionada com sucesso!', 'success');
        this.isModalVisible = false;
        this.loadingInsert = false;
      } catch (error) {
        this.showToast('Erro ao adicionar distância. Tente novamente.', 'danger');
        this.loadingInsert = false;
      }
    },
    async uploadCsv() {
      if (!this.csvFile) {
        this.showToast('Por favor, selecione um arquivo CSV.', 'warning');
        return;
      }

      this.loadingCsv = true;
      const formData = new FormData();
      formData.append('file', this.csvFile);

      try {
        await axios.post('http://localhost/upload-csv', formData, {
          headers: {
            'Content-Type': 'multipart/form-data'
          }
        });

        this.fetchDistances();
        this.csvFile = null;
        this.showToast('Arquivo CSV enviado com sucesso!', 'success');
      } catch (error) {
        this.csvFile = null;
        this.showToast('Erro ao enviar o arquivo CSV. Tente novamente.', 'danger');
      } finally {
        this.loadingCsv = false;
      }
    },
    async fetchDistances() {
      this.loading = true;
      try {
        const response = await axios.get('http://localhost/list');
        this.distances = response.data;
      } catch (error) {
        console.error('Erro ao buscar distâncias:', error);
      } finally {
        this.loading = false;
      }
    },
    showToast(message, variant = null) {
      this.$bvToast.toast(message, {
        title: `${variant}`,
        variant: variant,
        solid: true
      });
    },
    editDistance(distance) {
      this.currentDistanceId = distance.id;
      this.cepOrigem = distance.cep_origem;
      this.cepDestino = distance.cep_destino;
      this.isModalVisible = true;
    },
    async updateDistance() {
      this.loadingInsert = true;
      
      try {
        await axios.put(`http://localhost/update-distance/${this.currentDistanceId}`, {
          cep_origem: this.cepOrigem.replace('-', ''),
          cep_destino: this.cepDestino.replace('-', '')
        });

        this.isModalVisible = false;
        this.loadingInsert = false;
        this.fetchDistances();
        this.showToast('Distância atualizada com sucesso!', 'success');
      } catch (error) {
        this.loadingInsert = false;
        this.showToast('Erro ao atualizar distância. Tente novamente.', 'danger');
      }
    }
  },
  mounted() {
    this.fetchDistances();
  }
};
</script>

<style>
body {
  background-color: #f8f9fa;
}

.container-upload {
  display: flex;
  flex-direction: row;
}
</style>
