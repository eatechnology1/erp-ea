import { defineStore } from 'pinia'

export const useNotificacionesStore = defineStore('notificaciones', {
  state: () => ({
    notificaciones: [
      {
        id: 1,
        titulo: 'Nueva cotización creada',
        mensaje: 'Se ha creado la cotización #COT-2024-001 para el cliente Constructora ABC',
        fecha: new Date(Date.now() - 1000 * 60 * 30), // 30 minutos atrás
        leida: false,
        tipo: 'success',
        icono: 'description',
      },
      {
        id: 2,
        titulo: 'Proyecto actualizado',
        mensaje: 'El proyecto "Instalación Domótica Edificio Central" ha sido actualizado',
        fecha: new Date(Date.now() - 1000 * 60 * 60 * 2), // 2 horas atrás
        leida: false,
        tipo: 'info',
        icono: 'engineering',
      },
      {
        id: 3,
        titulo: 'Cliente nuevo registrado',
        mensaje: 'Se ha registrado un nuevo cliente: Tecnología Avanzada S.A.S.',
        fecha: new Date(Date.now() - 1000 * 60 * 60 * 5), // 5 horas atrás
        leida: false,
        tipo: 'success',
        icono: 'person_add',
      },
    ],
  }),

  getters: {
    // Obtener solo las notificaciones no leídas
    noLeidas(state) {
      return state.notificaciones.filter((n) => !n.leida).length
    },

    // Obtener todas las notificaciones ordenadas por fecha
    todas(state) {
      return [...state.notificaciones].sort((a, b) => b.fecha - a.fecha)
    },

    // Obtener las últimas N notificaciones
    ultimas:
      (state) =>
      (limite = 5) => {
        return [...state.notificaciones].sort((a, b) => b.fecha - a.fecha).slice(0, limite)
      },
  },

  actions: {
    // Marcar una notificación como leída
    marcarComoLeida(id) {
      const notificacion = this.notificaciones.find((n) => n.id === id)
      if (notificacion) {
        notificacion.leida = true
      }
    },

    // Marcar todas las notificaciones como leídas
    marcarTodasComoLeidas() {
      this.notificaciones.forEach((n) => {
        n.leida = true
      })
    },

    // Agregar una nueva notificación
    agregarNotificacion(notificacion) {
      const nuevaNotificacion = {
        id: Date.now(), // ID único basado en timestamp
        fecha: new Date(),
        leida: false,
        tipo: 'info',
        icono: 'notifications',
        ...notificacion,
      }
      this.notificaciones.unshift(nuevaNotificacion)
    },

    // Eliminar una notificación
    eliminarNotificacion(id) {
      const index = this.notificaciones.findIndex((n) => n.id === id)
      if (index !== -1) {
        this.notificaciones.splice(index, 1)
      }
    },

    // Limpiar todas las notificaciones leídas
    limpiarLeidas() {
      this.notificaciones = this.notificaciones.filter((n) => !n.leida)
    },
  },
})
