<template>
  <q-page class="q-pa-md nueva-cotizacion-page">
    <!-- Header -->
    <div class="page-header q-mb-md">
      <h4 class="page-title">
        <q-icon name="point_of_sale" class="title-icon" />
        Nueva Cotización
      </h4>
      <div class="page-subtitle">Sistema DIAN - Régimen Tributario por Ítem</div>
    </div>

    <!-- Sección Cliente -->
    <q-card class="q-mb-md card-cyber">
      <q-card-section>
        <div class="text-h6 q-mb-md">Cliente</div>
        <q-select
          v-model="cotizadorStore.cliente"
          :options="clientesFiltrados"
          option-label="razon_social"
          label="Seleccionar Cliente *"
          outlined
          use-input
          input-debounce="300"
          @filter="filtrarClientes"
          :loading="clientesStore.loading"
        >
          <template v-slot:no-option>
            <q-item>
              <q-item-section class="text-grey"> No se encontraron clientes </q-item-section>
            </q-item>
          </template>

          <template v-slot:option="scope">
            <q-item v-bind="scope.itemProps">
              <q-item-section>
                <q-item-label>{{ scope.opt.razon_social }}</q-item-label>
                <q-item-label caption>{{ scope.opt.nit_cedula }}</q-item-label>
              </q-item-section>
            </q-item>
          </template>

          <template v-slot:selected-item="scope">
            <div>
              <div class="text-weight-medium">{{ scope.opt.razon_social }}</div>
              <div class="text-caption text-grey">{{ scope.opt.nit_cedula }}</div>
            </div>
          </template>
        </q-select>
      </q-card-section>
    </q-card>

    <!-- Sección Buscador -->
    <q-card class="q-mb-md card-cyber">
      <q-card-section>
        <div class="text-h6 q-mb-md">Agregar Productos/Servicios</div>
        <q-select
          v-model="itemSeleccionado"
          :options="inventarioFiltrado"
          option-label="nombre"
          label="Buscar producto o servicio"
          outlined
          use-input
          input-debounce="300"
          @filter="filtrarInventario"
          @update:model-value="agregarItemSeleccionado"
          :loading="inventarioStore.loading"
          clearable
        >
          <template v-slot:no-option>
            <q-item>
              <q-item-section class="text-grey"> No se encontraron ítems </q-item-section>
            </q-item>
          </template>

          <template v-slot:option="scope">
            <q-item v-bind="scope.itemProps">
              <q-item-section>
                <q-item-label>{{ scope.opt.nombre }}</q-item-label>
                <q-item-label caption>
                  {{ scope.opt.codigo }} - {{ formatearPrecio(scope.opt.precio_base) }}
                </q-item-label>
              </q-item-section>
              <q-item-section side>
                <q-badge
                  :color="scope.opt.categoria === 'producto' ? 'blue' : 'green'"
                  :label="scope.opt.categoria === 'producto' ? 'Producto' : 'Servicio'"
                />
              </q-item-section>
            </q-item>
          </template>
        </q-select>
      </q-card-section>
    </q-card>

    <!-- Tabla de Items -->
    <q-card class="q-mb-md card-cyber">
      <q-card-section>
        <div class="text-h6 q-mb-md">Ítems de la Cotización</div>

        <q-table
          :rows="cotizadorStore.itemsConCalculos"
          :columns="columns"
          row-key="id"
          flat
          bordered
          :hide-pagination="true"
          class="tabla-items-cyber"
        >
          <template v-slot:no-data>
            <div class="full-width row flex-center text-accent q-gutter-sm q-pa-md">
              <q-icon size="2em" name="shopping_cart" />
              <span>No hay ítems agregados</span>
            </div>
          </template>

          <!-- Columna de Cantidad (editable) -->
          <template v-slot:body-cell-cantidad="props">
            <q-td :props="props">
              <q-input
                v-model.number="props.row.cantidad"
                type="number"
                dense
                outlined
                min="1"
                style="width: 80px"
                @update:model-value="
                  (val) => cotizadorStore.actualizarCantidad(props.rowIndex, val)
                "
              />
            </q-td>
          </template>

          <!-- Columna de Precio (editable) -->
          <template v-slot:body-cell-precio="props">
            <q-td :props="props">
              <q-input
                v-model.number="props.row.precio"
                type="number"
                dense
                outlined
                prefix="$"
                min="0"
                style="width: 120px"
                @update:model-value="(val) => cotizadorStore.actualizarPrecio(props.rowIndex, val)"
              />
            </q-td>
          </template>

          <!-- Columna de Régimen Tributario -->
          <template v-slot:body-cell-regimen="props">
            <q-td :props="props">
              <q-select
                v-model="props.row.regimen"
                :options="regimenOptions"
                dense
                outlined
                style="min-width: 180px"
                @update:model-value="(val) => cotizadorStore.actualizarRegimen(props.rowIndex, val)"
              />
            </q-td>
          </template>

          <!-- Columna de AIU -->
          <template v-slot:body-cell-aiu="props">
            <q-td :props="props">
              <div v-if="requiereAIU(props.row.regimen)">
                <q-btn
                  flat
                  dense
                  icon="settings"
                  color="cyan"
                  @click="abrirDialogoAIU(props.rowIndex)"
                >
                  <q-tooltip>Configurar AIU</q-tooltip>
                </q-btn>
                <div class="text-caption">{{ calcularAIUTotal(props.row.aiu) }}%</div>
                <div v-if="calcularAIUTotal(props.row.aiu) < 10" class="text-caption text-orange">
                  ⚠️ Mínimo 10%
                </div>
              </div>
              <div v-else class="text-grey-5">N/A</div>
            </q-td>
          </template>

          <!-- Columna de Subtotal (calculado) -->
          <template v-slot:body-cell-subtotal="props">
            <q-td :props="props">
              <strong>{{ formatearPrecio(props.row.subtotal) }}</strong>
            </q-td>
          </template>

          <!-- Columna de Base Gravable -->
          <template v-slot:body-cell-base_gravable="props">
            <q-td :props="props">
              {{ formatearPrecio(props.row.base_gravable) }}
            </q-td>
          </template>

          <!-- Columna de IVA -->
          <template v-slot:body-cell-iva="props">
            <q-td :props="props">
              {{ formatearPrecio(props.row.iva) }}
            </q-td>
          </template>

          <!-- Columna de Acciones -->
          <template v-slot:body-cell-acciones="props">
            <q-td :props="props">
              <q-btn
                flat
                round
                dense
                color="negative"
                icon="delete"
                @click="cotizadorStore.removerItem(props.rowIndex)"
              >
                <q-tooltip>Eliminar</q-tooltip>
              </q-btn>
            </q-td>
          </template>
        </q-table>

        <!-- Footer: Totales -->
        <div class="q-mt-lg q-pa-md bg-grey-2 rounded-borders">
          <div class="row justify-end q-gutter-y-sm">
            <div class="col-12 col-md-5">
              <!-- Subtotal -->
              <div class="row justify-between items-center">
                <div class="text-subtitle1">Subtotal:</div>
                <div class="text-h6">{{ formatearPrecio(cotizadorStore.subtotal) }}</div>
              </div>

              <!-- Desglose por régimen (colapsable) -->
              <q-expansion-item
                v-if="Object.keys(cotizadorStore.desgloseRegimen).length > 1"
                label="Ver desglose por régimen"
                dense
                class="text-caption q-mt-sm"
              >
                <div
                  v-for="(datos, regimen) in cotizadorStore.desgloseRegimen"
                  :key="regimen"
                  class="q-ml-md q-mt-xs"
                >
                  <div class="row justify-between">
                    <div class="text-weight-medium">{{ nombreRegimen(regimen) }}:</div>
                    <div>{{ formatearPrecio(datos.subtotal) }}</div>
                  </div>
                  <div class="row justify-between q-ml-md text-grey-7">
                    <div>Base Gravable:</div>
                    <div>{{ formatearPrecio(datos.base_gravable) }}</div>
                  </div>
                  <div class="row justify-between q-ml-md text-grey-7">
                    <div>IVA:</div>
                    <div>{{ formatearPrecio(datos.iva) }}</div>
                  </div>
                </div>
              </q-expansion-item>

              <!-- IVA Total -->
              <div class="row justify-between items-center q-mt-sm">
                <div class="text-subtitle1">IVA Total:</div>
                <div class="text-h6">{{ formatearPrecio(cotizadorStore.iva) }}</div>
              </div>

              <q-separator class="q-my-sm" />

              <!-- Total a Pagar -->
              <div class="row justify-between items-center">
                <div class="text-h6 text-weight-bold">Total a Pagar:</div>
                <div class="text-h5 text-primary text-weight-bold">
                  {{ formatearPrecio(cotizadorStore.total) }}
                </div>
              </div>
            </div>
          </div>
        </div>
      </q-card-section>
    </q-card>

    <!-- Botones de acción -->
    <div class="row justify-end q-gutter-sm">
      <q-btn label="Cancelar" color="grey" outline @click="cancelar" />
      <q-btn
        label="Guardar Cotización"
        color="primary"
        icon="save"
        :disable="!cotizadorStore.puedeGuardar"
        :loading="cotizadorStore.loading"
        @click="guardarCotizacion"
      />
    </div>

    <!-- Diálogo de configuración AIU -->
    <q-dialog v-model="mostrarDialogoAIU">
      <q-card class="card-cyber" style="min-width: 450px" dark>
        <q-card-section>
          <div class="text-h6">Configurar AIU</div>
          <div class="text-caption text-grey-5">
            {{ cotizadorStore.items[itemSeleccionadoAIU]?.nombre }}
          </div>
        </q-card-section>

        <q-card-section class="q-pt-none">
          <q-input
            v-model.number="aiuTemp.administracion"
            label="Administración (%)"
            type="number"
            outlined
            dense
            dark
            suffix="%"
            min="0"
            max="100"
            :rules="[(val) => val >= 0 || 'Debe ser positivo']"
          />
          <q-input
            v-model.number="aiuTemp.imprevistos"
            label="Imprevistos (%)"
            type="number"
            outlined
            dense
            suffix="%"
            min="0"
            max="100"
            class="q-mt-md"
            :rules="[(val) => val >= 0 || 'Debe ser positivo']"
          />
          <q-input
            v-model.number="aiuTemp.utilidad"
            label="Utilidad (%)"
            type="number"
            outlined
            dense
            suffix="%"
            min="0"
            max="100"
            class="q-mt-md"
            :rules="[(val) => val >= 0 || 'Debe ser positivo']"
          />

          <q-banner v-if="aiuTotalPorcentaje < 10" class="bg-orange-9 text-white q-mt-md" rounded>
            <template v-slot:avatar>
              <q-icon name="warning" />
            </template>
            ⚠️ DIAN: El AIU total debe ser mínimo 10%
          </q-banner>

          <div class="q-mt-md text-caption">
            <strong>Total AIU:</strong> {{ aiuTotalPorcentaje }}%
          </div>
        </q-card-section>

        <q-card-actions align="right">
          <q-btn label="Cancelar" flat v-close-popup />
          <q-btn label="Aplicar" color="primary" @click="aplicarAIU" v-close-popup />
        </q-card-actions>
      </q-card>
    </q-dialog>
  </q-page>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue'
