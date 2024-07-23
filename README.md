# Projeto Cadastro de Distâncias

Este projeto é uma aplicação web para o cadastro de distâncias entre dois CEPs. A aplicação permite adicionar, editar e visualizar as distâncias, além de realizar o upload de um arquivo CSV com os dados.

## Requisitos

- Docker
- PHP
- Composer

## Passos para configurar o ambiente

### 1. Clonar o repositório

Primeiro, clone o repositório do projeto:

```sh
git clone https://github.com/MaiconGitPrange/datafrete.git
cd datafrete
```

### 2. Instalar as dependencias do PHP Backend
Entre na pasta /backend

```sh
cd backend/
composer install
```
Aguarde a instalação de todos os componentes do projeto.

### 3. Vamos aos comandos do Docker para deixar o projeto pronto para uso

Agora na pasta raiz do projeto onde temos o arquivo docker-compose.yml vamos rodar o Docker

```sh
docker-compose up --build
```
Caso tudo esteja funcionando corretamente com o frontend rodando os seguintes endereços podem ser acessados.

```sh
front: http://localhost:8081/
back: http://localhost
```

Caso precise encerrar o projeto no Docker rode o comando abaixo

```sh
docker-compose down
```

### OBS:

Ao rodar o docker pela primeira vez o front demora um pouco para ficar 100% por conta do NPM INSTALL que está fazendo a primeira instalação do projeto inteiro.
