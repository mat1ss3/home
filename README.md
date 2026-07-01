# joaomarioferreira.pt

Código-fonte do website e painel de controlo.

## Estrutura

```
index.html      → página principal do site
painel/          → painel de controlo protegido por login (PHP + SQLite)
```

## Deploy via cPanel Git Version Control

1. No cPanel, abre **Git™ Version Control**
2. **"Create"** → cola o URL SSH deste repositório: `git@github.com:mat1ss3/home.git`
3. Repository Path: escolhe onde queres o código (ex: `/home/joaomar9/repogit`)
4. Depois de clonado, define o **"Repository Deployment"** para copiar os ficheiros
   necessários para `public_html` (ou usa a opção "Manage" → "Pull or Deploy")

### Autenticação SSH (necessária por o repositório ser privado)
1. No cPanel → **SSH Access** → **Manage SSH Keys** → gera um novo par de chaves
2. Copia a chave pública gerada
3. No GitHub → repositório → **Settings** → **Deploy keys** → **Add deploy key**
   → cola a chave pública (só precisa de acesso de leitura)

## Depois do primeiro deploy no servidor

1. Edita `painel/config.php` **diretamente no servidor** (nunca commitar credenciais reais)
2. Abre `/painel/setup.php` uma vez para criar a conta de administrador
3. **Apaga `setup.php` do servidor** depois de criares a conta
4. A base de dados (`painel/data/panel.sqlite`) é criada automaticamente e nunca
   deve ser adicionada ao repositório (já está no `.gitignore`)

## Notas de segurança

- `painel/data/` e `painel/files/*` estão no `.gitignore` — são dados gerados em
  runtime no servidor, não fazem parte do código
- `painel/config.php` está no repositório como modelo/placeholder; os valores reais
  ficam só no servidor
