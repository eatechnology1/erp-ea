<template>
  <q-layout view="lHh Lpr lFf" class="cyberpunk-layout">
    <!-- Header con efecto Glass -->
    <q-header elevated class="glass-header">
      <q-toolbar class="toolbar-glass">
        <!-- Botón de menú con efecto futurista -->
        <q-btn
          flat
          dense
          round
          icon="menu"
          aria-label="Menu"
          @click="toggleLeftDrawer"
          class="menu-btn-cyber"
        />

        <!-- Logo y Título -->
        <div class="logo-container">
          <img src="/icons/favicon-128x128.png" alt="EA Technology" class="logo-img" />
          <q-toolbar-title class="title-cyber">
            <span class="brand-name">EA Technology</span>
            <span class="separator">|</span>
            <span class="app-name">ERP</span>
          </q-toolbar-title>
        </div>

        <q-space />

        <!-- Iconos de la derecha -->
        <div class="header-actions">
          <!-- Notificaciones -->
          <q-btn flat dense round icon="notifications" class="action-btn-cyber">
            <q-badge v-if="notificacionesStore.noLeidas > 0" color="cyan" floating>
              {{ notificacionesStore.noLeidas }}
            </q-badge>
            <q-tooltip>Notificaciones</q-tooltip>

            <!-- Menú de notificaciones -->
            <q-menu class="glass-menu notifications-menu" max-width="400px">
              <q-list class="notifications-list">
                <!-- Header del menú -->
                <q-item class="notifications-header">
                  <q-item-section>
                    <div class="text-h6">Notificaciones</div>
                  </q-item-section>
                  <q-item-section side v-if="notificacionesStore.noLeidas > 0">
                    <q-btn
                      flat
                      dense
                      size="sm"
                      label="Marcar todas"
                      @click="notificacionesStore.marcarTodasComoLeidas()"
                      class="mark-all-btn"
                    />
                  </q-item-section>
                </q-item>

                <q-separator class="cyber-separator" />

                <!-- Lista de notificaciones -->
                <div class="notifications-scroll">
                  <q-item
                    v-for="notif in notificacionesStore.todas"
                    :key="notif.id"
                    clickable
                    @click="notificacionesStore.marcarComoLeida(notif.id)"
                    :class="{ 'notification-unread': !notif.leida }"
                    class="notification-item"
                  >
                    <q-item-section avatar>
                      <q-avatar
                        :color="getNotificationColor(notif.tipo)"
                        text-color="white"
                        :icon="notif.icono"
                      />
                    </q-item-section>

                    <q-item-section>
                      <q-item-label class="notification-title">
                        {{ notif.titulo }}
                      </q-item-label>
                      <q-item-label caption class="notification-message">
                        {{ notif.mensaje }}
                      </q-item-label>
                      <q-item-label caption class="notification-time">
                        {{ formatearTiempo(notif.fecha) }}
                      </q-item-label>
                    </q-item-section>

                    <q-item-section side v-if="!notif.leida">
                      <div class="unread-dot"></div>
                    </q-item-section>
                  </q-item>

                  <!-- Mensaje cuando no hay notificaciones -->
                  <q-item v-if="notificacionesStore.todas.length === 0" class="no-notifications">
                    <q-item-section class="text-center">
                      <q-icon name="notifications_none" size="48px" color="grey-5" />
                      <div class="text-grey-5 q-mt-sm">No hay notificaciones</div>
                    </q-item-section>
                  </q-item>
                </div>
              </q-list>
            </q-menu>
          </q-btn>

          <!-- Perfil de usuario -->
          <q-btn flat dense round icon="account_circle" class="action-btn-cyber profile-btn">
            <q-tooltip>Perfil</q-tooltip>
            <q-menu>
              <q-list class="glass-menu">
                <q-item clickable v-close-popup>
                  <q-item-section avatar>
                    <q-icon name="person" />
                  </q-item-section>
                  <q-item-section>Mi Perfil</q-item-section>
                </q-item>
                <q-item clickable v-close-popup>
                  <q-item-section avatar>
                    <q-icon name="settings" />
                  </q-item-section>
                  <q-item-section>Configuración</q-item-section>
                </q-item>
                <q-separator class="cyber-separator" />
                <q-item clickable v-close-popup>
                  <q-item-section avatar>
                    <q-icon name="logout" />
                  </q-item-section>
                  <q-item-section>Cerrar Sesión</q-item-section>
                </q-item>
              </q-list>
            </q-menu>
          </q-btn>
        </div>
      </q-toolbar>
    </q-header>

    <!-- Sidebar con efecto Glass -->
    <q-drawer
      v-model="leftDrawerOpen"
      show-if-above
      bordered
      class="glass-drawer"
      :style="{ backgroundColor: 'var(--sidebar-bg)' }"
    >
      <!-- Header del Drawer -->
      <div class="drawer-header">
        <div class="drawer-title">
          <q-icon name="bolt" size="24px" class="drawer-icon" />
          <span>Navegación</span>
        </div>
      </div>

      <!-- Lista de navegación -->
      <q-list class="nav-list">
        <q-item
          v-for="item in menuItems"
          :key="item.path"
          clickable
          :to="item.path"
          exact
          class="nav-item-cyber"
          :class="{ 'active-route': isActiveRoute(item.path) }"
        >
          <q-item-section avatar>
            <q-icon :name="item.icon" class="nav-icon" />
          </q-item-section>
          <q-item-section>
            <q-item-label class="nav-label">{{ item.label }}</q-item-label>
            <q-item-label caption class="nav-caption" v-if="item.caption">
              {{ item.caption }}
            </q-item-label>
          </q-item-section>

          <!-- Indicador de ruta activa -->
          <q-item-section side v-if="isActiveRoute(item.path)">
            <div class="active-indicator"></div>
          </q-item-section>
        </q-item>
      </q-list>

      <!-- Footer del Drawer -->
      <div class="drawer-footer">
        <div class="version-info">
          <q-icon name="info" size="16px" />
          <span>v1.0.0</span>
        </div>
        <div class="status-indicator">
          <div class="status-dot"></div>
          <span>Sistema Activo</span>
        </div>
      </div>
    </q-drawer>

    <!-- Contenedor de páginas -->
    <q-page-container class="page-container-cyber">
      <router-view />
    </q-page-container>
  </q-layout>
