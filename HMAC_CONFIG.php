<?php

/**
 * Configuração para usar HMAC ao invés de SHA256 simples
 *
 * IMPORTANTE: HMAC requer uma chave secreta compartilhada.
 * Defina a chave abaixo ou use uma variável de ambiente.
 */

// Chave HMAC para hash de API Secrets
// NUNCA commite esta chave no repositório!
define('HMAC_SECRET_KEY', getenv('HMAC_SECRET_KEY') ?: 'sua_chave_secreta_aqui_change_me');

/**
 * Função para hashear API Secret usando HMAC
 */
function hashApiSecret($apiSecret) {
    return hash_hmac('sha256', $apiSecret, HMAC_SECRET_KEY);
}

/**
 * Função para verificar API Secret usando HMAC
 */
function verifyApiSecret($inputSecret, $storedHash) {
    $inputHash = hashApiSecret($inputSecret);
    return hash_equals($storedHash, $inputHash);
}
