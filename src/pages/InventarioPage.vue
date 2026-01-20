<template>
  <q-page class="q-pa-md">
    <div class="q-mb-md">
      <h4 class="q-my-md">Gestión de Inventario</h4>
    </div>

    <!-- Buscador -->
    <div class="q-mb-md">
      <q-input
        v-model="busqueda"
        outlined
        placeholder="Buscar por código, nombre o descripción..."
        clearable
        @update:model-value="buscarItems"
      >
        <template v-slot:prepend>
          <q-icon name="search" />
        </template>
      </q-input>
    </div>

    <!-- Tabla de inventario -->
    <q-table
      :rows="inventarioStore.items"
      :columns="columns"
      row-key="id"
      :loading="inventarioStore.loading"
      flat
      bordered
      class="shadow-2"
    >
      <template v-slot:loading>
        <q-inner-loading showing color="primary" />
      </template>

      <template v-slot:no-data>
        <div class="full-width row flex-center text-accent q-gutter-sm">
          <q-icon size="2em" name="warning" />
          <span>No hay ítems en el inventario</span>
        </div>
      </template>

      <!-- Columna personalizada para Tipo -->
      <template v-slot:body-cell-tipo="props">
        <q-td :props="props">
          <q-badge
            :color="props.row.tipo === 'producto' ? 'blue' : 'green'"
            :label="props.row.tipo === 'producto' ? 'Producto' : 'Servicio'"
          />
        </q-td>
      </template>

      <!-- Columna personalizada para Precio -->
      <template v-slot:body-cell-precio_base="props">
        <q-td :props="props">
          {{ formatearPrecio(props.row.precio_base) }}
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
      <q-card style="min-width: 500px">
        <q-card-section class="row items-center q-pb-none">
          <div class="text-h6">Nuevo Ítem de Inventario</div>
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
  </q-page>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import { useInventarioStore } from 'src/stores/inventario'
import { useQuasar } from 'quasar'

const $q = useQuasar()
const inventarioStore = useInventarioStore()

// Estado del diálogo
const dialogoAbierto = ref(false)

// Búsqueda
const busqueda = ref('')

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
]

// Funciones
const abrirDialogo = () => {
  dialogoAbierto.value = true
}

const cerrarDialogo = () => {
  dialogoAbierto.value = false
  limpiarFormulario()
}

const limpiarFormulario = () => {
  nuevoItem.value = {
    codigo: '',
    nombre: '',
    descripcion: '',
    precio_base: 0,
    tipo: 'producto',
  }
}

const formatearPrecio = (precio) => {
  return new Intl.NumberFormat('es-CO', {
    style: 'currency',
    currency: 'COP',
    minimumFractionDigits: 0,
  }).format(precio)
}

const buscarItems = () => {
  // Realizar búsqueda con debounce implícito
  inventarioStore.fetchInventario(busqueda.value)
}

const guardarItem = async () => {
  const resultado = await inventarioStore.crearItem(nuevoItem.value)

  if (resultado.success) {
    $q.notify({
      type: 'positive',
      message: 'Ítem creado exitosamente',
      position: 'top-right',
    })
    cerrarDialogo()
  } else {
    $q.notify({
      type: 'negative',
      message: resultado.error || 'Error al crear el ítem',
      position: 'top-right',
    })
  }
}

// Cargar inventario al montar el componente
onMounted(() => {
  inventarioStore.fetchInventario()
})
</script>

<style scoped>
/* Estilos personalizados si los necesitas */
</style>
