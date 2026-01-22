<template>
  <q-page class="q-pa-md configuracion-page">
    <div class="q-mb-md">
      <h4 class="q-my-md">Configuración del Sistema</h4>
      <p class="page-subtitle">Administra los ajustes y parámetros del ERP</p>
    </div>

    <!-- Tabs de configuración -->
    <q-tabs
      v-model="tab"
      dense
      class="tabs-cyber"
      active-color="primary"
      indicator-color="primary"
      align="left"
    >
      <q-tab name="general" icon="tune" label="General" />
      <q-tab name="empresa" icon="business" label="Empresa" />
      <q-tab name="facturacion" icon="receipt_long" label="Facturación" />
      <q-tab name="usuarios" icon="group" label="Usuarios" />
    </q-tabs>

    <q-separator class="separator-neon q-my-md" />

    <!-- Contenido de tabs -->
    <q-tab-panels v-model="tab" animated class="panels-cyber">
      <!-- Tab General -->
      <q-tab-panel name="general">
        <q-card class="card-cyber q-mb-md">
          <q-card-section>
            <div class="text-h6 q-mb-md">Configuración General</div>

            <div class="row q-col-gutter-md">
              <div class="col-12 col-md-6">
                <q-input
                  v-model="config.general.nombreSistema"
                  label="Nombre del Sistema"
                  outlined
                  dense
                />
              </div>
              <div class="col-12 col-md-6">
                <q-select
                  v-model="config.general.idioma"
                  :options="idiomasOptions"
                  label="Idioma"
                  outlined
                  dense
                />
              </div>
              <div class="col-12 col-md-6">
                <q-select
                  v-model="config.general.moneda"
                  :options="monedasOptions"
                  label="Moneda"
                  outlined
                  dense
                />
              </div>
              <div class="col-12 col-md-6">
                <q-select
                  v-model="config.general.zonaHoraria"
                  :options="zonasHorariaOptions"
                  label="Zona Horaria"
                  outlined
                  dense
                />
              </div>
            </div>
          </q-card-section>
        </q-card>

        <q-card class="card-cyber">
          <q-card-section>
            <div class="text-h6 q-mb-md">Preferencias de Visualización</div>

            <div class="row q-col-gutter-md">
              <div class="col-12">
                <q-toggle
                  v-model="darkMode"
                  label="Modo Oscuro (Cyberpunk Theme)"
                  color="primary"
                />
              </div>
              <div class="col-12">
                <q-toggle
                  v-model="config.general.animaciones"
                  label="Habilitar Animaciones"
                  color="primary"
                />
              </div>
              <div class="col-12">
                <q-toggle
                  v-model="config.general.notificaciones"
                  label="Mostrar Notificaciones"
                  color="primary"
                />
              </div>
            </div>
          </q-card-section>
        </q-card>
      </q-tab-panel>

      <!-- Tab Empresa -->
      <q-tab-panel name="empresa">
        <q-card class="card-cyber">
          <q-card-section>
            <div class="text-h6 q-mb-md">Información de la Empresa</div>

            <div class="row q-col-gutter-md">
              <div class="col-12 col-md-6">
                <q-input
                  v-model="config.empresa.razonSocial"
                  label="Razón Social *"
                  outlined
                  dense
                />
              </div>
              <div class="col-12 col-md-6">
                <q-input v-model="config.empresa.nit" label="NIT *" outlined dense />
              </div>
              <div class="col-12">
                <q-input v-model="config.empresa.direccion" label="Dirección" outlined dense />
              </div>
              <div class="col-12 col-md-6">
                <q-input v-model="config.empresa.telefono" label="Teléfono" outlined dense />
              </div>
              <div class="col-12 col-md-6">
                <q-input v-model="config.empresa.email" label="Email" type="email" outlined dense />
              </div>
              <div class="col-12">
                <q-input v-model="config.empresa.sitioWeb" label="Sitio Web" outlined dense />
              </div>
            </div>
          </q-card-section>
        </q-card>
      </q-tab-panel>

      <!-- Tab Facturación -->
      <q-tab-panel name="facturacion">
        <q-card class="card-cyber q-mb-md">
          <q-card-section>
            <div class="text-h6 q-mb-md">Configuración de Facturación</div>

            <div class="row q-col-gutter-md">
              <div class="col-12 col-md-6">
                <q-input
                  v-model.number="config.facturacion.ivaPorcentaje"
                  label="IVA (%)"
                  type="number"
                  outlined
                  dense
                  suffix="%"
                />
              </div>
              <div class="col-12 col-md-6">
                <q-input
                  v-model="config.facturacion.prefijo"
                  label="Prefijo de Factura"
                  outlined
                  dense
                  hint="Ej: FAC-"
                />
              </div>
              <div class="col-12 col-md-6">
                <q-input
                  v-model.number="config.facturacion.consecutivoInicial"
                  label="Consecutivo Inicial"
                  type="number"
                  outlined
                  dense
                />
              </div>
              <div class="col-12 col-md-6">
                <q-select
                  v-model="config.facturacion.formatoFecha"
                  :options="formatosFechaOptions"
                  label="Formato de Fecha"
                  outlined
                  dense
                />
              </div>
            </div>
          </q-card-section>
        </q-card>

        <q-card class="card-cyber">
          <q-card-section>
            <div class="text-h6 q-mb-md">Términos y Condiciones</div>

            <q-input
              v-model="config.facturacion.terminosCondiciones"
              label="Términos y Condiciones (aparecerán en las facturas)"
              type="textarea"
              outlined
              dense
              rows="5"
            />
          </q-card-section>
        </q-card>
      </q-tab-panel>

      <!-- Tab Usuarios -->
      <q-tab-panel name="usuarios">
        <q-card class="card-cyber">
          <q-card-section>
            <div class="text-h6 q-mb-md">Gestión de Usuarios</div>
            <p class="text-grey-5">Próximamente: Administración de usuarios y permisos</p>

            <div class="q-mt-lg">
              <q-banner rounded class="bg-blue-9 text-white">
                <template v-slot:avatar>
                  <q-icon name="info" color="white" />
                </template>
                Esta funcionalidad estará disponible en una próxima actualización. Podrás gestionar
                usuarios, roles y permisos desde aquí.
              </q-banner>
            </div>
          </q-card-section>
        </q-card>
      </q-tab-panel>
    </q-tab-panels>

    <!-- Botones de acción -->
    <div class="row justify-end q-gutter-sm q-mt-lg">
      <q-btn
        label="Restaurar Valores"
        color="grey"
        outline
        icon="restore"
        @click="restaurarValores"
      />
      <q-btn
        label="Guardar Cambios"
        color="primary"
        icon="save"
        :loading="guardando"
        @click="guardarConfiguracion"
      />
    </div>
  </q-page>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue'
