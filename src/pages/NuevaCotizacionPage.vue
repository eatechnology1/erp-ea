<template>
  <q-page class="q-pa-md">
    <div class="q-mb-md">
      <h4 class="q-my-md">Nueva Cotización</h4>
    </div>

    <!-- Sección Cliente -->
    <q-card class="q-mb-md">
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
    <q-card class="q-mb-md">
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
                  :color="scope.opt.tipo === 'producto' ? 'blue' : 'green'"
                  :label="scope.opt.tipo === 'producto' ? 'Producto' : 'Servicio'"
                />
              </q-item-section>
            </q-item>
          </template>
        </q-select>
      </q-card-section>
    </q-card>

    <!-- Tabla de Items -->
    <q-card class="q-mb-md">
      <q-card-section>
        <div class="text-h6 q-mb-md">Ítems de la Cotización</div>

        <q-table
          :rows="cotizadorStore.items"
          :columns="columns"
          row-key="id"
          flat
          bordered
          :hide-pagination="true"
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

          <!-- Columna de Subtotal (calculado) -->
          <template v-slot:body-cell-subtotal="props">
            <q-td :props="props">
              <strong>{{ formatearPrecio(props.row.cantidad * props.row.precio) }}</strong>
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
            <div class="col-12 col-md-4">
              <div class="row justify-between items-center">
                <div class="text-subtitle1">Subtotal:</div>
                <div class="text-h6">{{ formatearPrecio(cotizadorStore.subtotal) }}</div>
              </div>
              <div class="row justify-between items-center q-mt-sm">
                <div class="text-subtitle1">IVA (19%):</div>
                <div class="text-h6">{{ formatearPrecio(cotizadorStore.iva) }}</div>
              </div>
              <q-separator class="q-my-sm" />
              <div class="row justify-between items-center">
                <div class="text-h6 text-weight-bold">Total:</div>
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
  </q-page>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import { useCotizadorStore } from 'src/stores/cotizador'
import { useClientesStore } from 'src/stores/clientes'
import { useInventarioStore } from 'src/stores/inventario'
import { useQuasar } from 'quasar'
import { useRouter } from 'vue-router'

const $q = useQuasar()
const router = useRouter()
const cotizadorStore = useCotizadorStore()
const clientesStore = useClientesStore()
const inventarioStore = useInventarioStore()

// Estados locales
const clientesFiltrados = ref([])
const inventarioFiltrado = ref([])
const itemSeleccionado = ref(null)

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
    label: 'Precio',
    align: 'right',
    field: 'precio',
  },
  {
    name: 'subtotal',
    label: 'Subtotal',
    align: 'right',
    field: (row) => row.cantidad * row.precio,
  },
  {
    name: 'acciones',
    label: '',
    align: 'center',
  },
]

// Funciones
const formatearPrecio = (precio) => {
  return new Intl.NumberFormat('es-CO', {
    style: 'currency',
    currency: 'COP',
    minimumFractionDigits: 0,
  }).format(precio)
}

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
  await inventarioStore.fetchInventario()

  clientesFiltrados.value = clientesStore.clientes
  inventarioFiltrado.value = inventarioStore.items

  // Limpiar cotización anterior si existe
  cotizadorStore.limpiarCotizacion()
})
</script>

<style scoped>
/* Estilos personalizados */
</style>
