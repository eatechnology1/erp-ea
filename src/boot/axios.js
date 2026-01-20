import { boot } from 'quasar/wrappers'
import axios from 'axios'

// Configurar la URL base de la API
const api = axios.create({
  baseURL: 'http://erp.eatechnology.com.co/api',
})

export default boot(({ app }) => {
  // Para uso dentro de los componentes de Vue
  app.config.globalProperties.$axios = axios
  app.config.globalProperties.$api = api
})

export { api }
