import { boot } from 'quasar/wrappers'
import axios from 'axios'
import { Notify, Loading, QSpinnerOrbit } from 'quasar'

// Configurar la URL base de la API (usar servidor de producción)
// Los archivos PHP están desplegados en Hostinger
const api = axios.create({
  baseURL: 'https://erp.eatechnology.com.co/api',
  timeout: 30000, // 30 segundos de timeout
})

// Variable para rastrear peticiones activas
let activeRequests = 0

// Configuración de reintentos
const MAX_RETRIES = 3
const RETRY_DELAY = 1000 // 1 segundo

// ==========================================
// INTERCEPTOR DE REQUEST (Antes de enviar)
// ==========================================
api.interceptors.request.use(
  (config) => {
    // Incrementar contador de peticiones activas
    activeRequests++

    // Mostrar loading solo si es la primera petición
    if (activeRequests === 1) {
      Loading.show({
        spinner: QSpinnerOrbit,
        spinnerColor: 'cyan',
        spinnerSize: 80,
        backgroundColor: 'rgba(5, 10, 20, 0.9)',
        message: 'Procesando...',
        messageColor: 'cyan',
        customClass: 'loading-cyberpunk',
      })
    }

    // Inicializar contador de reintentos si no existe
    config.retryCount = config.retryCount || 0

    return config
  },
  (error) => {
    activeRequests--
    if (activeRequests === 0) {
      Loading.hide()
    }
    return Promise.reject(error)
  },
)

// ==========================================
// INTERCEPTOR DE RESPONSE (Después de recibir)
// ==========================================
api.interceptors.response.use(
  (response) => {
    // Decrementar contador de peticiones activas
    activeRequests--

    // Ocultar loading si no hay más peticiones
    if (activeRequests === 0) {
      Loading.hide()
    }

    return response
  },
  async (error) => {
    // Decrementar contador de peticiones activas
    activeRequests--

    // Ocultar loading si no hay más peticiones
    if (activeRequests === 0) {
      Loading.hide()
    }

    const config = error.config

    // ==========================================
    // LÓGICA DE AUTO-RETRY (Network Errors)
    // ==========================================
    if (!error.response && config && config.retryCount < MAX_RETRIES) {
      config.retryCount++

      // Mostrar notificación de reintento
      Notify.create({
        type: 'warning',
        message: `Sin conexión. Reintentando (${config.retryCount}/${MAX_RETRIES})...`,
        position: 'top',
        timeout: 1000,
        icon: 'wifi_off',
        classes: 'notify-cyberpunk',
      })

      // Esperar antes de reintentar
      await new Promise((resolve) => setTimeout(resolve, RETRY_DELAY))

      // Reintentar la petición
      return api(config)
    }

    // ==========================================
    // MANEJO DE ERRORES POR TIPO
    // ==========================================

    // Error de red (sin respuesta después de todos los reintentos)
    if (!error.response) {
      Notify.create({
        type: 'negative',
        message: 'Sin conexión al servidor. Verifique su conexión a internet.',
        position: 'top-right',
        timeout: 5000,
        icon: 'cloud_off',
        classes: 'notify-cyberpunk',
        actions: [
          {
            label: 'Cerrar',
            color: 'white',
            handler: () => {},
          },
        ],
      })
      return Promise.reject(error)
    }

    const status = error.response.status
    const errorData = error.response.data

    // Error 400/422 - Validación
    if (status === 400 || status === 422) {
      const message = errorData.error || errorData.message || 'Error de validación'

      Notify.create({
        type: 'warning',
        message: message,
        position: 'top-right',
        timeout: 4000,
        icon: 'warning',
        classes: 'notify-cyberpunk',
        html: true,
      })
    }

    // Error 401 - No autorizado
    else if (status === 401) {
      Notify.create({
        type: 'negative',
        message: 'Sesión expirada. Por favor, inicie sesión nuevamente.',
        position: 'top-right',
        timeout: 5000,
        icon: 'lock',
        classes: 'notify-cyberpunk',
      })
    }

    // Error 403 - Prohibido
    else if (status === 403) {
      Notify.create({
        type: 'negative',
        message: 'No tiene permisos para realizar esta acción.',
        position: 'top-right',
        timeout: 4000,
        icon: 'block',
        classes: 'notify-cyberpunk',
      })
    }

    // Error 404 - No encontrado
    else if (status === 404) {
      Notify.create({
        type: 'warning',
        message: 'Recurso no encontrado.',
        position: 'top-right',
        timeout: 3000,
        icon: 'search_off',
        classes: 'notify-cyberpunk',
      })
    }

    // Error 409 - Conflicto (duplicados)
    else if (status === 409) {
      const message = errorData.error || errorData.message || 'El registro ya existe'

      Notify.create({
        type: 'warning',
        message: message,
        position: 'top-right',
        timeout: 4000,
        icon: 'content_copy',
        classes: 'notify-cyberpunk',
      })
    }

    // Error 500 - Error del servidor
    else if (status === 500) {
      Notify.create({
        type: 'negative',
        message: 'Error interno del servidor. Contacte a soporte técnico.',
        position: 'top-right',
        timeout: 6000,
        icon: 'error',
        classes: 'notify-cyberpunk',
        actions: [
          {
            label: 'Reportar',
            color: 'white',
            handler: () => {
              console.error('Error 500:', errorData)
            },
          },
        ],
      })
    }

    // Error 503 - Servicio no disponible
    else if (status === 503) {
      Notify.create({
        type: 'negative',
        message: 'Servicio temporalmente no disponible. Intente más tarde.',
        position: 'top-right',
        timeout: 5000,
        icon: 'cloud_off',
        classes: 'notify-cyberpunk',
      })
    }

    // Otros errores
    else {
      const message = errorData.error || errorData.message || 'Ha ocurrido un error inesperado'

      Notify.create({
        type: 'negative',
        message: message,
        position: 'top-right',
        timeout: 4000,
        icon: 'error_outline',
        classes: 'notify-cyberpunk',
      })
    }

    return Promise.reject(error)
  },
)

export default boot(({ app }) => {
  // Para uso dentro de los componentes de Vue
  app.config.globalProperties.$axios = axios
  app.config.globalProperties.$api = api
})

export { api }
