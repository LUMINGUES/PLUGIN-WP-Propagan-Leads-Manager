<?php
/*
Plugin Name: Propagan Leads Manager
Description: Sistema completo de gerenciamento de leads com WhatsApp e IA DeepSeek
Version: 3.2.1
Author: Lumingues
*/

defined('ABSPATH') or die('Acesso direto não permitido!');

// ==================== ATIVAÇÃO DO PLUGIN ====================
register_activation_hook(__FILE__, 'propagan_leads_activate_plugin');

if (!function_exists('propagan_leads_activate_plugin')) {
    function propagan_leads_activate_plugin() {
        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'propagan_leads';
        $charset_collate = $wpdb->get_charset_collate();

        // ATENÇÃO: Coluna 'conversation' removida da criação da tabela.
        // Se a tabela já existir e você precisa remover a coluna 'conversation',
        // você pode precisar desativar e reativar o plugin (isso pode resetar opções)
        // OU executar uma query SQL manual no phpMyAdmin ou similar:
        // ALTER TABLE wp_propagan_leads DROP COLUMN conversation;
        // (substitua 'wp_' pelo prefixo da sua tabela se for diferente)
        if ($wpdb->get_var($wpdb->prepare("SHOW TABLES LIKE %s", $table_name)) != $table_name) {
            $sql = "CREATE TABLE $table_name (
                id mediumint(9) NOT NULL AUTO_INCREMENT,
                name varchar(100) NOT NULL,
                email varchar(100) NOT NULL,
                phone varchar(20) NOT NULL,
                message text,
                status varchar(20) DEFAULT 'novo',
                sale_value decimal(10,2) DEFAULT 0.00,
                created_at datetime DEFAULT CURRENT_TIMESTAMP,
                updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                source varchar(50) DEFAULT 'chatbot',
                PRIMARY KEY (id)
            ) $charset_collate;";

            dbDelta($sql);

            if (!empty($wpdb->last_error)) {
                error_log('Propagan Leads: Erro ao criar tabela de leads: ' . $wpdb->last_error);
                wp_die('Erro ao criar a tabela de leads. Por favor, verifique os logs de erro do servidor ou entre em contato com o suporte técnico.');
            }
        }

        add_option('propagan_options', propagan_leads_default_options());
    }
}

// ==================== CONFIGURAÇÕES INICIAIS ====================
if (!function_exists('propagan_leads_default_options')) {
    function propagan_leads_default_options() {
        return array(
            'profile_image' => 'https://agroforn.propagan.com.br/wp-content/uploads/2025/03/Design-sem-nome-9.png',
            'whatsapp_icon' => 'https://cdn-icons-png.flaticon.com/512/3670/3670051.png',
            'whatsapp_number' => '1436643083',
            'whatsapp_message' => 'Olá, vim através do site e gostaria de mais informações',
            'whatsapp_title' => 'Atendimento Teknoluvas',
            'chatbot_enabled' => '1',
            'form_enabled' => '1',
            'deepseek_api_key' => 'sk-ee0f4e948d8d4ddeb347057d1fb94705',
            'deepseek_prompt' => 'Você é um atendente virtual especializado em atendimento ao cliente de uma loja de e-commerce. Seu nome é Assistente Virtual. Siga estritamente estas instruções:
1. Seja educado, objetivo e prestativo.
2. Mantenha a conversa natural e focada em ajudar o cliente.
3. Se o cliente perguntar sobre produtos ou pedidos, use as informações do WooCommerce para fornecer respostas.
4. Se o cliente pedir para falar com um humano, responda: "Claro! Um atendente humano entrará em contato em breve. Deseja que eu envie seus dados agora?"',
            'woocommerce_consumer_key' => '', // Nova Chave de Consumidor WooCommerce
            'woocommerce_consumer_secret' => '', // Nova Chave Secreta de Consumidor WooCommerce
        );
    }
}

// ==================== CONFIGURAÇÕES DO PAINEL ====================
add_action('admin_init', 'propagan_leads_register_settings');

if (!function_exists('propagan_leads_register_settings')) {
    function propagan_leads_register_settings() {
        register_setting(
            'propagan_leads_settings_group',
            'propagan_options',
            'propagan_leads_sanitize_options'
        );

        add_settings_section(
            'propagan_main_settings',
            'Configurações Principais',
            'propagan_leads_main_settings_callback',
            'propagan-settings'
        );

        add_settings_field(
            'profile_image',
            'Imagem de Perfil',
            'propagan_leads_profile_image_callback',
            'propagan-settings',
            'propagan_main_settings'
        );

        add_settings_field(
            'whatsapp_icon',
            'Ícone do WhatsApp',
            'propagan_leads_whatsapp_icon_callback',
            'propagan-settings',
            'propagan_main_settings'
        );

        add_settings_field(
            'whatsapp_number',
            'Número do WhatsApp',
            'propagan_leads_whatsapp_number_callback',
            'propagan-settings',
            'propagan_main_settings'
        );

        add_settings_field(
            'whatsapp_message',
            'Mensagem Padrão',
            'propagan_leads_whatsapp_message_callback',
            'propagan-settings',
            'propagan_main_settings'
        );

        add_settings_field(
            'whatsapp_title',
            'Título do Chat',
            'propagan_leads_whatsapp_title_callback',
            'propagan-settings',
            'propagan_main_settings'
        );

        add_settings_field(
            'chatbot_enabled',
            'Chatbot WhatsApp',
            'propagan_leads_chatbot_enabled_callback',
            'propagan-settings',
            'propagan_main_settings'
        );

        add_settings_field(
            'form_enabled',
            'Formulário Simples',
            'propagan_leads_form_enabled_callback',
            'propagan-settings',
            'propagan_main_settings'
        );

        add_settings_field(
            'deepseek_api_key',
            'DeepSeek API Key',
            'propagan_leads_deepseek_api_key_callback',
            'propagan-settings',
            'propagan_main_settings'
        );

        add_settings_field(
            'deepseek_prompt',
            'Prompt da IA',
            'propagan_leads_deepseek_prompt_callback',
            'propagan-settings',
            'propagan_main_settings'
        );

        // Nova Seção de Configurações WooCommerce
        add_settings_section(
            'propagan_woocommerce_settings',
            'Configurações WooCommerce (Opcional)',
            'propagan_leads_woocommerce_settings_callback',
            'propagan-settings'
        );

        add_settings_field(
            'woocommerce_consumer_key',
            'WooCommerce Consumer Key',
            'propagan_leads_woocommerce_consumer_key_callback',
            'propagan-settings',
            'propagan_woocommerce_settings'
        );

        add_settings_field(
            'woocommerce_consumer_secret',
            'WooCommerce Consumer Secret',
            'propagan_leads_woocommerce_consumer_secret_callback',
            'propagan-settings',
            'propagan_woocommerce_settings'
        );
    }
}

if (!function_exists('propagan_leads_sanitize_options')) {
    function propagan_leads_sanitize_options($input) {
        $output = array();

        if (isset($input['profile_image'])) {
            $output['profile_image'] = esc_url_raw($input['profile_image']);
        }

        if (isset($input['whatsapp_icon'])) {
            $output['whatsapp_icon'] = esc_url_raw($input['whatsapp_icon']);
        }

        if (isset($input['whatsapp_number'])) {
            $output['whatsapp_number'] = preg_replace('/[^0-9]/', '', $input['whatsapp_number']);
        }

        if (isset($input['whatsapp_message'])) {
            $output['whatsapp_message'] = sanitize_text_field($input['whatsapp_message']);
        }

        if (isset($input['whatsapp_title'])) {
            $output['whatsapp_title'] = sanitize_text_field($input['whatsapp_title']);
        }

        if (isset($input['deepseek_api_key'])) {
            $output['deepseek_api_key'] = sanitize_text_field($input['deepseek_api_key']);
        }

        if (isset($input['deepseek_prompt'])) {
            $output['deepseek_prompt'] = sanitize_textarea_field($input['deepseek_prompt']);
        }

        // Sanitização das novas chaves WooCommerce
        if (isset($input['woocommerce_consumer_key'])) {
            $output['woocommerce_consumer_key'] = sanitize_text_field($input['woocommerce_consumer_key']);
        }

        if (isset($input['woocommerce_consumer_secret'])) {
            $output['woocommerce_consumer_secret'] = sanitize_text_field($input['woocommerce_consumer_secret']);
        }

        $output['chatbot_enabled'] = isset($input['chatbot_enabled']) ? '1' : '0';
        $output['form_enabled'] = isset($input['form_enabled']) ? '1' : '0';

        return $output;
    }
}

if (!function_exists('propagan_leads_main_settings_callback')) {
    function propagan_leads_main_settings_callback() {
        echo '<p>Configure as opções do chat e WhatsApp</p>';
    }
}

// Callback para a nova seção de configurações WooCommerce
if (!function_exists('propagan_leads_woocommerce_settings_callback')) {
    function propagan_leads_woocommerce_settings_callback() {
        echo '<p>Insira suas chaves de API do WooCommerce para permitir que a IA acesse informações da loja (produtos, pedidos, etc.).</p>';
        echo '<p>Você pode gerar suas chaves de API do WooCommerce em: <strong>WooCommerce > Configurações > Avançado > REST API</strong>.</p>';
        echo '<p>Certifique-se de que as permissões para as chaves sejam de <strong>Leitura</strong>, ou <strong>Leitura/Escrita</strong> se planeja funcionalidades mais avançadas no futuro.</p>';
    }
}

// Callback para o campo WooCommerce Consumer Key
if (!function_exists('propagan_leads_woocommerce_consumer_key_callback')) {
    function propagan_leads_woocommerce_consumer_key_callback() {
        $options = get_option('propagan_options', propagan_leads_default_options());
        echo '<input type="text" name="propagan_options[woocommerce_consumer_key]" value="'.esc_attr($options['woocommerce_consumer_key']).'" class="regular-text" />
        <p class="description">Sua Chave de Consumidor (Consumer Key) do WooCommerce.</p>';
    }
}

// Callback para o campo WooCommerce Consumer Secret
if (!function_exists('propagan_leads_woocommerce_consumer_secret_callback')) {
    function propagan_leads_woocommerce_consumer_secret_callback() {
        $options = get_option('propagan_options', propagan_leads_default_options());
        echo '<input type="text" name="propagan_options[woocommerce_consumer_secret]" value="'.esc_attr($options['woocommerce_consumer_secret']).'" class="regular-text" />
        <p class="description">Sua Chave Secreta de Consumidor (Consumer Secret) do WooCommerce.</p>';
    }
}


if (!function_exists('propagan_leads_profile_image_callback')) {
    function propagan_leads_profile_image_callback() {
        $options = get_option('propagan_options', propagan_leads_default_options());
        ?>
        <div class="propagan-image-upload">
            <div class="image-preview">
                <img id="propagan_profile_preview" src="<?php echo esc_url($options['profile_image']); ?>" style="max-width: 100px; max-height: 100px; display: block;">
            </div>
            <input type="text" id="propagan_profile_image" name="propagan_options[profile_image]" value="<?php echo esc_url($options['profile_image']); ?>" class="regular-text">
            <button type="button" class="button" onclick="document.getElementById('propagan_profile_image').value=''; document.getElementById('propagan_profile_preview').src='';">Remover Imagem</button>
            <p class="description">Cole a URL da imagem</p>
        </div>
        <?php
    }
}

