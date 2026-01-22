<template>
  <q-page class="q-pa-md proyectos-page">
    <div class="q-mb-md row items-center justify-between">
      <h4 class="q-my-md">Proyectos y Presupuestos</h4>
      <q-btn
        label="Nuevo Proyecto"
        icon="add"
        color="primary"
        @click="mostrarDialogoNuevo = true"
        size="md"
      />
    </div>

    <!-- Grid de Proyectos (Kanban Style) -->
    <div class="row q-col-gutter-md">
      <div
        v-for="proyecto in proyectosStore.proyectos"
        :key="proyecto.id"
        class="col-12 col-sm-6 col-md-4 col-lg-3"
      >
        <q-card class="proyecto-card-cyber">
          <!-- Header -->
          <q-card-section class="card-header">
            <div class="proyecto-nombre">{{ proyecto.nombre }}</div>
            <div class="proyecto-cliente">
              <q-icon name="business" size="14px" />
              {{ proyecto.cliente_nombre }}
            </div>
            <q-chip
              :color="getEstadoColor(proyecto.estado)"
              text-color="white"
              size="sm"
              class="q-mt-sm"
            >
              {{ proyecto.estado }}
            </q-chip>
          </q-card-section>

          <q-separator class="separator-neon" />

          <!-- Métricas -->
          <q-card-section class="card-metrics">
            <div class="metric-row">
              <span class="metric-label">Cierre:</span>
              <span class="metric-value cierre">{{ formatearMoneda(proyecto.valor_cierre) }}</span>
            </div>
            <div class="metric-row">
              <span class="metric-label">Costos:</span>
              <span class="metric-value costo">{{ formatearMoneda(proyecto.costo_total) }}</span>
            </div>
            <div class="metric-row">
              <span class="metric-label">Utilidad:</span>
              <span class="metric-value utilidad">{{ formatearMoneda(proyecto.utilidad) }}</span>
            </div>

            <!-- Barra de Progreso -->
            <div class="progress-container q-mt-md">
              <div class="progress-label">Margen: {{ proyecto.margen_porcentaje.toFixed(1) }}%</div>
              <q-linear-progress
                :value="proyecto.margen_porcentaje / 100"
                :color="getMargenColor(proyecto.margen_porcentaje)"
                size="8px"
                class="progress-bar-cyber"
              />
            </div>
          </q-card-section>

          <q-separator class="separator-neon" />

          <!-- Acciones -->
          <q-card-actions class="card-actions">
            <q-btn
              flat
              icon="edit"
              label="Editar"
              color="cyan"
              @click="editarProyecto(proyecto.id)"
              size="sm"
            />
            <q-space />
            <q-btn flat icon="delete" color="red" @click="confirmarEliminar(proyecto)" size="sm" />
          </q-card-actions>
        </q-card>
      </div>
    </div>

    <!-- Diálogo Nuevo Proyecto -->
    <q-dialog v-model="mostrarDialogoNuevo" persistent>
      <q-card style="min-width: 500px" class="modal-cyber">
        <q-card-section class="row items-center q-pb-none">
          <div class="text-h6">Nuevo Proyecto</div>
          <q-space />
          <q-btn icon="close" flat round dense v-close-popup />
        </q-card-section>

        <q-card-section>
          <q-form @submit="crearProyecto" class="q-gutter-md">
            <q-input
              v-model="nuevoProyecto.nombre"
              label="Nombre del Proyecto *"
              outlined
              :rules="[(val) => !!val || 'Campo requerido']"
            />

            <q-select
              v-model="nuevoProyecto.cliente_id"
              :options="clientesStore.clientes"
              option-value="id"
              option-label="razon_social"
              label="Cliente *"
              outlined
              emit-value
              map-options
              :rules="[(val) => !!val || 'Campo requerido']"
            />

            <q-input
              v-model="nuevoProyecto.fecha_inicio"
              label="Fecha de Inicio *"
              outlined
              type="date"
              :rules="[(val) => !!val || 'Campo requerido']"
            />

            <div class="row justify-end q-gutter-sm q-mt-md">
              <q-btn label="Cancelar" color="grey" flat v-close-popup />
              <q-btn
                label="Crear"
                type="submit"
                color="primary"
                :loading="proyectosStore.loading"
              />
            </div>
          </q-form>
        </q-card-section>
      </q-card>
    </q-dialog>
  </q-page>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import { useProyectosStore } from 'src/stores/proyectos'
import { useClientesStore } from 'src/stores/clientes'
import { useFormatters } from 'src/composables/useFormatters'
import { useQuasar } from 'quasar'
import { useRouter } from 'vue-router'

