<template>
  <q-page class="q-pa-md inventario-page">
    <div class="q-mb-md">
      <h4 class="q-my-md">Gestión de Inventario</h4>
    </div>

    <!-- Buscador Responsivo -->
    <div class="row q-mb-md">
      <div class="col-12 col-md-auto" style="flex: 1">
        <q-input
          v-model="filter"
          outlined
          placeholder="Buscar por código, nombre o descripción..."
          clearable
          @update:model-value="onSearch"
        >
          <template v-slot:prepend>
            <q-icon name="search" />
          </template>
        </q-input>
      </div>
    </div>

    <!-- Botones de Importación/Exportación -->
    <div class="row q-mb-md q-gutter-sm">
      <q-btn
        color="secondary"
        icon="download"
        label="Descargar Plantilla"
        @click="descargarPlantilla"
        :disable="inventarioStore.loading"
      >
        <q-tooltip>Descargar plantilla Excel para importar ítems</q-tooltip>
      </q-btn>
      <q-btn
        color="primary"
        icon="upload"
        label="Importar"
        @click="abrirDialogoImportacion"
        :disable="inventarioStore.loading"
      >
        <q-tooltip>Importar ítems desde archivo Excel</q-tooltip>
      </q-btn>
    </div>

    <!-- Tabla de inventario con modo grid responsivo -->
    <q-table
      :rows="inventarioStore.items"
      :columns="columns"
      row-key="id"
      :loading="inventarioStore.loading"
      v-model:pagination="pagination"
      @request="onRequest"
      :rows-per-page-options="[10, 50, 100, 1000]"
      :grid="$q.screen.lt.md"
      flat
      bordered
      class="shadow-2 tabla-inventario-cyber"
    >
      <template v-slot:loading>
        <q-inner-loading showing color="primary" />
      </template>

      <template v-slot:no-data>
        <div class="full-width row flex-center text-accent q-gutter-sm q-pa-md">
          <q-icon size="2em" name="warning" />
          <span>No hay ítems en el inventario</span>
        </div>
      </template>

      <!-- Vista Grid para Móviles (Cards) -->
      <template v-slot:item="props">
        <div class="col-12 col-sm-6 col-md-4 q-pa-xs">
          <q-card class="inventory-card-mobile" :class="'card-' + props.row.tipo">
            <!-- Header de la Card -->
            <q-card-section class="card-header">
              <div class="row items-center">
                <div class="col">
                  <div class="item-nombre-mobile">{{ props.row.nombre }}</div>
                  <div class="item-codigo-mobile">
                    <q-icon name="qr_code" size="14px" />
                    {{ props.row.codigo }}
                  </div>
                </div>
                <div class="col-auto">
                  <q-chip
                    :color="props.row.tipo === 'producto' ? 'blue' : 'green'"
                    text-color="white"
                    size="sm"
                    :icon="props.row.tipo === 'producto' ? 'inventory_2' : 'build'"
                  >
                    {{ props.row.tipo === 'producto' ? 'Producto' : 'Servicio' }}
                  </q-chip>
                </div>
              </div>
            </q-card-section>

            <q-separator class="card-separator" />

            <!-- Cuerpo de la Card -->
            <q-card-section class="card-body">
              <div class="item-descripcion-mobile" v-if="props.row.descripcion">
                {{ props.row.descripcion }}
              </div>
              <div class="item-precio-mobile">
                <q-icon name="attach_money" size="20px" class="precio-icon" />
                {{ formatearMoneda(props.row.precio_base) }}
              </div>
            </q-card-section>

            <!-- Footer de la Card con Acciones -->
            <q-card-actions align="right" class="card-actions">
              <q-btn
                flat
                round
                dense
                color="primary"
                icon="edit"
                size="sm"
                @click="editarItem(props.row)"
              >
                <q-tooltip>Editar</q-tooltip>
              </q-btn>
              <q-btn flat round dense color="negative" icon="delete" size="sm">
                <q-tooltip>Eliminar</q-tooltip>
              </q-btn>
            </q-card-actions>
          </q-card>
        </div>
      </template>

      <!-- Columna personalizada para Tipo (Vista Tabla) -->
      <template v-slot:body-cell-tipo="props">
        <q-td :props="props">
          <q-badge
            :color="props.row.tipo === 'producto' ? 'blue' : 'green'"
            :label="props.row.tipo === 'producto' ? 'Producto' : 'Servicio'"
          />
        </q-td>
      </template>

      <!-- Columna personalizada para Precio (Vista Tabla) -->
      <template v-slot:body-cell-precio_base="props">
        <q-td :props="props">
          {{ formatearMoneda(props.row.precio_base) }}
        </q-td>
      </template>

      <!-- Columna personalizada para Acciones (Vista Tabla) -->
      <template v-slot:body-cell-acciones="props">
        <q-td :props="props">
          <q-btn
            flat
            round
            dense
            color="primary"
            icon="edit"
            size="sm"
            @click="editarItem(props.row)"
          >
            <q-tooltip>Editar Ítem</q-tooltip>
          </q-btn>
        </q-td>
      </template>
    </q-table>

    <!-- Botón flotante para agregar ítem -->
    <q-page-sticky position="bottom-right" :offset="[18, 18]">
      <q-btn fab icon="add" color="primary" @click="abrirDialogo" size="lg">
        <q-tooltip>Agregar Ítem</q-tooltip>
      </q-btn>
    </q-page-sticky>

    <!-- Diálogo para crear ítem -->
    <q-dialog v-model="dialogoAbierto" persistent>
      <q-card style="min-width: 500px; max-width: 90vw" class="modal-cyber">
        <q-card-section class="row items-center q-pb-none">
          <div class="text-h6">{{ modoEdicion ? 'Editar Ítem' : 'Nuevo Ítem de Inventario' }}</div>
          <q-space />
          <q-btn icon="close" flat round dense v-close-popup />
        </q-card-section>

        <q-card-section>
          <q-form @submit="guardarItem" class="q-gutter-md">
            <q-input
              v-model="nuevoItem.codigo"
              label="Código *"
              outlined
              :rules="[(val) => !!val || 'Campo requerido']"
              hint="Código único del ítem"
            />

            <q-input
              v-model="nuevoItem.nombre"
              label="Nombre *"
              outlined
              :rules="[(val) => !!val || 'Campo requerido']"
            />

            <q-select
              v-model="nuevoItem.tipo"
              :options="tiposOptions"
              label="Tipo *"
              outlined
              emit-value
              map-options
              :rules="[(val) => !!val || 'Campo requerido']"
            />

            <q-input
              v-model.number="nuevoItem.precio_base"
              label="Precio Base *"
              outlined
              type="number"
              prefix="$"
              :rules="[(val) => val >= 0 || 'El precio debe ser mayor o igual a 0']"
            />

            <q-input
              v-model="nuevoItem.descripcion"
              label="Descripción"
              outlined
              type="textarea"
              rows="3"
            />

            <div class="row justify-end q-gutter-sm q-mt-md">
              <q-btn label="Cancelar" color="grey" flat @click="cerrarDialogo" />
              <q-btn
                label="Guardar"
                type="submit"
                color="primary"
                :loading="inventarioStore.loading"
              />
            </div>
          </q-form>
        </q-card-section>
      </q-card>
    </q-dialog>

    <!-- Diálogo de Importación -->
    <q-dialog v-model="dialogoImportacionAbierto" persistent>
      <q-card style="min-width: 500px; max-width: 90vw" class="modal-cyber">
        <q-card-section class="row items-center q-pb-none">
          <div class="text-h6">Importar Inventario</div>
          <q-space />
          <q-btn icon="close" flat round dense v-close-popup />
        </q-card-section>

        <q-card-section>
          <div class="q-mb-md">
            <p class="text-body2" style="opacity: 0.8">
              Seleccione un archivo Excel (.xlsx) con el formato de la plantilla. El archivo debe
              contener las columnas:
              <strong>Código</strong>, <strong>Nombre</strong>, <strong>Descripción</strong>,
              <strong>Precio Base</strong>, <strong>Tipo</strong>.
            </p>
          </div>

          <q-file
            v-model="archivoImportacion"
            outlined
            label="Seleccionar archivo Excel"
            accept=".xlsx,.xls"
            :disable="importando"
          >
            <template v-slot:prepend>
              <q-icon name="attach_file" />
            </template>
          </q-file>

          <!-- Resultados de la importación -->
          <div v-if="resultadosImportacion" class="q-mt-md">
            <q-separator class="q-mb-md" />
            <div class="text-h6 q-mb-sm">Resultados de la Importación</div>

            <div class="q-mb-sm">
              <q-chip color="blue" text-color="white" icon="info">
                Total: {{ resultadosImportacion.total }}
              </q-chip>
              <q-chip color="positive" text-color="white" icon="check_circle">
                Exitosos: {{ resultadosImportacion.success }}
              </q-chip>
              <q-chip color="negative" text-color="white" icon="error">
                Errores: {{ resultadosImportacion.errors }}
              </q-chip>
            </div>

            <!-- Detalles de errores -->
            <div
              v-if="
                resultadosImportacion.error_details &&
                resultadosImportacion.error_details.length > 0
              "
            >
              <div class="text-subtitle2 q-mb-sm">Detalles de Errores:</div>
              <q-list
                bordered
                separator
                class="rounded-borders"
                style="max-height: 300px; overflow-y: auto"
              >
                <q-item v-for="(error, index) in resultadosImportacion.error_details" :key="index">
                  <q-item-section>
                    <q-item-label>
                      <strong>Línea {{ error.line }}</strong>
                      <span v-if="error.codigo"> - Código: {{ error.codigo }}</span>
                    </q-item-label>
                    <q-item-label caption>
                      <ul class="q-ma-none q-pl-md">
                        <li v-for="(err, idx) in error.errors" :key="idx">{{ err }}</li>
                      </ul>
                    </q-item-label>
                  </q-item-section>
                </q-item>
              </q-list>
            </div>
          </div>

          <div class="row justify-end q-gutter-sm q-mt-md">
            <q-btn
              label="Cancelar"
              color="grey"
              flat
              @click="cerrarDialogoImportacion"
              :disable="importando"
            />
            <q-btn
              label="Importar"
              color="primary"
              @click="procesarImportacion"
              :loading="importando"
              :disable="!archivoImportacion"
            />
          </div>
        </q-card-section>
      </q-card>
    </q-dialog>
  </q-page>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import { useInventarioStore } from 'src/stores/inventario'
