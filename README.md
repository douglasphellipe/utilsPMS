
\============================================================

🚀 Guia de Git: Fluxo main e develop

\============================================================

Bem-vindo! Este projeto usa um fluxo de branches claro para garantir organização e estabilidade.

Leia atentamente e siga estes passos SEMPRE.

\------------------------------------------------------------

1) Estrutura de branches

\------------------------------------------------------------

- main: Branch de produção. SEMPRE estável. Só recebe merge de develop (ou hotfix crítico).
- develop: Branch de desenvolvimento. Base para novas features e correções.

\------------------------------------------------------------

\2) Criação do repositório

\------------------------------------------------------------

Se o repositório ainda não existir:

1) Crie no GitHub: New Repository -> Dê um nome -> NÃO adicione README ou .gitignore.

1) Localmente:

mkdir nome-do-projeto

cd nome-do-projeto

git init

echo "# Nome do Projeto" > README.md

git add README.md

git commit -m "docs: adiciona README inicial"

1) Conecte o remoto:

git remote add origin https://github.com/SEU\_USUARIO/NOME\_DO\_REPOSITORIO.git

1) Crie e suba main:

git branch -M main

git push -u origin main

1) Crie develop a partir de main:

git checkout -b develop

git push -u origin develop

\------------------------------------------------------------

1) Clonar o repositório

\------------------------------------------------------------

git clone https://github.com/SEU\_USUARIO/NOME\_DO\_REPOSITORIO.git

cd NOME\_DO\_REPOSITORIO

Verifique remotos e branches:

git remote -v

git branch -a

\------------------------------------------------------------

1) Fluxo de trabalho no dia a dia

\------------------------------------------------------------

Sempre atualize develop antes de começar:

git checkout develop

git pull origin develop

Crie uma branch de feature ou correção:

git checkout develop

git checkout -b feature/nome-da-feature

Trabalhe na branch:

git add .

git commit -m "feat: implementa funcionalidade X"

Envie a branch para o GitHub:

git push -u origin feature/nome-da-feature

Abra um Pull Request (PR):

- No GitHub, clique em Compare & Pull Request
- base: develop
- compare: sua feature
- Descreva as mudanças, solicite revisão

\------------------------------------------------------------

\5) Merge para main

\------------------------------------------------------------

Quando develop estiver estável:

1) Crie PR de develop para main (base: main, compare: develop).
1) Após revisão, merge.
1) (Opcional) Crie uma tag de release:

git checkout main

git pull

git tag -a v1.0.0 -m "Primeira release"

git push origin v1.0.0

\------------------------------------------------------------

\6) Manter repositório local atualizado

\------------------------------------------------------------

Atualize develop:

git checkout develop

git pull origin develop

Atualize sua feature com develop:

git checkout feature/nome-da-feature

git pull origin develop

\------------------------------------------------------------

\7) Configurar HTTPS com Personal Access Token

\------------------------------------------------------------

GitHub NÃO aceita senha!

1) Gere um token em https://github.com/settings/tokens
- Clique em Generate new token (classic)
- Marque 'repo'
- Copie e guarde

\2) No push:

- Username: seu login GitHub
- Password: cole o token

\3) Para não digitar sempre:

git config --global credential.helper store

\------------------------------------------------------------

\8) Usar SSH (opcional, recomendado)

\------------------------------------------------------------

1) Gere chave:

ssh-keygen -t ed25519 -C "seu-email@exemplo.com"

1) Copie chave pública:

cat ~/.ssh/id\_ed25519.pub

1) Adicione em GitHub -> Settings -> SSH and GPG keys

1) Teste:

ssh -T git@github.com

\------------------------------------------------------------

\9) Boas práticas

\------------------------------------------------------------

- Use nomes claros: feature/, bugfix/, hotfix/
- Commits pequenos e frequentes
- Nunca comite direto na main
- Sempre revise PRs antes de aprovar

\------------------------------------------------------------

Pronto para contribuir! Em caso de dúvidas, consulte o líder do projeto.

\============================================================