import { useCotizadorStore, REGIMEN_TRIBUTARIO } from 'src/stores/cotizador'
import { useClientesStore } from 'src/stores/clientes'
import { useInventarioStore } from 'src/stores/inventario'
import { useQuasar } from 'quasar'
import { useRouter } from 'vue-router'
import { useFormatters } from 'src/composables/useFormatters'

const $q = useQuasar()
const router = useRouter()
const cotizadorStore = useCotizadorStore()
const clientesStore = useClientesStore()
const inventarioStore = useInventarioStore()
const { formatearMoneda: formatearPrecio } = useFormatters()

// Estados locales
const clientesFiltrados = ref([])
const inventarioFiltrado = ref([])
const itemSeleccionado = ref(null)
const mostrarDialogoAIU = ref(false)
const itemSeleccionadoAIU = ref(null)
const aiuTemp = ref({ administracion: 10, imprevistos: 5, utilidad: 5 })

// Opciones de régimen tributario
const regimenOptions = [
  { label: 'Venta Directa', value: REGIMEN_TRIBUTARIO.VENTA_DIRECTA },
  { label: 'Servicio General', value: REGIMEN_TRIBUTARIO.SERVICIO_GENERAL },
  { label: 'Servicio Especial (AIU)', value: REGIMEN_TRIBUTARIO.SERVICIO_ESPECIAL },
  { label: 'Obra/Construcción', value: REGIMEN_TRIBUTARIO.OBRA_CONSTRUCCION },
]

