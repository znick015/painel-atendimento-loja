CREATE DATABASE IF NOT EXISTS db_atendimento CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE db_atendimento;

-- Tabela de empresas (permite expandir para outros clientes no futuro)
CREATE TABLE empresas (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nome VARCHAR(100) NOT NULL,
    telefone_whatsapp VARCHAR(20) NOT NULL,
    status ENUM('ativo', 'inativo') DEFAULT 'ativo',
    criado_em TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Tabela de usuários (Dona e Vendedoras)
CREATE TABLE usuarios (
    id INT AUTO_INCREMENT PRIMARY KEY,
    empresa_id INT NOT NULL,
    nome VARCHAR(100) NOT NULL,
    email VARCHAR(100) UNIQUE NOT NULL,
    senha VARCHAR(255) NOT NULL,
    perfil ENUM('admin', 'vendedora') DEFAULT 'vendedora',
    ativo TINYINT(1) DEFAULT 1,
    criado_em TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (empresa_id) REFERENCES empresas(id) ON DELETE CASCADE
);

-- Tabela de clientes/contatos que chegam pelo WhatsApp
CREATE TABLE contatos (
    id INT AUTO_INCREMENT PRIMARY KEY,
    empresa_id INT NOT NULL,
    usuario_id INT NULL, -- Vendedora responsável atribuída pela roleta
    numero_telefone VARCHAR(25) NOT NULL,
    nome VARCHAR(100) NULL,
    ultimo_atendimento TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (empresa_id) REFERENCES empresas(id) ON DELETE CASCADE,
    FOREIGN KEY (usuario_id) REFERENCES usuarios(id) ON DELETE SET NULL
);

-- Tabela de histórico de mensagens
CREATE TABLE mensagens (
    id INT AUTO_INCREMENT PRIMARY KEY,
    contato_id INT NOT NULL,
    remetente ENUM('cliente', 'vendedora') NOT NULL,
    conteudo TEXT NOT NULL,
    timestamp_envio TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    status_leitura TINYINT(1) DEFAULT 0,
    FOREIGN KEY (contato_id) REFERENCES contatos(id) ON DELETE CASCADE
);