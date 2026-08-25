-- Catálogo de cargos/ocupaciones para seleccionar al agregar o editar un empleado
-- (antes era texto libre). empleados.cargo sigue guardando el nombre tal cual, no un ID,
-- para no tener que tocar ningún otro lugar del código que ya lo usa como texto.

CREATE TABLE cargos (
  id_cargo INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  nombre VARCHAR(60) NOT NULL,
  estado ENUM('1','0') NOT NULL DEFAULT '1'
);