// Computed
const aiuTotalPorcentaje = computed(() => {
  return aiuTemp.value.administracion + aiuTemp.value.imprevistos + aiuTemp.value.utilidad
})

// Definición de columnas de la tabla
const columns = [
  {
    name: 'codigo',
    label: 'Código',
    align: 'left',
    field: 'codigo',
    sortable: true,
  },
  {
    name: 'nombre',
    label: 'Nombre',
    align: 'left',
    field: 'nombre',
    sortable: true,
  },
  {
    name: 'cantidad',
    label: 'Cantidad',
    align: 'center',
    field: 'cantidad',
  },
  {
    name: 'precio',
    label: 'Precio Unit.',
    align: 'right',
    field: 'precio',
  },
  {
    name: 'regimen',
    label: 'Régimen Tributario',
    align: 'left',
    field: 'regimen',
  },
  {
    name: 'aiu',
    label: 'AIU',
    align: 'center',
  },
  {
    name: 'subtotal',
    label: 'Subtotal',
    align: 'right',
    field: 'subtotal',
  },
  {
    name: 'base_gravable',
    label: 'Base Gravable',
    align: 'right',
    field: 'base_gravable',
  },
  {
    name: 'iva',
    label: 'IVA',
    align: 'right',
    field: 'iva',
  },
  {
    name: 'acciones',
    label: '',
    align: 'center',
  },
]

