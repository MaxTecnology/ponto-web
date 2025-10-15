# CHECKLIST_DE_DEPLOY.md — Produção (MySQL + Docker)

Este guia é um **checklist prático** para publicar o *Sistema de Ponto Web* em produção usando **Docker**. Pressupõe uma VPS Linux (Ubuntu 22.04+), domínio próprio e que o projeto já roda localmente com **Sail + MySQL**.

---

## 0) Decisões rápidas
- **Banco**: MySQL gerenciado ou em container (aceitável para MVP).
- **TLS**: Terminar HTTPS no **Nginx do host** e fazer **reverse proxy** para o container web.
- **Fila/Agendador**: Ativar **queue worker** (futuro) e **scheduler** (cron) no host chamando `artisan` dentro do container.
- **Backups**: `mysqldump` diário + retenção GFS, enviar para S3/Backblaze/Google Drive (rclone).
- **Segurança**: UFW/firewalld, mínimos privilégios, `.env` protegido, atualizações automáticas.

---

## 1) Pré-requisitos
- VPS Ubuntu 22.04+ com **2 vCPU / 4GB RAM** (recomendado para começar).
- Domínio e DNS `A` apontando para o IP público (ex.: `ponto.dominio.com`).
- Usuário de deploy sem `root`; **SSH** com chave; `ufw` ativo.
- **Docker** e **Docker Compose v2** instalados.
- Porta **3306** do MySQL **NÃO** exposta publicamente (somente dentro da rede Docker).

```bash
# Firewall básico (ajuste conforme necessidade)
sudo ufw allow OpenSSH
sudo ufw allow 80/tcp
sudo ufw allow 443/tcp
sudo ufw enable

# Instalar Docker e Compose (resumo)
sudo apt-get update && sudo apt-get install -y ca-certificates curl gnupg
sudo install -m 0755 -d /etc/apt/keyrings
curl -fsSL https://download.docker.com/linux/ubuntu/gpg | sudo gpg --dearmor -o /etc/apt/keyrings/docker.gpg
echo   "deb [arch=$(dpkg --print-architecture) signed-by=/etc/apt/keyrings/docker.gpg] https://download.docker.com/linux/ubuntu   $(. /etc/os-release && echo $VERSION_CODENAME) stable" |   sudo tee /etc/apt/sources.list.d/docker.list > /dev/null
sudo apt-get update
sudo apt-get install -y docker-ce docker-ce-cli containerd.io docker-buildx-plugin docker-compose-plugin
```

---

## 2) Obter o código e preparar variáveis de ambiente
```bash
git clone <SEU_REPO> ponto-web
cd ponto-web

cp .env.docker.example .env.docker
nano .env.docker
```

**Campos importantes em `.env.docker`:**
- `APP_URL`, `APP_NAME`, `APP_BRAND`, `APP_LOGO` — apontam o subdomínio público e a identidade visual.
- `DB_*` — credenciais usadas pelo container MySQL interno.
- `RUN_MIGRATIONS` — deixe `true` no primeiro deploy para rodar `php artisan migrate --force` automaticamente.

> Gere uma `APP_KEY` antes de subir (após o primeiro `docker compose -f docker-compose.prod.yml build`):
> `docker compose -f docker-compose.prod.yml run --rm app php artisan key:generate --show` — copie o valor para `.env.docker`.

---

## 3) Compose de produção pronto para uso
- `docker-compose.prod.yml` orquestra três serviços: `app` (PHP-FPM), `web` (Nginx) e `mysql`.
- O `Dockerfile` multi-stage compila assets (Vite) e instala dependências PHP durante o build.
- Volumes nomeados preservam `storage/`, `bootstrap/cache` e os dados do banco mesmo após recriações de containers.

Build + subida:
```bash
docker compose -f docker-compose.prod.yml build
docker compose -f docker-compose.prod.yml up -d
```

Logs iniciais (úteis para acompanhar criação de APP_KEY ou migrations):
```bash
docker compose -f docker-compose.prod.yml logs -f app
```

> O entrypoint executa cache de config (`config:cache`, `route:cache`, `view:cache`) e roda migrations se `RUN_MIGRATIONS=true`.

---

## 4) Nginx (host) como reverse proxy + TLS
Instale Nginx no host e crie um `server` apontando para o serviço `web` exposto na porta 8080 do host.

**/etc/nginx/sites-available/ponto.conf** (resumo):
```
server {
    listen 80;
    server_name ponto.dominio.com;
    location / {
        proxy_set_header Host $host;
        proxy_set_header X-Real-IP $remote_addr;
        proxy_set_header X-Forwarded-For $proxy_add_x_forwarded_for;
        proxy_set_header X-Forwarded-Proto $scheme;
        proxy_pass http://127.0.0.1:8080; # porta exposta pelo serviço web do compose
    }
}
```

