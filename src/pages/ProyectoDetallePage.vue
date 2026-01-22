<template>
  <q-page class="q-pa-md proyecto-detalle-page">
    <div v-if="proyectosStore.proyectoActual">
      <!-- Header -->
      <div class="page-header q-mb-lg">
        <div>
          <h4 class="q-my-none">{{ proyectosStore.proyectoActual.nombre }}</h4>
          <div class="subtitle">{{ proyectosStore.proyectoActual.cliente_nombre }}</div>
        </div>
        <div class="header-actions">
          <q-btn flat icon="arrow_back" label="Volver" @click="volver" />
          <q-btn flat icon="print" label="Imprimir" color="cyan" @click="imprimirProyecto" />
          <q-btn
            color="primary"
            icon="save"
            label="Guardar Cambios"
            @click="guardarCambios"
            :loading="proyectosStore.loading"
          />
        </div>
      </div>

      <!-- KPIs Financieros -->
      <div class="kpis-grid q-mb-lg">
        <q-card class="kpi-card">
          <q-card-section>
            <div class="kpi-label">Valor Cierre</div>
            <div class="kpi-value cierre">
              {{ formatearMoneda(proyectosStore.subtotalProyecto) }}
            </div>
          </q-card-section>
        </q-card>

        <q-card class="kpi-card">
          <q-card-section>
            <div class="kpi-label">Costos Materiales</div>
            <div class="kpi-value materiales">
              {{ formatearMoneda(proyectosStore.totalMateriales) }}
            </div>
          </q-card-section>
        </q-card>

        <q-card class="kpi-card">
          <q-card-section>
            <div class="kpi-label">Mano de Obra</div>
            <div class="kpi-value mano-obra">
              {{ formatearMoneda(proyectosStore.totalManoObra) }}
            </div>
          </q-card-section>
        </q-card>

        <q-card class="kpi-card">
          <q-card-section>
            <div class="kpi-label">Utilidad Neta</div>
            <div class="kpi-value utilidad">{{ formatearMoneda(proyectosStore.utilidadNeta) }}</div>
          </q-card-section>
        </q-card>

        <q-card class="kpi-card">
          <q-card-section>
            <div class="kpi-label">Margen</div>
            <div class="kpi-value margen">{{ proyectosStore.margenPorcentaje.toFixed(1) }}%</div>
          </q-card-section>
        </q-card>
      </div>

      <!-- Pestañas de Especialidad -->
      <q-card class="tabla-card">
        <q-tabs
          v-model="categoriaActiva"
          dense
          class="tabs-cyber"
          active-color="cyan"
          indicator-color="cyan"
        >
          <q-tab
            v-for="cat in proyectosStore.proyectoActual.categorias"
            :key="cat.id"
            :name="cat.id"
            :label="cat.nombre"
            :icon="cat.icono"
          />
          <q-tab name="nueva" label="+ Agregar Categoría" icon="add" />
        </q-tabs>

        <q-separator class="separator-neon" />

        <q-tab-panels v-model="categoriaActiva" animated class="tab-panels-cyber">
          <q-tab-panel
            v-for="cat in proyectosStore.proyectoActual.categorias"
            :key="cat.id"
            :name="cat.id"
          >
            <!-- Tabla Editable -->
            <q-table
              :rows="cat.items"
              :columns="columns"
              row-key="id"
              flat
              class="tabla-presupuesto"
              :hide-pagination="true"
            >
              <template v-slot:top>
                <div class="table-header">
                  <span class="text-h6">{{ cat.nombre }}</span>
                  <q-btn
                    flat
                    icon="add"
                    label="Agregar Ítem"
                    color="cyan"
                    @click="agregarItem(cat.id)"
                    size="sm"
                  />
                </div>
              </template>

              <template v-slot:body="props">
                <q-tr :props="props">
                  <q-td key="numero" :props="props">
                    {{ props.rowIndex + 1 }}
                  </q-td>

                  <q-td key="descripcion" :props="props">
                    <q-input v-model="props.row.descripcion" dense borderless class="input-cell" />
                  </q-td>

                  <q-td key="unidad" :props="props">
                    <q-input
                      v-model="props.row.unidad"
                      dense
                      borderless
                      class="input-cell"
                      style="width: 60px"
                    />
                  </q-td>

                  <q-td key="cantidad" :props="props">
                    <q-input
                      v-model.number="props.row.cantidad"
                      type="number"
                      dense
                      borderless
                      class="input-cell"
                      style="width: 80px"
                    />
                  </q-td>

                  <q-td key="valor_unitario" :props="props">
                    <q-input
                      v-model.number="props.row.valor_unitario"
                      type="number"
                      dense
                      borderless
                      prefix="$"
                      class="input-cell"
                      style="width: 120px"
                    />
                  </q-td>

                  <q-td key="valor_total" :props="props">
                    <strong>{{
                      formatearMoneda(props.row.cantidad * props.row.valor_unitario)
                    }}</strong>
                  </q-td>

                  <q-td key="acciones" :props="props">
                    <q-btn
                      flat
                      round
                      dense
                      icon="delete"
                      color="red"
                      @click="eliminarItem(cat.id, props.rowIndex)"
                      size="sm"
                    />
                  </q-td>
                </q-tr>
              </template>
            </q-table>
          </q-tab-panel>
        </q-tab-panels>
      </q-card>

      <!-- Footer AIU -->
      <q-card class="aiu-card q-mt-lg">
        <q-card-section>
          <div class="text-h6 q-mb-md">Administración, Imprevistos y Utilidades (AIU)</div>

          <div class="aiu-grid">
            <div class="aiu-input">
              <q-input
                v-model.number="proyectosStore.proyectoActual.iva_porcentaje"
                label="IVA %"
                type="number"
                outlined
                dense
                suffix="%"
              />
              <div class="aiu-value">{{ formatearMoneda(proyectosStore.valorIVA) }}</div>
            </div>

            <div class="aiu-input">
              <q-input
                v-model.number="proyectosStore.proyectoActual.administracion_porcentaje"
                label="Administración %"
                type="number"
                outlined
                dense
                suffix="%"
              />
              <div class="aiu-value">{{ formatearMoneda(proyectosStore.valorAdministracion) }}</div>
            </div>

            <div class="aiu-input">
              <q-input
                v-model.number="proyectosStore.proyectoActual.improvistos_porcentaje"
                label="Improvistos %"
                type="number"
                outlined
                dense
                suffix="%"
              />
              <div class="aiu-value">{{ formatearMoneda(proyectosStore.valorImprevistos) }}</div>
            </div>
          </div>

          <q-separator class="q-my-md separator-neon" />

          <div class="total-final">
            <span class="total-label">TOTAL FINAL DEL PROYECTO:</span>
            <span class="total-value">{{ formatearMoneda(proyectosStore.totalFinal) }}</span>
          </div>
        </q-card-section>
      </q-card>
    </div>

    <div v-else class="loading-container">
      <q-spinner-orbit color="cyan" size="50px" />
    </div>
  </q-page>
