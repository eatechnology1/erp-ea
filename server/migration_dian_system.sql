-- Script de migración para sistema DIAN-compliant
-- Ejecutar en phpMyAdmin o MySQL CLI

-- 1. Modificar tabla cotizaciones: eliminar campos globales de AIU
ALTER TABLE cotizaciones 
DROP COLUMN IF EXISTS tipo_impuesto,
DROP COLUMN IF EXISTS aiu_administracion,
DROP COLUMN IF EXISTS aiu_imprevistos,
DROP COLUMN IF EXISTS aiu_utilidad;

-- 2. Agregar campos de régimen y AIU a cotizacion_items
ALTER TABLE cotizacion_items
ADD COLUMN regimen ENUM('VENTA_DIRECTA', 'SERVICIO_GENERAL', 'SERVICIO_ESPECIAL', 'OBRA_CONSTRUCCION') 
  DEFAULT 'VENTA_DIRECTA' NOT NULL AFTER subtotal,
ADD COLUMN aiu_administracion DECIMAL(5,2) DEFAULT 0.00 AFTER regimen,
ADD COLUMN aiu_imprevistos DECIMAL(5,2) DEFAULT 0.00 AFTER aiu_administracion,
ADD COLUMN aiu_utilidad DECIMAL(5,2) DEFAULT 0.00 AFTER aiu_imprevistos,
ADD COLUMN base_gravable DECIMAL(15,2) DEFAULT 0.00 AFTER aiu_utilidad,
ADD COLUMN iva_item DECIMAL(15,2) DEFAULT 0.00 AFTER base_gravable;

-- 3. Verificar que las columnas se agregaron correctamente
DESCRIBE cotizacion_items;

-- 4. Actualizar datos existentes (opcional, si hay datos)
-- UPDATE cotizacion_items SET regimen = 'VENTA_DIRECTA' WHERE regimen IS NULL;