if (!function_exists('propagan_leads_whatsapp_icon_callback')) {
    function propagan_leads_whatsapp_icon_callback() {
        $options = get_option('propagan_options', propagan_leads_default_options());
        ?>
        <div class="propagan-image-upload">
            <div class="image-preview">
                <img id="propagan_whatsapp_icon_preview" src="<?php echo esc_url($options['whatsapp_icon']); ?>" style="max-width: 100px; max-height: 100px; display: block;">
            </div>
            <input type="text" id="propagan_whatsapp_icon" name="propagan_options[whatsapp_icon]" value="<?php echo esc_url($options['whatsapp_icon']); ?>" class="regular-text">
            <button type="button" class="button" onclick="document.getElementById('propagan_whatsapp_icon').value=''; document.getElementById('propagan_whatsapp_icon_preview').src='';">Remover Ícone</button>
            <p class="description">Cole a URL do ícone</p>
        </div>
        <?php
    }
}

if (!function_exists('propagan_leads_whatsapp_number_callback')) {
    function propagan_leads_whatsapp_number_callback() {
        $options = get_option('propagan_options', propagan_leads_default_options());
        echo '<input type="text" name="propagan_options[whatsapp_number]" value="'.esc_attr($options['whatsapp_number']).'" class="regular-text" placeholder="5511999999999" />
        <p class="description">Número no formato: 5511999999999 (código do país + DDD + número)</p>';
    }
}

if (!function_exists('propagan_leads_whatsapp_message_callback')) {
    function propagan_leads_whatsapp_message_callback() {
        $options = get_option('propagan_options', propagan_leads_default_options());
        echo '<textarea name="propagan_options[whatsapp_message]" class="large-text" rows="3">'.esc_textarea($options['whatsapp_message']).'</textarea>
        <p class="description">Mensagem padrão que será enviada ao clicar no WhatsApp</p>';
    }
}

if (!function_exists('propagan_leads_whatsapp_title_callback')) {
    function propagan_leads_whatsapp_title_callback() {
        $options = get_option('propagan_options', propagan_leads_default_options());
        echo '<input type="text" name="propagan_options[whatsapp_title]" value="'.esc_attr($options['whatsapp_title']).'" class="regular-text" placeholder="Atendimento Propagan" />
        <p class="description">Título que aparecerá no cabeçalho do chat</p>';
    }
}

if (!function_exists('propagan_leads_chatbot_enabled_callback')) {
    function propagan_leads_chatbot_enabled_callback() {
        $options = get_option('propagan_options', propagan_leads_default_options());
        echo '<label><input type="checkbox" name="propagan_options[chatbot_enabled]" value="1" '.checked('1', $options['chatbot_enabled'], false).'> Ativar Chatbot WhatsApp</label>';
    }
}

if (!function_exists('propagan_leads_form_enabled_callback')) {
    function propagan_leads_form_enabled_callback() {
        $options = get_option('propagan_options', propagan_leads_default_options());
        echo '<label><input type="checkbox" name="propagan_options[form_enabled]" value="1" '.checked('1', $options['form_enabled'], false).'> Ativar Formulário Simples</label>';
    }
}

if (!function_exists('propagan_leads_deepseek_api_key_callback')) {
    function propagan_leads_deepseek_api_key_callback() {
        $options = get_option('propagan_options', propagan_leads_default_options());
        echo '<input type="text" name="propagan_options[deepseek_api_key]" value="'.esc_attr($options['deepseek_api_key']).'" class="regular-text" />
        <p class="description">Sua chave de API da DeepSeek</p>';
    }
}

if (!function_exists('propagan_leads_deepseek_prompt_callback')) {
    function propagan_leads_deepseek_prompt_callback() {
        $options = get_option('propagan_options', propagan_leads_default_options());
        echo '<textarea name="propagan_options[deepseek_prompt]" class="large-text" rows="10">'.esc_textarea($options['deepseek_prompt']).'</textarea>
        <p class="description">Instruções para a IA (ex: "Você é um atendente virtual da empresa X...")</p>';
    }
}

// ==================== MENU ADMINISTRATIVO ====================
add_action('admin_menu', 'propagan_leads_add_admin_menu');

if (!function_exists('propagan_leads_add_admin_menu')) {
    function propagan_leads_add_admin_menu() {
        add_menu_page(
            'Leads Propagan',
            'Leads Propagan',
            'manage_options',
            'propagan-leads',
            'propagan_leads_page',
            'dashicons-groups',
            30
        );

        add_submenu_page(
            'propagan-leads',
            'Configurações',
            'Configurações',
            'manage_options',
            'propagan-settings',
            'propagan_leads_settings_page'
        );

        add_submenu_page(
            'propagan-leads',
            'Exportar Leads',
            'Exportar Leads',
            'manage_options',
            'propagan-export',
            'propagan_leads_export_page'
        );
    }
}

// ==================== PÁGINA DE CONFIGURAÇÕES ====================
if (!function_exists('propagan_leads_settings_page')) {
    function propagan_leads_settings_page() {
        if (!current_user_can('manage_options')) {
            wp_die('Você não tem permissão para acessar esta página.');
        }
        ?>
        <div class="wrap">
            <h1>Configurações do Propagan Leads</h1>
            <form method="post" action="options.php">
                <?php
                settings_fields('propagan_leads_settings_group');
                do_settings_sections('propagan-settings');
                submit_button('Salvar Configurações');
                ?>
            </form>
        </div>
        <?php
    }
}