import { useQuasar } from 'quasar'

const $q = useQuasar()
const tab = ref('general')
const guardando = ref(false)

// Configuración del sistema
const config = ref({
  general: {
    nombreSistema: 'EA Technology ERP',
    idioma: 'Español',
    moneda: 'COP (Peso Colombiano)',
    zonaHoraria: 'America/Bogota',
    modoOscuro: true,
    animaciones: true,
    notificaciones: true,
  },
  empresa: {
    razonSocial: 'EA Technology SAS',
    nit: '900123456-7',
    direccion: 'Calle 123 #45-67, Bogotá',
    telefono: '+57 300 123 4567',
    email: 'contacto@eatechnology.com.co',
    sitioWeb: 'https://eatechnology.com.co',
  },
  facturacion: {
    ivaPorcentaje: 19,
    prefijo: 'FAC-',
    consecutivoInicial: 1,
    formatoFecha: 'DD/MM/YYYY',
    terminosCondiciones: 'Términos y condiciones de la factura...',
  },
})

// Computed for Dark Mode Toggle
const darkMode = computed({
  get: () => $q.dark.isActive,
  set: (val) => {
    $q.dark.set(val)
    localStorage.setItem('theme_dark', val)
    config.value.general.modoOscuro = val
  },
})

// Opciones para selects
const idiomasOptions = ['Español', 'English', 'Português']
const monedasOptions = ['COP (Peso Colombiano)', 'USD (Dólar)', 'EUR (Euro)']
const zonasHorariaOptions = ['America/Bogota', 'America/New_York', 'Europe/Madrid']
const formatosFechaOptions = ['DD/MM/YYYY', 'MM/DD/YYYY', 'YYYY-MM-DD']

