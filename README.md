# Biblioteca

Aplicação para cadastro de livros, autores e assuntos, com relatório PDF de livros agrupados por autor.

## Requisitos

- Docker
- Docker Compose v2 (`docker compose`)

## Iniciar a aplicação

1. Na primeira execução, crie o arquivo `.env` a partir do exemplo:

   ```bash
   cp .env.example .env
   ```

   Confirme que `DATABASE_URL` usa o host `database` e a porta `3306`, e que as senhas correspondem a `MYSQL_PASSWORD` e `MYSQL_ROOT_PASSWORD`.

2. Na raiz do projeto, construa e inicie os serviços:

   ```bash
   docker compose up --build -d
   ```

3. Aplique as migrations do banco:

   ```bash
   docker compose exec app php bin/console doctrine:migrations:migrate --no-interaction
   ```

4. Acesse:

   - Frontend: <http://localhost:8080>
   - API: <http://localhost:8000/api>

## Comandos úteis

Ver o estado dos serviços:
```bash
docker compose ps
```

Acompanhar os logs:
```bash
docker compose logs -f app frontend database
```

Parar os serviços sem apagar os dados do banco:
```bash
docker compose down
```

Parar os serviços sem preservar os dados:
```bash
docker compose down -v
```