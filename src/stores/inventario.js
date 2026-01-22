import { defineStore } from 'pinia'
import { api } from 'src/boot/axios'

export const useInventarioStore = defineStore('inventario', {
  state: () => ({
    items: [],
    loading: false,
    error: null,
    // Metadatos de paginación
    totalRows: 0,
    currentPage: 1,
    rowsPerPage: 10,
  }),

  getters: {
    totalPages: (state) => Math.ceil(state.totalRows / state.rowsPerPage),
  },

  actions: {
    // Obtener inventario con paginación del servidor
    async fetchInventario(props = {}) {
      this.loading = true
      this.error = null

      try {
        // Extraer parámetros de paginación de Quasar Table
        const page = props.page || this.currentPage || 1
        const rowsPerPage = props.rowsPerPage || this.rowsPerPage || 10
        const filter = props.filter || ''

        // Construir query params
        const params = new URLSearchParams({
          page: page.toString(),
          limit: rowsPerPage.toString(),
        })

        // Agregar búsqueda si existe
        if (filter) {
          params.append('q', filter)
        }

        // Llamar al API con paginación
        const response = await api.get(`/inventario.php?${params.toString()}`)

        if (response.data.success) {
          this.items = response.data.data

          // Actualizar metadatos de paginación
          if (response.data.meta) {
            this.totalRows = response.data.meta.total_rows
            this.currentPage = response.data.meta.page
            this.rowsPerPage = response.data.meta.limit
          }

          return {
            success: true,
            rowsNumber: this.totalRows,
          }
        } else {
          this.error = 'Error al cargar el inventario'
          return { success: false, error: this.error }
        }
      } catch (error) {
        console.error('Error al obtener inventario:', error)
        this.error = error.message || 'Error de conexión con el servidor'
        return { success: false, error: this.error }
      } finally {
        this.loading = false
      }
    },

    // Crear un nuevo ítem de inventario
    async crearItem(itemData) {
      this.loading = true
      this.error = null

      try {
        const response = await api.post('/inventario.php', itemData)

        if (response.data.success) {
          // Recargar la primera página después de crear
          await this.fetchInventario({ page: 1, rowsPerPage: this.rowsPerPage })

          return {
            success: true,
            message: 'Ítem creado exitosamente',
            id: response.data.id,
          }
        } else {
          this.error = response.data.error || 'Error al crear el ítem'
          return { success: false, error: this.error }
        }
      } catch (error) {
        console.error('Error al crear ítem:', error)
        this.error = error.message || 'Error de conexión con el servidor'
        return { success: false, error: this.error }
      } finally {
        this.loading = false
      }
    },

    // Obtener TODO el inventario sin paginación (para selects y búsquedas)
    async fetchAllInventario() {
      this.loading = true
      this.error = null

      try {
        // Solicitar un límite muy alto para obtener todos los registros
        const response = await api.get('/inventario.php?page=1&limit=10000')

        if (response.data.success) {
          this.items = response.data.data
          return { success: true }
        } else {
          this.error = 'Error al cargar el inventario'
          return { success: false, error: this.error }
        }
      } catch (error) {
        console.error('Error al obtener inventario:', error)
        this.error = error.message || 'Error de conexión con el servidor'
        return { success: false, error: this.error }
      } finally {
        this.loading = false
      }
    },

    // Actualizar un ítem de inventario existente
    async actualizarItem(id, itemData) {
      this.loading = true
      this.error = null

      try {
        const response = await api.put('/inventario.php', { id, ...itemData })

        if (response.data.success) {
          // Recargar la página actual después de actualizar
          await this.fetchInventario({ page: this.currentPage, rowsPerPage: this.rowsPerPage })
          return { success: true, message: 'Ítem actualizado exitosamente' }
        } else {
          this.error = response.data.error || 'Error al actualizar el ítem'
          return { success: false, error: this.error }
        }
      } catch (error) {
        console.error('Error al actualizar ítem:', error)
        this.error = error.message || 'Error de conexión con el servidor'
        return { success: false, error: this.error }
      } finally {
        this.loading = false
      }
    },

    // Descargar plantilla CSV para importación
    async descargarPlantilla() {
      try {
        const response = await api.get('/inventario-template.php', {
          responseType: 'blob',
        })

        // Crear un enlace de descarga
        const url = window.URL.createObjectURL(new Blob([response.data]))
        const link = document.createElement('a')
        link.href = url
        link.setAttribute('download', 'plantilla_inventario.xlsx')
        document.body.appendChild(link)
        link.click()
        link.remove()
        window.URL.revokeObjectURL(url)

        return { success: true }
      } catch (error) {
        console.error('Error al descargar plantilla:', error)
        return { success: false, error: error.message || 'Error al descargar la plantilla' }
      }
    },

    // Importar inventario desde archivo CSV
    async importarInventario(file) {
      this.loading = true
      this.error = null

      try {
        const formData = new FormData()
        formData.append('file', file)

        const response = await api.post('/inventario-import.php', formData, {
          headers: {
            'Content-Type': 'multipart/form-data',
          },
        })

        if (response.data.success) {
          // Recargar inventario después de importar
          await this.fetchInventario({ page: 1, rowsPerPage: this.rowsPerPage })
          return {
            success: true,
            results: response.data.results,
          }
        } else {
          this.error = response.data.error || 'Error al importar el archivo'
          return { success: false, error: this.error }
        }
      } catch (error) {
        console.error('Error al importar inventario:', error)
        this.error = error.message || 'Error de conexión con el servidor'
        return { success: false, error: this.error }
      } finally {
        this.loading = false
      }
    },
  },
})