onMounted(() => {
  // Restore theme preference
  const savedTheme = localStorage.getItem('theme_dark')
  if (savedTheme !== null) {
    const isDark = savedTheme === 'true'
    $q.dark.set(isDark)
    config.value.general.modoOscuro = isDark
  } else {
    // Default to dark
    $q.dark.set(true)
  }
})

// Funciones
const guardarConfiguracion = () => {
  guardando.value = true

  // Simular guardado (aquí iría la llamada al backend)
  setTimeout(() => {
    guardando.value = false
    $q.notify({
      type: 'positive',
      message: 'Configuración guardada exitosamente',
      position: 'top-right',
      icon: 'check_circle',
    })
  }, 1000)
}

const restaurarValores = () => {
  $q.dialog({
    title: 'Restaurar Valores',
    message:
      '¿Está seguro de restaurar los valores por defecto? Se perderán los cambios no guardados.',
    cancel: true,
    persistent: true,
  }).onOk(() => {
    // Restaurar valores por defecto
    config.value = {
      general: {
        nombreSistema: 'EA Technology ERP',
        idioma: 'Español',
        moneda: 'COP (Peso Colombiano)',
        zonaHoraria: 'America/Bogota',
        modoOscuro: true,
        animaciones: true,
        notificaciones: true,
      },
      empresa: {
        razonSocial: '',
        nit: '',
        direccion: '',
        telefono: '',
        email: '',
        sitioWeb: '',
      },
      facturacion: {
        ivaPorcentaje: 19,
        prefijo: 'FAC-',
        consecutivoInicial: 1,
        formatoFecha: 'DD/MM/YYYY',
        terminosCondiciones: '',
      },
    }

    // Also reset dark mode
    darkMode.value = true

    $q.notify({
      type: 'info',
      message: 'Valores restaurados',
      position: 'top-right',
    })
  })
}
</script>

<style lang="scss" scoped>
// Página
.configuracion-page {
  background: var(--bg-app);
}

// Título
h4 {
  color: var(--text-primary) !important;
  margin-bottom: 8px;
}

.page-subtitle {
  color: var(--text-primary);
  opacity: 0.7;
  font-size: 14px;
}

// Tabs
.tabs-cyber {
  background: var(--glass-color);
  border-radius: 12px 12px 0 0;
  padding: 8px;
  border-bottom: 1px solid var(--border-color);

  :deep(.q-tab) {
    color: var(--text-primary);
    opacity: 0.7;
    transition: all 0.3s ease;

    &:hover {
      color: var(--accent-color);
      opacity: 1;
    }

    &.q-tab--active {
      color: var(--accent-color);
      opacity: 1;
      text-shadow: 0 0 10px var(--accent-glow);
    }
  }

  :deep(.q-tabs__arrow) {
    color: var(--accent-color);
  }
}

// Separador neón
.separator-neon {
  background: linear-gradient(90deg, transparent, var(--accent-color), transparent);
  height: 1px;
  opacity: 0.5;
}

// Panels
.panels-cyber {
  background: transparent;

  :deep(.q-tab-panel) {
    padding: 0;
  }
}

// Cards
.card-cyber {
  background: var(--card-bg) !important;
  backdrop-filter: blur(12px) saturate(180%);
  -webkit-backdrop-filter: blur(12px) saturate(180%);
  border: 1px solid var(--border-color) !important;
  border-radius: 12px;
  box-shadow: 0 8px 32px rgba(0, 0, 0, 0.1);
  color: var(--text-primary) !important;

  :deep(.text-h6) {
    color: var(--accent-color) !important;
    text-shadow: 0 0 8px var(--accent-glow);
  }

  // Inputs and fields - ensure they integrate with the theme
  :deep(.q-field__label) {
    color: var(--text-primary);
    opacity: 0.7;
  }

  :deep(.q-field__native),
  :deep(input),
  :deep(textarea) {
    color: var(--text-primary) !important;
  }

  :deep(.q-icon) {
    color: var(--accent-color) !important;
  }

  :deep(.q-field--outlined .q-field__control:before) {
    border-color: var(--border-color);
  }

  :deep(.q-field--outlined:hover .q-field__control:before) {
    border-color: var(--accent-color);
  }

  :deep(.q-field__bottom) {
    color: var(--text-primary);
    opacity: 0.7;
  }

  :deep(.q-toggle__label) {
    color: var(--text-primary);
  }
}
</style>
