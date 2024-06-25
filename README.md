# Banking API

Esta é uma API RESTful que simula operações bancárias para diferentes moedas. A API permite realizar depósitos, saques e consultar saldos em várias moedas utilizando as taxas de câmbio PTAX do Banco Central do Brasil.

## Tecnologias Utilizadas

- PHP ^8.2
- Laravel Framework ^11.9
- MySQL
- PHPUnit
- Jenssegers/Date ^2.0
- Nesbot/Carbon ^3.6

## Requisitos

- PHP >= 8.2
- Composer
- MySQL
- Extensões do PHP: pdo_mysql, mbstring, tokenizer, xml

## Instalação

### Passo 1: Clone o Repositório

```bash
git clone git@github.com:henriquemalvar/banking-api.git

cd banking-api

```

### Passo 2: Instale as Dependências

```bash
composer install
```

### Passo 3: Configure o Arquivo `.env`

Crie um arquivo `.env` baseado no arquivo de exemplo `.env.example` e configure as variáveis de ambiente, especialmente a conexão com o banco de dados MySQL:

```
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=laravel
DB_USERNAME=root
DB_PASSWORD=password
```

### Passo 4: Execute as Migrações

```bash
php artisan migrate
```

## Execução da Aplicação

### Passo 1: Inicie o Servidor de Desenvolvimento

```bash
php artisan serve
```

### Passo 2: Acesse a Aplicação

A aplicação estará disponível em `http://127.0.0.1:8000`.

## Endpoints da API

### 1. Criar Conta

**Endpoint:** `POST /api/accounts`

**Parâmetros:**

- Nenhum

**Exemplo de Resposta:**

```json
{
    "message": "Account created successfully",
    "account": {
        "id": 1,
        "account_number": 1,
        "created_at": "2024-06-24T12:34:56.000000Z",
        "updated_at": "2024-06-24T12:34:56.000000Z"
    }
}
```

### 2. Depósito

**Endpoint:** `POST /api/accounts/{accountNumber}/deposit`

**Parâmetros:**

- `amount` (float) - Valor do depósito
- `currency` (string) - Moeda do depósito (e.g., USD, BRL)

**Exemplo de Resposta:**

```json
{
    "message": "Depósito realizado com sucesso"
}
```

### 3. Saque

**Endpoint:** `POST /api/accounts/{accountNumber}/withdraw`

**Parâmetros:**

- `amount` (float) - Valor do saque
- `currency` (string) - Moeda do saque (e.g., USD, BRL)

**Exemplo de Resposta:**

```json
{
    "message": "Saque realizado com sucesso"
}
```

### 4. Saldo

**Endpoint:** `GET /api/accounts/{accountNumber}/balance`

**Parâmetros Opcionais:**

- `currency` (string) - Moeda para consulta de saldo (e.g., USD, BRL)

**Exemplo de Resposta:**

```json
{
    "account_number": 1,
    "balances": [
        {
            "currency": "USD",
            "balance": 100.0
        },
        {
            "currency": "BRL",
            "balance": 500.0
        }
    ]
}
```

## Testes

### Passo 1: Executar os Testes


```bash
php artisan test --coverage --min=90
```

Os testes incluem cobertura para as operações de depósito, saque e consulta de saldo, com uma cobertura mínima de 90%.