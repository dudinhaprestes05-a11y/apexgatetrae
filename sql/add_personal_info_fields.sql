/*
  # Adicionar Campos de Informações Pessoais

  1. Campos Adicionados na Tabela `sellers`
    - `personal_document_type` (enum: 'rg', 'cnh') - Tipo do documento pessoal
    - `rg_number` (varchar) - Número do RG
    - `rg_issuer` (varchar) - Órgão emissor do RG
    - `rg_issue_date` (date) - Data de emissão do RG
    - `cnh_number` (varchar) - Número da CNH
    - `cnh_category` (varchar) - Categoria da CNH
    - `cnh_expiry_date` (date) - Data de validade da CNH
    - `birth_date` (date) - Data de nascimento
    - `address_zipcode` (varchar) - CEP
    - `address_street` (varchar) - Rua/Avenida
    - `address_number` (varchar) - Número do endereço
    - `address_complement` (varchar) - Complemento
    - `address_neighborhood` (varchar) - Bairro
    - `address_city` (varchar) - Cidade
    - `address_state` (varchar) - Estado (UF)
    - `personal_info_completed` (boolean) - Se preencheu dados pessoais

  2. Notas
    - Os campos de RG e CNH são opcionais, dependendo do tipo escolhido
    - O campo `personal_info_completed` controla o acesso ao envio de documentos
*/

-- Adicionar novos campos para informações pessoais
ALTER TABLE `sellers`
  ADD COLUMN `personal_document_type` ENUM('rg', 'cnh') DEFAULT NULL COMMENT 'Tipo do documento pessoal' AFTER `person_type`,
  ADD COLUMN `rg_number` VARCHAR(20) DEFAULT NULL COMMENT 'Número do RG' AFTER `personal_document_type`,
  ADD COLUMN `rg_issuer` VARCHAR(50) DEFAULT NULL COMMENT 'Órgão emissor do RG (ex: SSP/SP)' AFTER `rg_number`,
  ADD COLUMN `rg_issue_date` DATE DEFAULT NULL COMMENT 'Data de emissão do RG' AFTER `rg_issuer`,
  ADD COLUMN `cnh_number` VARCHAR(20) DEFAULT NULL COMMENT 'Número da CNH' AFTER `rg_issue_date`,
  ADD COLUMN `cnh_category` VARCHAR(10) DEFAULT NULL COMMENT 'Categoria da CNH (A, B, AB, etc)' AFTER `cnh_number`,
  ADD COLUMN `cnh_expiry_date` DATE DEFAULT NULL COMMENT 'Data de validade da CNH' AFTER `cnh_category`,
  ADD COLUMN `birth_date` DATE DEFAULT NULL COMMENT 'Data de nascimento' AFTER `cnh_expiry_date`,
  ADD COLUMN `address_zipcode` VARCHAR(9) DEFAULT NULL COMMENT 'CEP' AFTER `birth_date`,
  ADD COLUMN `address_street` VARCHAR(255) DEFAULT NULL COMMENT 'Rua/Avenida' AFTER `address_zipcode`,
  ADD COLUMN `address_number` VARCHAR(20) DEFAULT NULL COMMENT 'Número do endereço' AFTER `address_street`,
  ADD COLUMN `address_complement` VARCHAR(100) DEFAULT NULL COMMENT 'Complemento (Apto, Sala, etc)' AFTER `address_number`,
  ADD COLUMN `address_neighborhood` VARCHAR(100) DEFAULT NULL COMMENT 'Bairro' AFTER `address_complement`,
  ADD COLUMN `address_city` VARCHAR(100) DEFAULT NULL COMMENT 'Cidade' AFTER `address_neighborhood`,
  ADD COLUMN `address_state` CHAR(2) DEFAULT NULL COMMENT 'Estado (UF)' AFTER `address_city`,
  ADD COLUMN `personal_info_completed` TINYINT(1) DEFAULT 0 COMMENT 'Dados pessoais preenchidos' AFTER `address_state`;

-- Criar índice para busca por CEP
ALTER TABLE `sellers` ADD INDEX `idx_zipcode` (`address_zipcode`);