</template>

<script setup>
import { ref } from 'vue'
import { useRoute } from 'vue-router'
import { useNotificacionesStore } from 'src/stores/notificaciones'

const notificacionesStore = useNotificacionesStore()

const route = useRoute()
const leftDrawerOpen = ref(false)

// Menú de navegación
const menuItems = [
  {
    label: 'Dashboard',
    caption: 'Panel principal',
    icon: 'dashboard',
    path: '/',
  },
  {
    label: 'Clientes',
    caption: 'Gestión de clientes',
    icon: 'people_alt',
    path: '/clientes',
  },
  {
    label: 'Inventario',
    caption: 'Productos y servicios',
    icon: 'inventory_2',
    path: '/inventario',
  },
  {
    label: 'Cotizaciones',
    caption: 'Historial de cotizaciones',
    icon: 'description',
    path: '/cotizaciones',
  },
  {
    label: 'Proyectos',
    caption: 'Gestión y presupuestos',
    icon: 'engineering',
    path: '/proyectos',
  },
  {
    label: 'Nueva Cotización',
    caption: 'Crear cotización',
    icon: 'point_of_sale',
    path: '/nueva-cotizacion',
  },
  {
    label: 'Configuración',
    caption: 'Ajustes del sistema',
    icon: 'settings_suggest',
    path: '/configuracion',
  },
]

function toggleLeftDrawer() {
  leftDrawerOpen.value = !leftDrawerOpen.value
}

function isActiveRoute(path) {
  return route.path === path
}

// Formatear tiempo relativo
function formatearTiempo(fecha) {
  const ahora = new Date()
  const diff = ahora - fecha
  const minutos = Math.floor(diff / 1000 / 60)
  const horas = Math.floor(minutos / 60)
  const dias = Math.floor(horas / 24)

  if (minutos < 1) return 'Ahora'
  if (minutos < 60) return `Hace ${minutos} min`
  if (horas < 24) return `Hace ${horas}h`
  if (dias < 7) return `Hace ${dias}d`
  return fecha.toLocaleDateString()
}