import { useQuasar } from 'quasar'
import { useFormatters } from 'src/composables/useFormatters'

const $q = useQuasar()
const inventarioStore = useInventarioStore()
const { formatearMoneda } = useFormatters()

// Estado del diálogo
const dialogoAbierto = ref(false)
const modoEdicion = ref(false)
const itemEditandoId = ref(null)

// Estado del diálogo de importación
const dialogoImportacionAbierto = ref(false)
const archivoImportacion = ref(null)
const importando = ref(false)
const resultadosImportacion = ref(null)

// Filtro de búsqueda
const filter = ref('')

// Paginación del servidor
const pagination = ref({
  sortBy: 'id',
  descending: true,
  page: 1,
  rowsPerPage: 10,
  rowsNumber: 10,
})

// Datos del nuevo ítem
const nuevoItem = ref({
  codigo: '',
  nombre: '',
  descripcion: '',
  precio_base: 0,
  tipo: 'producto',
})

// Opciones para el select de tipo
const tiposOptions = [
  { label: 'Producto', value: 'producto' },
  { label: 'Servicio', value: 'servicio' },
]

// Definición de columnas de la tabla
const columns = [
  {
    name: 'codigo',
    required: true,
    label: 'Código',
    align: 'left',
    field: 'codigo',
    sortable: true,
  },
  {
    name: 'nombre',
    required: true,
    label: 'Nombre',
    align: 'left',
    field: 'nombre',
    sortable: true,
  },
  {
    name: 'tipo',
    label: 'Tipo',
    align: 'center',
    field: 'tipo',
    sortable: true,
  },
  {
    name: 'precio_base',
    label: 'Precio',
    align: 'right',
    field: 'precio_base',
    sortable: true,
  },
  {
    name: 'descripcion',
    label: 'Descripción',
    align: 'left',
    field: 'descripcion',
    sortable: false,
  },
  {
    name: 'acciones',
    label: 'Acciones',
    align: 'center',
    field: 'acciones',
    sortable: false,
  },
]