// Funciones
const filtrarClientes = (val, update) => {
  update(() => {
    if (val === '') {
      clientesFiltrados.value = clientesStore.clientes
    } else {
      const needle = val.toLowerCase()
      clientesFiltrados.value = clientesStore.clientes.filter(
        (c) =>
          c.razon_social.toLowerCase().includes(needle) ||
          c.nit_cedula.toLowerCase().includes(needle),
      )
    }
  })
}

const filtrarInventario = (val, update) => {
  update(() => {
    if (val === '') {
      inventarioFiltrado.value = inventarioStore.items
    } else {
      const needle = val.toLowerCase()
      inventarioFiltrado.value = inventarioStore.items.filter(
        (i) => i.nombre.toLowerCase().includes(needle) || i.codigo.toLowerCase().includes(needle),
      )
    }
  })
}

const agregarItemSeleccionado = (item) => {
  if (item) {
    cotizadorStore.agregarItem(item)
    itemSeleccionado.value = null // Limpiar selección

    $q.notify({
      type: 'positive',
      message: `${item.nombre} agregado`,
      position: 'top-right',
      timeout: 1000,
    })
  }
}

const requiereAIU = (regimen) => {
  return (
    regimen === REGIMEN_TRIBUTARIO.SERVICIO_ESPECIAL ||
    regimen === REGIMEN_TRIBUTARIO.OBRA_CONSTRUCCION
  )
}

