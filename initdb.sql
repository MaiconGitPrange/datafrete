CREATE DATABASE IF NOT EXISTS datafrete;

USE datafrete;

CREATE TABLE `ceps_distance` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `cep_origem` varchar(8) NOT NULL,
  `cep_destino` varchar(8) NOT NULL,
  `distancia` float NOT NULL,
  `data_cadastro` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `data_alteracao` timestamp NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=77 DEFAULT CHARSET=latin1;