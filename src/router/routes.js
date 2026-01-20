const routes = [
  {
    path: '/',
    component: () => import('layouts/MainLayout.vue'),
    children: [
      { path: '', component: () => import('pages/IndexPage.vue') },
      { path: 'clientes', component: () => import('pages/ClientesPage.vue') },
      { path: 'inventario', component: () => import('pages/InventarioPage.vue') },
      { path: 'cotizaciones', component: () => import('pages/CotizacionesPage.vue') },
      { path: 'nueva-cotizacion', component: () => import('pages/NuevaCotizacionPage.vue') },
    ],
  },

  // Always leave this as last one,
  // but you can also remove it
  {
    path: '/:catchAll(.*)*',
    component: () => import('pages/ErrorNotFound.vue'),
  },
]

export default routes