// Obtener color según tipo de notificación
function getNotificationColor(tipo) {
  const colores = {
    success: 'positive',
    error: 'negative',
    warning: 'warning',
    info: 'info',
  }
  return colores[tipo] || 'info'
}
</script>

<style lang="scss" scoped>
// ==========================================
// LAYOUT PRINCIPAL
// ==========================================
.cyberpunk-layout {
  background: var(--bg-app);

  // Patrón de fondo tecnológico sutil
  &::before {
    content: '';
    position: fixed;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background-image:
      linear-gradient(var(--border-color) 1px, transparent 1px),
      linear-gradient(90deg, var(--border-color) 1px, transparent 1px);
    background-size: 50px 50px;
    pointer-events: none;
    z-index: 0;
    opacity: 0.3;
  }
}

// ==========================================
// HEADER CON EFECTO GLASS
// ==========================================
.glass-header {
  background: var(--glass-color) !important;
  backdrop-filter: blur(16px) saturate(180%);
  -webkit-backdrop-filter: blur(16px) saturate(180%);
  border-bottom: 1px solid var(--border-color);
  box-shadow: 0 4px 30px rgba(0, 0, 0, 0.1);
}

.toolbar-glass {
  background: transparent;
  padding: 0 16px;
}

// Botón de menú con efecto cyber
.menu-btn-cyber {
  color: var(--accent-color);
  transition: all 0.3s ease;

  &:hover {
    color: var(--text-primary);
    box-shadow: 0 0 20px var(--accent-glow);
    transform: scale(1.1);
  }
}

// Logo y título
.logo-container {
  display: flex;
  align-items: center;
  gap: 12px;
  margin-left: 8px;
}

.logo-img {
  height: 36px;
  width: auto;
  filter: drop-shadow(0 0 8px var(--accent-glow));
  transition: filter 0.3s ease;

  &:hover {
    filter: drop-shadow(0 0 16px var(--accent-glow));
  }
}

.title-cyber {
  display: flex;
  align-items: center;
  gap: 8px;
  font-weight: 600;
  letter-spacing: 0.5px;

  .brand-name {
    color: var(--text-primary);
    font-size: 18px;
    text-shadow: 0 0 10px var(--accent-glow);
  }

  .separator {
    color: var(--accent-color);
    font-weight: 300;
    opacity: 0.6;
  }

  .app-name {
    color: var(--accent-color);
    font-size: 16px;
    font-weight: 500;
    text-shadow: 0 0 10px var(--accent-glow);
  }
}

// Acciones del header
.header-actions {
  display: flex;
  gap: 8px;
  align-items: center;
}

.action-btn-cyber {
  color: var(--text-primary);
  opacity: 0.8;
  transition: all 0.3s ease;
  position: relative;

  &:hover {
    color: var(--accent-color);
    opacity: 1;
    box-shadow: 0 0 15px var(--accent-glow);
  }

  &.profile-btn {
    border: 1px solid var(--border-color);

    &:hover {
      border-color: var(--accent-color);
      box-shadow: 0 0 20px var(--accent-glow);
    }
  }
}

// ==========================================
// DRAWER CON EFECTO GLASS
// ==========================================
.glass-drawer {
  background: var(--sidebar-bg) !important;
  border-right: 1px solid var(--border-color) !important;
  box-shadow: 4px 0 24px rgba(0, 0, 0, 0.1);

  // Forzar fondo oscuro en elementos internos de Quasar
  :deep(.q-drawer__content) {
    background: transparent !important;
  }

  :deep(.q-scrollarea__content) {
    background: transparent !important;
  }
}

// Header del drawer
.drawer-header {
  padding: 24px 16px;
  border-bottom: 1px solid var(--border-color);
  background: rgba(var(--accent-color), 0.1); // Fallback assumption
  background: var(--glass-color);
  position: relative;
  z-index: 1;
}

