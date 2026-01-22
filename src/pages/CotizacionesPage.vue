<template>
  <q-page class="q-pa-md cotizaciones-page">
    <div class="q-mb-md">
      <h4 class="q-my-md">Historial de Cotizaciones</h4>
    </div>

    <!-- Tabla de cotizaciones -->
    <q-table
      :rows="cotizadorStore.cotizaciones"
      :columns="columns"
      row-key="id"
      :loading="cotizadorStore.loading"
      flat
      bordered
      class="shadow-2 tabla-cotizaciones-cyber"
    >
      <template v-slot:loading>
        <q-inner-loading showing color="primary" />
      </template>

      <template v-slot:no-data>
        <div class="full-width row flex-center text-accent q-gutter-sm">
          <q-icon size="2em" name="description" />
          <span>No hay cotizaciones registradas</span>
        </div>
      </template>

      <!-- Columna de Estado con Chip de Color -->
      <template v-slot:body-cell-estado="props">
        <q-td :props="props">
          <q-chip
            :color="getEstadoColor(props.row.estado)"
            text-color="white"
            :icon="getEstadoIcon(props.row.estado)"
            size="sm"
          >
            {{ props.row.estado }}
          </q-chip>
        </q-td>
      </template>

      <!-- Columna de Total -->
      <template v-slot:body-cell-total="props">
        <q-td :props="props">
          <strong>{{ formatearMoneda(props.row.total) }}</strong>
        </q-td>
      </template>

      <!-- Columna de Fecha -->
      <template v-slot:body-cell-fecha="props">
        <q-td :props="props">
          {{ formatearFecha(props.row.fecha) }}
        </q-td>
      </template>

      <!-- Columna de Acciones -->
      <template v-slot:body-cell-acciones="props">
        <q-td :props="props">
          <div class="row q-gutter-xs no-wrap">
            <!-- Botón Ver Detalle -->
            <q-btn
              flat
              round
              dense
              color="primary"
              icon="visibility"
              @click="verDetalle(props.row.id)"
            >
              <q-tooltip>Ver Detalle</q-tooltip>
            </q-btn>

            <!-- Menú de Acciones de Estado -->
            <q-btn-dropdown flat round dense color="grey-7" icon="more_vert" dropdown-icon="none">
              <q-tooltip>Más acciones</q-tooltip>

              <q-list>
                <!-- Marcar como Enviada (solo si es borrador) -->
                <q-item
                  v-if="props.row.estado === 'borrador'"
                  clickable
                  v-close-popup
                  @click="cambiarEstado(props.row.id, 'enviada')"
                >
                  <q-item-section avatar>
                    <q-icon name="send" color="blue" />
                  </q-item-section>
                  <q-item-section>
                    <q-item-label>Marcar como Enviada</q-item-label>
                  </q-item-section>
                </q-item>

                <!-- Aprobar Cotización -->
                <q-item
                  v-if="props.row.estado !== 'aprobada'"
                  clickable
                  v-close-popup
                  @click="cambiarEstado(props.row.id, 'aprobada')"
                >
                  <q-item-section avatar>
                    <q-icon name="check_circle" color="green" />
                  </q-item-section>
                  <q-item-section>
                    <q-item-label>Aprobar Cotización</q-item-label>
                  </q-item-section>
                </q-item>

                <!-- Rechazar Cotización -->
                <q-item
                  v-if="props.row.estado !== 'rechazada'"
                  clickable
                  v-close-popup
                  @click="cambiarEstado(props.row.id, 'rechazada')"
                >
                  <q-item-section avatar>
                    <q-icon name="cancel" color="red" />
                  </q-item-section>
                  <q-item-section>
                    <q-item-label>Rechazar Cotización</q-item-label>
                  </q-item-section>
                </q-item>

                <q-separator />

                <!-- Volver a Borrador -->
                <q-item
                  v-if="props.row.estado !== 'borrador'"
                  clickable
                  v-close-popup
                  @click="cambiarEstado(props.row.id, 'borrador')"
                >
                  <q-item-section avatar>
                    <q-icon name="edit" color="grey" />
                  </q-item-section>
                  <q-item-section>
                    <q-item-label>Volver a Borrador</q-item-label>
                  </q-item-section>
                </q-item>
              </q-list>
            </q-btn-dropdown>
          </div>
        </q-td>
      </template>
    </q-table>

    <!-- Modal de Detalle Cyberpunk -->
    <q-dialog v-model="mostrarDetalle" class="dialog-cyberpunk">
      <q-card class="detalle-card-cyberpunk" style="min-width: 800px; max-width: 90vw">
        <!-- Header del Modal -->
        <q-card-section class="detalle-header">
          <div class="row items-center justify-between">
            <div class="col">
              <div class="detalle-numero">
                <q-icon name="description" class="detalle-icon" />
                Cotización #{{ cotizacion?.id }}
              </div>
              <div class="detalle-fecha">{{ formatearFecha(cotizacion?.fecha) }}</div>
            </div>
            <div class="col-auto">
              <q-chip
                :color="getEstadoColor(cotizacion?.estado)"
                text-color="white"
                :icon="getEstadoIcon(cotizacion?.estado)"
                class="estado-badge-large"
              >
                {{ cotizacion?.estado }}
              </q-chip>
            </div>
          </div>
        </q-card-section>

        <q-separator class="separator-neon" />

        <!-- Datos del Cliente -->
        <q-card-section class="detalle-cliente">
          <div class="section-title">
            <q-icon name="person" />
            Información del Cliente
          </div>
          <div class="cliente-grid">
            <div class="cliente-item">
              <div class="cliente-label">Razón Social:</div>
              <div class="cliente-value">{{ cotizacion?.cliente_nombre }}</div>
            </div>
            <div class="cliente-item">
              <div class="cliente-label">NIT/Cédula:</div>
              <div class="cliente-value">{{ cotizacion?.cliente_nit }}</div>
            </div>
            <div class="cliente-item" v-if="cotizacion?.cliente_telefono">
              <div class="cliente-label">Teléfono:</div>
              <div class="cliente-value">{{ cotizacion?.cliente_telefono }}</div>
            </div>
            <div class="cliente-item" v-if="cotizacion?.cliente_email">
              <div class="cliente-label">Email:</div>
              <div class="cliente-value">{{ cotizacion?.cliente_email }}</div>
            </div>
          </div>
        </q-card-section>

        <q-separator class="separator-neon" />

        <!-- Tabla de Ítems -->
        <q-card-section class="detalle-items">
          <div class="section-title">
            <q-icon name="inventory_2" />
            Productos/Servicios
          </div>

          <q-markup-table flat bordered class="items-table-cyberpunk">
            <thead>
              <tr>
                <th class="text-left">Código</th>
                <th class="text-left">Producto/Servicio</th>
                <th class="text-center">Cantidad</th>
                <th class="text-right">Precio Unit.</th>
                <th class="text-right">Subtotal</th>
              </tr>
            </thead>
            <tbody>
              <tr v-for="item in items" :key="item.id">
                <td class="text-left">
                  <code class="item-codigo">{{ item.codigo }}</code>
                </td>
                <td class="text-left">
                  <div class="item-nombre">{{ item.nombre }}</div>
                  <div class="item-descripcion" v-if="item.descripcion">
                    {{ item.descripcion }}
                  </div>
                </td>
                <td class="text-center">
                  <q-badge color="blue" :label="item.cantidad" />
                </td>
                <td class="text-right">{{ formatearMoneda(item.precio_unitario) }}</td>
                <td class="text-right">
                  <strong class="item-subtotal">{{ formatearMoneda(item.subtotal) }}</strong>
                </td>
              </tr>
            </tbody>
          </q-markup-table>
        </q-card-section>

        <q-separator class="separator-neon" />

        <!-- Totales -->
        <q-card-section class="detalle-totales">
          <div class="totales-grid">
            <div class="total-row">
              <span class="total-label">Subtotal:</span>
              <span class="total-value">{{ formatearMoneda(cotizacion?.subtotal) }}</span>
            </div>
            <div class="total-row">
              <span class="total-label">IVA (19%):</span>
              <span class="total-value">{{ formatearMoneda(cotizacion?.iva) }}</span>
            </div>
            <q-separator class="my-separator" />
            <div class="total-row total-final">
              <span class="total-label">Total:</span>
              <span class="total-value-final">{{ formatearMoneda(cotizacion?.total) }}</span>
            </div>
          </div>
        </q-card-section>

        <!-- Acciones del Modal -->
        <q-card-actions align="right" class="detalle-actions">
          <q-btn
            flat
            label="Imprimir PDF"
            color="cyan"
            icon="print"
            class="action-btn"
            @click="imprimirPDF"
          />
          <q-btn flat label="Cerrar" color="grey" icon="close" class="action-btn" v-close-popup />
        </q-card-actions>
      </q-card>
    </q-dialog>

    <!-- Botón flotante para nueva cotización -->
    <q-page-sticky position="bottom-right" :offset="[18, 18]">
      <q-btn fab icon="add" color="primary" @click="nuevaCotizacion" size="lg">
        <q-tooltip>Nueva Cotización</q-tooltip>
      </q-btn>
    </q-page-sticky>
  </q-page>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue'