// ==================== PÁGINA DE LEADS ====================
if (!function_exists('propagan_leads_page')) {
    function propagan_leads_page() {
        global $wpdb;
        $table_name = $wpdb->prefix . 'propagan_leads';

        // Check if the table exists, and if not, show an error and return.
        if ($wpdb->get_var("SHOW TABLES LIKE '$table_name'") != $table_name) {
            echo '<div class="notice notice-error"><p>A tabela de leads não foi encontrada. Por favor, desative e ative o plugin novamente para garantir a criação da tabela.</p></div>';
            return;
        }

        // --- Handle Bulk Actions ---
        if (isset($_POST['propagan_bulk_action']) && current_user_can('manage_options')) {
            $leads_ids = isset($_POST['bulk_leads_ids']) ? array_map('intval', $_POST['bulk_leads_ids']) : array();
            $action = sanitize_text_field($_POST['propagan_bulk_action']);

            if (!empty($leads_ids)) {
                $ids_placeholder = implode(', ', array_fill(0, count($leads_ids), '%d'));
                $success_count = 0;
                $error_count = 0;

                if ($action === 'delete') {
                    $deleted = $wpdb->query($wpdb->prepare("DELETE FROM $table_name WHERE id IN ($ids_placeholder)", $leads_ids));
                    if ($deleted !== false) {
                        $success_count = $deleted;
                    } else {
                        $error_count = count($leads_ids);
                    }
                    if ($success_count > 0) {
                        echo '<div class="notice notice-success"><p>' . sprintf(__('%d leads excluídos com sucesso!', 'propagan-leads'), $success_count) . '</p></div>';
                    }
                    if ($error_count > 0) {
                        echo '<div class="notice notice-error"><p>' . sprintf(__('Erro ao excluir %d leads. Detalhes: %s', 'propagan-leads'), $error_count, $wpdb->last_error) . '</p></div>';
                    }
                } elseif (in_array($action, ['novo', 'converteu', 'ignorou', 'cliente'])) {
                    $updated = $wpdb->query($wpdb->prepare("UPDATE $table_name SET status = %s WHERE id IN ($ids_placeholder)", array_merge([$action], $leads_ids)));
                    if ($updated !== false) {
                        $success_count = $updated;
                    } else {
                        $error_count = count($leads_ids);
                    }
                    if ($success_count > 0) {
                        echo '<div class="notice notice-success"><p>' . sprintf(__('%d leads atualizados para "%s" com sucesso!', 'propagan-leads'), $success_count, $action) . '</p></div>';
                    }
                    if ($error_count > 0) {
                        echo '<div class="notice notice-error"><p>' . sprintf(__('Erro ao atualizar status de %d leads. Detalhes: %s', 'propagan-leads'), $error_count, $wpdb->last_error) . '</p></div>';
                    }
                }
            } else {
                echo '<div class="notice notice-warning"><p>Nenhum lead selecionado para a ação em massa.</p></div>';
            }
        }
        // --- End Handle Bulk Actions ---

        // --- Handle Single Lead Actions ---
        if (isset($_GET['delete_lead']) && check_admin_referer('delete_lead')) {
            $deleted = $wpdb->delete($table_name, array('id' => intval($_GET['delete_lead'])));

            if ($deleted) {
                echo '<div class="notice notice-success"><p>Lead excluído com sucesso!</p></div>';
            } else {
                echo '<div class="notice notice-error"><p>Erro ao excluir lead: ' . $wpdb->last_error . '</p></div>';
            }
        }

        if (isset($_POST['update_lead'])) {
            $lead_id = intval($_POST['lead_id']);
            $status = sanitize_text_field($_POST['status']);
            $sale_value = floatval($_POST['sale_value']);
            $source = sanitize_text_field($_POST['source']);

            $updated = $wpdb->update(
                $table_name,
                array(
                    'status' => $status,
                    'sale_value' => $sale_value,
                    'source' => $source
                ),
                array('id' => $lead_id),
                array('%s', '%f', '%s'),
                array('%d')
            );

            if ($updated !== false) {
                echo '<div class="notice notice-success"><p>Lead atualizado com sucesso!</p></div>';
            } else {
                echo '<div class="notice notice-error"><p>Erro ao atualizar lead: ' . $wpdb->last_error . '</p></div>';
            }
        }
        // --- End Handle Single Lead Actions ---


        // --- Pagination Logic ---
        $leads_per_page = 25;
        $current_page = isset($_GET['paged']) ? max(1, intval($_GET['paged'])) : 1;
        $offset = ($current_page - 1) * $leads_per_page;
        // --- End Pagination Logic ---


        // --- Filtering Logic ---
        $status_filter = isset($_GET['status_filter']) ? sanitize_text_field($_GET['status_filter']) : '';
        $source_filter = isset($_GET['source_filter']) ? sanitize_text_field($_GET['source_filter']) : '';
        $date_start = isset($_GET['date_start']) ? sanitize_text_field($_GET['date_start']) : '';
        $date_end = isset($_GET['date_end']) ? sanitize_text_field($_GET['date_end']) : '';

        $where = array();
        $params = array();
        if ($status_filter) {
            $where[] = "status = %s";
            $params[] = $status_filter;
        }
        if ($source_filter) {
            $where[] = "source = %s";
            $params[] = $source_filter;
        }
        if ($date_start && $date_end) {
            $where[] = "DATE(created_at) BETWEEN %s AND %s";
            $params[] = $date_start;
            $params[] = $date_end;
        } elseif ($date_start) {
            $where[] = "DATE(created_at) >= %s";
            $params[] = $date_start;
        } elseif ($date_end) {
            $where[] = "DATE(created_at) <= %s";
            $params[] = $date_end;
        }
        $where_clause = $where ? ' WHERE ' . implode(' AND ', $where) : '';

        // Selecionando apenas as colunas necessárias (sem 'conversation')
        $query_leads = "SELECT id, name, email, phone, message, status, sale_value, created_at, updated_at, source FROM $table_name $where_clause ORDER BY created_at DESC LIMIT %d OFFSET %d";
        $total_leads_filtered = $wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM $table_name $where_clause", $params));

        $query_params_with_pagination = array_merge($params, [$leads_per_page, $offset]);
        $leads = $wpdb->get_results($wpdb->prepare($query_leads, $query_params_with_pagination));

        $total_pages = ceil($total_leads_filtered / $leads_per_page);
        // --- End Filtering Logic ---

        $total_leads_all = $wpdb->get_var("SELECT COUNT(*) FROM $table_name") ?: 0; // Total leads overall, not just filtered
        $total_sales = $wpdb->get_var("SELECT COALESCE(SUM(sale_value), 0) FROM $table_name WHERE status = 'cliente'");
        $new_leads = $wpdb->get_var("SELECT COUNT(*) FROM $table_name WHERE status = 'novo'") ?: 0;

        ?>
        <div class="wrap">
            <h1>Painel de Leads Propagan</h1>
            <div class="sales-notice">
                <p><span class="dashicons dashicons-warning"></span> Para calcular o valor, o Lead precisa estar com status <strong>"Cliente"</strong>.</p>
            </div>

            <div class="propagan-dashboard">
                <div class="dashboard-cards">
                    <div class="card total-leads">
                        <h3>Total de Leads</h3>
                        <div class="card-value"><?php echo $total_leads_all; ?></div>
                    </div>

                    <div class="card total-sales">
                        <h3>Vendas Totais</h3>
                        <div class="card-value">R$ <?php echo number_format(floatval($total_sales), 2, ',', '.'); ?></div>
                    </div>

                    <div class="card new-leads">
                        <h3>Novos Leads</h3>
                        <div class="card-value"><?php echo $new_leads; ?></div>
                        <?php if ($new_leads > 0): ?>
                            <div class="new-indicator">!</div>
                        <?php endif; ?>
                    </div>
                </div>

                <div class="filters-container">
                    <form method="get" action="<?php echo admin_url('admin.php'); ?>">
                        <input type="hidden" name="page" value="propagan-leads">

                        <div class="filter-row">
                            <div class="filter-group">
                                <label for="status_filter">Status:</label>
                                <select name="status_filter" id="status_filter">
                                    <option value="">Todos os Status</option>
                                    <option value="novo" <?php selected($status_filter, 'novo'); ?>>Novo</option>
                                    <option value="converteu" <?php selected($status_filter, 'converteu'); ?>>Converteu</option>
                                    <option value="ignorou" <?php selected($status_filter, 'ignorou'); ?>>Ignorou</option>
                                    <option value="cliente" <?php selected($status_filter, 'cliente'); ?>>Virou Cliente</option>
                                </select>
                            </div>

                            <div class="filter-group">
                                <label for="source_filter">Fonte:</label>
                                <select name="source_filter" id="source_filter">
                                    <option value="">Todas as Fontes</option>
                                    <option value="chatbot" <?php selected($source_filter, 'chatbot'); ?>>Chatbot</option>
                                    <option value="formulario" <?php selected($source_filter, 'formulario'); ?>>Formulário</option>
                                </select>
                            </div>
                        </div>

                        <div class="filter-row">
                            <div class="filter-group">
                                <label for="date_start">Data Início:</label>
                                <input type="date" name="date_start" id="date_start" value="<?php echo esc_attr($date_start); ?>">
                            </div>

                            <div class="filter-group">
                                <label for="date_end">Data Fim:</label>
                                <input type="date" name="date_end" id="date_end" value="<?php echo esc_attr($date_end); ?>">
                            </div>
                        </div>

                        <div class="filter-actions">
                            <button type="submit" class="button button-primary">Filtrar</button>
                            <a href="<?php echo admin_url('admin.php?page=propagan-leads'); ?>" class="button button-secondary">Limpar Filtros</a>
                        </div>
                    </form>
                </div>

                <?php if (empty($leads)): ?>
                    <div class="notice notice-info"><p>Nenhum lead encontrado.</p></div>
                <?php else: ?>
                    <form method="post" action="<?php echo admin_url('admin.php?page=propagan-leads'); ?>" class="alignleft actions bulkactions">
                        <label for="bulk-action-selector-top" class="screen-reader-text">Selecionar ação em massa</label>
                        <select name="propagan_bulk_action" id="bulk-action-selector-top">
                            <option value="-1">Ações em massa</option>
                            <option value="delete">Excluir</option>
                            <option value="novo">Mudar para Novo</option>
                            <option value="converteu">Mudar para Converteu</option>
                            <option value="ignorou">Mudar para Ignorou</option>
                            <option value="cliente">Mudar para Cliente</option>
                        </select>
                        <input type="submit" id="doaction" class="button action" value="Aplicar">

                        <table class="wp-list-table widefat fixed striped propagan-leads-table">
                            <thead>
                                <tr>
                                    <th scope="col" id="cb" class="manage-column column-cb check-column">
                                        <input type="checkbox" id="select-all-leads">
                                    </th>
                                    <th>ID</th>
                                    <th>Nome</th>
                                    <th>E-mail</th>
                                    <th>Telefone</th>
                                    <th>Status</th>
                                    <th>Valor Venda</th>
                                    <th>Fonte</th>
                                    <th>Data/Hora</th>
                                    <th>Atendimento</th>
                                    <th>Ações</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($leads as $lead): ?>
                                    <tr>
                                        <th scope="row" class="check-column">
                                            <input type="checkbox" name="bulk_leads_ids[]" value="<?php echo esc_attr($lead->id); ?>" class="lead-checkbox">
                                        </th>
                                        <td><?php echo esc_html($lead->id); ?></td>
                                        <td><?php echo esc_html($lead->name); ?></td>
                                        <td><?php echo esc_html($lead->email); ?></td>
                                        <td><?php echo esc_html($lead->phone); ?></td>
                                        <td>
                                            <span class="status-badge status-<?php echo esc_attr($lead->status); ?>">
                                                <?php
                                                $status_labels = [
                                                    'novo' => 'Novo',
                                                    'converteu' => 'Converteu',
                                                    'ignorou' => 'Ignorou',
                                                    'cliente' => 'Cliente'
                                                ];
                                                echo $status_labels[$lead->status] ?? $lead->status;
                                                ?>
                                                <?php if ($lead->status === 'novo'): ?>
                                                    <span class="new-dot"></span>
                                                <?php endif; ?>
                                            </span>
                                        </td>
                                        <td>
                                            <?php if ($lead->sale_value > 0): ?>
                                                R$ <?php echo number_format($lead->sale_value, 2, ',', '.'); ?>
                                            <?php else: ?>
                                                -
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <?php echo $lead->source === 'formulario' ? 'Formulário' : 'Chatbot'; ?>
                                        </td>
                                        <td><?php echo date_i18n('d/m/Y H:i', strtotime($lead->created_at)); ?></td>
                                        <td>
                                            <a href="https://wa.me/<?php echo esc_attr(preg_replace('/[^0-9]/', '', $lead->phone)); ?>"
                                                target="_blank"
                                                class="whatsapp-button">
                                                Contactar
                                            </a>
                                        </td>
                                        <td class="actions">
                                            <a href="#" class="button button-small edit-lead" data-id="<?php echo $lead->id; ?>">Editar</a>
                                            <a href="<?php echo esc_url(wp_nonce_url(
                                                add_query_arg('delete_lead', $lead->id),
                                                'delete_lead'
                                            )); ?>"
                                            class="button button-small button-secondary"
                                            onclick="return confirm('Tem certeza que deseja excluir este lead?')">
                                                Excluir
                                            </a>
                                        </td>
                                    </tr>
                                    <tr class="edit-form" id="edit-form-<?php echo $lead->id; ?>" style="display: none;">
                                        <td colspan="11">
                                            <form method="post" class="lead-edit-form">
                                                <input type="hidden" name="lead_id" value="<?php echo $lead->id; ?>">
                                                <div class="form-fields">
                                                    <div class="form-group">
                                                        <label>Status:</label>
                                                        <select name="status" required>
                                                            <option value="novo" <?php selected($lead->status, 'novo'); ?>>Novo</option>
                                                            <option value="converteu" <?php selected($lead->status, 'converteu'); ?>>Converteu</option>
                                                            <option value="ignorou" <?php selected($lead->status, 'ignorou'); ?>>Ignorou</option>
                                                            <option value="cliente" <?php selected($lead->status, 'cliente'); ?>>Virou Cliente</option>
                                                        </select>
                                                    </div>
                                                    <div class="form-group">
                                                        <label>Valor da Venda (R$):</label>
                                                        <input type="number" name="sale_value" step="0.01" min="0" value="<?php echo esc_attr($lead->sale_value); ?>">
                                                    </div>
                                                    <div class="form-group">
                                                        <label>Fonte:</label>
                                                        <select name="source" required>
                                                            <option value="chatbot" <?php selected($lead->source, 'chatbot'); ?>>Chatbot</option>
                                                            <option value="formulario" <?php selected($lead->source, 'formulario'); ?>>Formulário</option>
                                                        </select>
                                                    </div>
                                                </div>
                                                <button type="submit" name="update_lead" class="button button-primary">Salvar</button>
                                                <button type="button" class="button button-secondary cancel-edit">Cancelar</button>
                                            </form>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </form>
                    <?php
                    $pagination_args = array(
                        'base' => add_query_arg('paged', '%#%'),
                        'format' => '',
                        'total' => $total_pages,
                        'current' => $current_page,
                        'show_all' => false,
                        'end_size' => 1,
                        'mid_size' => 2,
                        'prev_next' => true,
                        'prev_text' => __('&laquo; Anterior'),
                        'next_text' => __('Próximo &raquo;'),
                        'type' => 'plain',
                    );
                    echo '<div class="tablenav"><div class="tablenav-pages">' . paginate_links($pagination_args) . '</div></div>';
                    ?>
                <?php endif; ?>
            </div>

            <script>
                jQuery(document).ready(function($) {
                    $('.edit-lead').click(function(e) {
                        e.preventDefault();
                        var leadId = $(this).data('id');
                        $('#edit-form-' + leadId).toggle(); // Alterna a visibilidade
                    });

                    $('.cancel-edit').click(function() {
                        $(this).closest('.edit-form').hide();
                    });

                    if (!$('#date_end').val()) {
                        var today = new Date().toISOString().split('T')[0];
                        $('#date_end').val(today);
                    }

                    // Select all leads for bulk actions
                    $('#select-all-leads').on('change', function() {
                        $('.lead-checkbox').prop('checked', $(this).prop('checked'));
                    });
                });
            </script>
        </div>
        <?php
    }
}

// ==================== SHORTCODES ====================
add_shortcode('propagan_chatbot', 'propagan_leads_chatbot_shortcode');

