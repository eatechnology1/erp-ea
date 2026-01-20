import { defineStore } from 'pinia'
import { api } from 'src/boot/axios'

export const useCotizadorStore = defineStore('cotizador', {
  state: () => ({
    // Cotización actual en proceso
    cliente: null,
    items: [], // Items agregados temporalmente

    // Historial de cotizaciones
    cotizaciones: [],
    loading: false,
    error: null,
  }),

  getters: {
    // Subtotal de la cotización (sin IVA)
    subtotal: (state) => {
      return state.items.reduce((total, item) => {
        return total + item.cantidad * item.precio
      }, 0)
    },

    // IVA (19% del subtotal)
    iva: (state, getters) => {
      return getters.subtotal * 0.19
    },

    // Total de la cotización (subtotal + IVA)
    total: (state, getters) => {
      return getters.subtotal + getters.iva
    },

    // Cantidad de ítems en el carrito
    cantidadItems: (state) => state.items.length,

    // Verificar si hay ítems en el carrito
    hayItems: (state) => state.items.length > 0,

    // Verificar si se puede guardar (tiene cliente e ítems)
    puedeGuardar: (state) => {
      return state.cliente !== null && state.items.length > 0
    },
  },

  actions: {
    // Seleccionar cliente para la cotización
    seleccionarCliente(clienteData) {
      this.cliente = clienteData
    },

    // Agregar ítem al carrito (agregarItem como solicitó el usuario)
    agregarItem(producto) {
      // Verificar si el producto ya existe en la lista
      const existe = this.items.find((i) => i.id === producto.id)

      if (existe) {
        // Si existe, incrementar cantidad
        existe.cantidad += 1
      } else {
        // Si no existe, agregarlo
        this.items.push({
          id: producto.id,
          codigo: producto.codigo,
          nombre: producto.nombre,
          precio: parseFloat(producto.precio_base),
          cantidad: 1,
        })
      }
    },

    // Remover ítem del carrito (por índice como solicitó el usuario)
    removerItem(index) {
      if (index >= 0 && index < this.items.length) {
        this.items.splice(index, 1)
      }
    },

    // Actualizar cantidad de un ítem
    actualizarCantidad(index, cantidad) {
      if (index >= 0 && index < this.items.length) {
        this.items[index].cantidad = Math.max(1, parseInt(cantidad) || 1)
      }
    },

    // Actualizar precio de un ítem
    actualizarPrecio(index, precio) {
      if (index >= 0 && index < this.items.length) {
        this.items[index].precio = Math.max(0, parseFloat(precio) || 0)
      }
    },

    // Limpiar carrito y cliente
    limpiarCotizacion() {
      this.cliente = null
      this.items = []
    },

    // Crear cotización (guardar en el backend)
    async crearCotizacion() {
      if (!this.puedeGuardar) {
        return {
          success: false,
          error: 'Debe seleccionar un cliente y agregar al menos un ítem',
        }
      }

      this.loading = true
      this.error = null

      try {
        const payload = {
          cliente_id: this.cliente.id,
          items: this.items.map((item) => ({
            id: item.id,
            cantidad: item.cantidad,
            precio: item.precio,
          })),
        }

        const response = await api.post('/cotizaciones.php', payload)

        if (response.data.success) {
          // Limpiar el carrito después de guardar
          this.limpiarCotizacion()

          // Recargar el historial
          await this.fetchCotizaciones()

          return {
            success: true,
            message: 'Cotización guardada exitosamente',
            id: response.data.id,
          }
        } else {
          this.error = response.data.error || 'Error al guardar la cotización'
          return { success: false, error: this.error }
        }
      } catch (error) {
        console.error('Error al crear cotización:', error)
        this.error = error.message || 'Error de conexión con el servidor'
        return { success: false, error: this.error }
      } finally {
        this.loading = false
      }
    },

    // Obtener historial de cotizaciones
    async fetchCotizaciones() {
      this.loading = true
      this.error = null

      try {
        const response = await api.get('/cotizaciones.php')

        if (response.data.success) {
          this.cotizaciones = response.data.data
        } else {
          this.error = 'Error al cargar las cotizaciones'
        }
      } catch (error) {
        console.error('Error al obtener cotizaciones:', error)
        this.error = error.message || 'Error de conexión con el servidor'
      } finally {
        this.loading = false
      }
    },
  },
})
