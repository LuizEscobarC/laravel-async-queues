# Laravel Docker Queue Balance

Sistema de filas assíncronas com Laravel usando Docker para balanceamento de workers.

## Funcionalidades

- Múltiplos workers Docker para diferentes prioridades de filas
- Processamento assíncrono de arquivos
- Comando artisan para consumir arquivos
- Balanceamento automático de carga

## Como usar

```bash
# Subir ambiente
docker-compose up -d

# Processar arquivo
php artisan file:process caminho/para/arquivo.txt --priority=high

# Verificar status das filas
php artisan queue:monitor
```