import { useCotizadorStore } from 'src/stores/cotizador'
import { useRouter } from 'vue-router'
import { useFormatters } from 'src/composables/useFormatters'
import { useQuasar } from 'quasar'

const router = useRouter()
const cotizadorStore = useCotizadorStore()
const { formatearMoneda, formatearFecha } = useFormatters()
const $q = useQuasar()

// Estados locales
const mostrarDetalle = ref(false)

// Computed
const cotizacion = computed(() => cotizadorStore.cotizacionSeleccionada)
const items = computed(() => cotizadorStore.cotizacionSeleccionada?.items || [])

// Definición de columnas de la tabla
const columns = [
  {
    name: 'id',
    label: '#',
    align: 'left',
    field: 'id',
    sortable: true,
  },
  {
    name: 'fecha',
    label: 'Fecha',
    align: 'left',
    field: 'fecha',
    sortable: true,
  },
  {
    name: 'cliente_nombre',
    label: 'Cliente',
    align: 'left',
    field: 'cliente_nombre',
    sortable: true,
  },
  {
    name: 'cliente_nit',
    label: 'NIT',
    align: 'left',
    field: 'cliente_nit',
    sortable: true,
  },
  {
    name: 'total',
    label: 'Total',
    align: 'right',
    field: 'total',
    sortable: true,
  },
  {
    name: 'estado',
    label: 'Estado',
    align: 'center',
    field: 'estado',
    sortable: true,
  },
  {
    name: 'acciones',
    label: 'Acciones',
    align: 'center',
    field: 'acciones',
  },
]

