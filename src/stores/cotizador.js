import { defineStore } from 'pinia'
import { api } from 'src/boot/axios'

// Enum de regímenes tributarios según DIAN
export const REGIMEN_TRIBUTARIO = {
  VENTA_DIRECTA: 'VENTA_DIRECTA', // IVA sobre 100% del valor
  SERVICIO_GENERAL: 'SERVICIO_GENERAL', // IVA sobre 100% del valor
  SERVICIO_ESPECIAL: 'SERVICIO_ESPECIAL', // IVA sobre (A+I+U)
  OBRA_CONSTRUCCION: 'OBRA_CONSTRUCCION', // IVA solo sobre U
}

export const useCotizadorStore = defineStore('cotizador', {
  state: () => ({
    // Cotización actual en proceso
    cliente: null,
    items: [], // Items con régimen tributario individual

    // Historial de cotizaciones
    cotizaciones: [],

    // Detalle de cotización seleccionada
    cotizacionSeleccionada: null,

    loading: false,
    error: null,
  }),

  getters: {
    // Calcular cada ítem con su régimen tributario
    itemsConCalculos(state) {
      if (!state.items || !Array.isArray(state.items)) {
        return []
      }

      return state.items.map((item) => {
        const cantidad = parseFloat(item.cantidad) || 0
        const precio_unitario = parseFloat(item.precio) || 0
        const subtotal = cantidad * precio_unitario

        let base_gravable = 0
        let iva = 0
        let desglose_aiu = null

        // Asegurar que AIU existe (safety check)
        const aiu = item.aiu || { administracion: 10, imprevistos: 5, utilidad: 5 }

        // CRITICAL FIX: Extract regimen value (handle both object and string)
        const regimenValue = typeof item.regimen === 'object' ? item.regimen.value : item.regimen

        switch (regimenValue) {
          case REGIMEN_TRIBUTARIO.VENTA_DIRECTA:
          case REGIMEN_TRIBUTARIO.SERVICIO_GENERAL:
            // IVA sobre 100% del subtotal
            base_gravable = subtotal
            iva = subtotal * 0.19
            break

          case REGIMEN_TRIBUTARIO.SERVICIO_ESPECIAL: {
            // IVA sobre (A+I+U)
            const aiu_porcentaje = (aiu.administracion + aiu.imprevistos + aiu.utilidad) / 100

            base_gravable = subtotal * aiu_porcentaje
            iva = base_gravable * 0.19

            desglose_aiu = {
              A: subtotal * (aiu.administracion / 100),
              I: subtotal * (aiu.imprevistos / 100),
              U: subtotal * (aiu.utilidad / 100),
              total_porcentaje: aiu.administracion + aiu.imprevistos + aiu.utilidad,
            }
            break
          }

          case REGIMEN_TRIBUTARIO.OBRA_CONSTRUCCION: {
            // IVA SOLO sobre la Utilidad
            const utilidad = subtotal * (aiu.utilidad / 100)

            base_gravable = utilidad
            iva = utilidad * 0.19

            desglose_aiu = {
              A: subtotal * (aiu.administracion / 100),
              I: subtotal * (aiu.imprevistos / 100),
              U: utilidad,
              total_porcentaje: aiu.administracion + aiu.imprevistos + aiu.utilidad,
            }
            break
          }

          default:
            // Por defecto, IVA sobre 100%
            base_gravable = subtotal
            iva = subtotal * 0.19
        }

        return {
          ...item,
          aiu, // Asegurar que AIU siempre esté presente
          regimen: regimenValue, // Normalizar a string
          subtotal,
          base_gravable,
          iva,
          total: subtotal + iva,
          desglose_aiu,
        }
      })
    },

    // Subtotal general (suma de todos los subtotales)
    subtotal() {
      return this.itemsConCalculos.reduce((sum, item) => sum + item.subtotal, 0)
    },

    // IVA total (suma de todos los IVAs)
    iva() {
      return this.itemsConCalculos.reduce((sum, item) => sum + item.iva, 0)
    },

    // Total a pagar
    total() {
      return this.subtotal + this.iva
    },

    // Desglose por régimen tributario (para reportes)
    desgloseRegimen() {
      const desglose = {}

      this.itemsConCalculos.forEach((item) => {
        if (!desglose[item.regimen]) {
          desglose[item.regimen] = {
            subtotal: 0,
            base_gravable: 0,
            iva: 0,
            total: 0,
            cantidad_items: 0,
          }
        }

        desglose[item.regimen].subtotal += item.subtotal
        desglose[item.regimen].base_gravable += item.base_gravable
        desglose[item.regimen].iva += item.iva
        desglose[item.regimen].total += item.total
        desglose[item.regimen].cantidad_items += 1
      })

      return desglose
    },

    // Cantidad de ítems en el carrito
    cantidadItems(state) {
      if (!state.items || !Array.isArray(state.items)) {
        return 0
      }
      return state.items.length
    },

    // Verificar si hay ítems en el carrito
    hayItems(state) {
      if (!state.items || !Array.isArray(state.items)) {
        return false
      }
      return state.items.length > 0
    },

    // Verificar si se puede guardar (tiene cliente e ítems)
    puedeGuardar(state) {
      const tieneCliente = state.cliente !== null && state.cliente !== undefined
      const tieneItems = state.items && Array.isArray(state.items) && state.items.length > 0
      return tieneCliente && tieneItems
    },
  },

  actions: {
    // Seleccionar cliente para la cotización
    seleccionarCliente(clienteData) {
      this.cliente = clienteData
    },

    // Agregar ítem al carrito
    agregarItem(producto) {
      // Verificar si el producto ya existe en la lista
      const existe = this.items.find((i) => i.id === producto.id)

      if (existe) {
        // Si existe, incrementar cantidad
        existe.cantidad += 1

        // Asegurar que tiene AIU (por si es un item antiguo)
        if (!existe.aiu) {
          existe.aiu = {
            administracion: 10,
            imprevistos: 5,
            utilidad: 5,
          }
        }

        // Asegurar que tiene régimen
        if (!existe.regimen) {
          existe.regimen =
            producto.categoria === 'servicio'
              ? REGIMEN_TRIBUTARIO.SERVICIO_GENERAL
              : REGIMEN_TRIBUTARIO.VENTA_DIRECTA
        }
      } else {
        // Determinar régimen por defecto según categoría
        let regimen = REGIMEN_TRIBUTARIO.VENTA_DIRECTA
        if (producto.categoria === 'servicio') {
          regimen = REGIMEN_TRIBUTARIO.SERVICIO_GENERAL
        }

        // Si no existe, agregarlo
        this.items.push({
          id: producto.id,
          codigo: producto.codigo,
          nombre: producto.nombre,
          precio: parseFloat(producto.precio_base),
          cantidad: 1,
          categoria: producto.categoria || 'producto',
          regimen: regimen,
          aiu: {
            administracion: 10,
            imprevistos: 5,
            utilidad: 5,
          },
        })
      }
    },

    // Remover ítem del carrito
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

    // Actualizar régimen tributario de un ítem
    actualizarRegimen(index, nuevoRegimen) {
      if (index >= 0 && index < this.items.length) {
        // Extract value if it's an object (from q-select)
        const regimenValue = typeof nuevoRegimen === 'object' ? nuevoRegimen.value : nuevoRegimen
        this.items[index].regimen = regimenValue
      }
    },

    // Actualizar porcentajes AIU de un ítem
    actualizarAIU(index, nuevosAIU) {
      if (index >= 0 && index < this.items.length) {
        this.items[index].aiu = { ...this.items[index].aiu, ...nuevosAIU }
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
            regimen: item.regimen,
            aiu_administracion: item.aiu.administracion,
            aiu_imprevistos: item.aiu.imprevistos,
            aiu_utilidad: item.aiu.utilidad,
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

    // Obtener detalle de una cotización específica
    async fetchDetalleCotizacion(id) {
      this.loading = true
      this.error = null
      this.cotizacionSeleccionada = null

      try {
        const response = await api.get(`/cotizaciones.php?id=${id}`)

        if (response.data.success) {
          this.cotizacionSeleccionada = {
            ...response.data.cotizacion,
            items: response.data.items,
          }
          return {
            success: true,
            data: this.cotizacionSeleccionada,
          }
        } else {
          this.error = 'Error al cargar el detalle de la cotización'
          return { success: false, error: this.error }
        }
      } catch (error) {
        console.error('Error al obtener detalle de cotización:', error)
        this.error = error.message || 'Error de conexión con el servidor'
        return { success: false, error: this.error }
      } finally {
        this.loading = false
      }
    },

    // Actualizar el estado de una cotización
    async actualizarEstado(id, nuevoEstado) {
      this.loading = true
      this.error = null

      try {
        const response = await api.put('/cotizaciones.php', {
          id,
          estado: nuevoEstado,
        })

        if (response.data.success) {
          // Actualizar el estado en el array local sin recargar toda la lista
          const cotizacion = this.cotizaciones.find((c) => c.id === id)
          if (cotizacion) {
            cotizacion.estado = nuevoEstado
          }

          return {
            success: true,
            message: 'Estado actualizado exitosamente',
          }
        } else {
          this.error = 'Error al actualizar el estado'
          return { success: false, error: this.error }
        }
      } catch (error) {
        console.error('Error al actualizar estado:', error)
        this.error = error.message || 'Error de conexión con el servidor'
        return { success: false, error: this.error }
      } finally {
        this.loading = false
      }
    },
  },
})