// Función de request para paginación del servidor
const onRequest = async (props) => {
  const { page, rowsPerPage } = props.pagination
  const filterValue = props.filter || filter.value

  const result = await inventarioStore.fetchInventario({
    page,
    rowsPerPage,
    filter: filterValue,
  })

  if (result.success) {
    pagination.value.page = page
    pagination.value.rowsPerPage = rowsPerPage
    pagination.value.rowsNumber = result.rowsNumber
  }
}

// Búsqueda con debounce
let searchTimeout = null
const onSearch = () => {
  clearTimeout(searchTimeout)
  searchTimeout = setTimeout(() => {
    pagination.value.page = 1
    onRequest({
      pagination: pagination.value,
      filter: filter.value,
    })
  }, 500)
}

// Funciones del diálogo
const abrirDialogo = () => {
  modoEdicion.value = false
  itemEditandoId.value = null
  dialogoAbierto.value = true
}

const editarItem = (item) => {
  modoEdicion.value = true
  itemEditandoId.value = item.id
  nuevoItem.value = {
    codigo: item.codigo,
    nombre: item.nombre,
    descripcion: item.descripcion || '',
    precio_base: item.precio_base,
    tipo: item.tipo,
  }
  dialogoAbierto.value = true
}

const cerrarDialogo = () => {
  dialogoAbierto.value = false
  limpiarFormulario()
}

