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
            <q-badge color="cyan" floating>3</q-badge>
            <q-tooltip>Notificaciones</q-tooltip>
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
      :style="{ backgroundColor: '#050a14' }"
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
</script>

<style lang="scss" scoped>
// ==========================================
// VARIABLES Y PALETA DE COLORES CYBERPUNK
// ==========================================
$bg-primary: #050a14;
$blue-primary: #1e3a8a;
$blue-bright: #3b82f6;
$cyan-neon: #00e5ff;
$white: #ffffff;
$gray-light: #e2e8f0;
$gray-medium: #64748b;

// ==========================================
// LAYOUT PRINCIPAL
// ==========================================
.cyberpunk-layout {
  background: $bg-primary;

  // Patrón de fondo tecnológico sutil
  &::before {
    content: '';
    position: fixed;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background-image:
      linear-gradient(rgba($cyan-neon, 0.03) 1px, transparent 1px),
      linear-gradient(90deg, rgba($cyan-neon, 0.03) 1px, transparent 1px);
    background-size: 50px 50px;
    pointer-events: none;
    z-index: 0;
  }
}

// ==========================================
// HEADER CON EFECTO GLASS
// ==========================================
.glass-header {
  background: linear-gradient(135deg, rgba($blue-primary, 0.15), rgba($cyan-neon, 0.08)) !important;
  backdrop-filter: blur(16px) saturate(180%);
  -webkit-backdrop-filter: blur(16px) saturate(180%);
  border-bottom: 1px solid rgba($white, 0.1);
  box-shadow:
    0 8px 32px 0 rgba($cyan-neon, 0.1),
    inset 0 1px 0 0 rgba($white, 0.1);
}

.toolbar-glass {
  background: transparent;
  padding: 0 16px;
}

