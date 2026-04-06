<?php
// C:\xampp\htdocs\PBV\especialidades-admin.php - FASE 2 - NOVO ARQUIVO (Admin)

if (session_status() == PHP_SESSION_NONE) { session_start(); }
if (!isset($conn)) { require_once('conexao.php'); }

// --- 1. Verificação de Acesso ---
if (!isset($_SESSION['loggedin']) || $_SESSION['tipo_usuario'] !== 'admin') {
    $_SESSION['error_message'] = 'Acesso negado.'; header('Location: index.php?page=login'); exit;
}

// --- 2. Buscar Especialidades Existentes ---
$especialidades = [];
$sql = "SELECT e.idEspecialidade, e.nome, COUNT(me.idMedico_Especialidade) AS totalMedicos
        FROM Especialidade e
        LEFT JOIN Medico_Especialidade me ON e.idEspecialidade = me.fkidEspecialidade
        GROUP BY e.idEspecialidade, e.nome
        ORDER BY e.nome";
$stmt = $conn->prepare($sql);
if ($stmt) {
    $stmt->execute();
    $result = $stmt->get_result();
    while ($row = $result->fetch_object()) {
        $especialidades[] = $row;
    }
    $stmt->close();
} else {
    echo "<p class='alert alert-danger'>Erro ao buscar especialidades: " . $conn->error . "</p>";
}
?>

<div class="container mt-4">
    <div class="row">
        <div class="col-md-8">
            <div class="card shadow-sm">
                <div class="card-header bg-secondary text-white">
                    <h4 class="mb-0"><i class="bi bi-bookmark-star-fill me-2"></i> Gerenciar Especialidades</h4>
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
                                    <th>Nome da Especialidade</th>
                                    <th>Nº de Médicos</th>
                                    <th class="text-end">Ação</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($especialidades)): ?>
                                    <tr><td colspan="4" class="text-center text-muted">Nenhuma especialidade cadastrada.</td></tr>
                                <?php else: ?>
                                    <?php foreach ($especialidades as $e): ?>
                                    <tr>
                                        <td><?= $e->idEspecialidade ?></td>
                                        <td><?= htmlspecialchars($e->nome) ?></td>
                                        <td><?= $e->totalMedicos ?></td>
                                        <td class="text-end">
                                            <form action="acoes.php" method="POST" class="d-inline" onsubmit="return confirm('Excluir \'<?= htmlspecialchars(addslashes($e->nome)) ?>\'? <?= ($e->totalMedicos > 0 ? 'Existem médicos associados!' : '') ?>')">
                                                <input type="hidden" name="acao" value="delete_especialidade">
                                                <input type="hidden" name="idEspecialidade" value="<?= $e->idEspecialidade ?>">
                                                <button type="submit" class="btn btn-danger btn-sm" title="Excluir">
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
        
        <div class="col-md-4">
            <div class="card shadow-sm">
                <div class="card-header">
                    <h5 class="mb-0">Adicionar Nova</h5>
                </div>
                <div class="card-body">
                    <form action="acoes.php" method="POST">
                        <input type="hidden" name="acao" value="add_especialidade">
                        <div class="mb-3">
                            <label for="nomeEspecialidade" class="form-label">Nome da Especialidade</label>
                            <input type="text" class="form-control" id="nomeEspecialidade" name="nome" placeholder="Ex: Ortopedia" required>
                        </div>
                        <button type="submit" class="btn btn-primary w-100">
                            <i class="bi bi-plus-circle me-1"></i> Adicionar
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>