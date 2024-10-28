# SIGAA Integration

Este plug-in automatiza o cadastro de categorias, disciplinas/cursos e incrições de alunos e professores, com base nas informações disponíveis na API REST do SIGAA.

# Branching Workflow

Este repositório utiliza um modelo simplificado de versionamento, composto pelas branchs `main` e  `develop`.

- **main:** Versão estável.
- **develop:** Versão de desenvolvimento/teste.

# Instalação 

## Método 1 (download)

- Faça download do pacote .zip última release disponível.
```
wget https://github.com/igorcemim/sigaaintegration/archive/refs/tags/v1.0.5.zip
```
- Mova o pacote para a pasta `local/` da instalação do Moodle.
```
mv sigaaintegration-1.0.5.zip /var/www/html/moodle/local
```
- Realize a extração do pacote.
```
unzip sigaaintegration-1.0.5.zip
```
- Renomeie o diretório extraído.
```
mv sigaaintegration-1.0.5 sigaaintegration
```

## Método 2 (git)

Faça clone do repositório dentro da pasta `local/` da instalação do Moodle.

```
cd /var/www/html/moodle/local
git clone https://github.com/igorcemim/sigaaintegration.git
```