// Funciones de estado
const getEstadoColor = (estado) => {
  const colores = {
    borrador: 'grey',
    enviada: 'blue',
    aprobada: 'green',
    rechazada: 'red',
  }
  return colores[estado] || 'grey'
}

const getEstadoIcon = (estado) => {
  const iconos = {
    borrador: 'edit',
    enviada: 'send',
    aprobada: 'check_circle',
    rechazada: 'cancel',
  }
  return iconos[estado] || 'help'
}

const cambiarEstado = async (id, nuevoEstado) => {
  // Confirmación antes de cambiar estado
  $q.dialog({
    title: 'Confirmar cambio de estado',
    message: `¿Está seguro de cambiar el estado a "${nuevoEstado}"?`,
    cancel: true,
    persistent: true,
  }).onOk(async () => {
    const resultado = await cotizadorStore.actualizarEstado(id, nuevoEstado)

    if (resultado.success) {
      $q.notify({
        type: 'positive',
        message: `Estado actualizado a "${nuevoEstado}"`,
        position: 'top-right',
        icon: 'check_circle',
      })
    }
    // Los errores se manejan automáticamente por el interceptor
  })
}

const verDetalle = async (id) => {
  await cotizadorStore.fetchDetalleCotizacion(id)
  mostrarDetalle.value = true
}