if (!function_exists('propagan_leads_chatbot_shortcode')) {
    function propagan_leads_chatbot_shortcode() {
        $options = get_option('propagan_options', propagan_leads_default_options());

        if (!isset($options['chatbot_enabled']) || $options['chatbot_enabled'] !== '1') {
            return '';
        }

        wp_enqueue_script('propagan-chatbot', plugin_dir_url(__FILE__) . 'js/chatbot.js', array('jquery'), '1.2', true);
        wp_localize_script('propagan-chatbot', 'propagan_vars', array(
            'ajax_url' => admin_url('admin-ajax.php'),
            'api_key' => $options['deepseek_api_key'],
            'whatsapp_number' => preg_replace('/[^0-9]/', '', $options['whatsapp_number']),
            'default_message' => $options['whatsapp_message'],
            'profile_image' => $options['profile_image'],
            'chat_title' => $options['whatsapp_title'],
            'deepseek_nonce' => wp_create_nonce('propagan_deepseek_nonce'),
            'wc_api_enabled' => (!empty($options['woocommerce_consumer_key']) && !empty($options['woocommerce_consumer_secret'])) ? '1' : '0'
        ));

        ob_start();
        ?>
        <div id="propagan-chatbot-container">
            <div class="whatsapp-icon" onclick="togglePropaganChat()">
                <img src="<?php echo esc_url($options['whatsapp_icon']); ?>" alt="WhatsApp Icon">
            </div>

            <div class="whatsapp-chat-container">
                <div class="whatsapp-chat-header">
                    <div class="profile">
                        <img src="<?php echo esc_url($options['profile_image']); ?>" alt="Profile Picture">
                        <span><?php echo esc_html($options['whatsapp_title']); ?></span>
                    </div>
                    <button class="close-btn" onclick="togglePropaganChat()">×</button>
                </div>

                <div class="chat-messages" id="chat-messages">
                    <div class="message bot-message">
                        <div class="message-content">
                            Olá! Por favor, preencha seus dados para que possamos iniciar o atendimento:
                        </div>
                        <div class="message-time"><?php echo date('H:i'); ?></div>
                    </div>

                    <div class="lead-form-container" id="lead-form-container">
                        <form id="propagan-lead-form">
                            <?php wp_nonce_field('propagan_lead_form_action', 'propagan_lead_form_nonce'); ?>
                            <div class="form-group">
                                <input type="text" name="name" placeholder="Seu nome completo" required>
                            </div>
                            <div class="form-group">
                                <input type="email" name="email" placeholder="Seu e-mail válido" required>
                            </div>
                            <div class="form-group">
                                <input type="tel" name="phone" placeholder="Seu telefone com DDD" required>
                            </div>
                            <button type="submit" id="form-submit-button">Iniciar Atendimento</button>
                        </form>
                    </div>
                    <div class="message bot-message typing-indicator" style="display:none;">
                        <div class="message-content">
                            <div class="dot-flashing"></div>
                        </div>
                        <div class="message-time"></div>
                    </div>
                </div>

                <div class="chat-input-container" style="display: none;" id="chat-input-container">
                    <input type="text" id="chat-input" placeholder="Digite sua mensagem...">
                    <button id="send-button">Enviar</button>
                </div>
            </div>
        </div>
        <?php
        return ob_get_clean();
    }
}

add_shortcode('propagan_simple_form', 'propagan_leads_simple_form_shortcode');

if (!function_exists('propagan_leads_simple_form_shortcode')) {
    function propagan_leads_simple_form_shortcode() {
        $options = get_option('propagan_options', propagan_leads_default_options());

        if (!isset($options['form_enabled']) || $options['form_enabled'] !== '1') {
            return '';
        }

        ob_start();
        ?>
        <div class="propagan-simple-form">
            <form id="propagan-simple-form">
                <?php wp_nonce_field('propagan_simple_form_action', 'propagan_simple_form_nonce'); ?>
                <div class="form-group">
                    <input type="text" name="name" placeholder="Seu nome" required>
                </div>
                <div class="form-group">
                    <input type="email" name="email" placeholder="Seu e-mail" required>
                </div>
                <div class="form-group">
                    <input type="tel" name="phone" placeholder="Seu telefone com DDD" required>
                </div>
                <div class="form-group">
                    <textarea name="message" placeholder="Sua mensagem"></textarea>
                </div>
                <button type="submit">Enviar</button>
            </form>
        </div>

        <script>
        // Este é um script separado para o formulário simples
        jQuery(document).ready(function($) {
            // --- Funções de manipulação de Cookies (repetidas, pode ser otimizado para um arquivo JS comum) ---
            function setCookie(name, value, days) {
                var expires = "";
                if (days) {
                    var date = new Date();
                    date.setTime(date.getTime() + (days * 24 * 60 * 60 * 1000));
                    expires = "; expires=" + date.toUTCString();
                }
                document.cookie = name + "=" + (value || "") + expires + "; path=/";
            }

            function getCookie(name) {
                var nameEQ = name + "=";
                var ca = document.cookie.split(';');
                for (var i = 0; i < ca.length; i++) {
                    var c = ca[i];
                    while (c.charAt(0) === ' ') c = c.substring(1, c.length);
                    if (c.indexOf(nameEQ) === 0) return c.substring(nameEQ.length, c.length);
                }
                return null;
            }
            // --- Fim das funções de Cookies ---

            // Tentar carregar os dados dos cookies para preencher o formulário simples
            var savedName = getCookie('propagan_lead_name');
            var savedEmail = getCookie('propagan_lead_email');
            var savedPhone = getCookie('propagan_lead_phone');

            if (savedName) $('#propagan-simple-form input[name="name"]').val(savedName);
            if (savedEmail) $('#propagan-simple-form input[name="email"]').val(savedEmail);
            if (savedPhone) $('#propagan-simple-form input[name="phone"]').val(savedPhone);


            $('#propagan-simple-form').submit(function(e) {
                e.preventDefault();

                var formData = $(this).serialize();
                // O jQuery.serialize() já inclui o nonce, se estiver no formulário.
                // certifique-se de que o input hidden para o nonce está dentro do form.

                var simpleFormData = $(this).serializeArray().reduce(function(obj, item) {
                    obj[item.name] = item.value;
                    return obj;
                }, {});

                console.log('Dados do formulário simples para salvar lead:', simpleFormData);

                $.ajax({
                    url: '<?php echo admin_url('admin-ajax.php'); ?>',
                    type: 'POST',
                    data: formData + '&action=propagan_save_lead&source=formulario', // Adiciona a action e a source
                    success: function(response) {
                        console.log('Resposta do AJAX (salvar lead simples):', response);
                        if (response.success) {
                            alert('Obrigado! Seus dados foram enviados com sucesso.');
                            $('#propagan-simple-form')[0].reset();

                            // Salvar dados do formulário simples nos cookies também
                            // Se este formulário for a principal fonte, você pode ajustar os dias
                            setCookie('propagan_lead_name', simpleFormData.name, 30);
                            setCookie('propagan_lead_email', simpleFormData.email, 30);
                            setCookie('propagan_lead_phone', simpleFormData.phone, 30);

                        } else {
                            alert(response.data.message || 'Ocorreu um erro. Por favor, tente novamente.');
                        }
                    },
                    error: function(xhr) {
                        console.error('Erro na requisição AJAX de salvamento (xhr simples):', xhr);
                        var errorMsg = 'Erro de conexão. Por favor, tente novamente.';
                        if (xhr.responseJSON && xhr.responseJSON.message) {
                            errorMsg = xhr.responseJSON.message;
                        }
                        alert(errorMsg);
                    }
                });
            });
        });
        </script>
        <?php
        return ob_get_clean();
    }
}

// ==================== AJAX HANDLERS ====================
add_action('wp_ajax_propagan_save_lead', 'propagan_leads_ajax_save_lead');
add_action('wp_ajax_nopriv_propagan_save_lead', 'propagan_leads_ajax_save_lead');

if (!function_exists('propagan_leads_ajax_save_lead')) {
    function propagan_leads_ajax_save_lead() {
        $chatbot_nonce = isset($_POST['propagan_lead_form_nonce']) ? $_POST['propagan_lead_form_nonce'] : '';
        $simple_form_nonce = isset($_POST['propagan_simple_form_nonce']) ? $_POST['propagan_simple_form_nonce'] : '';
        
        $nonce_verified = false;
        
        if (!empty($chatbot_nonce) && wp_verify_nonce($chatbot_nonce, 'propagan_lead_form_action')) {
            $nonce_verified = true;
        } elseif (!empty($simple_form_nonce) && wp_verify_nonce($simple_form_nonce, 'propagan_simple_form_action')) {
            $nonce_verified = true;
        }

        if (!$nonce_verified) {
            wp_send_json_error(array('message' => 'Erro de segurança. Por favor, recarregue a página.'));
            return;
        }

        $required_fields = array('name', 'email', 'phone');
        foreach ($required_fields as $field) {
            if (empty($_POST[$field])) {
                wp_send_json_error(array('message' => 'Por favor, preencha todos os campos obrigatórios.'));
                return;
            }
        }

        $name = sanitize_text_field($_POST['name']);
        $email = sanitize_email($_POST['email']);
        $phone = sanitize_text_field($_POST['phone']);
        $message = isset($_POST['message']) ? sanitize_textarea_field($_POST['message']) : '';
        $source = isset($_POST['source']) ? sanitize_text_field($_POST['source']) : 'chatbot';
        // A variável $conversation foi removida, pois não está mais sendo usada/salva.

        if (!is_email($email)) {
            wp_send_json_error(array('message' => 'Por favor, insira um e-mail válido.'));
            return;
        }

        $phone_digits = preg_replace('/[^0-9]/', '', $phone);
        if (strlen($phone_digits) < 10) {
            wp_send_json_error(array('message' => 'Por favor, insira um telefone válido com DDD (mínimo 10 dígitos).'));
            return;
        }

        global $wpdb;
        $table_name = $wpdb->prefix . 'propagan_leads';

        if ($wpdb->get_var("SHOW TABLES LIKE '$table_name'") != $table_name) {
            wp_send_json_error(array('message' => 'Erro no banco de dados. A tabela de leads não foi encontrada.'));
            return;
        }

        $result = $wpdb->insert(
            $table_name,
            array(
                'name' => $name,
                'email' => $email,
                'phone' => $phone,
                'message' => $message,
                'status' => 'novo',
                'source' => $source,
                // Removido 'conversation' => $conversation,
                'created_at' => current_time('mysql'),
                'updated_at' => current_time('mysql')
            ),
            // Ajustar o array de formatos de acordo com as colunas inseridas.
            // Removido um '%s' referente à coluna 'conversation'.
            array(
                '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s'
            )
        );

        if ($result === false) {
            wp_send_json_error(array('message' => 'Erro ao salvar no banco de dados. Por favor, tente novamente. Detalhes: ' . $wpdb->last_error));
        } else {
            wp_send_json_success(array(
                'message' => 'Dados salvos com sucesso!',
                'lead_id' => $wpdb->insert_id
            ));
        }
    }
}

add_action('wp_ajax_propagan_deepseek_chat', 'propagan_leads_ajax_deepseek_chat');
add_action('wp_ajax_nopriv_propagan_deepseek_chat', 'propagan_leads_ajax_deepseek_chat');