const $q = useQuasar()
const router = useRouter()
const proyectosStore = useProyectosStore()
const clientesStore = useClientesStore()
const { formatearMoneda } = useFormatters()

const mostrarDialogoNuevo = ref(false)
const nuevoProyecto = ref({
  nombre: '',
  cliente_id: null,
  fecha_inicio: '',
  estado: 'activo',
})

const getEstadoColor = (estado) => {
  const colores = {
    activo: 'green',
    finalizado: 'blue',
    cancelado: 'red',
  }
  return colores[estado] || 'grey'
}

const getMargenColor = (margen) => {
  if (margen >= 30) return 'green'
  if (margen >= 15) return 'orange'
  return 'red'
}

const editarProyecto = (id) => {
  router.push(`/proyectos/${id}`)
}

const confirmarEliminar = (proyecto) => {
  $q.dialog({
    title: 'Confirmar',
    message: `¿Eliminar el proyecto "${proyecto.nombre}"?`,
    cancel: true,
    persistent: true,
  }).onOk(async () => {
    const resultado = await proyectosStore.eliminarProyecto(proyecto.id)
    if (resultado.success) {
      $q.notify({
        type: 'positive',
        message: 'Proyecto eliminado',
        position: 'top-right',
      })
    }
  })
}

const crearProyecto = async () => {
  const resultado = await proyectosStore.crearProyecto(nuevoProyecto.value)

  if (resultado.success) {
    $q.notify({
      type: 'positive',
      message: 'Proyecto creado exitosamente',
      position: 'top-right',
    })
    mostrarDialogoNuevo.value = false
    router.push(`/proyectos/${resultado.id}`)
  }
}

onMounted(async () => {
  await proyectosStore.fetchProyectos()
  await clientesStore.fetchClientes()
})
</script>

<style lang="scss" scoped>
.proyectos-page {
  background: var(--bg-app);
}

h4 {
  color: var(--text-primary) !important;
}

.proyecto-card-cyber {
  background: var(--card-bg) !important;
  backdrop-filter: blur(12px) saturate(180%);
  -webkit-backdrop-filter: blur(12px) saturate(180%);
  border: 1px solid var(--border-color);
  border-radius: 12px;
  box-shadow: 0 8px 32px rgba(0, 0, 0, 0.1);
  transition: all 0.3s ease;

  &:hover {
    transform: translateY(-4px);
    box-shadow: 0 12px 40px var(--accent-glow);
    border-color: var(--accent-color);
  }
}

.card-header {
  .proyecto-nombre {
    font-size: 18px;
    font-weight: 700;
    color: var(--accent-color);
    text-shadow: 0 0 8px var(--accent-glow);
    margin-bottom: 8px;
  }

  .proyecto-cliente {
    font-size: 13px;
    color: var(--text-primary);
    opacity: 0.7;
    display: flex;
    align-items: center;
    gap: 4px;
  }
}

.separator-neon {
  background: linear-gradient(90deg, transparent, var(--border-color), transparent);
  height: 1px;
}

.card-metrics {
  .metric-row {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 8px;

    .metric-label {
      font-size: 12px;
      color: var(--text-primary);
      opacity: 0.7;
    }

    .metric-value {
      font-size: 14px;
      font-weight: 600;

      &.cierre {
        color: #10b981;
      }

      &.costo {
        color: #ef4444;
      }

      &.utilidad {
        color: var(--accent-color);
        text-shadow: 0 0 6px var(--accent-glow);
      }
    }
  }

  .progress-container {
    .progress-label {
      font-size: 11px;
      color: var(--text-primary);
      opacity: 0.7;
      margin-bottom: 4px;
    }

    .progress-bar-cyber {
      border-radius: 4px;
    }
  }
}

.card-actions {
  padding: 8px 16px;
  background: rgba(0, 0, 0, 0.1);
}

.modal-cyber {
  background: var(--card-bg) !important;
  backdrop-filter: blur(16px) saturate(180%);
  -webkit-backdrop-filter: blur(16px) saturate(180%);
  border: 1px solid var(--border-color) !important;
  border-radius: 16px;
  box-shadow: 0 8px 32px rgba(0, 0, 0, 0.2);

  :deep(.text-h6) {
    color: var(--accent-color) !important;
    text-shadow: 0 0 8px var(--accent-glow);
  }

  :deep(.q-field__label),
  :deep(.q-field__native),
  :deep(input) {
    color: var(--text-primary) !important;
  }

  :deep(.q-field__control) {
    color: var(--text-primary) !important;

    &:before,
    &:after {
      border-color: var(--border-color) !important;
    }
  }

  :deep(.q-field--focused) {
    .q-field__control {
      &:before,
      &:after {
        border-color: var(--accent-color) !important;
      }
    }
  }
}
</style>