const calcularAIUTotal = (aiu) => {
  if (!aiu) return 0
  return aiu.administracion + aiu.imprevistos + aiu.utilidad
}

const abrirDialogoAIU = (index) => {
  itemSeleccionadoAIU.value = index
  aiuTemp.value = { ...cotizadorStore.items[index].aiu }
  mostrarDialogoAIU.value = true
}

const aplicarAIU = () => {
  cotizadorStore.actualizarAIU(itemSeleccionadoAIU.value, aiuTemp.value)
  $q.notify({
    type: 'positive',
    message: 'Porcentajes AIU actualizados',
    position: 'top-right',
    timeout: 1000,
  })
}

const nombreRegimen = (regimen) => {
  const nombres = {
    [REGIMEN_TRIBUTARIO.VENTA_DIRECTA]: 'Venta Directa',
    [REGIMEN_TRIBUTARIO.SERVICIO_GENERAL]: 'Servicio General',
    [REGIMEN_TRIBUTARIO.SERVICIO_ESPECIAL]: 'Servicio Especial',
    [REGIMEN_TRIBUTARIO.OBRA_CONSTRUCCION]: 'Obra/Construcción',
  }
  return nombres[regimen] || regimen
}

const guardarCotizacion = async () => {
  const resultado = await cotizadorStore.crearCotizacion()

  if (resultado.success) {
    $q.notify({
      type: 'positive',
      message: 'Cotización guardada exitosamente',
      position: 'top-right',
    })

    // Redirigir al historial
    router.push('/cotizaciones')
  } else {
    $q.notify({
      type: 'negative',
      message: resultado.error || 'Error al guardar la cotización',
      position: 'top-right',
    })
  }
}

const cancelar = () => {
  $q.dialog({
    title: 'Confirmar',
    message: '¿Desea cancelar esta cotización? Se perderán los datos.',
    cancel: true,
    persistent: true,
  }).onOk(() => {
    cotizadorStore.limpiarCotizacion()
    router.push('/cotizaciones')
  })
}

// Cargar datos al montar el componente
onMounted(async () => {
  await clientesStore.fetchClientes()
  await inventarioStore.fetchAllInventario()
  clientesFiltrados.value = clientesStore.clientes
  inventarioFiltrado.value = inventarioStore.items
})
</script>

<style lang="scss" scoped>
.nueva-cotizacion-page-cyber {
  background: var(--bg-app);
  min-height: 100vh;
}

// ==========================================
// PAGE HEADER
// ==========================================
.page-header {
  margin-bottom: 24px;
}

.page-title {
  color: var(--text-primary);
  font-size: 32px;
  font-weight: 700;
  margin: 0 0 8px 0;
  display: flex;
  align-items: center;
  gap: 16px;

  .title-icon {
    color: var(--accent-color);
    font-size: 40px;
    filter: drop-shadow(0 0 12px var(--accent-glow));
  }
}

.page-subtitle {
  color: var(--text-primary);
  font-size: 14px;
  opacity: 0.7;
  margin-left: 56px;
}

// ==========================================
// CARDS
// ==========================================
.card-cyber {
  background: var(--card-bg) !important;
  backdrop-filter: blur(12px);
  -webkit-backdrop-filter: blur(12px);
  border: 1px solid var(--border-color);
  border-radius: 12px;
  transition: all 0.3s ease;

  &:hover {
    border-color: var(--accent-color);
    box-shadow: 0 4px 20px var(--accent-glow);
  }

  :deep(.text-h6) {
    color: var(--accent-color);
    text-shadow: 0 0 10px var(--accent-glow);
  }
}

