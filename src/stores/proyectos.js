import { defineStore } from 'pinia'
import { api } from 'src/boot/axios'

export const useProyectosStore = defineStore('proyectos', {
  state: () => ({
    proyectos: [],
    proyectoActual: null,
    categorias: [],
    loading: false,
    error: null,
  }),

  getters: {
    // Calcular totales del proyecto actual
    subtotalProyecto: (state) => {
      if (!state.proyectoActual || !state.proyectoActual.categorias) return 0

      let total = 0
      state.proyectoActual.categorias.forEach((cat) => {
        cat.items.forEach((item) => {
          total += (item.cantidad || 0) * (item.valor_unitario || 0)
        })
      })
      return total
    },

    totalMateriales: (state) => {
      if (!state.proyectoActual || !state.proyectoActual.categorias) return 0

      let total = 0
      state.proyectoActual.categorias.forEach((cat) => {
        cat.items.forEach((item) => {
          total += item.costo_materiales || 0
        })
      })
      return total
    },

    totalManoObra: (state) => {
      if (!state.proyectoActual || !state.proyectoActual.categorias) return 0

      let total = 0
      state.proyectoActual.categorias.forEach((cat) => {
        cat.items.forEach((item) => {
          total += item.mano_de_obra || 0
        })
      })
      return total
    },

    costoTotal: (state) => {
      return state.totalMateriales + state.totalManoObra
    },

    utilidadNeta: (state) => {
      return state.subtotalProyecto - state.costoTotal
    },

    margenPorcentaje: (state) => {
      if (state.subtotalProyecto === 0) return 0
      return (state.utilidadNeta / state.subtotalProyecto) * 100
    },

    // Calcular AIU
    valorIVA: (state) => {
      if (!state.proyectoActual) return 0
      const porcentaje = state.proyectoActual.iva_porcentaje || 0
      return (state.subtotalProyecto * porcentaje) / 100
    },

    valorAdministracion: (state) => {
      if (!state.proyectoActual) return 0
      const porcentaje = state.proyectoActual.administracion_porcentaje || 0
      return (state.subtotalProyecto * porcentaje) / 100
    },

    valorImprevistos: (state) => {
      if (!state.proyectoActual) return 0
      const porcentaje = state.proyectoActual.improvistos_porcentaje || 0
      return (state.subtotalProyecto * porcentaje) / 100
    },

    totalFinal: (state) => {
      return (
        state.subtotalProyecto + state.valorIVA + state.valorAdministracion + state.valorImprevistos
      )
    },
  },

  actions: {
    // Obtener lista de proyectos
    async fetchProyectos() {
      this.loading = true
      this.error = null

      try {
        const response = await api.get('/proyectos.php')

        if (response.data.success) {
          this.proyectos = response.data.data
          return { success: true }
        } else {
          this.error = 'Error al cargar proyectos'
          return { success: false, error: this.error }
        }
      } catch (error) {
        console.error('Error al obtener proyectos:', error)
        this.error = error.message || 'Error de conexión'
        return { success: false, error: this.error }
      } finally {
        this.loading = false
      }
    },

    // Obtener detalle de un proyecto
    async fetchProyectoDetalle(id) {
      this.loading = true
      this.error = null

      try {
        const response = await api.get(`/proyectos.php?id=${id}`)

        if (response.data.success) {
          this.proyectoActual = response.data.data
          return { success: true }
        } else {
          this.error = 'Error al cargar el proyecto'
          return { success: false, error: this.error }
        }
      } catch (error) {
        console.error('Error al obtener proyecto:', error)
        this.error = error.message || 'Error de conexión'
        return { success: false, error: this.error }
      } finally {
        this.loading = false
      }
    },

    // Crear nuevo proyecto
    async crearProyecto(proyectoData) {
      this.loading = true
      this.error = null

      try {
        const response = await api.post('/proyectos.php', proyectoData)

        if (response.data.success) {
          await this.fetchProyectos()
          return { success: true, id: response.data.id }
        } else {
          this.error = response.data.error || 'Error al crear proyecto'
          return { success: false, error: this.error }
        }
      } catch (error) {
        console.error('Error al crear proyecto:', error)
        this.error = error.message || 'Error de conexión'
        return { success: false, error: this.error }
      } finally {
        this.loading = false
      }
    },

    // Actualizar proyecto
    async actualizarProyecto(proyectoData) {
      this.loading = true
      this.error = null

      try {
        const response = await api.put('/proyectos.php', proyectoData)

        if (response.data.success) {
          await this.fetchProyectos()
          if (this.proyectoActual && this.proyectoActual.id === proyectoData.id) {
            await this.fetchProyectoDetalle(proyectoData.id)
          }
          return { success: true }
        } else {
          this.error = response.data.error || 'Error al actualizar proyecto'
          return { success: false, error: this.error }
        }
      } catch (error) {
        console.error('Error al actualizar proyecto:', error)
        this.error = error.message || 'Error de conexión'
        return { success: false, error: this.error }
      } finally {
        this.loading = false
      }
    },

    // Eliminar proyecto
    async eliminarProyecto(id) {
      this.loading = true
      this.error = null

      try {
        const response = await api.delete(`/proyectos.php?id=${id}`)

        if (response.data.success) {
          await this.fetchProyectos()
          if (this.proyectoActual && this.proyectoActual.id === id) {
            this.proyectoActual = null
          }
          return { success: true }
        } else {
          this.error = response.data.error || 'Error al eliminar proyecto'
          return { success: false, error: this.error }
        }
      } catch (error) {
        console.error('Error al eliminar proyecto:', error)
        this.error = error.message || 'Error de conexión'
        return { success: false, error: this.error }
      } finally {
        this.loading = false
      }
    },

    // Obtener categorías
    async fetchCategorias() {
      try {
        const response = await api.get('/proyecto_categorias.php')

        if (response.data.success) {
          this.categorias = response.data.data
        }
      } catch (error) {
        console.error('Error al obtener categorías:', error)
      }
    },

    // Limpiar proyecto actual
    limpiarProyectoActual() {
      this.proyectoActual = null
    },
  },
})
