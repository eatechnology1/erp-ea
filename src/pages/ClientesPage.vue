<template>
  <q-page class="q-pa-md clientes-page">
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
      class="shadow-2 tabla-clientes-cyber"
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

      <template v-slot:body-cell-acciones="props">
        <q-td :props="props">
          <q-btn
            flat
            round
            dense
            color="primary"
            icon="edit"
            size="sm"
            @click="editarCliente(props.row)"
          >
            <q-tooltip>Editar Cliente</q-tooltip>
          </q-btn>
        </q-td>
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
      <q-card style="min-width: 500px" class="modal-cyber">
        <q-card-section class="row items-center q-pb-none">
          <div class="text-h6">{{ modoEdicion ? 'Editar Cliente' : 'Nuevo Cliente' }}</div>
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
const modoEdicion = ref(false)
const clienteEditandoId = ref(null)

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
  {
    name: 'acciones',
    label: 'Acciones',
    align: 'center',
    field: 'acciones',
    sortable: false,
  },
]

// Funciones
const abrirDialogo = () => {
  modoEdicion.value = false
  clienteEditandoId.value = null
  dialogoAbierto.value = true
}

const editarCliente = (cliente) => {
  modoEdicion.value = true
  clienteEditandoId.value = cliente.id
  nuevoCliente.value = {
    nit_cedula: cliente.nit_cedula,
    razon_social: cliente.razon_social,
    telefono: cliente.telefono || '',
    email: cliente.email || '',
    direccion: cliente.direccion || '',
  }
  dialogoAbierto.value = true
}

const cerrarDialogo = () => {
  dialogoAbierto.value = false
  limpiarFormulario()
}

const limpiarFormulario = () => {
  modoEdicion.value = false
  clienteEditandoId.value = null
  nuevoCliente.value = {
    nit_cedula: '',
    razon_social: '',
    telefono: '',
    email: '',
    direccion: '',
  }
}

const guardarCliente = async () => {
  let resultado

  if (modoEdicion.value) {
    resultado = await clientesStore.actualizarCliente(clienteEditandoId.value, nuevoCliente.value)
  } else {
    resultado = await clientesStore.crearCliente(nuevoCliente.value)
  }

  if (resultado.success) {
    $q.notify({
      type: 'positive',
      message: modoEdicion.value
        ? 'Cliente actualizado exitosamente'
        : 'Cliente creado exitosamente',
      position: 'top-right',
    })
    cerrarDialogo()
  } else {
    $q.notify({
      type: 'negative',
      message:
        resultado.error || `Error al ${modoEdicion.value ? 'actualizar' : 'crear'} el cliente`,
      position: 'top-right',
    })
  }
}

// Cargar clientes al montar el componente
onMounted(() => {
  clientesStore.fetchClientes()
})
</script>

<style lang="scss" scoped>
.clientes-page {
  background: var(--bg-app);
}

h4 {
  color: var(--text-primary);
}

.tabla-clientes-cyber {
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

  :deep(*) {
    border-color: var(--border-color) !important;
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
</style>
