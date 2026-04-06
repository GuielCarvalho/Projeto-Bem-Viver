<?php
// C:\xampp\htdocs\PBV\clinica-listar.php - FASE 2 - NOVO ARQUIVO (Admin)

if (session_status() == PHP_SESSION_NONE) { session_start(); }
if (!isset($conn)) { require_once('conexao.php'); }

// --- 1. Verificação de Acesso ---
if (!isset($_SESSION['loggedin']) || $_SESSION['tipo_usuario'] !== 'admin') {
    $_SESSION['error_message'] = 'Acesso negado.'; header('Location: index.php?page=login'); exit;
}

// --- 2. Buscar Todas as Clínicas com seus Admins ---
$clinicas = [];
$sql = "SELECT c.idClinica, c.nome, c.localidade, c.cnpj, 
               u.idUsuario AS idAdmin, u.Nome AS nomeAdmin, u.email AS emailAdmin
        FROM Clinica c
        LEFT JOIN ClinicaUsuario cu ON c.idClinica = cu.fkidClinica
        LEFT JOIN Usuario u ON cu.fkidUsuario = u.idUsuario
        ORDER BY c.nome";
$stmt = $conn->prepare($sql);
if ($stmt) {
    $stmt->execute();
    $result = $stmt->get_result();
    while ($row = $result->fetch_object()) {
        $clinicas[] = $row;
    }
    $stmt->close();
} else {
    echo "<p class='alert alert-danger'>Erro ao buscar clínicas: " . $conn->error . "</p>";
}
?>

<div class="container mt-4">
    <div class="card shadow-sm">
        <div class="card-header bg-secondary text-white">
            <h4 class="mb-0"><i class="bi bi-hospital-fill me-2"></i> Gerenciamento de Clínicas (Admin)</h4>
        </div>
        <div class="card-body">
            <?php
                if (isset($_SESSION['success_message'])) { echo '<div class="alert alert-success">' . htmlspecialchars($_SESSION['success_message']) . '</div>'; unset($_SESSION['success_message']); }
                if (isset($_SESSION['error_message'])) { echo '<div class="alert alert-danger">' . htmlspecialchars($_SESSION['error_message']) . '</div>'; unset($_SESSION['error_message']); }
            ?>
        
            <div class="table-responsive">
                <table class="table table-striped table-hover">
                    <thead class="table-light">
                        <tr>
                            <th>ID</th>
                            <th>Nome da Clínica</th>
                            <th>Localidade</th>
                            <th>CNPJ</th>
                            <th>Administrador (Usuário)</th>
                            <th class="text-end">Ações</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($clinicas)): ?>
                            <tr><td colspan="6" class="text-center text-muted">Nenhuma clínica cadastrada no sistema.</td></tr>
                        <?php else: ?>
                            <?php foreach ($clinicas as $c): ?>
                            <tr>
                                <td><?= $c->idClinica ?></td>
                                <td><?= htmlspecialchars($c->nome) ?></td>
                                <td><?= htmlspecialchars($c->localidade) ?></td>
                                <td><?= htmlspecialchars($c->cnpj ?? 'N/A') ?></td>
                                <td>
                                    <?php if ($c->idAdmin): ?>
                                        <?= htmlspecialchars($c->nomeAdmin) ?> (<?= htmlspecialchars($c->emailAdmin) ?>)
                                    <?php else: ?>
                                        <span class="text-muted">Nenhum admin associado</span>
                                    <?php endif; ?>
                                </td>
                                <td class="text-end">
                                    <a href="?page=clinica-edit-admin&id=<?= $c->idClinica ?>" class="btn btn-warning btn-sm" title="Editar Clínica">
                                        <i class="bi bi-pencil-fill"></i>
                                    </a>
                                    <form action="acoes.php" method="POST" class="d-inline" onsubmit="return confirm('ATENÇÃO: Excluir a clínica \'<?= htmlspecialchars(addslashes($c->nome)) ?>\' removerá TODAS as suas associações, horários e consultas. Continuar?')">
                                        <input type="hidden" name="acao" value="delete_clinica_admin">
                                        <input type="hidden" name="idClinica" value="<?= $c->idClinica ?>">
                                        <button type="submit" class="btn btn-danger btn-sm" title="Excluir Clínica">
                                            <i class="bi bi-trash3-fill"></i>
                                        </button>
                                    </form>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>