</template>

<script setup>
import { ref, onMounted, onUnmounted } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { useProyectosStore } from 'src/stores/proyectos'
import { useFormatters } from 'src/composables/useFormatters'
import { useQuasar } from 'quasar'

const route = useRoute()
const router = useRouter()
const $q = useQuasar()
const proyectosStore = useProyectosStore()
const { formatearMoneda } = useFormatters()

const categoriaActiva = ref(null)

const columns = [
  { name: 'numero', label: '#', align: 'center', field: 'id' },
  { name: 'descripcion', label: 'Descripción', align: 'left', field: 'descripcion' },
  { name: 'unidad', label: 'Unidad', align: 'center', field: 'unidad' },
  { name: 'cantidad', label: 'Cantidad', align: 'right', field: 'cantidad' },
  { name: 'valor_unitario', label: 'Valor Unitario', align: 'right', field: 'valor_unitario' },
  { name: 'valor_total', label: 'Valor Total', align: 'right' },
  { name: 'acciones', label: '', align: 'center' },
]

const agregarItem = (categoriaId) => {
  const categoria = proyectosStore.proyectoActual.categorias.find((c) => c.id === categoriaId)
  if (categoria) {
    categoria.items.push({
      id: Date.now(),
      categoria_id: categoriaId,
      descripcion: '',
      unidad: 'UN',
      cantidad: 1,
      valor_unitario: 0,
      costo_materiales: 0,
      mano_de_obra: 0,
    })
  }
}