const imprimirPDF = () => {
  console.log('Imprimir PDF de cotización:', cotizacion.value?.id)
  // TODO: Implementar generación de PDF
}

const nuevaCotizacion = () => {
  router.push('/nueva-cotizacion')
}

// Cargar cotizaciones al montar el componente
onMounted(() => {
  cotizadorStore.fetchCotizaciones()
})
</script>

<style lang="scss" scoped>
.cotizaciones-page {
  background: var(--bg-app);
}

h4 {
  color: var(--text-primary) !important;
}

.tabla-cotizaciones-cyber {
  background: var(--card-bg) !important;
  backdrop-filter: blur(12px) saturate(180%);
  -webkit-backdrop-filter: blur(12px) saturate(180%);
  border: 1px solid var(--border-color) !important;
  border-radius: 12px;
  box-shadow: 0 8px 32px rgba(0, 0, 0, 0.1);

  :deep(.q-table__container),
  :deep(.q-table__middle),
  :deep(.q-table__top),
  :deep(.q-table__bottom),
  :deep(.q-table__card),
  :deep(table),
  :deep(thead),
  :deep(tbody),
  :deep(tr),
  :deep(th),
  :deep(td) {
    background: transparent !important;
    background-color: transparent !important;
  }

  :deep(thead tr) {
    background: var(--glass-color) !important;

    th {
      background: transparent !important;
      background-color: transparent !important;
      color: var(--accent-color) !important;
      font-weight: 600;
      font-size: 14px;
      text-transform: uppercase;
      letter-spacing: 0.5px;
      border-bottom: 2px solid var(--border-color) !important;
      text-shadow: 0 0 8px var(--accent-glow);
    }
  }

  :deep(tbody tr) {
    background: transparent !important;
    background-color: transparent !important;
    border-bottom: 1px solid var(--border-color) !important;
    transition: background 0.2s ease;

    &:hover {
      background: rgba(0, 229, 255, 0.1) !important;
      background-color: rgba(0, 229, 255, 0.1) !important;
    }

    td {
      background: transparent !important;
      background-color: transparent !important;
      color: var(--text-primary) !important;
      opacity: 0.9;
      font-size: 14px;
    }
  }

  :deep(.q-table__bottom) {
    border-top: 1px solid var(--border-color);
    color: var(--text-primary);
    opacity: 0.8;
  }
}

// Modal
.detalle-card-cyberpunk {
  background: var(--card-bg) !important;
  backdrop-filter: blur(16px) saturate(180%);
  -webkit-backdrop-filter: blur(16px) saturate(180%);
  border: 1px solid var(--border-color);
  border-radius: 16px;
  box-shadow: 0 8px 32px rgba(0, 0, 0, 0.2);
  color: var(--text-primary);
}

.detalle-header {
  background: var(--glass-color);
  border-bottom: 1px solid var(--border-color);
}

.detalle-numero {
  font-size: 28px;
  font-weight: 700;
  color: var(--text-primary);
  display: flex;
  align-items: center;
  gap: 12px;
  text-shadow: 0 0 10px var(--accent-glow);
}

.detalle-icon {
  color: var(--accent-color);
  font-size: 32px;
  filter: drop-shadow(0 0 8px var(--accent-glow));
}

.detalle-fecha {
  color: var(--text-primary);
  font-size: 14px;
  margin-top: 4px;
  opacity: 0.7;
}

