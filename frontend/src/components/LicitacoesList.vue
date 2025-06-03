<template>
  <div class="licitacoes-list">
    <h2>Licitações do Dia</h2>

    <div class="search-bar">
      <input v-model="searchUasg" type="text" placeholder="Código UASG" @keyup.enter="applySearchUasg"/>
      <button @click="applySearchUasg" :disabled="loading || !searchUasg.trim()">
        Buscar UASG
      </button>
      <button v-if="isSearchingUasg" @click="clearSearch" :disabled="loading">
        Limpar UASG
      </button>
    </div>

    <div class="search-bar">
      <input v-model="searchPregao" type="text" placeholder="Número (ou parte) do Pregão" @keyup.enter="applySearchPregao"/>
      <button @click="applySearchPregao" :disabled="loading || !searchPregao.trim()">
        Buscar Pregão
      </button>
      <button v-if="isSearchingPregao" @click="clearSearch" :disabled="loading">
        Limpar Pregão
      </button>
    </div>

    <div v-if="loading" class="status">Carregando...</div>
    <div v-else-if="error" class="status erro">{{ error }}</div>

    <div v-else-if="isSearchingUasg && !loading && licitacoes.length === 0" class="status">
      Nenhuma licitação encontrada para UASG “{{ searchUasg.trim() }}”.
    </div>
    <div v-else-if="isSearchingPregao && !loading && licitacoes.length === 0" class="status">
      Nenhum pregão contendo “{{ searchPregao.trim() }}” encontrado.
    </div>
    <div v-else-if="!isSearchingUasg && !isSearchingPregao && !loading && licitacoes.length === 0" class="status">
      Nenhuma licitação encontrada.
    </div>

    <div v-else class="lista-cards">
      <div class="card" v-for="item in licitacoes" :key="item.ordem + '-' + item.uasg + '-' + item.modalidade_numero">
        <div class="card-header">
          <label class="checkbox-label">
            <input
              type="checkbox"
              :value="itemKey(item)"
              v-model="vistos"
              @change="atualizaLocalStorage"
            />
            Visto
          </label>
          <span class="ordem">#{{ item.ordem }}</span>
          <span class="uasg">UASG: {{ item.uasg }}</span>
        </div>
        <div class="card-body">
          <div class="campo">
            <strong>Órgão:</strong> {{ item.orgao }}
          </div>
          <div class="campo">
            <strong>Pregão:</strong> {{ item.modalidade_numero }}
          </div>
          <div class="campo">
            <strong>Objeto:</strong> {{ item.objeto }}
          </div>
          <div class="campo">
            <strong>Data Abertura:</strong> {{ item.edital_inicio }}
          </div>
          <div class="campo">
            <strong>Endereço:</strong> {{ item.endereco || '—' }}
          </div>
          <div class="campo">
            <strong>Telefone:</strong> {{ item.telefone || '—' }}
            <span v-if="item.fax">Fax: {{ item.fax }}</span>
          </div>
          <div class="campo">
            <strong>Entrega da Proposta:</strong> {{ item.entrega_proposta }}
          </div>
        </div>
        <div class="card-footer">
          <a v-if="item.itens_download_url" :href="item.itens_download_url" target="_blank" class="btn-download">
            Baixar Itens/Edital
          </a>
          <span v-else class="sem-download">Sem download</span>
        </div>
      </div>
    </div>

    <div v-if="!loading && !error && licitacoes.length > 0 && !isSearchingUasg && !isSearchingPregao" class="pagination">
      <button @click="prevPage" :disabled="page <= 1">Anterior</button>
      <span>Página {{ page }}</span>
      <button @click="nextPage">Próximo</button>
    </div>
  </div>
</template>

