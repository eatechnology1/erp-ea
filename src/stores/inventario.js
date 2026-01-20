import { defineStore } from 'pinia'
import { api } from 'src/boot/axios'

export const useInventarioStore = defineStore('inventario', {
  state: () => ({
    items: [],
    loading: false,
    error: null,
  }),

  getters: {
    // Obtener todos los ítems
    todosLosItems: (state) => state.items,

    // Verificar si hay ítems
    hayItems: (state) => state.items.length > 0,

    // Filtrar por tipo
    productos: (state) => state.items.filter((item) => item.tipo === 'producto'),
    servicios: (state) => state.items.filter((item) => item.tipo === 'servicio'),
  },

  actions: {
    // Obtener todos los ítems del inventario (con búsqueda opcional)
    async fetchInventario(busqueda = '') {
      this.loading = true
      this.error = null

      try {
        // Si hay búsqueda, agregar parámetro q
        const url = busqueda
          ? `/inventario.php?q=${encodeURIComponent(busqueda)}`
          : '/inventario.php'
        const response = await api.get(url)

        if (response.data.success) {
          this.items = response.data.data
        } else {
          this.error = 'Error al cargar el inventario'
        }
      } catch (error) {
        console.error('Error al obtener inventario:', error)
        this.error = error.message || 'Error de conexión con el servidor'
      } finally {
        this.loading = false
      }
    },

    // Crear un nuevo ítem de inventario
    async crearItem(datos) {
      this.loading = true
      this.error = null

      try {
        const response = await api.post('/inventario.php', datos)

        if (response.data.success) {
          // Recargar la lista de ítems después de crear uno nuevo
          await this.fetchInventario()
          return { success: true, message: 'Ítem creado exitosamente' }
        } else {
          this.error = response.data.error || 'Error al crear el ítem'
          return { success: false, error: this.error }
        }
      } catch (error) {
        console.error('Error al crear ítem:', error)

        // Manejar error de código duplicado
        if (error.response && error.response.status === 409) {
          this.error = error.response.data.error || 'El código ya existe'
        } else {
          this.error = error.message || 'Error de conexión con el servidor'
        }

        return { success: false, error: this.error }
      } finally {
        this.loading = false
      }
    },
  },
})