// ==========================================
// TABLE
// ==========================================
.tabla-items-cyber {
  background: transparent !important;

  :deep(thead) {
    tr {
      background: var(--glass-color);

      th {
        color: var(--accent-color) !important;
        font-weight: 600;
        font-size: 11px;
        text-transform: uppercase;
        letter-spacing: 0.5px;
        border-bottom: 2px solid var(--border-color);
        padding: 12px 8px;
      }
    }
  }

  :deep(tbody) {
    tr {
      border-bottom: 1px solid var(--border-color);
      transition: background 0.2s ease;

      &:hover {
        background: rgba(0, 229, 255, 0.08);
      }

      td {
        color: var(--text-primary);
        opacity: 0.9;
        font-size: 13px;
        padding: 8px;
      }
    }
  }

  // Inputs dentro de la tabla
  :deep(.q-field) {
    .q-field__control {
      background: rgba(0, 0, 0, 0.1);
      border: 1px solid var(--border-color);

      &:hover {
        border-color: var(--accent-color);
      }
    }

    .q-field__native {
      color: var(--text-primary);
    }
  }

  // Selects dentro de la tabla
  :deep(.q-select) {
    .q-field__control {
      background: rgba(0, 0, 0, 0.1);
      border: 1px solid var(--border-color);
    }
  }
}

// ==========================================
// TOTALS SECTION
// ==========================================
.bg-grey-2 {
  background: var(--card-bg) !important;
  border: 1px solid var(--border-color);
  border-radius: 8px;
  color: var(--text-primary) !important;

  .text-subtitle1 {
    color: var(--text-primary);
    opacity: 0.8;
  }

  .text-h6,
  .text-h5 {
    color: var(--text-primary);
  }

  .text-primary {
    color: var(--accent-color) !important;
    text-shadow: 0 0 10px var(--accent-glow);
  }

  .text-grey-7 {
    color: var(--text-primary);
    opacity: 0.6;
  }
}

// ==========================================
// EXPANSION ITEM
// ==========================================
:deep(.q-expansion-item) {
  .q-item {
    color: var(--text-primary);
    opacity: 0.8;
  }

  .q-expansion-item__content {
    color: var(--text-primary);
    opacity: 0.8;
  }
}

// ==========================================
// BUTTONS
// ==========================================
:deep(.q-btn) {
  &.bg-primary {
    background: linear-gradient(135deg, var(--accent-color), var(--accent-color)) !important;
    color: var(--text-primary);
    font-weight: 600;

    &:hover {
      box-shadow: 0 0 20px var(--accent-glow);
    }
  }
}

// ==========================================
// INPUTS & SELECTS
// ==========================================
:deep(.q-field) {
  .q-field__control {
    color: var(--text-primary);

    &:before {
      border-color: var(--border-color);
    }

    &:hover:before {
      border-color: var(--accent-color);
    }
  }

  .q-field__label {
    color: var(--text-primary);
    opacity: 0.7;
  }

  .q-field__native {
    color: var(--text-primary);
  }

  &.q-field--focused {
    .q-field__control:before {
      border-color: var(--accent-color);
    }
  }
}

// ==========================================
// DIALOG
// ==========================================
:deep(.q-dialog) {
  .card-cyber {
    .q-card__section {
      color: var(--text-primary);
    }
  }
}

// ==========================================
// BADGES & BANNERS
// ==========================================
:deep(.q-badge) {
  font-weight: 600;
}

:deep(.q-banner) {
  border-radius: 8px;
}

// ==========================================
// RESPONSIVE
// ==========================================
@media (max-width: 768px) {
  .page-title {
    font-size: 24px;

    .title-icon {
      font-size: 32px;
    }
  }

  .page-subtitle {
    font-size: 12px;
    margin-left: 48px;
  }

  .tabla-items-cyber {
    :deep(thead th) {
      font-size: 10px;
      padding: 8px 4px;
    }

    :deep(tbody td) {
      font-size: 12px;
      padding: 6px 4px;
    }
  }
}
</style>
