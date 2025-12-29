# Laravel Financial API

API REST para gerenciamento de transações financeiras com suporte a importação OFX, membros, categorias e centros de custo.

## Instalação

1. Clone o repositório
2. Configure o arquivo `.env` com suas credenciais de banco de dados
3. Execute as migrations:

```bash
php artisan migrate
```

4. (Opcional) Execute os seeders para dados de exemplo:

```bash
php artisan db:seed
```

## Endpoints da API

### Transações

- `GET /api/transactions` - Listar todas as transações (filtro opcional: `?type=income|expense`)
- `GET /api/transactions/{id}` - Obter uma transação específica
- `POST /api/transactions/{id}/confirm` - Confirmar uma transação
- `POST /api/transactions/{id}/split` - Dividir uma transação
- `POST /api/transactions/import` - Importar arquivo OFX

### Membros

- `GET /api/members` - Listar todos os membros
- `GET /api/members/{id}` - Obter um membro específico
- `POST /api/members` - Criar um novo membro
- `PATCH /api/members/{id}` - Atualizar um membro

### Categorias

- `GET /api/categories` - Listar todas as categorias (filtro opcional: `?type=income|expense`)

### Centros de Custo

- `GET /api/cost-centers` - Listar todos os centros de custo

### Caixa

- `GET /api/cash-register` - Obter saldo do caixa (filtros opcionais: `?startDate=YYYY-MM-DD&endDate=YYYY-MM-DD`)

### Dashboard

- `GET /api/dashboard/stats` - Obter estatísticas do dashboard

### Relatórios

- `GET /api/reports/{type}` - Obter relatórios (type: income|expense|category|member)
  - Query params opcionais: `?startDate=YYYY-MM-DD&endDate=YYYY-MM-DD`

## Estrutura do Banco de Dados

- **members**: Membros/Pessoas
- **categories**: Categorias de receitas/despesas
- **cost_centers**: Centros de custo
- **transactions**: Transações financeiras

## Recursos

- ✅ CRUD completo de transações, membros, categorias e centros de custo
- ✅ Importação de arquivos OFX
- ✅ Divisão de transações (split)
- ✅ Confirmação de transações
- ✅ Relatórios financeiros
- ✅ Dashboard com estatísticas
- ✅ API Resources para formatação consistente
- ✅ Form Requests para validação
- ✅ Relacionamentos Eloquent

## Observações

Para a importação OFX completa, considere instalar a biblioteca `asgrim/ofxparser`:

```bash
composer require asgrim/ofxparser
```

A implementação atual é simplificada para demonstração.
