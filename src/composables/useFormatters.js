/**
 * Composable para formateo de datos
 * Centraliza funciones de formateo reutilizables en toda la aplicación
 */

/**
 * Formatea un valor numérico como moneda colombiana (COP)
 * @param {number|string|null|undefined} valor - El valor a formatear
 * @param {number} decimales - Número de decimales a mostrar (default: 0)
 * @returns {string} - Valor formateado como moneda (ej: "$ 1.250.000")
 */
export const formatearMoneda = (valor, decimales = 0) => {
  // Validación: Si el valor es null, undefined o no es un número válido
  if (valor === null || valor === undefined || isNaN(valor)) {
    return new Intl.NumberFormat('es-CO', {
      style: 'currency',
      currency: 'COP',
      minimumFractionDigits: 0,
      maximumFractionDigits: 0,
    }).format(0)
  }

  // Convertir a número si es string
  const valorNumerico = typeof valor === 'string' ? parseFloat(valor) : valor

  // Formatear con configuración colombiana
  return new Intl.NumberFormat('es-CO', {
    style: 'currency',
    currency: 'COP',
    minimumFractionDigits: decimales,
    maximumFractionDigits: decimales,
  }).format(valorNumerico)
}

/**
 * Formatea una fecha en formato colombiano
 * @param {string|Date} fecha - La fecha a formatear
 * @param {object} opciones - Opciones de formato (opcional)
 * @returns {string} - Fecha formateada
 */
export const formatearFecha = (fecha, opciones = {}) => {
  if (!fecha) return ''

  const opcionesPorDefecto = {
    year: 'numeric',
    month: 'long',
    day: 'numeric',
    hour: '2-digit',
    minute: '2-digit',
    ...opciones,
  }

  return new Date(fecha).toLocaleDateString('es-CO', opcionesPorDefecto)
}

/**
 * Formatea una fecha en formato corto (solo fecha, sin hora)
 * @param {string|Date} fecha - La fecha a formatear
 * @returns {string} - Fecha formateada (ej: "19 de enero de 2026")
 */
export const formatearFechaCorta = (fecha) => {
  if (!fecha) return ''

  return new Date(fecha).toLocaleDateString('es-CO', {
    year: 'numeric',
    month: 'long',
    day: 'numeric',
  })
}

/**
 * Formatea un número con separadores de miles
 * @param {number} numero - El número a formatear
 * @param {number} decimales - Número de decimales (default: 0)
 * @returns {string} - Número formateado (ej: "1.250.000")
 */
export const formatearNumero = (numero, decimales = 0) => {
  if (numero === null || numero === undefined || isNaN(numero)) {
    return '0'
  }

  return new Intl.NumberFormat('es-CO', {
    minimumFractionDigits: decimales,
    maximumFractionDigits: decimales,
  }).format(numero)
}

/**
 * Formatea un porcentaje
 * @param {number} valor - El valor decimal (ej: 0.19 para 19%)
 * @param {number} decimales - Número de decimales (default: 0)
 * @returns {string} - Porcentaje formateado (ej: "19%")
 */
export const formatearPorcentaje = (valor, decimales = 0) => {
  if (valor === null || valor === undefined || isNaN(valor)) {
    return '0%'
  }

  return new Intl.NumberFormat('es-CO', {
    style: 'percent',
    minimumFractionDigits: decimales,
    maximumFractionDigits: decimales,
  }).format(valor)
}

/**
 * Hook principal del composable
 * Exporta todas las funciones de formateo
 */
export const useFormatters = () => {
  return {
    formatearMoneda,
    formatearFecha,
    formatearFechaCorta,
    formatearNumero,
    formatearPorcentaje,
  }
}

// Exportación por defecto del composable
export default useFormatters
