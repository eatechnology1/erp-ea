<template>
  <q-page class="q-pa-md">
    <div class="q-mb-md">
      <h4 class="q-my-md">Gestión de Clientes</h4>
    </div>

    <!-- Tabla de clientes -->
    <q-table
      :rows="clientesStore.clientes"
      :columns="columns"
      row-key="id"
      :loading="clientesStore.loading"
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
          <span>No hay clientes registrados</span>
        </div>
      </template>
    </q-table>

    <!-- Botón flotante para agregar cliente -->
    <q-page-sticky position="bottom-right" :offset="[18, 18]">
      <q-btn fab icon="add" color="primary" @click="abrirDialogo" size="lg">
        <q-tooltip>Agregar Cliente</q-tooltip>
      </q-btn>
    </q-page-sticky>

    <!-- Diálogo para crear cliente -->
    <q-dialog v-model="dialogoAbierto" persistent>
      <q-card style="min-width: 500px">
        <q-card-section class="row items-center q-pb-none">
          <div class="text-h6">Nuevo Cliente</div>
          <q-space />
          <q-btn icon="close" flat round dense v-close-popup />
        </q-card-section>

        <q-card-section>
          <q-form @submit="guardarCliente" class="q-gutter-md">
            <q-input
              v-model="nuevoCliente.nit_cedula"
              label="NIT / Cédula *"
              outlined
              :rules="[(val) => !!val || 'Campo requerido']"
            />

            <q-input
              v-model="nuevoCliente.razon_social"
              label="Razón Social *"
              outlined
              :rules="[(val) => !!val || 'Campo requerido']"
            />

            <q-input v-model="nuevoCliente.telefono" label="Teléfono" outlined type="tel" />

            <q-input v-model="nuevoCliente.email" label="Email" outlined type="email" />

            <q-input
              v-model="nuevoCliente.direccion"
              label="Dirección"
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
                :loading="clientesStore.loading"
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
import { useClientesStore } from 'src/stores/clientes'
import { useQuasar } from 'quasar'

const $q = useQuasar()
const clientesStore = useClientesStore()

// Estado del diálogo
const dialogoAbierto = ref(false)

// Datos del nuevo cliente
const nuevoCliente = ref({
  nit_cedula: '',
  razon_social: '',
  telefono: '',
  email: '',
  direccion: '',
})

// Definición de columnas de la tabla
const columns = [
  {
    name: 'nit_cedula',
    required: true,
    label: 'NIT / Cédula',
    align: 'left',
    field: 'nit_cedula',
    sortable: true,
  },
  {
    name: 'razon_social',
    required: true,
    label: 'Razón Social',
    align: 'left',
    field: 'razon_social',
    sortable: true,
  },
  {
    name: 'telefono',
    label: 'Teléfono',
    align: 'left',
    field: 'telefono',
    sortable: true,
  },
  {
    name: 'email',
    label: 'Email',
    align: 'left',
    field: 'email',
    sortable: true,
  },
  {
    name: 'direccion',
    label: 'Dirección',
    align: 'left',
    field: 'direccion',
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
  nuevoCliente.value = {
    nit_cedula: '',
    razon_social: '',
    telefono: '',
    email: '',
    direccion: '',
  }
}

const guardarCliente = async () => {
  const resultado = await clientesStore.crearCliente(nuevoCliente.value)

  if (resultado.success) {
    $q.notify({
      type: 'positive',
      message: 'Cliente creado exitosamente',
      position: 'top-right',
    })
    cerrarDialogo()
  } else {
    $q.notify({
      type: 'negative',
      message: resultado.error || 'Error al crear el cliente',
      position: 'top-right',
    })
  }
}

// Cargar clientes al montar el componente
onMounted(() => {
  clientesStore.fetchClientes()
})
</script>

<style scoped>
/* Estilos personalizados si los necesitas */
</style>
