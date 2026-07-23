<?php
/**
 * Validación de cédula ecuatoriana.
 * Algoritmo oficial: verifica provincia (2 primeros dígitos), tercer dígito < 6,
 * y dígito verificador (posición 10).
 */
function validarCedulaEcuatoriana(string $cedula): bool
{
    $cedula = trim($cedula);

    if (!preg_match('/^\d{10}$/', $cedula)) {
        return false;
    }

    $provincia = (int)substr($cedula, 0, 2);
    if ($provincia < 1 || $provincia > 24) {
        return false;
    }

    if ((int)$cedula[2] >= 6) {
        return false;
    }

    $suma = 0;
    for ($i = 0; $i < 9; $i++) {
        $digito = (int)$cedula[$i];
        $mult = ($i % 2 === 0) ? $digito * 2 : $digito * 1;
        $suma += ($mult >= 10) ? $mult - 9 : $mult;
    }

    $residuo = $suma % 10;
    $verificador = ($residuo === 0) ? 0 : 10 - $residuo;

    return $verificador === (int)$cedula[9];
}

/**
 * Validación de RUC ecuatoriano (13 dígitos).
 * Acepta RUC de personas naturales (primeros 10 = cédula válida + '001'),
 * sociedades privadas (09 + 11 dígitos) y entidades públicas (01-24 + 000 + 9 dígitos).
 */
function validarRUCEcuatoriano(string $ruc): bool
{
    $ruc = trim($ruc);

    if (!preg_match('/^\d{13}$/', $ruc)) {
        return false;
    }

    $provincia = (int)substr($ruc, 0, 2);
    if ($provincia < 1 || $provincia > 24) {
        return false;
    }

    $tercerDigito = (int)$ruc[2];

    // Persona natural: tercer dígito < 6, cédula válida + '001'
    if ($tercerDigito < 6) {
        $cedulaBase = substr($ruc, 0, 10);
        return validarCedulaEcuatoriana($cedulaBase) && substr($ruc, 10) === '001';
    }

    // Sociedad privada: tercer dígito = 9
    if ($tercerDigito === 9) {
        $coefs = [4, 3, 2, 7, 6, 5, 4, 3, 2];
        $suma = 0;
        for ($i = 0; $i < 9; $i++) {
            $suma += (int)$ruc[$i] * $coefs[$i];
        }
        $verificador = ($suma % 11 === 0) ? 0 : 11 - ($suma % 11);
        return $verificador === (int)$ruc[9] && substr($ruc, 10) === '001';
    }

    // Entidad pública: tercer dígito = 6
    if ($tercerDigito === 6) {
        $coefs = [3, 2, 7, 6, 5, 4, 3, 2];
        $suma = 0;
        for ($i = 0; $i < 8; $i++) {
            $suma += (int)$ruc[$i] * $coefs[$i];
        }
        $verificador = ($suma % 11 === 0) ? 0 : 11 - ($suma % 11);
        return $verificador === (int)$ruc[9] && substr($ruc, 10) === '001';
    }

    return false;
}

/**
 * Valida cédula o RUC ecuatoriano según la longitud del documento.
 */
function validarCedulaO_RUC(string $documento): bool
{
    $documento = trim($documento);
    $longitud = strlen($documento);

    if ($longitud === 10) {
        return validarCedulaEcuatoriana($documento);
    }
    if ($longitud === 13) {
        return validarRUCEcuatoriano($documento);
    }

    return false;
}