.estado-badge-large {
  font-size: 14px;
  padding: 8px 16px;
  text-transform: uppercase;
  font-weight: 600;
}

.separator-neon {
  background: linear-gradient(90deg, transparent, var(--border-color), transparent);
  height: 1px;
}

.detalle-cliente {
  background: rgba(0, 0, 0, 0.05);
}

.section-title {
  font-size: 18px;
  font-weight: 600;
  color: var(--accent-color);
  margin-bottom: 16px;
  display: flex;
  align-items: center;
  gap: 8px;

  .q-icon {
    filter: drop-shadow(0 0 6px var(--accent-glow));
  }
}

.cliente-grid {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
  gap: 16px;
}

.cliente-item {
  .cliente-label {
    font-size: 12px;
    color: var(--text-primary);
    opacity: 0.6;
    margin-bottom: 4px;
    text-transform: uppercase;
    letter-spacing: 0.5px;
  }

  .cliente-value {
    font-size: 16px;
    color: var(--text-primary);
    font-weight: 500;
  }
}

.detalle-items {
  padding: 20px;
}

.items-table-cyberpunk {
  background: transparent !important;
  border: 1px solid var(--border-color);
  border-radius: 8px;
  overflow: hidden;

  thead {
    background: var(--glass-color);

    th {
      color: var(--accent-color) !important;
      font-weight: 600;
      font-size: 13px;
      text-transform: uppercase;
      letter-spacing: 0.5px;
      padding: 12px;
      border-bottom: 2px solid var(--border-color);
    }
  }

  tbody {
    tr {
      border-bottom: 1px solid var(--border-color);
      transition: background 0.2s ease;

      &:hover {
        background: rgba(0, 229, 255, 0.05);
      }

      td {
        color: var(--text-primary);
        opacity: 0.8;
        padding: 12px;
      }
    }
  }
}

.item-codigo {
  background: rgba(0, 229, 255, 0.15);
  color: var(--accent-color);
  padding: 4px 8px;
  border-radius: 4px;
  font-family: 'Courier New', monospace;
  font-size: 13px;
  border: 1px solid var(--border-color);
}

.item-nombre {
  color: var(--text-primary);
  font-weight: 500;
  font-size: 14px;
}

.item-descripcion {
  color: var(--text-primary);
  font-size: 12px;
  opacity: 0.6;
  margin-top: 2px;
}

.item-subtotal {
  color: var(--accent-color);
  font-size: 15px;
  text-shadow: 0 0 8px var(--accent-glow);
}

.detalle-totales {
  background: rgba(0, 0, 0, 0.05);
  padding: 24px;
}

.totales-grid {
  max-width: 400px;
  margin-left: auto;
}

.total-row {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 8px 0;

  &.total-final {
    padding: 16px 0 8px 0;
  }
}

.total-label {
  color: var(--text-primary);
  opacity: 0.8;
  font-size: 16px;
  font-weight: 500;
}

.total-value {
  color: var(--text-primary);
  font-size: 16px;
  font-weight: 600;
}

.total-value-final {
  color: var(--accent-color);
  font-size: 28px;
  font-weight: 700;
  text-shadow: 0 0 12px var(--accent-glow);
}

.my-separator {
  background: var(--border-color);
  margin: 8px 0;
}

.detalle-actions {
  background: rgba(0, 0, 0, 0.05);
  padding: 16px 24px;
  border-top: 1px solid var(--border-color);
}

.action-btn {
  font-weight: 600;
  transition: all 0.3s ease;

  &:hover {
    box-shadow: 0 0 12px var(--accent-glow);
  }
}

@media (max-width: 768px) {
  .detalle-card-cyberpunk {
    min-width: 100% !important;
  }

  .cliente-grid {
    grid-template-columns: 1fr;
  }

  .totales-grid {
    max-width: 100%;
  }
}
</style>