if (!function_exists('propagan_leads_ajax_deepseek_chat')) {
    function propagan_leads_ajax_deepseek_chat() {
        check_ajax_referer('propagan_deepseek_nonce', 'nonce');

        $options = get_option('propagan_options', propagan_leads_default_options());
        $api_key = $options['deepseek_api_key'];
        $prompt = $options['deepseek_prompt'];
        $user_message = sanitize_text_field($_POST['message']);

        // Recupera dados do lead para incluir no contexto da IA
        $lead_name = isset($_POST['lead_name']) ? sanitize_text_field($_POST['lead_name']) : '';
        $lead_email = isset($_POST['lead_email']) ? sanitize_email($_POST['lead_email']) : '';
        $lead_phone = isset($_POST['lead_phone']) ? sanitize_text_field($_POST['lead_phone']) : '';

        $messages = array(
            array(
                'role' => 'system',
                'content' => $prompt
            )
        );

        // Adiciona contexto do lead se disponível
        if (!empty($lead_name)) {
            $messages[] = array(
                'role' => 'system',
                'content' => "Informações do Cliente: Nome: $lead_name, Email: $lead_email, Telefone: $lead_phone."
            );
        }

        // Adiciona contexto do WooCommerce se as chaves forem fornecidas e o WooCommerce estiver ativo
        if (class_exists('WooCommerce') && !empty($options['woocommerce_consumer_key']) && !empty($options['woocommerce_consumer_secret'])) {
            $wc_consumer_key = $options['woocommerce_consumer_key'];
            $wc_consumer_secret = $options['woocommerce_consumer_secret'];

            // Constrói a URL base para a API REST do WooCommerce
            $woocommerce_api_base = get_rest_url() . 'wc/v3/';

            // Exemplo: Buscar alguns produtos recentes para dar contexto à IA
            // Note: Para um ambiente de produção, considere usar uma biblioteca de cliente WooCommerce API mais robusta.
            // Isso é um exemplo simplificado. Para buscas mais complexas, você precisaria de lógica para interpretar a intenção do usuário.
            $products_response = wp_remote_get(add_query_arg(array(
                'consumer_key' => $wc_consumer_key,
                'consumer_secret' => $wc_consumer_secret,
                'per_page' => 5, // Buscar 5 produtos recentes
                'status' => 'publish' // Apenas produtos publicados
            ), $woocommerce_api_base . 'products'));

            $products_data = array();
            if (!is_wp_error($products_response) && wp_remote_retrieve_response_code($products_response) === 200) {
                $products_body = wp_remote_retrieve_body($products_response);
                $products = json_decode($products_body, true);
                if (!empty($products)) {
                    foreach ($products as $product) {
                        $products_data[] = [
                            'id' => $product['id'],
                            'name' => $product['name'],
                            'price' => $product['price'],
                            'description' => substr(strip_tags($product['description']), 0, 150) . '...', // Limita o tamanho da descrição
                            'stock_status' => $product['stock_status']
                        ];
                    }
                }
            }

            $wc_context = "Informações da Loja WooCommerce (se relevante para a pergunta do cliente):\n";
            if (!empty($products_data)) {
                $wc_context .= "Produtos recentes:\n";
                foreach ($products_data as $p) {
                    $wc_context .= "- ID: {$p['id']}, Nome: {$p['name']}, Preço: R$ {$p['price']}, Estoque: {$p['stock_status']}, Descrição: {$p['description']}\n";
                }
            } else {
                $wc_context .= "Nenhum produto recente encontrado ou erro ao buscar produtos do WooCommerce.\n";
            }
            // Você pode adicionar mais dados do WooCommerce aqui conforme necessário (ex: categorias, pedidos para um cliente específico se tiver o ID dele)

            $messages[] = array(
                'role' => 'system',
                'content' => $wc_context
            );
        }


        $messages[] = array(
            'role' => 'user',
            'content' => $user_message
        );

        $api_args = array(
            'headers' => array(
                'Content-Type' => 'application/json',
                'Authorization' => 'Bearer ' . $api_key
            ),
            'body' => json_encode(array(
                'model' => 'deepseek-chat',
                'messages' => $messages,
                'temperature' => 0.7,
                'max_tokens' => 1000,
                'stream' => false
            )),
            'timeout' => 30
        );

        $response = wp_remote_post('https://api.deepseek.com/v1/chat/completions', $api_args);

        if (is_wp_error($response)) {
            wp_send_json_error(array(
                'message' => 'Erro na conexão com a API DeepSeek: ' . $response->get_error_message()
            ));
            return;
        }

        $response_code = wp_remote_retrieve_response_code($response);
        $response_body = wp_remote_retrieve_body($response);
        $data = json_decode($response_body, true);

        if ($response_code !== 200) {
            wp_send_json_error(array(
                'message' => 'Erro na API DeepSeek: ' . ($data['message'] ?? 'Código ' . $response_code),
                'response' => $data
            ));
            return;
        }

        if (empty($data['choices'][0]['message']['content'])) {
            wp_send_json_error(array(
                'message' => 'Resposta vazia da API DeepSeek',
                'response' => $data
            ));
            return;
        }

        $ai_response = $data['choices'][0]['message']['content'];
        
        wp_send_json_success(array(
            'response' => $ai_response,
            'requires_human' => (stripos($ai_response, 'atendente humano') !== false)
        ));
    }
}

// ==================== FUNÇÃO PARA SALVAR LEADS ====================
if (!function_exists('propagan_leads_save_lead')) {
    function propagan_leads_save_lead($name, $email, $phone, $message = '', $source = 'chatbot') { // Parâmetro $conversation removido
        global $wpdb;
        $table_name = $wpdb->prefix . 'propagan_leads';

        $result = $wpdb->insert(
            $table_name,
            array(
                'name' => $name,
                'email' => $email,
                'phone' => $phone,
                'message' => $message,
                'source' => $source,
                // Removido 'conversation' => $conversation,
                'status' => 'novo',
                'created_at' => current_time('mysql'),
                'updated_at' => current_time('mysql')
            ),
            // Ajustar o array de formatos de acordo com as colunas.
            // Removido um '%s' correspondente à coluna 'conversation'.
            array('%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s')
        );

        return $result ? $wpdb->insert_id : false;
    }
}

// ==================== CARREGAMENTO DE ASSETS ====================
add_action('wp_enqueue_scripts', 'propagan_leads_load_assets');

if (!function_exists('propagan_leads_load_assets')) {
    function propagan_leads_load_assets() {
        if (!is_admin()) {
            wp_enqueue_style('font-awesome', 'https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css');
            wp_enqueue_style('propagan-chatbot', plugin_dir_url(__FILE__) . 'css/chatbot.css');
        }
    }
}

add_action('admin_enqueue_scripts', 'propagan_leads_load_admin_assets');

if (!function_exists('propagan_leads_load_admin_assets')) {
    function propagan_leads_load_admin_assets($hook) {
        if ($hook == 'propagan-leads_page_propagan-settings' || $hook == 'toplevel_page_propagan-leads' || $hook == 'propagan-leads_page_propagan-export') {
            wp_enqueue_media();
            wp_enqueue_style('wp-color-picker');
            wp_enqueue_script('wp-color-picker');
        }

        wp_enqueue_style('font-awesome', 'https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css');
        wp_enqueue_style('propagan-admin', plugin_dir_url(__FILE__) . 'css/admin.css');
    }
}

// ==================== EXPORTAR LEADS ====================
if (!function_exists('propagan_leads_export_page')) {
    function propagan_leads_export_page() {
        global $wpdb;
        $table_name = $wpdb->prefix . 'propagan_leads';

        $status_filter = isset($_GET['status_filter']) ? sanitize_text_field($_GET['status_filter']) : '';
        $source_filter = isset($_GET['source_filter']) ? sanitize_text_field($_GET['source_filter']) : '';
        $date_from = isset($_GET['date_from']) ? sanitize_text_field($_GET['date_from']) : '';
        $date_to = isset($_GET['date_to']) ? sanitize_text_field($_GET['date_to']) : '';
        $min_value = isset($_GET['min_value']) ? floatval($_GET['min_value']) : '';

        $where = array();
        $params = array();

        if ($status_filter) {
            $where[] = 'status = %s';
            $params[] = $status_filter;
        }

        if ($date_from) {
            $where[] = 'created_at >= %s';
            $params[] = $date_from . ' 00:00:00';
        }

        if ($date_to) {
            $where[] = 'created_at <= %s';
            $params[] = $date_to . ' 23:59:59';
        }

        if ($source_filter) {
            $where[] = 'source = %s';
            $params[] = $source_filter;
        }

        if ($min_value !== '') {
            $where[] = 'sale_value >= %f';
            $params[] = $min_value;
        }

        // Selecionando apenas as colunas necessárias (sem 'conversation')
        $query = "SELECT id, name, email, phone, message, status, sale_value, created_at, updated_at, source FROM $table_name";
        if (!empty($where)) {
            $query .= ' WHERE ' . implode(' AND ', $where);
        }
        $query .= ' ORDER BY created_at DESC';

        if (!empty($params)) {
            $leads = $wpdb->get_results($wpdb->prepare($query, $params));
        } else {
            $leads = $wpdb->get_results($query);
        }

        $statuses = $wpdb->get_col("SELECT DISTINCT status FROM $table_name");
        $sources = $wpdb->get_col("SELECT DISTINCT source FROM $table_name");

        $total_leads = count($leads);
        $total_value = 0;
        foreach ($leads as $lead) {
            $total_value += $lead->sale_value;
        }

        ?>
        <div class="wrap">
            <h1>Exportar Leads</h1>

            <div class="propagan-export-container">
                <form method="get" action="">
                    <input type="hidden" name="page" value="propagan-export">

                    <div class="filter-row">
                        <div class="filter-group">
                            <label for="status_filter">Status</label>
                            <select name="status_filter" id="status_filter">
                                <option value="">Todos os status</option>
                                <?php foreach ($statuses as $status): ?>
                                    <option value="<?php echo esc_attr($status); ?>" <?php selected($status_filter, $status); ?>>
                                        <?php echo esc_html($status); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="filter-group">
                            <label for="source_filter">Fonte</label>
                            <select name="source_filter" id="source_filter">
                                <option value="">Todas as fontes</option>
                                <?php foreach ($sources as $source): ?>
                                    <option value="<?php echo esc_attr($source); ?>" <?php selected($source_filter, $source); ?>>
                                        <?php echo esc_html($source); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="filter-group">
                            <label for="min_value">Valor mínimo</label>
                            <input type="number" name="min_value" id="min_value" value="<?php echo esc_attr($min_value); ?>" step="0.01" min="0">
                        </div>
                    </div>

                    <div class="filter-row">
                        <div class="filter-group">
                            <label for="date_from">Data de</label>
                            <input type="date" name="date_from" id="date_from" value="<?php echo esc_attr($date_from); ?>">
                        </div>

                        <div class="filter-group">
                            <label for="date_to">Data até</label>
                            <input type="date" name="date_to" id="date_to" value="<?php echo esc_attr($date_to); ?>">
                        </div>

                        <div class="filter-actions">
                            <button type="submit" class="button button-primary">Filtrar</button>
                            <a href="<?php echo admin_url('admin.php?page=propagan-export'); ?>" class="button button-secondary">Limpar</a>
                        </div>
                    </div>
                </form>

                <?php if (!empty($leads)): ?>
                    <form method="post" action="<?php echo admin_url('admin-post.php'); ?>">
                        <input type="hidden" name="action" value="propagan_export_csv">
                        <input type="hidden" name="status_filter" value="<?php echo esc_attr($status_filter); ?>">
                        <input type="hidden" name="date_from" value="<?php echo esc_attr($date_from); ?>">
                        <input type="hidden" name="date_to" value="<?php echo esc_attr($date_to); ?>">
                        <input type="hidden" name="source_filter" value="<?php echo esc_attr($source_filter); ?>">
                        <input type="hidden" name="min_value" value="<?php echo esc_attr($min_value); ?>">

                        <div class="export-actions">
                            <input type="checkbox" id="select_all" onclick="jQuery('.lead-checkbox').prop('checked', this.checked)">
                            <label for="select_all">Selecionar todos</label>

                            <button type="submit" class="button button-primary">Exportar selecionados</button>
                        </div>

                        <table class="wp-list-table widefat fixed striped">
                            <thead>
                                <tr>
                                    <th width="50px"></th>
                                    <th>Nome</th>
                                    <th>E-mail</th>
                                    <th>Telefone</th>
                                    <th>Status</th>
                                    <th>Valor</th>
                                    <th>Fonte</th>
                                    <th>Data</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($leads as $lead): ?>
                                    <tr>
                                        <td><input type="checkbox" class="lead-checkbox" name="leads_ids[]" value="<?php echo $lead->id; ?>"></td>
                                        <td><?php echo esc_html($lead->name); ?></td>
                                        <td><?php echo esc_html($lead->email); ?></td>
                                        <td><?php echo esc_html($lead->phone); ?></td>
                                        <td><?php echo esc_html($lead->status); ?></td>
                                        <td><?php echo $lead->sale_value > 0 ? 'R$ ' . number_format($lead->sale_value, 2, ',', '.') : '-'; ?></td>
                                        <td><?php echo esc_html($lead->source); ?></td>
                                        <td><?php echo date('d/m/Y H:i', strtotime($lead->created_at)); ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </form>
                <?php else: ?>
                    <p>Nenhum lead encontrado com os filtros selecionados.</p>
                <?php endif; ?>
            </div>
        </div>
        <?php
    }
}

