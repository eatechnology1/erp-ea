<template>
  <q-page class="dashboard-cyber">
    <!-- Loading State -->
    <div v-if="loading" class="loading-container">
      <q-spinner-orbit color="cyan" size="80px" />
      <div class="loading-text">Cargando datos del sistema...</div>
    </div>

    <!-- Dashboard Content -->
    <div v-else class="dashboard-content">
      <!-- Header -->
      <div class="dashboard-header">
        <h3 class="dashboard-title">
          <q-icon name="dashboard" class="title-icon" />
          Panel de Control
        </h3>
        <div class="dashboard-subtitle">Bienvenido al sistema ERP - EA Technology</div>
      </div>

      <!-- KPI Cards -->
      <div class="kpi-section">
        <q-card class="kpi-card kpi-clientes" @click="$router.push('/clientes')">
          <q-card-section class="kpi-content">
            <div class="kpi-icon-container">
              <q-icon name="people_alt" class="kpi-icon" />
            </div>
            <div class="kpi-data">
              <div class="kpi-value">{{ dashboardData.total_clientes }}</div>
              <div class="kpi-label">Clientes</div>
            </div>
          </q-card-section>
          <div class="kpi-glow"></div>
        </q-card>

        <q-card class="kpi-card kpi-productos" @click="$router.push('/inventario')">
          <q-card-section class="kpi-content">
            <div class="kpi-icon-container">
              <q-icon name="inventory_2" class="kpi-icon" />
            </div>
            <div class="kpi-data">
              <div class="kpi-value">{{ dashboardData.total_productos }}</div>
              <div class="kpi-label">Productos</div>
            </div>
          </q-card-section>
          <div class="kpi-glow"></div>
        </q-card>

        <q-card class="kpi-card kpi-cotizaciones" @click="$router.push('/cotizaciones')">
          <q-card-section class="kpi-content">
            <div class="kpi-icon-container">
              <q-icon name="description" class="kpi-icon" />
            </div>
            <div class="kpi-data">
              <div class="kpi-value">{{ dashboardData.total_cotizaciones }}</div>
              <div class="kpi-label">Cotizaciones</div>
            </div>
          </q-card-section>
          <div class="kpi-glow"></div>
        </q-card>
      </div>

      <!-- Quick Actions -->
      <div class="quick-actions-section">
        <h5 class="section-title">
          <q-icon name="flash_on" />
          Accesos Rápidos
        </h5>
        <div class="quick-actions-grid">
          <q-btn class="action-btn action-cotizacion" to="/nueva-cotizacion" no-caps>
            <q-icon name="point_of_sale" size="32px" />
            <span>Nueva Cotización</span>
          </q-btn>

          <q-btn class="action-btn action-cliente" to="/clientes" no-caps>
            <q-icon name="person_add" size="32px" />
            <span>Nuevo Cliente</span>
          </q-btn>

          <q-btn class="action-btn action-inventario" to="/inventario" no-caps>
            <q-icon name="inventory" size="32px" />
            <span>Ver Inventario</span>
          </q-btn>
        </div>
      </div>

      <!-- Recent Activity -->
      <div class="activity-section">
        <h5 class="section-title">
          <q-icon name="history" />
          Actividad Reciente
        </h5>

        <q-card class="activity-card">
          <q-table
            :rows="dashboardData.ultimas_cotizaciones"
            :columns="columns"
            row-key="id"
            flat
            class="cyber-table"
            :hide-pagination="true"
          >
            <template v-slot:body-cell-estado="props">
              <q-td :props="props">
                <q-badge
                  :color="getEstadoColor(props.row.estado)"
                  :label="props.row.estado"
                  class="estado-badge"
                />
              </q-td>
            </template>

            <template v-slot:body-cell-total="props">
              <q-td :props="props">
                <span class="total-amount">{{ formatearPrecio(props.row.total) }}</span>
              </q-td>
            </template>

            <template v-slot:body-cell-fecha="props">
              <q-td :props="props">
                {{ formatearFecha(props.row.fecha) }}
              </q-td>
            </template>

            <template v-slot:no-data>
              <div class="no-data-message">
                <q-icon name="inbox" size="48px" />
                <div>No hay cotizaciones recientes</div>
              </div>
            </template>
          </q-table>
        </q-card>
      </div>
    </div>
  </q-page>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import { api } from 'src/boot/axios'
import { useQuasar } from 'quasar'

const $q = useQuasar()
const loading = ref(true)
const dashboardData = ref({
  total_clientes: 0,
  total_productos: 0,
  total_cotizaciones: 0,
  ultimas_cotizaciones: [],
})

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
    month: 'short',
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

const cargarDatos = async () => {
  try {
    loading.value = true

    const response = await api.get('/dashboard.php')

    if (response.data.success) {
      dashboardData.value = response.data.data
    }
  } catch (error) {
    console.error('Error al cargar dashboard:', error)
    $q.notify({
      type: 'negative',
      message: 'Error al conectar con el servidor',
      position: 'top-right',
    })
  } finally {
    loading.value = false
  }
}

onMounted(() => {
  cargarDatos()
})
</script>

<style lang="scss" scoped>
.dashboard-cyber {
  background: var(--bg-app);
  min-height: 100vh;
  padding: 24px;
}

// ==========================================
// LOADING STATE
// ==========================================
.loading-container {
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  min-height: 80vh;
  gap: 24px;
}

.loading-text {
  color: var(--accent-color);
  font-size: 18px;
  font-weight: 500;
  text-shadow: 0 0 10px var(--accent-glow);
}

