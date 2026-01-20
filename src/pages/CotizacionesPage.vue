<template>
  <q-page class="q-pa-md">
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
      class="shadow-2"
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

      <!-- Columna de Estado -->
      <template v-slot:body-cell-estado="props">
        <q-td :props="props">
          <q-badge :color="getEstadoColor(props.row.estado)" :label="props.row.estado" />
        </q-td>
      </template>

      <!-- Columna de Total -->
      <template v-slot:body-cell-total="props">
        <q-td :props="props">
          <strong>{{ formatearPrecio(props.row.total) }}</strong>
        </q-td>
      </template>

      <!-- Columna de Fecha -->
      <template v-slot:body-cell-fecha="props">
        <q-td :props="props">
          {{ formatearFecha(props.row.fecha) }}
        </q-td>
      </template>
    </q-table>

    <!-- Botón flotante para nueva cotización -->
    <q-page-sticky position="bottom-right" :offset="[18, 18]">
      <q-btn fab icon="add" color="primary" @click="nuevaCotizacion" size="lg">
        <q-tooltip>Nueva Cotización</q-tooltip>
      </q-btn>
    </q-page-sticky>
  </q-page>
</template>

<script setup>
import { onMounted } from 'vue'
import { useCotizadorStore } from 'src/stores/cotizador'
import { useRouter } from 'vue-router'

const router = useRouter()
const cotizadorStore = useCotizadorStore()

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
]

// Funciones
const formatearPrecio = (precio) => {
  return new Intl.NumberFormat('es-CO', {
    style: 'currency',
    currency: 'COP',
    minimumFractionDigits: 0,
  }).format(precio)
}

const formatearFecha = (fecha) => {
  return new Date(fecha).toLocaleDateString('es-CO', {
    year: 'numeric',
    month: 'long',
    day: 'numeric',
    hour: '2-digit',
    minute: '2-digit',
  })
}

const getEstadoColor = (estado) => {
  const colores = {
    pendiente: 'orange',
    aprobada: 'green',
    rechazada: 'red',
    enviada: 'blue',
  }
  return colores[estado] || 'grey'
}

const nuevaCotizacion = () => {
  router.push('/nueva-cotizacion')
}

// Cargar cotizaciones al montar el componente
onMounted(() => {
  cotizadorStore.fetchCotizaciones()
})
</script>

<style scoped>
/* Estilos personalizados */
</style>