add_action('admin_post_propagan_export_csv', 'propagan_leads_export_csv');

if (!function_exists('propagan_leads_export_csv')) {
    function propagan_leads_export_csv() {
        if (!current_user_can('manage_options')) {
            wp_die('Sem permissão');
        }

        if (empty($_POST['leads_ids'])) {
            wp_die('Nenhum lead selecionado.');
        }

        global $wpdb;
        $table_name = $wpdb->prefix . 'propagan_leads';

        $ids = array_map('intval', $_POST['leads_ids']);
        $ids_sql = implode(',', $ids);

        $where = array("id IN ($ids_sql)");
        $params = array();

        if (!empty($_POST['status_filter'])) {
            $where[] = 'status = %s';
            $params[] = sanitize_text_field($_POST['status_filter']);
        }

        if (!empty($_POST['date_from'])) {
            $where[] = 'created_at >= %s';
            $params[] = sanitize_text_field($_POST['date_from']) . ' 00:00:00';
        }

        if (!empty($_POST['date_to'])) {
            $where[] = 'created_at <= %s';
            $params[] = sanitize_text_field($_POST['date_to']) . ' 23:59:59';
        }

        if (!empty($_POST['source_filter'])) {
            $where[] = 'source = %s';
            $params[] = sanitize_text_field($_POST['source_filter']);
        }

        if (!empty($_POST['min_value']) && is_numeric($_POST['min_value'])) {
            $where[] = 'sale_value >= %f';
            $params[] = floatval($_POST['min_value']);
        }

        // Selecionando apenas as colunas necessárias (sem 'conversation')
        $query = "SELECT id, name, email, phone, message, status, sale_value, created_at, updated_at, source FROM $table_name WHERE " . implode(' AND ', $where) . " ORDER BY created_at DESC";

        if (!empty($params)) {
            $leads = $wpdb->get_results($wpdb->prepare($query, $params));
        } else {
            $leads = $wpdb->get_results($query);
        }

        header('Content-Type: text/csv');
        header('Content-Disposition: attachment;filename="leads-exportados-' . date('Y-m-d-H-i-s') . '.csv"');

        $output = fopen('php://output', 'w');

        // Removida a coluna 'Conversa' do cabeçalho do CSV
        fputcsv($output, ['ID', 'Nome', 'Email', 'Telefone', 'Mensagem', 'Status', 'Valor Venda', 'Fonte', 'Criado em'], ';');

        foreach ($leads as $lead) {
            fputcsv($output, [
                $lead->id,
                $lead->name,
                $lead->email,
                $lead->phone,
                $lead->message,
                $lead->status,
                number_format($lead->sale_value, 2, ',', '.'),
                $lead->source,
                date('d/m/Y H:i', strtotime($lead->created_at)),
            ], ';');
        }

        fclose($output);
        exit;
    }
}

// ==================== ESTILOS DO PAINEL ADMINISTRATIVO ====================
add_action('admin_head', 'propagan_leads_admin_styles');

