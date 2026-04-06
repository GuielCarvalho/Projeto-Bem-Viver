<?php
// C:\xampp\htdocs\PBV\painel-clinica.php - FASE 2 - NOVO ARQUIVO

// Garante sessão e conexão
if (session_status() == PHP_SESSION_NONE) { session_start(); }
if (!isset($conn)) { require_once('conexao.php'); }

// --- 1. Verificação de Acesso ---
if (!isset($_SESSION['loggedin']) || $_SESSION['tipo_usuario'] !== 'admin_clinica') {
    $_SESSION['error_message'] = 'Acesso negado.';
    header('Location: index.php?page=login');
    exit;
}
$idAdminUsuario = $_SESSION['idUsuario'];

// --- 2. Buscar Dados da Clínica Associada ---
$clinica = null;
$sql_clinica = "SELECT c.idClinica, c.nome
                FROM Clinica c
                JOIN ClinicaUsuario cu ON c.idClinica = cu.fkidClinica
                WHERE cu.fkidUsuario = ?";
$stmt_cli = $conn->prepare($sql_clinica);
if (!$stmt_cli) {
    echo "<p class='alert alert-danger'>Erro DB (buscar clínica): " . $conn->error . "</p>";
    exit;
}
$stmt_cli->bind_param("i", $idAdminUsuario);
$stmt_cli->execute();
$result_cli = $stmt_cli->get_result();

if ($result_cli->num_rows === 0) {
    // Se, por algum motivo, o admin logado não tem clínica (ex: cadastro interrompido),
    // força o cadastro da clínica.
    $_SESSION['info_message'] = 'Por favor, complete o cadastro da sua clínica.';
    header('Location: index.php?page=clinica-create');
    exit;
}
$clinica = $result_cli->fetch_object();
$stmt_cli->close();
?>

<div class="container mt-4">
    
    <h2 class="mb-3">Painel de Controle - <span class="text-primary"><?= htmlspecialchars($clinica->nome) ?></span></h2>
    <p class="lead text-muted">Bem-vindo(a), <?= htmlspecialchars($_SESSION['nome']) ?>. Gerencie sua clínica aqui.</p>

    <?php
        // Exibe mensagens de sucesso/erro (ex: vindo de uma edição)
        if (isset($_SESSION['success_message'])) { echo '<div class="alert alert-success">' . htmlspecialchars($_SESSION['success_message']) . '</div>'; unset($_SESSION['success_message']); }
        if (isset($_SESSION['error_message'])) { echo '<div class="alert alert-danger">' . htmlspecialchars($_SESSION['error_message']) . '</div>'; unset($_SESSION['error_message']); }
        if (isset($_SESSION['info_message'])) { echo '<div class="alert alert-info">' . htmlspecialchars($_SESSION['info_message']) . '</div>'; unset($_SESSION['info_message']); }
    ?>

    <div class="row g-3 mt-3">
        <div class="col-md-6 col-lg-4">
            <div class="card h-100 shadow-sm">
                <div class="card-body text-center">
                    <i class="bi bi-pencil-square display-4 text-primary mb-3"></i>
                    <h5 class="card-title">Editar Dados da Clínica</h5>
                    <p class="card-text small">Altere o nome, localidade ou CNPJ da sua clínica.</p>
                    <a href="?page=clinica-edit" class="btn btn-primary">Editar Informações</a>
                </div>
            </div>
        </div>

        <div class="col-md-6 col-lg-4">
            <div class="card h-100 shadow-sm">
                <div class="card-body text-center">
                    <i class="bi bi-people-fill display-4 text-success mb-3"></i>
                    <h5 class="card-title">Gerenciar Médicos</h5>
                    <p class="card-text small">Adicione ou remova médicos/especialidades associados à sua clínica.</p>
                    <a href="?page=cadastrar-medicos-clinica&idClinica=<?= $clinica->idClinica ?>" class="btn btn-success">Gerenciar Médicos</a>
                </div>
            </div>
        </div>

        <div class="col-md-6 col-lg-4">
            <div class="card h-100 shadow-sm">
                <div class="card-body text-center">
                    <i class="bi bi-calendar-check-fill display-4 text-warning mb-3"></i>
                    <h5 class="card-title">Horários e Valores</h5>
                    <p class="card-text small">Defina preços e durações para os horários disponibilizados pelos médicos.</p>
                    <a href="?page=gerenciar-agenda-valores" class="btn btn-warning">Definir Preços</a>
                </div>
            </div>
        </div>

        <div class="col-md-6 col-lg-4">
            <div class="card h-100 bg-light border-dashed">
                <div class="card-body text-center text-muted">
                    <i class="bi bi-card-list display-4 text-muted mb-3"></i>
                    <h5 class="card-title">Visualizar Consultas</h5>
                    <p class="card-text small">Acompanhe, edite ou cancele os agendamentos da sua clínica.</p>
                    <a href="#" class="btn btn-secondary disabled" aria-disabled="true">(Em breve)</a>
                </div>
            </div>
        </div>
    </div>
</div>