const limpiarFormulario = () => {
  modoEdicion.value = false
  itemEditandoId.value = null
  nuevoItem.value = {
    codigo: '',
    nombre: '',
    descripcion: '',
    precio_base: 0,
    tipo: 'producto',
  }
}

const guardarItem = async () => {
  let resultado

  if (modoEdicion.value) {
    resultado = await inventarioStore.actualizarItem(itemEditandoId.value, nuevoItem.value)
  } else {
    resultado = await inventarioStore.crearItem(nuevoItem.value)
  }

  if (resultado.success) {
    $q.notify({
      type: 'positive',
      message: modoEdicion.value ? 'Ítem actualizado exitosamente' : 'Ítem creado exitosamente',
      position: 'top-right',
    })
    cerrarDialogo()
    onRequest({ pagination: pagination.value })
  } else {
    $q.notify({
      type: 'negative',
      message: resultado.error || `Error al ${modoEdicion.value ? 'actualizar' : 'crear'} el ítem`,
      position: 'top-right',
    })
  }
}

// Funciones de importación/exportación
const descargarPlantilla = async () => {
  const resultado = await inventarioStore.descargarPlantilla()

  if (resultado.success) {
    $q.notify({
      type: 'positive',
      message: 'Plantilla descargada exitosamente',
      position: 'top-right',
    })
  } else {
    $q.notify({
      type: 'negative',
      message: resultado.error || 'Error al descargar la plantilla',
      position: 'top-right',
    })
  }
}

const abrirDialogoImportacion = () => {
  dialogoImportacionAbierto.value = true
  archivoImportacion.value = null
  resultadosImportacion.value = null
}

const cerrarDialogoImportacion = () => {
  dialogoImportacionAbierto.value = false
  archivoImportacion.value = null
  resultadosImportacion.value = null
}

const procesarImportacion = async () => {
  if (!archivoImportacion.value) {
    $q.notify({
      type: 'warning',
      message: 'Por favor seleccione un archivo',
      position: 'top-right',
    })
    return
  }

  importando.value = true
  resultadosImportacion.value = null

  const resultado = await inventarioStore.importarInventario(archivoImportacion.value)

  importando.value = false

  if (resultado.success) {
    resultadosImportacion.value = resultado.results

    // Mostrar notificación de resumen
    const { success, errors } = resultado.results
    if (errors === 0) {
      $q.notify({
        type: 'positive',
        message: `Importación completada: ${success} ítems creados exitosamente`,
        position: 'top-right',
      })
    } else if (success > 0) {
      $q.notify({
        type: 'warning',
        message: `Importación parcial: ${success} exitosos, ${errors} errores`,
        position: 'top-right',
      })
    } else {
      $q.notify({
        type: 'negative',
        message: `Importación fallida: ${errors} errores`,
        position: 'top-right',
      })
    }

    // Recargar la tabla
    onRequest({ pagination: pagination.value })
  } else {
    $q.notify({
      type: 'negative',
      message: resultado.error || 'Error al importar el archivo',
      position: 'top-right',
    })
  }
}

// Cargar inventario al montar el componente
onMounted(() => {
  onRequest({ pagination: pagination.value })
})
</script>

<style lang="scss" scoped>
.inventario-page {
  background: var(--bg-app);
}

h4 {
  color: var(--text-primary) !important;
}

:deep(.q-field) {
  .q-field__control {
    color: var(--text-primary) !important;
  }

  .q-field__native,
  input {
    color: var(--text-primary) !important;
  }

  .q-field__label {
    color: var(--text-primary) !important;
    opacity: 0.7;
  }

  .q-icon {
    color: var(--accent-color) !important;
  }
}

