-- =============================================================================
-- Meu Orçamento — ATUALIZACAO (rode APÓS já ter importado o install.sql antes)
--
-- Por que este arquivo existe: o install.sql usa INSERT IGNORE, que NÃO
-- sobrescreve registros que já existiam. Este script atualiza os dados que
-- ficaram com o conteudo antigo (nome, URL, produtos, e-mails, WhatsApp).
--
-- Seguro rodar mais de uma vez. phpMyAdmin > Importar, ou:
--   mysql -u USUARIO -p NOME_DO_BANCO < database/update.sql
-- =============================================================================

SET NAMES utf8mb4;

-- ---- Marca e URL ----
UPDATE settings SET `value` = 'Meu Orçamento' WHERE `key` IN ('app.name','mail.from_name');
UPDATE settings SET `value` = 'https://meuorcamento.lrvweb.com.br' WHERE `key` = 'app.url';

-- ---- Produtos (descricoes, features e ancoragem de preco) ----
UPDATE products SET
    price = 39.90, promo_price = 19.90,
    description = 'Crie orcamentos profissionais em 2 minutos, gere PDF com a sua marca e envie pelo WhatsApp. Acompanhe quando o cliente visualiza e aprova.',
    features = '["Orcamentos ilimitados","PDF profissional com a sua marca","Link publico com aprovar/recusar","Envio em 1 clique pelo WhatsApp","Status em tempo real (enviado, visto, aprovado)","Cadastro de clientes e servicos reutilizaveis"]'
WHERE slug = 'gerador-orcamentos';

UPDATE products SET
    price = 49.90, promo_price = 29.90,
    description = 'Saiba exatamente quanto cobrar e para onde vai o seu dinheiro. Controle receitas, despesas e descubra o preco ideal dos seus servicos.',
    features = '["Precificador inteligente (preco minimo e ideal)","Controle de receitas e despesas","Dashboard com lucro real do mes","Alertas de contas a receber e a pagar","Comparativo mes a mes"]'
WHERE slug = 'kit-financeiro';

UPDATE products SET
    price = 89.80, promo_price = 39.90,
    description = 'A caixa de ferramentas completa do seu negocio: orcamentos, financeiro, precificador, clientes e relatorios num so lugar, com o melhor custo-beneficio.',
    features = '["Tudo do Gerador de Orcamentos","Tudo do Kit Financeiro + Precificador","Relatorios completos do negocio","Economize mais de 50% vs. comprar separado","Suporte prioritario"]'
WHERE slug = 'plano-completo';

-- ---- Upsell ----
UPDATE upsells u
JOIN products tp ON tp.id = u.trigger_product_id AND tp.slug = 'gerador-orcamentos'
JOIN products op ON op.id = u.offer_product_id AND op.slug = 'kit-financeiro'
SET u.title = 'Leve tambem o Kit Financeiro + Precificador com 33% OFF',
    u.description = 'Voce ja monta orcamentos como um profissional. Agora descubra o preco ideal de cada servico e controle seu dinheiro sem planilha. So nesta tela: de R$ 29,90 por R$ 19,90 (pagamento unico).',
    u.price = 19.90, u.discount = 10.00;

