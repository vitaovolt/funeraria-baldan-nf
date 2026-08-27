# F2 Auth

**Objetivo:** Sanctum login/refresh/logout. FE: tela Entrar (T1) + interceptors + rotas privadas.

## Escopo entregue

| Item | Detalhe |
|------|---------|
| API | `POST /auth/login`, `GET /auth/me`, `POST /auth/logout`, `POST /auth/refresh` |
| Throttle | `login` 5/min em produção; sem teto em local/testing |
| FE | `/login` (Baldan), `/` protegido (Home + Sair), AuthContext, interceptor 401 |
| Seed | `operador@baldan.local` / `password` |

## Arquivos

- `code/backend/app/Http/Controllers/Api/AuthController.php`
- `code/backend/app/Http/Requests/LoginRequest.php`
- `code/backend/tests/Feature/AuthTest.php`
- `code/frontend/src/pages/LoginPage.jsx`, `HomePage.jsx`
- `code/frontend/src/context/AuthContext.jsx`
- `code/frontend/src/components/layout/ProtectedRoute.jsx`

## Critério de done

- [x] Escopo da fase implementado
- [x] Suite E2E da fase **verde** (agente executou)
- [x] Roteiro manual preenchido
- [x] OpenAPI atualizado
- [x] LESSONS.md da fase + sync KB
- [x] Rotas privadas no grupo `auth:sanctum`
- [x] Feature: 401 sem token (incl. Accept `*/*`)

## Suite E2E (automática) — gate

| # | Cenário | Arquivo | OK? |
|---|---------|---------|-----|
| 1 | Login → token + user + personal_access_tokens | AuthTest | [x] |
| 2 | Login inválido 422 sem token | AuthTest | [x] |
| 3 | me / refresh / logout (token rotaciona e revoga) | AuthTest | [x] |
| 4 | 401 sem token (+ Accept */*) | AuthTest | [x] |
| 5 | Seed operador consegue logar | AuthTest | [x] |
| 6 | Regressão F0/F1 | Health / Schema / Cors / Dominio | [x] |
| 7 | FE build | `npm run build` | [x] |

**Comandos:**

```powershell
cd C:\Users\Admin\Documents\EDUCRAFT\educraft-devkit\projects\funeraria-baldan-nf\code\backend
php artisan test --filter="AuthTest|HealthTest|DominioApiTest|BootstrapSchemaTest|CorsBootstrapTest"
cd ..\frontend
npm run build
```

**Resultado:** 16 passed / 0 failed (125 assertions); FE build OK.

## Como testar manualmente (só após E2E verde)

### O que é o smoke nesta fase

**API + tela Entrar.** Login na UI e sessão com Sair.

### Preparar

```powershell
cd C:\Users\Admin\Documents\EDUCRAFT\educraft-devkit\projects\funeraria-baldan-nf\code\backend
php artisan migrate:fresh --seed --force
php artisan serve
```

```powershell
cd C:\Users\Admin\Documents\EDUCRAFT\educraft-devkit\projects\funeraria-baldan-nf\code\frontend
npm run dev
```

| Item | Valor |
|------|--------|
| URL FE | http://localhost:5173/login |
| Usuário | `operador@baldan.local` |
| Senha | `password` |

### Passos

1. Abrir http://localhost:5173 — deve redirecionar para `/login`.
2. Entrar com `operador@baldan.local` / `password`.
3. Esperado: Home com e-mail do operador e botão **Sair**.
4. Clicar **Sair** → volta para `/login`.
5. Senha errada → mensagem de erro; botão mostra “Entrando…” e não duplica submit.
6. Opcional API: `POST /api/v1/auth/login` com JSON → token; sem token `GET /produtos` → 401.

### Checklist

- [x] Login UI OK
- [x] Home autenticada + Sair
- [x] Credencial inválida mostra erro
- [x] 401 sem token na API

**Smoke operador:** OK (26/08/2026) — gate F2 fechado.
