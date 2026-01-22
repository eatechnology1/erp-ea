-- Script de migración para agregar soporte de AIU a cotizaciones
-- Ejecutar en phpMyAdmin o MySQL CLI

-- Agregar columnas para tipo de impuesto y porcentajes AIU
ALTER TABLE cotizaciones 
ADD COLUMN tipo_impuesto ENUM('IVA', 'AIU') DEFAULT 'IVA' NOT NULL AFTER total,
ADD COLUMN aiu_administracion DECIMAL(5,2) DEFAULT 10.00 AFTER tipo_impuesto,
ADD COLUMN aiu_imprevistos DECIMAL(5,2) DEFAULT 5.00 AFTER aiu_administracion,
ADD COLUMN aiu_utilidad DECIMAL(5,2) DEFAULT 5.00 AFTER aiu_imprevistos;

-- Verificar que las columnas se agregaron correctamente
DESCRIBE cotizaciones;