.drawer-title {
  display: flex;
  align-items: center;
  gap: 12px;
  color: var(--text-primary);
  font-size: 16px;
  font-weight: 600;
  letter-spacing: 1px;
  text-transform: uppercase;

  .drawer-icon {
    color: var(--accent-color);
    filter: drop-shadow(0 0 8px var(--accent-glow));
  }
}

// Lista de navegación
.nav-list {
  padding: 16px 8px;
}

.nav-item-cyber {
  margin-bottom: 8px;
  border-radius: 12px;
  transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
  position: relative;
  overflow: hidden;

  // Efecto de fondo sutil
  &::before {
    content: '';
    position: absolute;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background: linear-gradient(90deg, transparent, var(--glass-border), transparent);
    transform: translateX(-100%);
    transition: transform 0.6s ease;
    opacity: 0.1;
  }

  &:hover {
    background: var(--glass-color);
    transform: translateX(4px);

    &::before {
      transform: translateX(100%);
    }

    .nav-icon {
      color: var(--accent-color);
      transform: scale(1.1);
    }

    .nav-label {
      color: var(--text-primary);
    }
  }

  // Estado activo
  &.active-route {
    background: rgba(var(--accent-color), 0.1); // Not exact but close proxy if we can't use rgb var
    background: var(--glass-color);
    border: 1px solid var(--accent-color);
    box-shadow:
      0 0 20px var(--accent-glow),
      inset 0 0 20px var(--accent-glow);

    .nav-icon {
      color: var(--accent-color);
      filter: drop-shadow(0 0 8px var(--accent-glow));
    }

    .nav-label {
      color: var(--accent-color);
      font-weight: 600;
      text-shadow: 0 0 10px var(--accent-glow);
    }

    .nav-caption {
      color: var(--accent-color);
      opacity: 0.7;
    }
  }
}

.nav-icon {
  color: var(--text-primary);
  opacity: 0.7;
  transition: all 0.3s ease;
  font-size: 24px;
}

.nav-label {
  color: var(--text-primary);
  font-size: 14px;
  font-weight: 500;
  transition: all 0.3s ease;
}

.nav-caption {
  color: var(--text-primary);
  opacity: 0.5;
  font-size: 11px;
  margin-top: 2px;
}

// Indicador de ruta activa
.active-indicator {
  width: 4px;
  height: 24px;
  background: var(--accent-color);
  border-radius: 2px;
  box-shadow: 0 0 10px var(--accent-glow);
  animation: pulse-glow 2s ease-in-out infinite;
}

@keyframes pulse-glow {
  0%,
  100% {
    box-shadow: 0 0 10px var(--accent-glow);
  }
  50% {
    box-shadow: 0 0 20px var(--accent-glow);
  }
}

// Footer del drawer
.drawer-footer {
  position: absolute;
  bottom: 0;
  left: 0;
  right: 0;
  padding: 16px;
  border-top: 1px solid var(--border-color);
  background: var(--glass-color);
  backdrop-filter: blur(8px);
  z-index: 1;
}

.version-info {
  display: flex;
  align-items: center;
  gap: 8px;
  color: var(--text-primary);
  opacity: 0.5;
  font-size: 12px;
  margin-bottom: 8px;
}

.status-indicator {
  display: flex;
  align-items: center;
  gap: 8px;
  color: var(--text-primary);
  font-size: 11px;
}

.status-dot {
  width: 8px;
  height: 8px;
  background: var(--accent-color);
  border-radius: 50%;
  box-shadow: 0 0 10px var(--accent-glow);
  animation: pulse-dot 2s ease-in-out infinite;
}

@keyframes pulse-dot {
  0%,
  100% {
    transform: scale(1);
    opacity: 1;
  }
  50% {
    transform: scale(1.2);
    opacity: 0.8;
  }
}