<script setup>
  import { ref, onMounted } from 'vue'

  function getInitialPage() {
    const params = new URLSearchParams(window.location.search)
    const p = parseInt(params.get('pagina'))
    return isNaN(p) || p < 1 ? 1 : p
  }

  const licitacoes = ref([])
  const loading = ref(true)
  const error = ref('')
  const page = ref(getInitialPage())
  const searchUasg = ref('')
  const isSearchingUasg = ref(false)
  const searchPregao = ref('')
  const isSearchingPregao = ref(false)

  const vistos = ref([])

  function itemKey(item) {
    return `${item.ordem}-${item.uasg}`
  }

  function carregaVistosDoLocalStorage() {
    try {
      const raw = localStorage.getItem('licitacoesVistas')
      if (raw) {
        vistos.value = JSON.parse(raw)
      }
    } catch {
      vistos.value = []
    }
  }

  function atualizaLocalStorage() {
    localStorage.setItem('licitacoesVistas', JSON.stringify(vistos.value))
  }

  async function fetchData() {
    loading.value = true
    error.value = ''
    licitacoes.value = []

    try {
      if (isSearchingUasg.value) {
        const resp = await fetch(
          `/api/licitacoes/uasg/${encodeURIComponent(searchUasg.value.trim())}`)

        if (resp.status === 404) {
          licitacoes.value = []
        } else if (!resp.ok) {
          throw new Error(`HTTP ${resp.status}`)
        } else {
          licitacoes.value = await resp.json()
        }
      } else if (isSearchingPregao.value) {
        const termo = encodeURIComponent(searchPregao.value.trim())
        const resp = await fetch(`/api/licitacoes/pregao?numero=${termo}`)
        if (resp.status === 404) {
          licitacoes.value = []
        } else if (!resp.ok) {
          throw new Error(`HTTP ${resp.status}`)
        } else {
          licitacoes.value = await resp.json()
        }
      } else {
        const resp = await fetch(`/api/licitacoes?pagina=${page.value}`)
        if (resp.status === 404) {
          licitacoes.value = []
        } else if (!resp.ok) {
          throw new Error(`HTTP ${resp.status}`)
        } else {
          licitacoes.value = await resp.json()
        }
      }
    } catch (err) {
      error.value = 'Falha ao carregar: ' + err.message
      licitacoes.value = []
    } finally {
      loading.value = false

      let newUrl = window.location.pathname
      if (isSearchingUasg.value) {
        newUrl += `?uasg=${encodeURIComponent(searchUasg.value.trim())}`
      } else if (isSearchingPregao.value) {
        newUrl += `?numero=${encodeURIComponent(searchPregao.value.trim())}`
      } else if (page.value > 1) {
        newUrl += `?pagina=${page.value}`
      }
      window.history.replaceState(null, '', newUrl)
    }
  }

  function applySearchUasg() {
    const termo = searchUasg.value.trim()
    if (!termo) return
    isSearchingUasg.value = true
    isSearchingPregao.value = false
    page.value = 1
    fetchData()
  }

  function applySearchPregao() {
    const termo = searchPregao.value.trim()
    if (!termo) return
    isSearchingPregao.value = true
    isSearchingUasg.value = false
    page.value = 1
    fetchData()
  }

  function clearSearch() {
    isSearchingUasg.value = false
    isSearchingPregao.value = false
    searchUasg.value = ''
    searchPregao.value = ''
    page.value = 1
    fetchData()
  }

  function nextPage() {
    page.value++
    fetchData()
  }

  function prevPage() {
    if (page.value > 1) {
      page.value--
      fetchData()
    }
  }

  function limparSeMudouDia() {
    const hoje = new Date().toISOString().slice(0, 10) 
    const ultimaData = localStorage.getItem('licitacoesVistasData')

    if (ultimaData !== hoje) {
      localStorage.removeItem('licitacoesVistas')
      localStorage.setItem('licitacoesVistasData', hoje)
      vistos.value = []
    }
  }

  onMounted(() => {
    const params = new URLSearchParams(window.location.search)
    const uasgParam = params.get('uasg')
    const pregParam = params.get('numero')
    if (uasgParam) {
      searchUasg.value = uasgParam
      isSearchingUasg.value = true
    } else if (pregParam) {
      searchPregao.value = pregParam
      isSearchingPregao.value = true
    }
    carregaVistosDoLocalStorage()
    fetchData()
    limparSeMudouDia()
  })
</script>

<style scoped>
  h2 {
    margin-bottom: 1rem;
    font-size: 1.5rem;
  }

  .licitacoes-list {
    margin: 20px;
  }

  .search-bar {
    display: flex;
    flex-wrap: wrap;
    gap: 0.5rem;
    margin-bottom: 1rem;
  }

  .search-bar input {
    flex: 1;
    min-width: 200px;
    padding: 0.5rem;
    border: 1px solid #ccc;
    border-radius: 4px;
  }

  .search-bar button {
    padding: 0.5rem 1rem;
    border: 1px solid #007bff;
    background: #007bff;
    color: #fff;
    width: 150px;
    border-radius: 4px;
    cursor: pointer;
  }

  .search-bar button:disabled {
    opacity: 0.5;
    cursor: default;
  }

  .status {
    margin: 1rem 0;
    font-style: italic;
  }

  .erro {
    color: red;
  }

  .lista-cards {
    display: grid;
    grid-template-columns: repeat(4, 1fr);
    gap: 1rem;
  }

  @media (max-width: 1100px) {
    .lista-cards {
      grid-template-columns: repeat(3, 1fr);
    }
  }

   @media (max-width: 800px) {
    .lista-cards {
      grid-template-columns: repeat(2, 1fr);
    }
  }

   @media (max-width: 600px) {
    .lista-cards {
      grid-template-columns: repeat(1, 1fr);
    }
  }

  .card {
    background: #fff;
    border: 1px solid #ddd;
    border-radius: 6px;
    display: flex;
    flex-direction: column;
    box-shadow: 0 2px 4px rgba(0,0,0,0.05);
  }

  .card-header {
    background: #f5f5f5;
    padding: 0.5rem 1rem;
    border-bottom: 1px solid #ddd;
    display: flex;
    justify-content: space-between;
    font-weight: bold;
    font-size: 0.9rem;
  }

  .checkbox-label {
    display: flex;
    align-items: center;
  }

  .checkbox-label input {
    margin-right: 0.5rem;
  }

  .card-body {
    padding: 1rem;
    flex: 1;
  }

  .campo {
    margin-bottom: 0.5rem;
    font-size: 0.9rem;
  }

  .card-footer {
    padding: 0.75rem 1rem;
    border-top: 1px solid #ddd;
    text-align: right;
  }

  .btn-download {
    display: inline-block;
    padding: 0.4rem 0.8rem;
    background: #007bff;
    color: #fff;
    border-radius: 4px;
    text-decoration: none;
    font-size: 0.9rem;
  }

  .btn-download:hover {
    background: #0056b3;
  }

  .sem-download {
    font-size: 0.9rem;
    color: #888;
  }

  .pagination {
    display: flex;
    justify-content: center;
    align-items: center;
    margin-top: 1.5rem;
    gap: 0.5rem;
  }

  .pagination button {
    padding: 0.4rem 0.8rem;
    border: 1px solid #ccc;
    background: #fff;
    border-radius: 4px;
    cursor: pointer;
  }

  .pagination button:disabled {
    opacity: 0.5;
    cursor: default;
  }

  .pagination span {
    font-size: 0.9rem;
  }
</style>
