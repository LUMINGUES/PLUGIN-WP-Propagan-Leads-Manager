<img width="1536" height="672" alt="UX TOTALMENTE MODERNO E DE FÁCIL OPERAÇÃO" src="https://github.com/user-attachments/assets/787710ec-a354-42d5-ad2b-16c636ccd3b2" />

## 🚀 Propagan Leads Manager - Gestão de Leads, Chatbot IA & Integração WooCommerce

**Plugin Name:** Propagan Leads Manager
**Versão:** 3.2.1
**Descrição:** Sistema completo de gerenciamento de leads com Chatbot WhatsApp/Web, alimentado por **DeepSeek AI**, e integração de contexto com **WooCommerce** para respostas automatizadas e mais inteligentes.

-----

## 📋 Descrição Geral

O **Propagan Leads Manager** é uma solução completa para capturar, qualificar e gerenciar leads diretamente no seu site WordPress. Ele combina a conveniência do **WhatsApp** com a inteligência artificial do **DeepSeek** para oferecer atendimento 24/7 e salvar leads automaticamente no seu banco de dados para acompanhamento da equipe de vendas. A nova **Integração com WooCommerce** permite que o chatbot acesse dados de produtos, estoque e pedidos para fornecer informações precisas aos clientes.

## ✨ Recursos Principais

  * **Chatbot Inteligente (DeepSeek AI):** Atendimento automatizado via webchat, alimentado pela API DeepSeek (Deepseek-chat model).
  * **Contexto WooCommerce:** Use as chaves REST API do WooCommerce para fornecer contexto à IA sobre **produtos recentes, preços e status de estoque**, tornando o chatbot um assistente de vendas altamente informado.
  * **Captura de Leads:** Salva automaticamente Nome, E-mail e Telefone no banco de dados do WordPress ao iniciar o chat ou via Shortcode de Formulário Simples.
  * **Gerenciamento de Vendas (CRM Lite):** Painel administrativo para visualizar, filtrar, e atualizar o **Status do Lead** (Novo, Converteu, Ignorou, Cliente) e registrar o **Valor da Venda**.
  * **Ações em Massa e Filtros:** Filtre leads por Status, Fonte (Chatbot ou Formulário) e Período de Data para análise de desempenho.
  * **Exportação de Dados:** Funcionalidade dedicada para exportar leads filtrados para o formato **CSV**.
  * **Atendimento Humano Imediato:** O chatbot inclui uma opção para transferir o contato diretamente para o WhatsApp do atendente, com uma mensagem pré-preenchida contendo os dados do lead.

-----

## 🛠️ Instalação

### Requisitos Mínimos:

1.  WordPress 5.0 ou superior.
2.  Chave de API válida da **DeepSeek**.
3.  **WooCommerce** (opcional, apenas para a funcionalidade de contexto de loja da IA).

### Etapas de Instalação:

1.  Faça o upload do plugin para o seu diretório `/wp-content/plugins/`.
2.  Ative o plugin através do menu 'Plugins' no painel de administração.
3.  Ao ser ativado, o plugin cria a tabela `wp_propagan_leads` no seu banco de dados.

-----

## ⚙️ Configuração

Acesse **Leads Propagan \> Configurações** para configurar o plugin.

### Seção 1: Configurações Principais (Chatbot & WhatsApp)

| Campo | Descrição | Importância |
| :--- | :--- | :--- |
| **Número do WhatsApp** | Número de telefone com código do país e DDD (ex: `5511999999999`). | **Essencial** |
| **DeepSeek API Key** | Sua chave de acesso à API DeepSeek. | **Essencial para o Chatbot** |
| **Prompt da IA** | Instruções de comportamento para o chatbot (personalidade, regras, etc.). | **Essencial** |
| **Chatbot WhatsApp** | Ativa/Desativa o sistema de Chatbot Web. | Essencial |
| **Formulário Simples** | Ativa/Desativa o Shortcode `[propagan_simple_form]`. | Opcional |

### Seção 2: Configurações WooCommerce (Opcional)

| Campo | Descrição | Requisitos |
| :--- | :--- | :--- |
| **WooCommerce Consumer Key** | Chave de Consumidor da API REST do WooCommerce (permissão de **Leitura**). | Opcional |
| **WooCommerce Consumer Secret** | Chave Secreta de Consumidor da API REST do WooCommerce. | Opcional |

> ℹ️ **Como Obter as Chaves WC:** Vá para **WooCommerce \> Configurações \> Avançado \> REST API** e crie uma nova chave com permissão de Leitura para uso no chatbot.

-----

## 🧩 Shortcodes

| Shortcode | Descrição |
| :--- | :--- |
| `[propagan_chatbot]` | Exibe o ícone flutuante do WhatsApp que abre o Chatbot com o formulário de captura inicial. |
| `[propagan_simple_form]` | Exibe um formulário de contato simples (Nome, Email, Telefone, Mensagem) para captura direta de leads em qualquer página ou post. |

## 📊 Painel de Gerenciamento

Acesse **Leads Propagan** para:

  * Ver o **Dashboard** com o total de leads, novos leads e **Vendas Totais** (somente leads com status "Cliente").
  * **Filtrar** a lista por Status, Fonte ou Intervalo de Datas.
  * **Editar** Leads individualmente para atualizar o Status e registrar o **Valor da Venda**.
  * Usar **Ações em Massa** para mudar o status de múltiplos leads ou excluí-los.

-----

## 🗑️ Remoção de Coluna Obsoleta

Na **Versão 3.2.1**, a coluna `conversation` foi removida da tabela do banco de dados para otimização.

> ⚠️ **Atenção:** Se você está atualizando de uma versão anterior e a coluna `conversation` ainda existir, a ativação do plugin não a removerá automaticamente (para evitar perda de dados). Você deve removê-la manualmente através de um comando SQL no seu `phpMyAdmin` ou ferramenta similar:
>
> ```sql
> ALTER TABLE wp_propagan_leads DROP COLUMN conversation;
> ```
>
> *Substitua `wp_` pelo prefixo da sua tabela, se for diferente.*