.tabla-inventario-cyber {
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

.modal-cyber {
  background: var(--card-bg) !important;
  backdrop-filter: blur(16px) saturate(180%);
  -webkit-backdrop-filter: blur(16px) saturate(180%);
  border: 1px solid var(--border-color) !important;
  border-radius: 16px;
  box-shadow: 0 8px 32px rgba(0, 0, 0, 0.2);
  color: var(--text-primary) !important;

  :deep(.text-h6) {
    color: var(--accent-color) !important;
    text-shadow: 0 0 8px var(--accent-glow);
  }

  :deep(.q-field__label),
  :deep(.q-field__native),
  :deep(input),
  :deep(textarea) {
    color: var(--text-primary) !important;
  }

  :deep(.q-field__control) {
    color: var(--text-primary) !important;
  }

  :deep(.q-icon) {
    color: var(--accent-color) !important;
  }

  :deep(.hint),
  :deep(.q-field__bottom) {
    color: var(--text-primary) !important;
    opacity: 0.7;
  }

  :deep(.q-field__prefix),
  :deep(.q-field__suffix) {
    color: var(--text-primary) !important;
  }

  :deep(.q-field__control) {
    &:before,
    &:after {
      border-color: var(--border-color) !important;
    }
  }

  :deep(.q-field--outlined) {
    .q-field__control {
      &:before {
        border-color: var(--border-color) !important;
      }

      &:hover:before {
        border-color: var(--accent-color) !important;
      }
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

// Mobile cards
.inventory-card-mobile {
  background: var(--card-bg) !important;
  backdrop-filter: blur(8px) saturate(150%);
  -webkit-backdrop-filter: blur(8px) saturate(150%);
  border-radius: 12px;
  transition: all 0.3s ease;
  border: 1px solid var(--border-color);

  &.card-producto {
    border-color: var(--accent-color);
    opacity: 0.7;

    &:hover {
      border-color: var(--accent-color);
      opacity: 1;
      box-shadow: 0 4px 20px var(--accent-glow);
      transform: translateY(-2px);
    }
  }

  &.card-servicio {
    border-color: #10b981;
    opacity: 0.7;

    &:hover {
      border-color: #10b981;
      opacity: 1;
      box-shadow: 0 4px 20px rgba(16, 185, 129, 0.3);
      transform: translateY(-2px);
    }
  }
}

.card-header {
  padding: 12px 16px;
  background: var(--glass-color);
}

.item-nombre-mobile {
  font-size: 16px;
  font-weight: 600;
  color: var(--text-primary);
  margin-bottom: 4px;
  line-height: 1.3;
}

.item-codigo-mobile {
  font-size: 12px;
  color: var(--text-primary);
  opacity: 0.7;
  display: flex;
  align-items: center;
  gap: 4px;
  font-family: 'Courier New', monospace;
}

.card-separator {
  background: linear-gradient(90deg, transparent, var(--border-color), transparent);
  height: 1px;
  margin: 0;
}

.card-body {
  padding: 12px 16px;
}

.item-descripcion-mobile {
  font-size: 13px;
  color: var(--text-primary);
  opacity: 0.8;
  margin-bottom: 12px;
  line-height: 1.4;
  max-height: 40px;
  overflow: hidden;
  text-overflow: ellipsis;
  display: -webkit-box;
  -webkit-line-clamp: 2;
  -webkit-box-orient: vertical;
}

.item-precio-mobile {
  font-size: 24px;
  font-weight: 700;
  color: var(--accent-color);
  display: flex;
  align-items: center;
  gap: 4px;
  text-shadow: 0 0 10px var(--accent-glow);

  .precio-icon {
    color: var(--accent-color);
    filter: drop-shadow(0 0 6px var(--accent-glow));
  }
}

.card-actions {
  padding: 8px 16px;
  background: rgba(0, 0, 0, 0.1);
  border-top: 1px solid var(--border-color);

  .q-btn {
    transition: all 0.3s ease;

    &:hover {
      transform: scale(1.1);
    }
  }
}

@media (max-width: 599px) {
  .inventory-card-mobile {
    margin-bottom: 8px;
  }

  .item-nombre-mobile {
    font-size: 15px;
  }

  .item-precio-mobile {
    font-size: 20px;
  }
}
</style>