-- ---- Templates de e-mail (conteudo novo, com layout aplicado no envio) ----
UPDATE email_templates SET subject='Bem-vindo(a) ao {{app_name}}! 🎉', body='<h2 style="margin:0 0 12px;color:#0f172a;font-size:22px">Bem-vindo(a), {{name}}! 👋</h2><p>Que bom ter você aqui. A partir de agora, criar orçamentos profissionais, descobrir o preço certo de cobrar e organizar suas finanças vai ficar muito mais simples.</p><p><strong>Seu primeiro passo:</strong> crie um orçamento em menos de 2 minutos e envie pelo WhatsApp.</p><table role="presentation" cellpadding="0" cellspacing="0" style="margin:24px 0"><tr><td style="border-radius:10px;background:#6366f1"><a href="{{dashboard_url}}" style="display:inline-block;padding:13px 28px;color:#ffffff;text-decoration:none;font-weight:600;font-size:15px;border-radius:10px">Acessar meu painel</a></td></tr></table><p>Qualquer dúvida, é só responder este e-mail. Estamos por aqui. 🙂</p>' WHERE slug='welcome';
UPDATE email_templates SET subject='Confirme seu e-mail no {{app_name}}', body='<h2 style="margin:0 0 12px;color:#0f172a;font-size:22px">Falta só um passo, {{name}}</h2><p>Confirme seu e-mail para deixar sua conta 100% ativa e segura.</p><table role="presentation" cellpadding="0" cellspacing="0" style="margin:24px 0"><tr><td style="border-radius:10px;background:#6366f1"><a href="{{confirm_url}}" style="display:inline-block;padding:13px 28px;color:#ffffff;text-decoration:none;font-weight:600;font-size:15px;border-radius:10px">Confirmar meu e-mail</a></td></tr></table><p style="color:#94a3b8;font-size:13px;margin-top:24px">Se você não criou uma conta no {{app_name}}, pode ignorar este e-mail com segurança.</p>' WHERE slug='email_confirmation';
UPDATE email_templates SET subject='Redefinição de senha — {{app_name}}', body='<h2 style="margin:0 0 12px;color:#0f172a;font-size:22px">Vamos redefinir sua senha</h2><p>Olá {{name}}, recebemos um pedido para redefinir a senha da sua conta. Clique no botão abaixo para criar uma nova senha:</p><table role="presentation" cellpadding="0" cellspacing="0" style="margin:24px 0"><tr><td style="border-radius:10px;background:#6366f1"><a href="{{reset_url}}" style="display:inline-block;padding:13px 28px;color:#ffffff;text-decoration:none;font-weight:600;font-size:15px;border-radius:10px">Criar nova senha</a></td></tr></table><p style="color:#94a3b8;font-size:13px;margin-top:24px">Este link expira em 1 hora. Se não foi você que solicitou, ignore este e-mail — sua senha continua a mesma.</p>' WHERE slug='password_reset';
UPDATE email_templates SET subject='✅ Pagamento aprovado — acesso liberado!', body='<h2 style="margin:0 0 12px;color:#16a34a;font-size:22px">Pagamento aprovado! 🎉</h2><p>Obrigado, {{name}}! Seu pagamento foi confirmado e seu acesso já está liberado. Aproveite todos os recursos agora mesmo.</p><table role="presentation" cellpadding="0" cellspacing="0" style="margin:24px 0"><tr><td style="border-radius:10px;background:#6366f1"><a href="{{dashboard_url}}" style="display:inline-block;padding:13px 28px;color:#ffffff;text-decoration:none;font-weight:600;font-size:15px;border-radius:10px">Começar a usar agora</a></td></tr></table><p>Bom trabalho e boas vendas! 🚀</p>' WHERE slug='purchase_approved';
UPDATE email_templates SET subject='Não conseguimos confirmar seu pagamento', body='<h2 style="margin:0 0 12px;color:#0f172a;font-size:22px">Ops, algo deu errado no pagamento</h2><p>Olá {{name}}, não conseguimos confirmar seu pagamento. Isso costuma ser algo simples (limite, dados do cartão ou instabilidade). Você pode tentar de novo em segundos:</p><table role="presentation" cellpadding="0" cellspacing="0" style="margin:24px 0"><tr><td style="border-radius:10px;background:#6366f1"><a href="{{checkout_url}}" style="display:inline-block;padding:13px 28px;color:#ffffff;text-decoration:none;font-weight:600;font-size:15px;border-radius:10px">Tentar novamente</a></td></tr></table><p style="color:#94a3b8;font-size:13px;margin-top:24px">Nenhuma cobrança foi confirmada. Se precisar de ajuda, é só responder este e-mail.</p>' WHERE slug='payment_failed';
UPDATE email_templates SET subject='Você recebeu um orçamento 📄', body='<h2 style="margin:0 0 12px;color:#0f172a;font-size:22px">Olá {{customer_name}}, seu orçamento está pronto</h2><p>Preparamos um orçamento no valor de <strong style="color:#6366f1">{{quote_total}}</strong> para você. É rápido: abra, confira os detalhes e responda com um clique.</p><table role="presentation" cellpadding="0" cellspacing="0" style="margin:24px 0"><tr><td style="border-radius:10px;background:#6366f1"><a href="{{quote_url}}" style="display:inline-block;padding:13px 28px;color:#ffffff;text-decoration:none;font-weight:600;font-size:15px;border-radius:10px">Ver meu orçamento</a></td></tr></table><p style="color:#94a3b8;font-size:13px;margin-top:24px">Você poderá aprovar ou recusar direto na página do orçamento.</p>' WHERE slug='quote_sent';
UPDATE email_templates SET subject='🎉 Orçamento {{quote_number}} aprovado!', body='<h2 style="margin:0 0 12px;color:#16a34a;font-size:22px">Boa notícia, {{name}}!</h2><p>O orçamento <strong>{{quote_number}}</strong> acabou de ser <strong>aprovado</strong> pelo cliente. 🙌</p><p>Que tal já registrar essa receita e agendar o serviço?</p><table role="presentation" cellpadding="0" cellspacing="0" style="margin:24px 0"><tr><td style="border-radius:10px;background:#6366f1"><a href="{{dashboard_url}}" style="display:inline-block;padding:13px 28px;color:#ffffff;text-decoration:none;font-weight:600;font-size:15px;border-radius:10px">Ir para o painel</a></td></tr></table>' WHERE slug='quote_approved';
UPDATE email_templates SET subject='Orçamento {{quote_number}} foi recusado', body='<h2 style="margin:0 0 12px;color:#0f172a;font-size:22px">Atualização do orçamento {{quote_number}}</h2><p>Olá {{name}}, o cliente recusou o orçamento <strong>{{quote_number}}</strong>. Acontece! Que tal revisar o valor ou as condições e reenviar uma nova proposta?</p><table role="presentation" cellpadding="0" cellspacing="0" style="margin:24px 0"><tr><td style="border-radius:10px;background:#6366f1"><a href="{{dashboard_url}}" style="display:inline-block;padding:13px 28px;color:#ffffff;text-decoration:none;font-weight:600;font-size:15px;border-radius:10px">Revisar e reenviar</a></td></tr></table>' WHERE slug='quote_refused';
UPDATE email_templates SET subject='🎁 Uma oferta especial pra você, {{name}}', body='<h2 style="margin:0 0 12px;color:#0f172a;font-size:22px">{{upsell_title}}</h2><p>Preparamos uma condição exclusiva para turbinar ainda mais o seu dia a dia. Dá uma olhada antes que expire:</p><table role="presentation" cellpadding="0" cellspacing="0" style="margin:24px 0"><tr><td style="border-radius:10px;background:#6366f1"><a href="{{upsell_url}}" style="display:inline-block;padding:13px 28px;color:#ffffff;text-decoration:none;font-weight:600;font-size:15px;border-radius:10px">Ver oferta especial</a></td></tr></table>' WHERE slug='upsell';
UPDATE email_templates SET subject='🔔 Um lembrete do {{app_name}}', body='<h2 style="margin:0 0 12px;color:#0f172a;font-size:22px">Olá {{name}}, passando pra lembrar</h2><p>{{reminder_body}}</p><table role="presentation" cellpadding="0" cellspacing="0" style="margin:24px 0"><tr><td style="border-radius:10px;background:#6366f1"><a href="{{dashboard_url}}" style="display:inline-block;padding:13px 28px;color:#ffffff;text-decoration:none;font-weight:600;font-size:15px;border-radius:10px">Acessar o painel</a></td></tr></table>' WHERE slug='reminder';
UPDATE email_templates SET subject='📬 Nova mensagem de contato', body='<h2 style="margin:0 0 12px;color:#0f172a;font-size:22px">Nova mensagem pelo site</h2><p><strong>De:</strong> {{from_name}} &lt;{{from_email}}&gt;</p><div style="background:#f8fafc;border-left:3px solid #6366f1;padding:12px 16px;border-radius:8px;margin-top:12px">{{message}}</div>' WHERE slug='contact';

