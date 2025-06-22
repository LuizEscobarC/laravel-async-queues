#!/bin/bash

echo "🚀 Inicializando projeto Supervisor + Horizon..."

# Verificar se .env existe
if [ ! -f .env ]; then
    echo "📝 Copiando .env.example para .env..."
    cp .env.example .env
fi

# Gerar key se não existe
if ! grep -q "APP_KEY=base64:" .env; then
    echo "🔑 Gerando APP_KEY..."
    php artisan key:generate
fi

# Criar banco SQLite se não existe
if [ ! -f database/database.sqlite ]; then
    echo "🗄️  Criando banco SQLite..."
    touch database/database.sqlite
fi

# Executar migrations
echo "📊 Executando migrations..."
php artisan migrate --force

# Instalar Horizon se necessário
echo "🎯 Publicando Horizon..."
php artisan horizon:install

echo "✅ Inicialização completa!"
echo "🌐 Horizon Dashboard: http://localhost:8000/horizon"
echo "📊 Para processar CSV: docker exec supervisor-app php artisan process:csv-data"