// Botón de menú con efecto cyber
.menu-btn-cyber {
  color: $cyan-neon;
  transition: all 0.3s ease;

  &:hover {
    color: $white;
    box-shadow: 0 0 20px rgba($cyan-neon, 0.6);
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
  filter: drop-shadow(0 0 8px rgba($cyan-neon, 0.5));
  transition: filter 0.3s ease;

  &:hover {
    filter: drop-shadow(0 0 16px rgba($cyan-neon, 0.8));
  }
}

.title-cyber {
  display: flex;
  align-items: center;
  gap: 8px;
  font-weight: 600;
  letter-spacing: 0.5px;

  .brand-name {
    color: $white;
    font-size: 18px;
    text-shadow: 0 0 10px rgba($cyan-neon, 0.5);
  }

  .separator {
    color: $cyan-neon;
    font-weight: 300;
    opacity: 0.6;
  }

  .app-name {
    color: $cyan-neon;
    font-size: 16px;
    font-weight: 500;
    text-shadow: 0 0 10px rgba($cyan-neon, 0.8);
  }
}

// Acciones del header
.header-actions {
  display: flex;
  gap: 8px;
  align-items: center;
}

.action-btn-cyber {
  color: $gray-light;
  transition: all 0.3s ease;
  position: relative;

  &:hover {
    color: $cyan-neon;
    box-shadow: 0 0 15px rgba($cyan-neon, 0.4);
  }

  &.profile-btn {
    border: 1px solid rgba($cyan-neon, 0.3);

    &:hover {
      border-color: $cyan-neon;
      box-shadow: 0 0 20px rgba($cyan-neon, 0.6);
    }
  }
}

// ==========================================
// DRAWER CON EFECTO GLASS
// ==========================================
.glass-drawer {
  background: $bg-primary !important;
  border-right: 1px solid rgba($cyan-neon, 0.2) !important;
  box-shadow: 4px 0 24px rgba($cyan-neon, 0.15);

  // Forzar fondo oscuro en elementos internos de Quasar
  :deep(.q-drawer__content) {
    background: $bg-primary !important;
  }

  :deep(.q-scrollarea__content) {
    background: $bg-primary !important;
  }

  // Overlay con efecto glass
  &::after {
    content: '';
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background: linear-gradient(
      180deg,
      rgba($blue-primary, 0.15),
      transparent 30%,
      transparent 70%,
      rgba($bg-primary, 0.3)
    );
    pointer-events: none;
    z-index: 0;
  }

  // Asegurar que el contenido esté sobre el overlay
  > * {
    position: relative;
    z-index: 1;
  }
}

// Header del drawer
.drawer-header {
  padding: 24px 16px;
  border-bottom: 1px solid rgba($cyan-neon, 0.2);
  background: rgba($blue-primary, 0.2);
  position: relative;
  z-index: 1;
}

.drawer-title {
  display: flex;
  align-items: center;
  gap: 12px;
  color: $white;
  font-size: 16px;
  font-weight: 600;
  letter-spacing: 1px;
  text-transform: uppercase;

  .drawer-icon {
    color: $cyan-neon;
    filter: drop-shadow(0 0 8px rgba($cyan-neon, 0.8));
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
    background: linear-gradient(90deg, transparent, rgba($cyan-neon, 0.05), transparent);
    transform: translateX(-100%);
    transition: transform 0.6s ease;
  }

  &:hover {
    background: rgba($blue-bright, 0.15);
    transform: translateX(4px);

    &::before {
      transform: translateX(100%);
    }

    .nav-icon {
      color: $cyan-neon;
      transform: scale(1.1);
    }

    .nav-label {
      color: $white;
    }
  }

  // Estado activo con resplandor neón
  &.active-route {
    background: linear-gradient(90deg, rgba($cyan-neon, 0.2), rgba($blue-bright, 0.15));
    border: 1px solid rgba($cyan-neon, 0.4);
    box-shadow:
      0 0 20px rgba($cyan-neon, 0.4),
      inset 0 0 20px rgba($cyan-neon, 0.1);

    .nav-icon {
      color: $cyan-neon;
      filter: drop-shadow(0 0 8px rgba($cyan-neon, 0.8));
    }

    .nav-label {
      color: $cyan-neon;
      font-weight: 600;
      text-shadow: 0 0 10px rgba($cyan-neon, 0.5);
    }

    .nav-caption {
      color: rgba($cyan-neon, 0.7);
    }
  }
}

.nav-icon {
  color: $gray-light;
  transition: all 0.3s ease;
  font-size: 24px;
}

.nav-label {
  color: $gray-light;
  font-size: 14px;
  font-weight: 500;
  transition: all 0.3s ease;
}

.nav-caption {
  color: $gray-medium;
  font-size: 11px;
  margin-top: 2px;
}

// Indicador de ruta activa
.active-indicator {
  width: 4px;
  height: 24px;
  background: $cyan-neon;
  border-radius: 2px;
  box-shadow: 0 0 10px rgba($cyan-neon, 0.8);
  animation: pulse-glow 2s ease-in-out infinite;
}

@keyframes pulse-glow {
  0%,
  100% {
    box-shadow: 0 0 10px rgba($cyan-neon, 0.8);
  }
  50% {
    box-shadow: 0 0 20px rgba($cyan-neon, 1);
  }
}

// Footer del drawer
.drawer-footer {
  position: absolute;
  bottom: 0;
  left: 0;
  right: 0;
  padding: 16px;
  border-top: 1px solid rgba($cyan-neon, 0.2);
  background: rgba($bg-primary, 0.95);
  backdrop-filter: blur(8px);
  z-index: 1;
}

.version-info {
  display: flex;
  align-items: center;
  gap: 8px;
  color: $gray-medium;
  font-size: 12px;
  margin-bottom: 8px;
}

.status-indicator {
  display: flex;
  align-items: center;
  gap: 8px;
  color: $gray-light;
  font-size: 11px;
}

.status-dot {
  width: 8px;
  height: 8px;
  background: $cyan-neon;
  border-radius: 50%;
  box-shadow: 0 0 10px rgba($cyan-neon, 0.8);
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
  background: linear-gradient(135deg, rgba($blue-primary, 0.4), rgba($bg-primary, 0.98)) !important;
  backdrop-filter: blur(16px) saturate(180%);
  -webkit-backdrop-filter: blur(16px) saturate(180%);
  border: 1px solid rgba($cyan-neon, 0.2);
  border-radius: 12px;
  padding: 8px;
  min-width: 200px;
  box-shadow: 0 8px 32px rgba($cyan-neon, 0.2);

  .q-item {
    border-radius: 8px;
    color: $gray-light;
    transition: all 0.3s ease;
    margin-bottom: 4px;

    &:hover {
      background: rgba($cyan-neon, 0.15);
      color: $cyan-neon;
      box-shadow: 0 0 10px rgba($cyan-neon, 0.3);
    }

    .q-icon {
      color: $gray-light;
    }

    &:hover .q-icon {
      color: $cyan-neon;
    }
  }

  .q-item__section--main {
    color: $gray-light;
  }
}

.cyber-separator {
  background: rgba($cyan-neon, 0.2);
  margin: 8px 0;
}

// ==========================================
// CONTENEDOR DE PÁGINAS
// ==========================================
.page-container-cyber {
  background: $bg-primary;
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
</style>
