<?php
// C:\xampp\htdocs\PBV\index.php - ATUALIZADO E COMPLETO

// Bloco de inicialização
// Garante que a sessão esteja sempre iniciada
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}
// Inclui a conexão (necessária para 'usuario-create.php' e outras páginas que buscam dados)
require_once('conexao.php');

// Define a página atual ou 'home' como padrão
$page = isset($_REQUEST['page']) ? $_REQUEST['page'] : 'home';
?>
<!DOCTYPE html> 
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Bem Viver</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css">
</head>
<body>

    <?php require_once('navbar.php'); // Inclui a barra de navegação ?>

    <main class="container mt-4">
        <?php
            // --- Bloco para exibir mensagens de feedback da sessão ---
            // Usado pelo acoes.php para mostrar o resultado de uma operação após o redirecionamento.
            if (isset($_SESSION['success_message'])) {
                echo '<div class="alert alert-success alert-dismissible fade show" role="alert">
                        <i class="bi bi-check-circle-fill me-2"></i>' . htmlspecialchars($_SESSION['success_message']) . '
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                      </div>';
                unset($_SESSION['success_message']); // Limpa a mensagem após exibir
            }
            if (isset($_SESSION['error_message'])) {
                echo '<div class="alert alert-danger alert-dismissible fade show" role="alert">
                        <i class="bi bi-exclamation-triangle-fill me-2"></i>' . htmlspecialchars($_SESSION['error_message']) . '
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                      </div>';
                unset($_SESSION['error_message']); // Limpa a mensagem após exibir
            }
            if (isset($_SESSION['info_message'])) {
                echo '<div class="alert alert-info alert-dismissible fade show" role="alert">
                        <i class="bi bi-info-circle-fill me-2"></i>' . htmlspecialchars($_SESSION['info_message']) . '
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                      </div>';
                unset($_SESSION['info_message']); // Limpa a mensagem após exibir
            }
            // --- Fim do bloco de mensagens ---


            // --- Roteador de Páginas ---
            // O 'switch' decide qual arquivo PHP de conteúdo incluir com base no parâmetro 'page'
            switch($page) {
                
                // === PÁGINAS PRINCIPAIS E LOGIN ===
                case 'home':
                    include('home.php');
                    break;
                case 'login':
                    include('login.php');
                    break;
                case 'login_admin': // Corrigido de 'login-admin'
                    include('login_admin.php');
                    break;
                case 'logout':
                    include('logout.php');
                    break;

                // === CRUD DE USUÁRIO (Admin do Site e Cadastro) ===
                case 'usuario-create':
                    include('usuario-create.php');
                    break;
                case 'usuario-listar': // Painel principal do Admin do Site
                    include('usuario-listar.php');
                    break;
                case 'usuario-edit':
                    include('usuario-edit.php');
                    break;
                
                // === PERFIL DO USUÁRIO LOGADO (Comum a todos) ===
                case 'perfil':
                    include('perfil.php');
                    break;
                case 'perfil-editar':
                    include('perfil-editar.php');
                    break;

                // === PÁGINAS DO ADMIN DA CLÍNICA (NOVAS) ===
                case 'painel-clinica': // NOVO
                    include('painel-clinica.php');
                    break;
                case 'clinica-create': // NOVO
                    include('clinica-create.php');
                    break;
                case 'clinica-edit': // NOVO
                    include('clinica-edit.php');
                    break;
                case 'cadastrar-medicos-clinica': // NOVO
                    include('cadastrar-medicos-clinica.php');
                    break;
                case 'gerenciar-agenda-valores': // NOVO
                    include('gerenciar-agenda-valores.php');
                    break;
                case 'consultas-clinica': // NOVO (Placeholder)
                    include('consultas-clinica.php');
                    break;
                case 'clinica-create': // NOVO (Placeholder)
                    include('consultas-clinica.php');
                    break;

                // === PÁGINAS DO MÉDICO (NOVAS) ===
                case 'medico-gerenciar-horarios': // NOVO
                    include('medico-gerenciar-horarios.php');
                    break;
                case 'medico-agenda': // NOVO
                    include('medico-agenda.php');
                    break;
                case 'consulta-atendimento': // NOVO
                    include('consulta-atendimento.php');
                    break;
                case 'agendamento-create': // Esta página será o NOVO fluxo de busca/calendário
                    include('agendamento-create.php');
                    break;

                // === PÁGINAS DO ADMIN DO SITE (Extras) ===
                case 'controle': // Novo Painel Admin
                     include('controle.php');
                    break;
                case 'consultas-listar': // NOVO (Substitui agendamento-listar)
                     include('consultas-listar.php');
                    break;
                case 'clinica-listar': // NOVO
                    include('clinica-listar.php');
                    break;
                case 'consultas-geral': // NOVO
                    include('consultas-geral.php');
                    break;
                case 'especialidades-admin': // NOVO
                    include('especialidades-admin.php');
                    break;
                case 'clinica-edit-admin': // Admin edita QUALQUER clínica
                include('clinica-edit-admin.php'); // TODO: Criar este arquivo!
                break;
                
                // --- PÁGINA PADRÃO (Home) ---
                default:
                    include('home.php');
            }
        ?>
    </main>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>