// ==========================================
// DASHBOARD HEADER
// ==========================================
.dashboard-header {
  margin-bottom: 32px;
}

.dashboard-title {
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

.dashboard-subtitle {
  color: var(--text-primary);
  font-size: 16px;
  opacity: 0.7;
  margin-left: 56px;
}

// ==========================================
// KPI CARDS
// ==========================================
.kpi-section {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
  gap: 24px;
  margin-bottom: 40px;
}

.kpi-card {
  background: var(--card-bg) !important;
  backdrop-filter: blur(12px);
  -webkit-backdrop-filter: blur(12px);
  border: 1px solid var(--border-color);
  border-radius: 16px;
  position: relative;
  overflow: hidden;
  transition: all 0.3s ease;
  cursor: pointer;

  &:hover {
    transform: translateY(-4px);
    border-color: var(--accent-color);
    box-shadow: 0 8px 32px var(--accent-glow);

    .kpi-glow {
      opacity: 1;
    }
  }
}

.kpi-content {
  display: flex;
  align-items: center;
  gap: 24px;
  padding: 32px !important;
  position: relative;
  z-index: 1;
}

.kpi-icon-container {
  width: 80px;
  height: 80px;
  display: flex;
  align-items: center;
  justify-content: center;
  background: rgba(0, 229, 255, 0.1);
  border-radius: 50%;
  border: 2px solid var(--border-color);
}

.kpi-icon {
  font-size: 48px;
  color: var(--accent-color);
  filter: drop-shadow(0 0 12px var(--accent-glow));
}

.kpi-data {
  flex: 1;
}

.kpi-value {
  font-size: 48px;
  font-weight: 700;
  color: var(--text-primary);
  line-height: 1;
  margin-bottom: 8px;
  text-shadow: 0 0 20px var(--accent-glow);
}

.kpi-label {
  font-size: 16px;
  color: var(--text-primary);
  opacity: 0.7;
  text-transform: uppercase;
  letter-spacing: 1px;
  font-weight: 500;
}

.kpi-glow {
  position: absolute;
  top: 50%;
  left: 50%;
  width: 200px;
  height: 200px;
  background: radial-gradient(circle, var(--accent-glow), transparent);
  transform: translate(-50%, -50%);
  opacity: 0;
  transition: opacity 0.3s ease;
  pointer-events: none;
}

// ==========================================
// QUICK ACTIONS
// ==========================================
.quick-actions-section {
  margin-bottom: 40px;
}

.section-title {
  color: var(--text-primary);
  font-size: 20px;
  font-weight: 600;
  margin: 0 0 20px 0;
  display: flex;
  align-items: center;
  gap: 12px;

  .q-icon {
    color: var(--accent-color);
    filter: drop-shadow(0 0 8px var(--accent-glow));
  }
}

.quick-actions-grid {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
  gap: 16px;
}

.action-btn {
  height: 120px;
  background: var(--glass-color) !important;
  border: 1px solid var(--border-color);
  border-radius: 12px;
  color: var(--text-primary) !important;
  font-size: 16px;
  font-weight: 600;
  display: flex;
  flex-direction: column;
  gap: 12px;
  transition: all 0.3s ease;

  &:hover {
    background: var(--card-bg) !important;
    border-color: var(--accent-color);
    box-shadow: 0 0 24px var(--accent-glow);
    transform: translateY(-2px);
  }

  .q-icon {
    color: var(--accent-color);
    filter: drop-shadow(0 0 8px var(--accent-glow));
  }
}

// ==========================================
// ACTIVITY TABLE
// ==========================================
.activity-section {
  margin-bottom: 24px;
}

.activity-card {
  background: var(--card-bg) !important;
  backdrop-filter: blur(12px);
  -webkit-backdrop-filter: blur(12px);
  border: 1px solid var(--border-color);
  border-radius: 12px;
}

.cyber-table {
  background: transparent !important;

  :deep(thead) {
    tr {
      background: var(--glass-color);

      th {
        color: var(--accent-color) !important;
        font-weight: 600;
        font-size: 14px;
        text-transform: uppercase;
        letter-spacing: 0.5px;
        border-bottom: 2px solid var(--border-color);
      }
    }
  }

  :deep(tbody) {
    tr {
      border-bottom: 1px solid var(--border-color);
      transition: background 0.2s ease;

      &:hover {
        background: rgba(0, 229, 255, 0.05);
      }

      td {
        color: var(--text-primary);
        opacity: 0.8;
        font-size: 14px;
      }
    }
  }
}

.estado-badge {
  font-weight: 600;
  text-transform: uppercase;
  font-size: 11px;
  padding: 4px 12px;
}

.total-amount {
  color: var(--accent-color);
  font-weight: 700;
  font-size: 16px;
  text-shadow: 0 0 8px var(--accent-glow);
}

.no-data-message {
  display: flex;
  flex-direction: column;
  align-items: center;
  gap: 16px;
  padding: 48px;
  color: var(--text-primary);
  opacity: 0.6;

  .q-icon {
    color: var(--accent-color);
    opacity: 0.3;
  }
}

// ==========================================
// RESPONSIVE
// ==========================================
@media (max-width: 768px) {
  .dashboard-cyber {
    padding: 16px;
  }

  .dashboard-title {
    font-size: 24px;

    .title-icon {
      font-size: 32px;
    }
  }

  .kpi-section {
    grid-template-columns: 1fr;
  }

  .kpi-value {
    font-size: 36px;
  }

  .quick-actions-grid {
    grid-template-columns: 1fr;
  }
}
</style>
