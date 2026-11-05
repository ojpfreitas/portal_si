<?php

/**
 * Bancos de dados que o instalador oferece na etapa "Banco de dados".
 * Este arquivo não guarda usuário nem senha: o instalador pede os dois.
 *
 * criar_banco: true  = o instalador cria o banco se ele não existir.
 *              false = o banco já deve existir.
 *
 * somente_atualizar (opcional, padrão false):
 *              true  = o banco já está instalado (tem Administrador). O instalador só aplica as
 *                      migrações pendentes e grava a conexão em config/database.php; não cria
 *                      banco nem Administrador.
 *
 * sslmode (opcional, padrão 'prefer'):
 *              disable | allow | prefer | require | verify-ca | verify-full.
 *              Bancos na nuvem devem usar 'require' (ou mais).
 */
return [
    'local' => [
        'nome'        => 'Banco local',
        'host'        => 'localhost',
        'port'        => '5432',
        'name'        => 'portal_si',
        'criar_banco' => true,
    ],
    'nuvem' => [
        'nome'        => 'Banco na nuvem (Aiven)',
        'host'        => 'postgres-portal-si-g1-phpau-portal-si-esucri-pe4-g1-phpau.a.aivencloud.com',
        'port'        => '10917',
        'name'        => 'portal_si',
        'criar_banco' => false,
        'somente_atualizar' => true,
        'sslmode'     => 'require',
    ],
];