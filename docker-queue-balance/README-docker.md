# Laravel Docker Queue Balance

Este projeto demonstra o balanceamento de filas assíncronas usando **múltiplos containers Docker**, cada um processando uma fila específica.

## 🐳 Conceito

- **Docker Compose**: Gerencia múltiplos containers workers
- **Filas Específicas**: Cada container processa uma fila dedicada
- **Balanceamento Manual**: Configuração explícita de workers por fila

## 🏗️ Arquitetura

```
CSV Data → Laravel Command → Jobs → Specific Queues → Docker Workers → Database
                                         ↓
                              [high-priority] → Container 1 (3 workers)
                              [default]       → Container 2 (2 workers)  
                              [low-priority]  → Container 3 (1 worker)
                                         ↑
                               [Docker Compose gerencia containers]
```

## 🚀 Como usar

### 1. Instalar dependências
```bash
composer install
```

### 2. Configurar environment
```bash
cp .env.example .env
php artisan key:generate
```

### 3. Configurar banco de dados
```bash
php artisan migrate
```

### 4. Subir containers
```bash
docker-compose up -d
```

### 5. Processar dados CSV
```bash
php artisan process:csv-data --batch-size=15
```

### 6. Monitorar containers
```bash
# Status dos containers
docker-compose ps

# Logs em tempo real
docker-compose logs -f

# Logs específicos
docker-compose logs -f queue-high
docker-compose logs -f queue-default  
docker-compose logs -f queue-low
```

## 📊 Containers Configurados

| Container | Fila | Workers | Critério |
|-----------|------|---------|----------|
| `queue-high` | `high-priority` | 3 | Lotes > 50 registros |
| `queue-default` | `default` | 2 | Lotes 20-50 registros |
| `queue-low` | `low-priority` | 1 | Lotes < 20 registros |

## 🐳 Docker Compose

O `docker-compose.yml` define:
- **Redis**: Broker de mensagens
- **3 Workers**: Cada um com fila específica
- **Networks**: Comunicação entre containers
- **Volumes**: Código compartilhado

## 🔍 Monitoramento Redis

```bash
# Conectar ao Redis
docker-compose exec redis redis-cli

# Ver filas
> LLEN laravel_database_queue:high-priority
> LLEN laravel_database_queue:default  
> LLEN laravel_database_queue:low-priority

# Ver jobs
> LRANGE laravel_database_queue:high-priority 0 -1
```

## 💡 Vantagens desta Abordagem

- **Isolamento**: Cada fila em container separado
- **Escalabilidade**: Fácil adicionar mais workers
- **Controle**: Configuração granular por fila
- **Simplicidade**: Docker Compose familiar
- **Recursos**: Controle de CPU/Memory por container

## 🛠️ Configuração

### Dockerfile
- Base: `php:8.2-cli`
- Extensions: Redis, PDO
- Composer instalado

### docker-compose.yml
- Redis service
- 3 Queue workers
- Volume mapping
- Network configuration
