# LandOfRole (MVP)

Stack: Apache + PHP 8 + MySQL (Docker), dev en VS Code.

## Dev
- Copiar `.env.example` a `.env`
- `docker compose up -d --build`
- App: http://localhost:8080
- Adminer: http://localhost:8081 (Server: db, User/Pass: ver .env)

## Estructura
app/          # PHP
sql/          # scripts de DB
docker/php/   # Dockerfile + configs