// ==========================================
// MENÚ DESPLEGABLE CON EFECTO GLASS
// ==========================================
.glass-menu {
  background: var(--glass-color) !important;
  backdrop-filter: blur(16px) saturate(180%);
  -webkit-backdrop-filter: blur(16px) saturate(180%);
  border: 1px solid var(--border-color);
  border-radius: 12px;
  padding: 8px;
  min-width: 200px;
  box-shadow: 0 8px 32px rgba(0, 0, 0, 0.2);

  .q-item {
    border-radius: 8px;
    color: var(--text-primary);
    transition: all 0.3s ease;
    margin-bottom: 4px;

    &:hover {
      background: rgba(0, 0, 0, 0.05); // Simple hover
      color: var(--accent-color);
      box-shadow: 0 0 10px var(--accent-glow);
    }

    .q-icon {
      color: var(--text-primary);
      opacity: 0.7;
    }

    &:hover .q-icon {
      color: var(--accent-color);
      opacity: 1;
    }
  }

  .q-item__section--main {
    color: var(--text-primary);
  }
}

.cyber-separator {
  background: var(--border-color);
  margin: 8px 0;
}

// ==========================================
// MENÚ DE NOTIFICACIONES
// ==========================================
.notifications-menu {
  max-height: 500px;
}

.notifications-list {
  padding: 0;
}

.notifications-header {
  padding: 16px;
  background: var(--glass-color);
  border-bottom: 1px solid var(--border-color);

  .text-h6 {
    color: var(--text-primary);
    font-size: 16px;
    font-weight: 600;
  }

  .mark-all-btn {
    color: var(--accent-color);
    font-size: 11px;

    &:hover {
      background: rgba(0, 0, 0, 0.05);
    }
  }
}

.notifications-scroll {
  max-height: 400px;
  overflow-y: auto;

  // Scrollbar personalizado
  &::-webkit-scrollbar {
    width: 6px;
  }

  &::-webkit-scrollbar-track {
    background: transparent;
  }

  &::-webkit-scrollbar-thumb {
    background: var(--accent-color);
    border-radius: 3px;
    opacity: 0.5;

    &:hover {
      background: var(--accent-color);
    }
  }
}

.notification-item {
  padding: 12px 16px;
  border-bottom: 1px solid var(--border-color);
  transition: all 0.3s ease;

  &:hover {
    background: var(--glass-color);
    transform: translateX(4px);
  }

  &.notification-unread {
    background: rgba(
      0,
      229,
      255,
      0.05
    ); // Keep legacy hardcode or try to adapt? Let's use accent glow
    border-left: 3px solid var(--accent-color);
  }
}

.notification-title {
  color: var(--text-primary);
  font-weight: 600;
  font-size: 13px;
  margin-bottom: 4px;
}

.notification-message {
  color: var(--text-primary);
  opacity: 0.8;
  font-size: 12px;
  line-height: 1.4;
  margin-bottom: 4px;
}

.notification-time {
  color: var(--text-primary);
  opacity: 0.6;
  font-size: 11px;
}

.unread-dot {
  width: 8px;
  height: 8px;
  background: var(--accent-color);
  border-radius: 50%;
  box-shadow: 0 0 8px var(--accent-glow);
  animation: pulse-dot 2s ease-in-out infinite;
}

.no-notifications {
  color: var(--text-primary);
  opacity: 0.5;
  padding: 40px 20px;
  text-align: center;
}

// ==========================================
// CONTENEDOR DE PÁGINAS
// ==========================================
.page-container-cyber {
  background: var(--bg-app);
}

// ==========================================
// RESPONSIVE
// ==========================================
@media (max-width: 1023px) {
  .title-cyber {
    .brand-name {
      font-size: 16px;
    }

    .app-name {
      font-size: 14px;
    }
  }

  .nav-caption {
    display: none;
  }
}

@media (max-width: 599px) {
  .logo-img {
    height: 28px;
  }

  .title-cyber {
    .brand-name {
      display: none;
    }

    .separator {
      display: none;
    }
  }
}

// ==========================================
// ESTILOS DE IMPRESIÓN
// ==========================================
@media print {
  .q-drawer,
  .q-header,
  .q-page-sticky,
  .q-btn,
  .q-dialog {
    display: none !important;
  }

  .q-page-container {
    padding-left: 0 !important;
    padding-top: 0 !important;
  }

  .cyberpunk-layout {
    background: white !important;

    &::before {
      display: none !important;
    }
  }

  body {
    background: white !important;
    color: black !important;
  }
}
</style>