-- ---- Templates de WhatsApp ----
UPDATE whatsapp_templates SET body='Ola, {{customer_name}}! 👋\n\nPreparei um orcamento especialmente pra voce, no valor de *{{quote_total}}*.\n\nQualquer duvida, e so me chamar por aqui. 😉' WHERE slug='quote_created';
UPDATE whatsapp_templates SET body='Ola, {{customer_name}}! 📄\n\nSeu orcamento no valor de *{{quote_total}}* ja esta pronto. Da uma olhada e me avisa o que achou:\n\n👉 {{quote_url}}\n\nQualquer coisa, estou por aqui! 🙌' WHERE slug='quote_sent';
UPDATE whatsapp_templates SET body='Que otima noticia! 🎉\n\nO orcamento *{{quote_number}}* foi aprovado. Ja vou dar andamento. Obrigado pela confianca! 🤝' WHERE slug='quote_approved';
UPDATE whatsapp_templates SET body='Ola, {{name}}! ✅\n\nSeu pagamento foi *aprovado* e seu acesso ja esta liberado. Bom trabalho e boas vendas! 🚀' WHERE slug='payment_approved';
UPDATE whatsapp_templates SET body='Oi, {{name}}! 👀\n\nVi que voce comecou sua compra mas nao finalizou. Ta a um passo de destravar tudo!\n\nFinalize aqui em 1 minuto: 👉 {{checkout_url}}\n\nSe precisar de ajuda, e so responder. 🙂' WHERE slug='checkout_recovery';
UPDATE whatsapp_templates SET body='Oi, {{name}}! 🔔\n\nSo passando pra lembrar: {{reminder_body}}' WHERE slug='reminder';

-- Fim. Recarregue o site (Ctrl+F5). O nome, precos, e-mails e mensagens ja estarao atualizados.
