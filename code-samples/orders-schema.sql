-- Easypocket - code sample: orders and payment tables
-- Excerpt from database/easypocket.sql. Copyright (c) 2016-2026 Fernando. All rights reserved.

-- -----------------------------------------------------------------------------
-- Orders + simulated payments
-- -----------------------------------------------------------------------------
CREATE TABLE `venta` (
  `id_venta`          INT            NOT NULL AUTO_INCREMENT,
  `id_cliente`        INT            NOT NULL,
  `fecha`             DATETIME       NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `subtotal`          DECIMAL(10,2)  NOT NULL,
  `descuento`         DECIMAL(10,2)  NOT NULL DEFAULT 0,
  `total`             DECIMAL(10,2)  NOT NULL,
  `metodo_pago`       ENUM('tarjeta','paypal') NOT NULL,
  `referencia`        VARCHAR(40)    NOT NULL,        -- fake transaction id
  `tarjeta_marca`     VARCHAR(20)    NULL,            -- only brand + last 4 digits are stored
  `tarjeta_ultimos4`  CHAR(4)        NULL,
  `paypal_correo`     VARCHAR(100)   NULL,
  `estado`            ENUM('pagado','cancelado') NOT NULL DEFAULT 'pagado',
  PRIMARY KEY (`id_venta`),
  UNIQUE KEY `uq_venta_ref` (`referencia`),
  CONSTRAINT `fk_venta_cliente` FOREIGN KEY (`id_cliente`) REFERENCES `cliente` (`id_cliente`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `detalleventa` (
  `id_detalle`       INT            NOT NULL AUTO_INCREMENT,
  `id_venta`         INT            NOT NULL,
  `id_producto`      INT            NOT NULL,
  `cantidad`         INT            NOT NULL,
  `precio_unitario`  DECIMAL(8,2)   NOT NULL,
  `total_compra`     DECIMAL(10,2)  NOT NULL,
  `id_promociones`   INT            NULL,
  PRIMARY KEY (`id_detalle`),
  CONSTRAINT `fk_det_venta` FOREIGN KEY (`id_venta`)    REFERENCES `venta` (`id_venta`) ON DELETE CASCADE,
  CONSTRAINT `fk_det_produ` FOREIGN KEY (`id_producto`) REFERENCES `productos` (`id_produ`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
