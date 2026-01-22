import { defineStore } from 'pinia'
import { api } from 'src/boot/axios'

export const useClientesStore = defineStore('clientes', {
  state: () => ({
    clientes: [],
    loading: false,
    error: null,
  }),

  getters: {
    // Obtener todos los clientes
    todosLosClientes: (state) => state.clientes,

    // Verificar si hay clientes
    hayClientes: (state) => state.clientes.length > 0,
  },

  actions: {
    // Obtener todos los clientes desde el backend
    async fetchClientes() {
      this.loading = true
      this.error = null

      try {
        const response = await api.get('/clientes.php')

        if (response.data.success) {
          this.clientes = response.data.data
        } else {
          this.error = 'Error al cargar los clientes'
        }
      } catch (error) {
        console.error('Error al obtener clientes:', error)
        this.error = error.message || 'Error de conexión con el servidor'
      } finally {
        this.loading = false
      }
    },

    // Crear un nuevo cliente
    async crearCliente(datos) {
      this.loading = true
      this.error = null

      try {
        const response = await api.post('/clientes.php', datos)

        if (response.data.success) {
          // Recargar la lista de clientes después de crear uno nuevo
          await this.fetchClientes()
          return { success: true, message: 'Cliente creado exitosamente' }
        } else {
          this.error = response.data.error || 'Error al crear el cliente'
          return { success: false, error: this.error }
        }
      } catch (error) {
        console.error('Error al crear cliente:', error)
        this.error = error.message || 'Error de conexión con el servidor'
        return { success: false, error: this.error }
      } finally {
        this.loading = false
      }
    },

    // Actualizar un cliente existente
    async actualizarCliente(id, datos) {
      this.loading = true
      this.error = null

      try {
        const response = await api.put('/clientes.php', { id, ...datos })

        if (response.data.success) {
          // Recargar la lista de clientes después de actualizar
          await this.fetchClientes()
          return { success: true, message: 'Cliente actualizado exitosamente' }
        } else {
          this.error = response.data.error || 'Error al actualizar el cliente'
          return { success: false, error: this.error }
        }
      } catch (error) {
        console.error('Error al actualizar cliente:', error)
        this.error = error.message || 'Error de conexión con el servidor'
        return { success: false, error: this.error }
      } finally {
        this.loading = false
      }
    },
  },
})