const eliminarItem = (categoriaId, index) => {
  const categoria = proyectosStore.proyectoActual.categorias.find((c) => c.id === categoriaId)
  if (categoria) {
    categoria.items.splice(index, 1)
  }
}

const guardarCambios = async () => {
  // Preparar datos para enviar
  const items = []
  proyectosStore.proyectoActual.categorias.forEach((cat) => {
    cat.items.forEach((item) => {
      items.push({
        categoria_id: cat.id,
        descripcion: item.descripcion,
        unidad: item.unidad,
        cantidad: item.cantidad,
        valor_unitario: item.valor_unitario,
        costo_materiales: item.costo_materiales || 0,
        mano_de_obra: item.mano_de_obra || 0,
      })
    })
  })

  // Recalcular costo total desde el store
  const costoTotal = proyectosStore.costoTotal

  const proyectoData = {
    id: proyectosStore.proyectoActual.id,
    nombre: proyectosStore.proyectoActual.nombre,
    cliente_id: proyectosStore.proyectoActual.cliente_id,
    fecha_inicio: proyectosStore.proyectoActual.fecha_inicio,
    fecha_fin: proyectosStore.proyectoActual.fecha_fin,
    estado: proyectosStore.proyectoActual.estado,
    valor_cierre: proyectosStore.subtotalProyecto,
    costo_total: costoTotal,
    iva_porcentaje: proyectosStore.proyectoActual.iva_porcentaje,
    administracion_porcentaje: proyectosStore.proyectoActual.administracion_porcentaje,
    improvistos_porcentaje: proyectosStore.proyectoActual.improvistos_porcentaje,
    items,
  }

  const resultado = await proyectosStore.actualizarProyecto(proyectoData)

  if (resultado.success) {
    $q.notify({
      type: 'positive',
      message: 'Proyecto actualizado exitosamente',
      position: 'top-right',
    })
  }
}
const volver = () => {
  router.push('/proyectos')
}

const imprimirProyecto = () => {
  window.print()
}

onMounted(async () => {
  const id = route.params.id
  await proyectosStore.fetchProyectoDetalle(id)

  if (proyectosStore.proyectoActual && proyectosStore.proyectoActual.categorias.length > 0) {
    categoriaActiva.value = proyectosStore.proyectoActual.categorias[0].id
  }
})

onUnmounted(() => {
  proyectosStore.limpiarProyectoActual()
})
</script>

<style lang="scss" scoped>
.proyecto-detalle-page {
  background: var(--bg-app);
}

.page-header {
  display: flex;
  justify-content: space-between;
  align-items: center;

  h4 {
    color: var(--accent-color) !important;
    text-shadow: 0 0 10px var(--accent-glow);
  }

  .subtitle {
    color: var(--text-primary);
    opacity: 0.7;
    font-size: 14px;
  }

  .header-actions {
    display: flex;
    gap: 12px;
  }
}

.kpis-grid {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
  gap: 16px;
}

.kpi-card {
  background: var(--card-bg) !important;
  backdrop-filter: blur(12px);
  border: 1px solid var(--border-color);
  border-radius: 12px;

  .kpi-label {
    font-size: 12px;
    color: var(--text-primary);
    opacity: 0.7;
    text-transform: uppercase;
    letter-spacing: 1px;
    margin-bottom: 8px;
  }

  .kpi-value {
    font-size: 28px;
    font-weight: 700;

    &.cierre {
      color: #10b981;
      text-shadow: 0 0 12px rgba(16, 185, 129, 0.6);
    }

    &.materiales {
      color: #f59e0b;
      text-shadow: 0 0 12px rgba(245, 158, 11, 0.6);
    }

    &.mano-obra {
      color: #8b5cf6;
      text-shadow: 0 0 12px rgba(139, 92, 246, 0.6);
    }

    &.utilidad {
      color: var(--accent-color);
      text-shadow: 0 0 12px var(--accent-glow);
    }

    &.margen {
      color: #3b82f6;
      text-shadow: 0 0 12px rgba(59, 130, 246, 0.6);
    }
  }
}