- Ajuste a porta alvo conforme seu `docker-compose` (ex.: mapear `laravel.test:80` → host `8080`).
- Ativar site, testar, recarregar:
```bash
sudo ln -s /etc/nginx/sites-available/ponto.conf /etc/nginx/sites-enabled/ponto.conf
sudo nginx -t && sudo systemctl reload nginx
```

**TLS (Let's Encrypt):**
```bash
sudo apt-get install -y certbot python3-certbot-nginx
sudo certbot --nginx -d ponto.dominio.com --redirect -m seu@email -n --agree-tos
```

> Renovação automática via `systemd`/cron do certbot já vem habilitada.

---

## 5) Pós-subida (ajustes rápidos)
Na maioria dos casos o entrypoint já terá executado os passos abaixo, mas vale validar:

```bash
# Opcional: imprimir uma nova chave (copie para .env.docker se precisar rotacionar)
docker compose -f docker-compose.prod.yml exec app php artisan key:generate --show

# Garantir symlink do storage
docker compose -f docker-compose.prod.yml exec app php artisan storage:link

# Rodar migrations manualmente se RUN_MIGRATIONS=false
docker compose -f docker-compose.prod.yml exec app php artisan migrate --force

# Seeds adicionais
docker compose -f docker-compose.prod.yml exec app php artisan db:seed --force
```

---

## 6) Scheduler e Queue
### Scheduler (CRON no host)
Adicionar ao crontab do host (`crontab -e`):
```
* * * * * docker compose -f /SEU/CAMINHO/docker-compose.prod.yml exec -T app php artisan schedule:run -q
```

### Queue worker (futuro)
- Adicionar um serviço no compose para rodar `php artisan queue:work --sleep=1 --tries=3`.
- Ou usar Supervisor no host chamando o container.

---

## 7) Backups
### Banco
Script simples `/usr/local/bin/backup_mysql.sh`:
```bash
#!/usr/bin/env bash
set -e
TS=$(date +%F_%H-%M)
OUT=/var/backups/ponto/mysql_$TS.sql.gz
docker compose exec -T mysql mysqldump -uponto -psenha_forte --single-transaction --quick --lock-tables=false ponto | gzip > "$OUT"
find /var/backups/ponto -type f -mtime +7 -delete
```
Crontab diário:
```
0 2 * * * /usr/local/bin/backup_mysql.sh
```

### Arquivos
- Compactar `storage/app` periodicamente (se houver anexos).
- Enviar para remoto (S3, Backblaze) com **rclone**.

---

## 8) Observabilidade e Logs
- `LOG_CHANNEL=daily` (rotação automática).
- Uptime monitor (Healthchecks.io, Uptime Kuma).
- Sentry (opcional) — setar `SENTRY_LARAVEL_DSN` e instalar SDK.

---

## 9) Segurança
- `.env` fora do versionamento; permissões 600.
- UFW ativo somente 22/80/443.
- Desabilitar root login SSH, usar chave.
- Mínimo de portas publicadas nos containers.
- Atualizações de segurança automáticas (`unattended-upgrades`).

---

## 10) Deploy sem downtime (básico)
```bash
git pull
docker compose -f docker-compose.prod.yml pull           # se usar imagens remotas
docker compose -f docker-compose.prod.yml build --no-cache
docker compose -f docker-compose.prod.yml up -d --remove-orphans
docker compose -f docker-compose.prod.yml exec -T app php artisan migrate --force
docker system prune -f        # limpar imagens antigas
```

---

## 11) Rollback
- Mantenha **backup do banco** antes de rodar migrations.
- Versões anteriores do código via git tag.
- Para voltar:
```bash
git checkout <tag_anterior>
docker compose -f docker-compose.prod.yml up -d --build
# Restaurar dump do MySQL se necessário
```

---

## 12) Testes pós-deploy (checklist rápido)
- Página de login abre em `https://ponto.dominio.com`.
- Criar usuário e trocar role.
- Registrar uma batida (IN) e ver no Dashboard RH.
- Export CSV baixa e contém colunas corretas.
- Certbot renovação OK: `sudo certbot renew --dry-run`.

---

**Pronto.** Com esse checklist você consegue subir o MVP com segurança básica, HTTPS e backup diário. Quando crescer: separar banco gerenciado, mover cache/sessão para Redis, ativar workers, e colocar CI/CD.
