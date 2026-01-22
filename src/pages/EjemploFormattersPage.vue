<template>
  <q-page class="q-pa-md">
    <h4>Ejemplo de Uso del Composable useFormatters</h4>

    <q-card class="q-pa-md q-mb-md">
      <q-card-section>
        <div class="text-h6">Formateo de Moneda</div>
        <p>Precio sin decimales: {{ formatearMoneda(1250000) }}</p>
        <p>Precio con 2 decimales: {{ formatearMoneda(1250000.5, 2) }}</p>
        <p>Valor null: {{ formatearMoneda(null) }}</p>
      </q-card-section>

      <q-card-section>
        <div class="text-h6">Formateo de Fechas</div>
        <p>Fecha completa: {{ formatearFecha(new Date()) }}</p>
        <p>Fecha corta: {{ formatearFechaCorta(new Date()) }}</p>
      </q-card-section>

      <q-card-section>
        <div class="text-h6">Formateo de Números</div>
        <p>Número: {{ formatearNumero(1250000) }}</p>
        <p>Con decimales: {{ formatearNumero(1250.567, 2) }}</p>
      </q-card-section>

      <q-card-section>
        <div class="text-h6">Formateo de Porcentajes</div>
        <p>IVA: {{ formatearPorcentaje(0.19) }}</p>
        <p>Descuento: {{ formatearPorcentaje(0.15, 1) }}</p>
      </q-card-section>
    </q-card>

    <!-- Ejemplo con datos dinámicos -->
    <q-card class="q-pa-md">
      <q-card-section>
        <div class="text-h6">Ejemplo con Producto</div>
        <p><strong>Nombre:</strong> {{ producto.nombre }}</p>
        <p><strong>Precio:</strong> {{ formatearMoneda(producto.precio) }}</p>
        <p><strong>Cantidad:</strong> {{ formatearNumero(producto.cantidad) }}</p>
        <p><strong>Total:</strong> {{ formatearMoneda(producto.precio * producto.cantidad) }}</p>
      </q-card-section>
    </q-card>
  </q-page>
</template>

<script setup>
import { ref } from 'vue'
import { useFormatters } from 'src/composables/useFormatters'

// Importar el composable y desestructurar las funciones que necesitas
const {
  formatearMoneda,
  formatearFecha,
  formatearFechaCorta,
  formatearNumero,
  formatearPorcentaje,
} = useFormatters()

// Datos de ejemplo
const producto = ref({
  nombre: 'Laptop Dell',
  precio: 2500000,
  cantidad: 3,
})
</script>

<style scoped>
/* Estilos opcionales */
</style>