if (!function_exists('propagan_leads_admin_styles')) {
    function propagan_leads_admin_styles() {
        ?>
        <style>
            .propagan-dashboard {
                font-family: 'Segoe UI', -apple-system, BlinkMacSystemFont, sans-serif;
                background-color: #f8f9fa;
                padding: 30px;
                border-radius: 12px;
                box-shadow: 0 4px 6px rgba(0, 0, 0, 0.05);
            }

            .dashboard-cards {
                display: grid;
                grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
                gap: 24px;
                margin-bottom: 40px;
            }

            .card {
                background: #fff;
                border-radius: 12px;
                padding: 25px;
                box-shadow: 0 4px 12px rgba(0, 0, 0, 0.08);
                position: relative;
                border-top: 4px solid;
                transition: transform 0.3s ease, box-shadow 0.3s ease;
            }

            .card:hover {
                transform: translateY(-5px);
                box-shadow: 0 8px 16px rgba(0, 0, 0, 0.1);
            }

            .card.total-leads {
                border-top-color: #4e73df;
            }

            .card.total-sales {
                border-top-color: #1cc88a;
            }

            .card.new-leads {
                border-top-color: #f6c23e;
            }

            .card h3 {
                margin: 0 0 12px;
                font-size: 14px;
                font-weight: 600;
                color: #5a5c69;
                text-transform: uppercase;
                letter-spacing: 0.5px;
            }

            .card-value {
                font-size: 36px;
                font-weight: 700;
                margin: 15px 0;
                color: #2e3a59;
            }

            .new-indicator {
                position: absolute;
                top: 15px;
                right: 15px;
                width: 20px;
                height: 20px;
                background-color: #e74a3b;
                color: white;
                border-radius: 50%;
                display: flex;
                align-items: center;
                justify-content: center;
                font-size: 12px;
                font-weight: bold;
                animation: pulse 1.5s infinite;
            }

            @keyframes pulse {
                0% { transform: scale(0.95); box-shadow: 0 0 0 0 rgba(231, 74, 59, 0.7); }
                70% { transform: scale(1.05); box-shadow: 0 0 0 10px rgba(231, 74, 59, 0); }
                100% { transform: scale(0.95); box-shadow: 0 0 0 0 rgba(231, 74, 59, 0); }
            }

            .filters-container {
                background: #fff;
                padding: 25px;
                margin-bottom: 30px;
                border-radius: 12px;
                box-shadow: 0 4px 12px rgba(0, 0, 0, 0.08);
                border-left: 4px solid #4e73df;
            }

            .filter-row {
                display: flex;
                flex-wrap: wrap;
                gap: 20px;
                margin-bottom: 15px;
            }

            .filter-group {
                flex: 1;
                min-width: 200px;
            }

            .filter-group label {
                display: block;
                margin-bottom: 8px;
                font-size: 14px;
                font-weight: 600;
                color: #5a5c69;
                text-transform: uppercase;
                letter-spacing: 0.5px;
            }

            .filter-group select,
            .filter-group input {
                width: 100%;
                height: 44px;
                padding: 0 16px;
                border: 1px solid #d1d3e2;
                border-radius: 8px;
                background-color: #f8f9fc;
                font-size: 14px;
                transition: all 0.3s;
            }

            .filter-group select:focus,
            .filter-group input:focus {
                border-color: #4e73df;
                box-shadow: 0 0 0 0.2rem rgba(78, 115, 223, 0.25);
                outline: none;
            }

            .filter-actions {
                display: flex;
                gap: 10px;
                margin-top: 15px;
            }

            .filter-actions .button {
                padding: 10px 20px;
                border-radius: 8px;
                font-weight: 600;
                transition: all 0.3s;
            }

            .filter-actions .button-primary {
                background-color: #4e73df;
                border-color: #4e73df;
            }

            .filter-actions .button-primary:hover {
                background-color: #2e59d9;
                border-color: #2e59d9;
                transform: translateY(-2px);
            }

            .filter-actions .button-secondary {
                background-color: #f8f9fc;
                border-color: #d1d3e2;
                color: #5a5c69;
            }

            .filter-actions .button-secondary:hover {
                background-color: #e2e6ea;
                border-color: #d1d3e2;
                transform: translateY(-2px);
            }

            .propagan-leads-table {
                width: 100%;
                background: #fff;
                border-radius: 12px;
                overflow: hidden;
                box-shadow: 0 4px 12px rgba(0, 0, 0, 0.08);
            }

            .propagan-leads-table th {
                background-color: #f8f9fc;
                color: #4e73df;
                font-weight: 700;
                padding: 18px;
                text-align: left;
                border-bottom: 2px solid #e3e6f0;
            }

            .propagan-leads-table td {
                padding: 18px;
                vertical-align: middle;
                border-bottom: 1px solid #e3e6f0;
            }

            .propagan-leads-table tr:last-child td {
                border-bottom: none;
            }

            .propagan-leads-table tr:hover td {
                background-color: #f8f9fc;
            }

            .status-badge {
                display: inline-flex;
                align-items: center;
                padding: 8px 14px;
                border-radius: 20px;
                font-size: 12px;
                font-weight: 600;
                color: #fff;
                text-transform: uppercase;
                letter-spacing: 0.5px;
            }

            .status-novo {
                background: linear-gradient(135deg, #f6c23e, #e74a3b);
            }

            .status-converteu {
                background: linear-gradient(135deg, #36b9cc, #1cc88a);
            }

            .status-ignorou {
                background: linear-gradient(135deg, #858796, #5a5c69);
            }

            .status-cliente {
                background: linear-gradient(135deg, #1cc88a, #4e73df);
            }

            .new-dot {
                display: inline-block;
                width: 8px;
                height: 8px;
                background-color: #fff;
                border-radius: 50%;
                margin-left: 6px;
                animation: blink 1.5s infinite;
            }

            @keyframes blink {
                0% { opacity: 1; }
                50% { opacity: 0.3; }
                100% { opacity: 1; }
            }

            .whatsapp-button {
                display: inline-flex;
                align-items: center;
                justify-content: center;
                padding: 8px 18px;
                border-radius: 20px;
                font-size: 13px;
                font-weight: 600;
                color: #fff;
                background: linear-gradient(135deg, #25D366, #128C7E);
                text-decoration: none;
                transition: all 0.3s;
            }

            .whatsapp-button:hover {
                background: linear-gradient(135deg, #128C7E, #075E56);
                transform: translateY(-2px);
                box-shadow: 0 4px 8px rgba(37, 211, 102, 0.3);
                color: #fff;
            }

            .actions .button {
                padding: 6px 12px;
                border-radius: 6px;
                font-size: 13px;
                font-weight: 600;
                transition: all 0.3s;
            }

            .actions .button-small {
                padding: 5px 10px;
                font-size: 12px;
            }

            .actions .button:hover {
                transform: translateY(-2px);
                box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
            }

            .actions .button-secondary {
                background-color: #f8f9fc;
                border-color: #d1d3e2;
                color: #5a5c69;
            }

            .actions .button-secondary:hover {
                background-color: #e2e6ea;
                border-color: #d1d3e2;
            }

            .lead-edit-form {
                background-color: #f8f9fc;
                padding: 20px;
                border-radius: 8px;
                border-left: 4px solid #4e73df;
            }

            .form-fields {
                display: grid;
                grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
                gap: 15px;
                margin-bottom: 15px;
            }

            .form-group label {
                display: block;
                margin-bottom: 8px;
                font-weight: 600;
                color: #5a5c69;
                font-size: 13px;
            }

            .form-group select,
            .form-group input,
            .form-group textarea { /* Adicionado textarea aqui */
                width: 100%;
                padding: 8px 12px;
                border: 1px solid #d1d3e2;
                border-radius: 6px;
                background-color: #fff;
                font-size: 13px;
            }

            .form-group input[type="number"] {
                padding: 8px 12px;
            }
            .form-group textarea {
                resize: vertical; /* Permite redimensionar verticalmente */
            }

            .sales-notice {
                background-color: #f8f9fc;
                border-left: 4px solid #f6c23e;
                padding: 15px;
                margin-bottom: 25px;
                border-radius: 6px;
                display: flex;
                align-items: center;
                gap: 10px;
            }

            .sales-notice p {
                margin: 0;
                color: #5a5c69;
                font-size: 14px;
            }

            .sales-notice .dashicons {
                color: #f6c23e;
                font-size: 20px;
            }

            .propagan-image-upload {
                margin-bottom: 15px;
            }
            .propagan-image-upload .image-preview {
                margin-bottom: 10px;
            }
            .propagan-image-upload .image-preview img {
                max-width: 100px;
                max-height: 100px;
                border-radius: 50%;
            }
            .propagan-image-upload .button {
                margin-right: 5px;
                margin-top: 5px;
            }
            .propagan-image-upload input[type="text"] {
                width: 100%;
                max-width: 500px;
                margin-bottom: 5px;
            }
            /* Bulk Actions styling */
            .tablenav .actions.bulkactions {
                display: flex;
                align-items: center;
                gap: 10px;
                margin-bottom: 15px;
            }
            .tablenav .actions.bulkactions select,
            .tablenav .actions.bulkactions .button {
                height: 38px;
                line-height: 1;
                padding: 0 10px;
                font-size: 13px;
            }
            .tablenav .tablenav-pages {
                float: right;
                margin: 15px 0;
            }
            .tablenav .tablenav-pages a,
            .tablenav .tablenav-pages .page-numbers.current {
                display: inline-block;
                padding: 5px 10px;
                margin: 0 2px;
                border: 1px solid #c3c4c7;
                border-radius: 3px;
                text-decoration: none;
            }
            .tablenav .tablenav-pages .page-numbers.current {
                background-color: #007cba;
                color: #fff;
                border-color: #007cba;
            }
        </style>
        <?php
    }
}

// ==================== ESTILOS DO FRONT-END ====================
add_action('wp_head', 'propagan_leads_frontend_styles');

if (!function_exists('propagan_leads_frontend_styles')) {
    function propagan_leads_frontend_styles() {
        ?>
        <style>
            #propagan-chatbot-container {
                --whatsapp-green: #25D366;
                --whatsapp-dark-green: #128C7E;
                --whatsapp-header: #075E54;
                --whatsapp-light: #DCF8C6;
                --whatsapp-bg: #ECE5DD;
            }

            .whatsapp-icon {
                position: fixed;
                bottom: 20px;
                right: 20px;
                width: 60px;
                height: 60px;
                background-color: var(--whatsapp-green);
                border-radius: 50%;
                display: flex;
                align-items: center;
                justify-content: center;
                cursor: pointer;
                box-shadow: 0 0 15px rgba(37, 211, 102, 0.6);
                z-index: 9999;
                animation: pulse 2s infinite;
            }

            @keyframes pulse {
                0% { transform: scale(1); box-shadow: 0 0 0 0 rgba(37, 211, 102, 0.7); }
                70% { transform: scale(1.05); box-shadow: 0 0 0 15px rgba(37, 211, 102, 0); }
                100% { transform: scale(1); box-shadow: 0 0 0 0 rgba(37, 211, 102, 0); }
            }

            .whatsapp-icon:hover {
                background-color: var(--whatsapp-dark-green);
            }

            .whatsapp-icon img {
                width: 30px;
                height: 30px;
            }

            .whatsapp-chat-container {
                position: fixed;
                bottom: 90px;
                right: 20px;
                width: 350px;
                height: 500px;
                background: white;
                border-radius: 10px;
                box-shadow: 0 5px 15px rgba(0, 0, 0, 0.2);
                z-index: 9998;
                display: none;
                flex-direction: column;
                overflow: hidden;
            }

            .whatsapp-chat-container.active {
                display: flex;
            }

            .whatsapp-chat-header {
                background-color: var(--whatsapp-header);
                color: white;
                padding: 15px;
                display: flex;
                justify-content: space-between;
                align-items: center;
            }

            .profile {
                display: flex;
                align-items: center;
                gap: 10px;
            }

            .profile img {
                width: 40px;
                height: 40px;
                border-radius: 50%;
                object-fit: cover;
            }

            .profile span {
                font-weight: 500;
            }

            .close-btn {
                background: none;
                border: none;
                color: white;
                font-size: 24px;
                cursor: pointer;
            }

            .chat-messages {
                flex: 1;
                padding: 15px;
                overflow-y: auto;
                background-color: var(--whatsapp-bg);
                background-image: url("data:image/svg+xml,%3Csvg width='100' height='100' viewBox='0 0 100 100' xmlns='http://www.w3.org/2000/svg'%3E%3Cpath d='M11 18c3.866 0 7-3.134 7-7s-3.134-7-7-7-7 3.134-7 7 3.134 7 7 7zm48 25c3.866 0 7-3.134 7-7s-3.134-7-7-7-7 3.134-7 7 3.134 7 7 7zm-43-7c1.657 0 3-1.343 3-3s-1.343-3-3-3-3 1.343-3 3 1.343 3 3 3zm63 31c1.657 0 3-1.343 3-3s-1.343-3-3-3-3 1.343-3 3 1.343 3 3 3zM34 90c1.657 0 3-1.343 3-3s-1.343-3-3-3-3 1.343-3 3 1.343 3 3 3zm56-76c1.657 0 3-1.343 3-3s-1.343-3-3-3-3 1.343-3 3 1.343 3 3 3zM12 86c2.21 0 4-1.79 4-4s-1.79-4-4-4-4 1.79-4 4 1.79 4 4 4zm28-65c2.21 0 4-1.79 4-4s-1.79-4-4-4-4 1.79-4 4 1.79 4 4 4zm23-11c2.76 0 5-2.24 5-5s-2.24-5-5-5-5 2.24-5 5 2.24 5 5 5zm-6 60c2.21 0 4-1.79 4-4s-1.79-4-4-4-4 1.79-4 4 1.79 4 4 4zm29 22c2.76 0 5-2.24 5-5s-2.24-5-5-5-5 2.24-5 5 2.24 5 5 5zM32 63c2.76 0 5-2.24 5-5s-2.24-5-5-5-5 2.24-5 5 2.24 5 5 5zm57-13c2.76 0 5-2.24 5-5s-2.24-5-5-5-5 2.24-5 5 2.24 5 5 5zm-9-21c1.105 0 2-.895 2-2s-.895-2-2-2-2 .895-2 2 .895 2 2 2zM60 91c1.105 0 2-.895 2-2s-.895-2-2-2-2 .895-2 2 .895 2 2 2zM35 41c1.105 0 2-.895 2-2s-.895-2-2-2-2 .895-2 2 .895 2 2 2zM12 60c1.105 0 2-.895 2-2s-.895-2-2-2-2 .895-2 2 .895 2 2 2z' fill='%239C92AC' fill-opacity='0.05' fill-rule='evenodd'/%3E%3C/svg%3E");
            }

            .message {
                max-width: 80%;
                margin-bottom: 15px;
                position: relative;
            }

            .message-content {
                padding: 10px 15px;
                border-radius: 18px;
                font-size: 14px;
                line-height: 1.4;
                word-wrap: break-word;
            }

            .bot-message {
                align-self: flex-start;
            }

            .bot-message .message-content {
                background-color: white;
                border-top-left-radius: 0;
                box-shadow: 0 1px 1px rgba(0, 0, 0, 0.1);
            }
            
            .bot-message a {
                color: #075e54;
                text-decoration: underline;
                font-weight: bold;
            }
            
            .bot-message a:hover {
                color: #25D366;
            }

            .user-message {
                align-self: flex-end;
            }

            .user-message .message-content {
                background-color: var(--whatsapp-light);
                border-top-right-radius: 0;
                color: #111;
            }

            .message-time {
                font-size: 11px;
                color: #999;
                margin-top: 5px;
                text-align: right;
            }

            .lead-form-container {
                padding: 15px;
                background-color: white;
                border-radius: 10px;
                margin: 10px 0;
                box-shadow: 0 1px 3px rgba(0,0,0,0.1);
            }

            #propagan-lead-form {
                display: flex;
                flex-direction: column;
                gap: 12px;
            }

            #propagan-lead-form .form-group {
                margin-bottom: 0;
            }

            #propagan-lead-form input {
                width: 100%;
                padding: 12px 15px;
                border: 1px solid #ddd;
                border-radius: 8px;
                font-size: 14px;
                transition: all 0.3s;
            }

            #propagan-lead-form input:focus {
                outline: none;
                border-color: #25D366;
                box-shadow: 0 0 0 3px rgba(37, 211, 102, 0.1);
            }

            #form-submit-button {
                padding: 12px;
                background: linear-gradient(135deg, #25D366, #128C7E);
                color: white;
                border: none;
                border-radius: 8px;
                font-weight: 500;
                cursor: pointer;
                transition: all 0.3s;
            }

            #form-submit-button:hover {
                background: linear-gradient(135deg, #128C7E, #075E54);
                transform: translateY(-2px);
            }

            .chat-input-container {
                display: flex;
                padding: 10px;
                background-color: #f5f5f5;
                border-top: 1px solid #e5e5e5;
            }

            #chat-input {
                flex: 1;
                padding: 10px 15px;
                border: 1px solid #ddd;
                border-radius: 20px;
                outline: none;
                font-size: 14px;
            }

            #send-button {
                margin-left: 10px;
                padding: 0 20px;
                background-color: var(--whatsapp-green);
                color: white;
                border: none;
                border-radius: 20px;
                cursor: pointer;
                font-weight: 500;
                transition: background-color 0.3s;
            }

            #send-button:hover {
                background-color: var(--whatsapp-dark-green);
            }

            .human-attendant {
                padding: 15px;
                text-align: center;
                background-color: #f5f5f5;
                border-top: 1px solid #e5e5e5;
            }

            #start-human-chat {
                padding: 10px 20px;
                background-color: var(--whatsapp-green);
                color: white;
                border: none;
                border-radius: 20px;
                cursor: pointer;
                font-weight: 500;
                transition: background-color 0.3s;
            }

            #start-human-chat:hover {
                background-color: var(--whatsapp-dark-green);
            }

            .propagan-simple-form {
                max-width: 500px;
                margin: 0 auto;
                padding: 20px;
                background: #fff;
                border-radius: 12px;
                box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
            }

            .propagan-simple-form .form-group {
                margin-bottom: 15px;
            }

            .propagan-simple-form input,
            .propagan-simple-form textarea {
                width: 100%;
                padding: 12px 15px;
                border: 1px solid #ddd;
                border-radius: 8px;
                font-size: 14px;
                transition: all 0.3s ease;
            }

            .propagan-simple-form textarea {
                min-height: 100px;
            }

            .propagan-simple-form input:focus,
            .propagan-simple-form textarea:focus {
                outline: none;
                border-color: #25D366;
                box-shadow: 0 0 0 3px rgba(37, 211, 102, 0.1);
            }

            .propagan-simple-form button {
                width: 100%;
                padding: 12px;
                background: linear-gradient(135deg, #25D366, #128C7E);
                color: white;
                border: none;
                border-radius: 8px;
                font-weight: 500;
                cursor: pointer;
                transition: all 0.3s ease;
            }

            .propagan-simple-form button:hover {
                background: linear-gradient(135deg, #128C7E, #075E54);
                transform: translateY(-2px);
            }

            /* Chatbot typing animation */
            .typing-indicator {
                display: flex;
                align-items: center;
                background-color: white;
                padding: 10px 15px;
                border-radius: 18px;
                border-top-left-radius: 0;
                box-shadow: 0 1px 1px rgba(0, 0, 0, 0.1);
                width: fit-content;
            }

            .dot-flashing {
                position: relative;
                width: 10px;
                height: 10px;
                border-radius: 5px;
                background-color: #999;
                color: #999;
                animation: dotFlashing 1s infinite alternate;
                animation-delay: 0s;
            }

            .dot-flashing::before, .dot-flashing::after {
                content: '';
                display: inline-block;
                position: absolute;
                top: 0;
                width: 10px;
                height: 10px;
                border-radius: 5px;
                background-color: #999;
                color: #999;
                animation: dotFlashing 1s infinite alternate;
            }

            .dot-flashing::before {
                left: -15px;
                animation-delay: .2s;
            }

            .dot-flashing::after {
                left: 15px;
                animation-delay: .4s;
            }

            @keyframes dotFlashing {
                0% { background-color: #999; }
                50%, 100% { background-color: #e0e0e0; }
            }
        </style>
        <?php
    }
}

// ==================== JAVASCRIPT DO CHATBOT ====================
add_action('wp_footer', 'propagan_leads_chatbot_scripts');

if (!function_exists('propagan_leads_chatbot_scripts')) {
    function propagan_leads_chatbot_scripts() {
        if (!is_admin()) {
            ?>
            <script>
            jQuery(document).ready(function($) {
                var leadData = {
                    name: '',
                    email: '',
                    phone: ''
                };

                // --- Funções de manipulação de Cookies ---
                function setCookie(name, value, days) {
                    var expires = "";
                    if (days) {
                        var date = new Date();
                        date.setTime(date.getTime() + (days * 24 * 60 * 60 * 1000));
                        expires = "; expires=" + date.toUTCString();
                    }
                    document.cookie = name + "=" + (value || "") + expires + "; path=/";
                }

                function getCookie(name) {
                    var nameEQ = name + "=";
                    var ca = document.cookie.split(';');
                    for (var i = 0; i < ca.length; i++) {
                        var c = ca[i];
                        while (c.charAt(0) === ' ') c = c.substring(1, c.length);
                        if (c.indexOf(nameEQ) === 0) return c.substring(nameEQ.length, c.length);
                    }
                    return null;
                }

                function eraseCookie(name) {
                    document.cookie = name + '=; Max-Age=-99999999;';
                }
                // --- Fim das funções de Cookies ---

                // Tentar carregar os dados do lead dos cookies ao iniciar
                var savedName = getCookie('propagan_lead_name');
                var savedEmail = getCookie('propagan_lead_email');
                var savedPhone = getCookie('propagan_lead_phone');

                if (savedName && savedEmail && savedPhone) {
                    leadData.name = savedName;
                    leadData.email = savedEmail;
                    leadData.phone = savedPhone;

                    $('#lead-form-container').hide();
                    $('#chat-input-container').show();
                    addMessage(`Olá novamente, ${leadData.name}! Como posso te ajudar hoje?`, false);
                    
                    // Adiciona a mensagem com o link logo após a saudação
                    addMessage(`Você pode <a href="#" id="start-human-chat-link">clicar aqui para falar com um humano</a> ou continuar sua conversa para tirar suas dúvidas com a nossa Inteligência Artificial.`, false);
                    
                    $('#chat-messages').scrollTop($('#chat-messages')[0].scrollHeight);

                    // Atacha o evento de clique ao novo link
                    $('#start-human-chat-link').off('click').on('click', function(e) {
                        e.preventDefault();
                        sendToWhatsApp();
                    });

                } else {
                    $('#chat-input-container').hide();
                }

                window.togglePropaganChat = function() {
                    var chatContainer = $('.whatsapp-chat-container');
                    if (chatContainer.css('display') === 'none') {
                        chatContainer.css('display', 'flex');
                        if (leadData.name) {
                            $('#chat-messages').scrollTop($('#chat-messages')[0].scrollHeight);
                        }
                    } else {
                        chatContainer.hide();
                    }
                };

                function addMessage(content, isUser) {
                    var messagesContainer = $('#chat-messages');
                    var messageClass = isUser ? 'user-message' : 'bot-message';
                    var now = new Date();
                    var time = now.toLocaleTimeString([], {
                        hour: '2-digit',
                        minute: '2-digit'
                    });

                    var messageHTML = `
                        <div class="message ${messageClass}">
                            <div class="message-content">${content}</div>
                            <div class="message-time">${time}</div>
                        </div>
                    `;

                    messagesContainer.append(messageHTML);
                    messagesContainer.scrollTop(messagesContainer[0].scrollHeight);
                }

                function showTypingIndicator() {
                    $('.typing-indicator').show();
                    $('#chat-messages').scrollTop($('#chat-messages')[0].scrollHeight);
                }

                function hideTypingIndicator() {
                    $('.typing-indicator').hide();
                }

                function sendToDeepSeek(message) {
                    showTypingIndicator();
                    return $.ajax({
                        url: propagan_vars.ajax_url,
                        type: 'POST',
                        dataType: 'json',
                        data: {
                            action: 'propagan_deepseek_chat',
                            message: message,
                            nonce: propagan_vars.deepseek_nonce,
                            lead_name: leadData.name,
                            lead_email: leadData.email,
                            lead_phone: leadData.phone
                        },
                        beforeSend: function() {
                            $('#send-button').prop('disabled', true).text('Enviando...');
                        },
                        success: function(response) {
                            if (response.success) {
                                addMessage(response.data.response, false);
                            } else {
                                console.error('Erro na API:', response.data);
                                addMessage('Desculpe, houve um erro ao processar sua mensagem. Por favor, tente novamente.', false);
                            }
                        },
                        error: function(xhr, status, error) {
                            console.error('Erro na requisição:', status, error);
                            addMessage('Erro de conexão com o serviço de atendimento. Por favor, tente novamente mais tarde.', false);
                        },
                        complete: function() {
                            hideTypingIndicator();
                            $('#send-button').prop('disabled', false).text('Enviar');
                        }
                    });
                }

                function saveLeadToDatabase() {
                    var formData = {
                        name: $('#propagan-lead-form input[name="name"]').val().trim(),
                        email: $('#propagan-lead-form input[name="email"]').val().trim(),
                        phone: $('#propagan-lead-form input[name="phone"]').val().trim(),
                        propagan_lead_form_nonce: $('#propagan-lead-form input[name="propagan_lead_form_nonce"]').val(),
                        action: 'propagan_save_lead',
                        source: 'chatbot',
                    };

                    return $.ajax({
                        url: propagan_vars.ajax_url,
                        type: 'POST',
                        data: formData,
                        beforeSend: function() {
                            $('#form-submit-button').prop('disabled', true).text('Salvando...');
                        },
                        success: function(response) {
                            if (response.success) {
                                leadData.name = formData.name;
                                leadData.email = formData.email;
                                leadData.phone = formData.phone;

                                setCookie('propagan_lead_name', leadData.name, 30);
                                setCookie('propagan_lead_email', leadData.email, 30);
                                setCookie('propagan_lead_phone', leadData.phone, 30);

                                $('#lead-form-container').hide();
                                $('#chat-input-container').show();
                                addMessage(`Obrigado, ${leadData.name}! Como posso te ajudar hoje?`, false);
                                
                                // Adiciona a mensagem com o link do WhatsApp
                                addMessage(`Você pode <a href="#" id="start-human-chat-link">clicar aqui para falar com um humano</a> ou continuar sua conversa para tirar suas dúvidas com a nossa Inteligência Artificial.`, false);
                                
                                $('#chat-messages').scrollTop($('#chat-messages')[0].scrollHeight);

                                // Atacha o evento de clique ao novo link
                                $('#start-human-chat-link').off('click').on('click', function(e) {
                                    e.preventDefault();
                                    sendToWhatsApp();
                                });
                            } else {
                                var errorMsg = response.data && response.data.message ?
                                    response.data.message :
                                    'Ocorreu um erro ao salvar seus dados. Por favor, tente novamente.';
                                addMessage(errorMsg, false);
                            }
                        },
                        error: function(xhr) {
                            var errorMsg = 'Erro de conexão ou validação ao salvar dados. Por favor, tente novamente.';
                            if (xhr.responseJSON && xhr.responseJSON.message) {
                                errorMsg = xhr.responseJSON.message;
                            }
                            addMessage(errorMsg, false);
                        },
                        complete: function() {
                            $('#form-submit-button').prop('disabled', false).text('Enviar Dados');
                        }
                    });
                }

                function sendToWhatsApp() {
                    var message = `Olá, meu nome é ${leadData.name}. Meu e-mail é ${leadData.email} e meu telefone é ${leadData.phone}. Gostaria de falar com um atendente humano.`;
                    var whatsappUrl = `https://wa.me/55${propagan_vars.whatsapp_number}?text=${encodeURIComponent(message)}`;

                    window.open(whatsappUrl, '_blank');
                    $('.whatsapp-chat-container').hide();
                }

                $('#propagan-lead-form').submit(function(e) {
                    e.preventDefault();

                    leadData = {
                        name: $('input[name="name"]').val().trim(),
                        email: $('input[name="email"]').val().trim(),
                        phone: $('input[name="phone"]').val().trim()
                    };

                    if (!leadData.name || !leadData.email || !leadData.phone) {
                        addMessage('Por favor, preencha todos os campos.', false);
                        return;
                    }

                    saveLeadToDatabase();
                });

                $('#send-button').click(function() {
                    var input = $('#chat-input');
                    var message = input.val().trim();

                    if (message) {
                        addMessage(message, true);
                        input.val('');

                        sendToDeepSeek(message);
                    }
                });

                $('#chat-input').keypress(function(e) {
                    if (e.which === 13) {
                        $('#send-button').click();
                    }
                });

                // Remover o botão fixo, já que a função foi movida para o chat
                $('#human-attendant').hide();
            });
            </script>
            <?php
        }
    }
}