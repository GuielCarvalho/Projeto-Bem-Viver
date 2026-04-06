<?php
// C:\xampp\htdocs\PBV\clinica-edit.php - FASE 2 - NOVO ARQUIVO

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
$sql_clinica = "SELECT c.idClinica, c.nome, c.localidade, c.cnpj
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
    // Admin não tem clínica, não pode editar. Manda criar.
    $_SESSION['info_message'] = 'Você precisa cadastrar uma clínica antes de poder editá-la.';
    header('Location: index.php?page=clinica-create');
    exit;
}
$clinica = $result_cli->fetch_object();
$stmt_cli->close();
?>

<div class="card mt-4 shadow-lg">
    <div class="card-header bg-primary text-white">
        <h4 class="mb-0"><i class="bi bi-pencil-square me-2"></i> Editar Dados da Clínica</h4>
    </div>
    <div class="card-body">
        <?php
            // Exibe mensagens de feedback da sessão (ex: após salvar)
            if (isset($_SESSION['success_message'])) { echo '<div class="alert alert-success">' . htmlspecialchars($_SESSION['success_message']) . '</div>'; unset($_SESSION['success_message']); }
            if (isset($_SESSION['error_message_form'])) { echo '<div class="alert alert-danger">' . htmlspecialchars($_SESSION['error_message_form']) . '</div>'; unset($_SESSION['error_message_form']); }
        ?>
    
        <form action="acoes.php" method="POST">
            <input type="hidden" name="acao" value="edit_clinica">
            <input type="hidden" name="idClinica" value="<?= $clinica->idClinica ?>">

            <div class="mb-3">
                <label for="nome" class="form-label">Nome da Clínica</label>
                <input type="text" id="nome" name="nome" class="form-control" value="<?= htmlspecialchars($clinica->nome) ?>" required>
            </div>

            <div class="mb-3">
                <label for="localidade" class="form-label">Localidade (Endereço)</label>
                <input type="text" id="localidade" name="localidade" class="form-control" value="<?= htmlspecialchars($clinica->localidade) ?>" required>
            </div>

            <div class="mb-3">
                <label for="cnpj" class="form-label">CNPJ (Opcional)</label>
                <input type="text" id="cnpj" name="cnpj" class="form-control" value="<?= htmlspecialchars($clinica->cnpj ?? '') ?>" placeholder="00.000.000/0000-00">
            </div>

            <div class="mt-4">
                <button type="submit" class="btn btn-success">
                    <i class="bi bi-save-fill me-1"></i> Salvar Alterações
                </button>
                <a href="?page=painel-clinica" class="btn btn-secondary">Cancelar</a>
            </div>
        </form>
    </div>
</div>