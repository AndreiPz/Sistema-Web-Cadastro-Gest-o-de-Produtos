CREATE DATABASE IF NOT EXISTS feira_livre
  CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE feira_livre;

CREATE TABLE IF NOT EXISTS tipoproduto (
    codtipo INT AUTO_INCREMENT PRIMARY KEY,
    nometipo VARCHAR(100) NOT NULL UNIQUE
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS produto (
    codproduto INT AUTO_INCREMENT PRIMARY KEY,
    nomeproduto VARCHAR(150) NOT NULL,
    quantidade DECIMAL(10,2) NOT NULL,
    valor DECIMAL(10,2) NOT NULL,
    datavencimento DATE NOT NULL,
    codtipofk INT NOT NULL,
    CONSTRAINT fk_produto_tipo FOREIGN KEY (codtipofk)
      REFERENCES tipoproduto(codtipo)
      ON UPDATE CASCADE ON DELETE RESTRICT
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS fotos (
    codfoto INT AUTO_INCREMENT PRIMARY KEY,
    descricao VARCHAR(255) NOT NULL,
    caminhofoto VARCHAR(255) NOT NULL,
    codprodutofk INT NOT NULL,
    CONSTRAINT fk_fotos_produto FOREIGN KEY (codprodutofk)
      REFERENCES produto(codproduto)
      ON UPDATE CASCADE ON DELETE CASCADE
) ENGINE=InnoDB;

INSERT IGNORE INTO tipoproduto (nometipo) VALUES
('Frutas'), ('Verduras'), ('Legumes'), ('Laticínios');