.tabla-card {
  background: var(--card-bg) !important;
  backdrop-filter: blur(12px);
  border: 1px solid var(--border-color);
  border-radius: 12px;
}

.tabs-cyber {
  :deep(.q-tab) {
    color: var(--text-primary);
    opacity: 0.7;

    &.q-tab--active {
      color: var(--accent-color);
      opacity: 1;
    }
  }
}

.separator-neon {
  background: linear-gradient(90deg, transparent, var(--border-color), transparent);
  height: 1px;
}

.tab-panels-cyber {
  background: transparent;
}

.tabla-presupuesto {
  :deep(thead tr th) {
    color: var(--accent-color) !important;
    font-weight: 600;
  }

  :deep(tbody tr td) {
    color: var(--text-primary) !important;
    opacity: 0.9;
  }

  .input-cell {
    :deep(input) {
      color: var(--text-primary) !important;
    }
  }
}

.table-header {
  width: 100%;
  display: flex;
  justify-content: space-between;
  align-items: center;

  .text-h6 {
    color: var(--accent-color);
  }
}

.aiu-card {
  background: var(--card-bg) !important;
  backdrop-filter: blur(12px);
  border: 1px solid var(--border-color);
  border-radius: 12px;

  .text-h6 {
    color: var(--accent-color);
  }
}

.aiu-grid {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
  gap: 16px;

  .aiu-input {
    .aiu-value {
      margin-top: 8px;
      font-size: 18px;
      font-weight: 600;
      color: var(--accent-color);
    }
  }
}

.total-final {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 16px;
  background: rgba(0, 229, 255, 0.1);
  border-radius: 8px;

  .total-label {
    font-size: 16px;
    font-weight: 600;
    color: var(--text-primary);
    opacity: 0.8;
    letter-spacing: 1px;
  }

  .total-value {
    font-size: 32px;
    font-weight: 700;
    color: var(--accent-color);
    text-shadow: 0 0 16px var(--accent-glow);
  }
}

.loading-container {
  display: flex;
  justify-content: center;
  align-items: center;
  min-height: 400px;
}

@media print {
  .proyecto-detalle-page {
    background: white !important;
    color: black !important;
  }

  .page-header h4 {
    color: black !important;
    text-shadow: none !important;
  }

  .subtitle {
    color: #333 !important;
  }

  .kpis-grid {
    display: none !important;
  }

  .tabla-card,
  .aiu-card {
    background: white !important;
    backdrop-filter: none !important;
    border: 1px solid #ddd !important;
    box-shadow: none !important;
    margin-bottom: 20px;
  }

  .q-tabs {
    display: none !important;
  }

  .q-tab-panels {
    background: white !important;
  }

  .q-tab-panel {
    display: block !important;
    padding: 0 !important;
    margin-bottom: 20px;
    page-break-inside: avoid;
  }

  .tabla-presupuesto {
    color: black !important;

    :deep(thead tr th) {
      color: black !important;
      background: #f0f0f0 !important;
      font-weight: bold;
      border-bottom: 2px solid #333 !important;
    }

    :deep(tbody tr td) {
      color: black !important;
      border-bottom: 1px solid #ddd !important;
    }

    :deep(input) {
      color: black !important;
    }
  }

  .tab-panels-cyber {
    display: block !important;
  }

  .aiu-grid .aiu-value,
  .total-final .total-value {
    color: black !important;
    text-shadow: none !important;
  }

  .total-final {
    background: #f0f0f0 !important;
    border: 1px solid #ddd;
  }

  .text-h6 {
    color: black !important;
    text-shadow: none !important;
  }
}
</style>
