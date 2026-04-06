<?php
// C:\xampp\htdocs\PBV\cadastrar-medicos-clinica.php - CORRIGIDO (Lê ID da Sessão)

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

// --- 2. Obter ID da Clínica DA SESSÃO --- // <<< ESSA É A CORREÇÃO
// Em vez de $_GET['idClinica'], lemos da sessão
$idClinica = isset($_SESSION['idClinica']) ? (int)$_SESSION['idClinica'] : 0;

// Se o ID da clínica não estiver na sessão (ex: login antigo, erro),
// redireciona para criar a clínica (ou para o painel se a sessão estiver incompleta)
if ($idClinica <= 0) {
    $_SESSION['error_message'] = 'Sessão inválida ou clínica não cadastrada. Por favor, cadastre sua clínica.';
    
    // Tenta descobrir se o usuário tem clínica mas a sessão falhou
    $stmt_find_cli = $conn->prepare("SELECT 1 FROM ClinicaUsuario WHERE fkidUsuario = ?");
    if ($stmt_find_cli) {
        $stmt_find_cli->bind_param("i", $idAdminUsuario);
        $stmt_find_cli->execute();
        $res_cli = $stmt_find_cli->get_result();
        $stmt_find_cli->close();
        
        if ($res_cli->num_rows > 0) {
             // Tem clínica, mas a sessão estava sem o ID. Redireciona para o painel.
             header('Location: index.php?page=painel-clinica');
        } else {
             // Realmente não tem clínica.
             header('Location: index.php?page=clinica-create');
        }
    } else {
         header('Location: index.php?page=home'); // Fallback
    }
    exit;
}

// --- 3. Verificar e Obter Nome da Clínica ---
// (Verifica se o ID da sessão realmente pertence ao admin logado)
$nomeClinica = null;
$sql_check_owner = "SELECT c.nome
                    FROM Clinica c
                    JOIN ClinicaUsuario cu ON c.idClinica = cu.fkidClinica
                    WHERE c.idClinica = ? AND cu.fkidUsuario = ?";
$stmt_check_owner = $conn->prepare($sql_check_owner);
if (!$stmt_check_owner) {
     $_SESSION['error_message'] = 'Erro ao verificar propriedade: ' . $conn->error;
     header('Location: index.php?page=painel-clinica'); exit;
}
$stmt_check_owner->bind_param("ii", $idClinica, $idAdminUsuario);
$stmt_check_owner->execute();
$result_owner = $stmt_check_owner->get_result();
if ($result_owner->num_rows === 0) {
    // A sessão está dessincronizada (ID da clínica não pertence a este admin)
    $_SESSION['error_message'] = 'Erro de permissão (Sessão/Clínica). Faça login novamente.';
    unset($_SESSION['loggedin'], $_SESSION['idUsuario'], $_SESSION['idClinica']); // Limpa sessão
    header('Location: index.php?page=login');
    exit;
}
$nomeClinica = $result_owner->fetch_object()->nome;
$stmt_check_owner->close();
$_SESSION['nomeClinica'] = $nomeClinica; // Salva o nome na sessão para uso futuro

// --- 4. Lógica de Busca de Médicos (se houver termo de busca) ---
$termoBusca = isset($_GET['busca_medico']) ? trim($_GET['busca_medico']) : '';
$medicosEncontrados = [];
if (!empty($termoBusca)) {
    $termoLike = "%" . $termoBusca . "%";
    $sql_busca = "SELECT me.idMedico_Especialidade, u.Nome, m.CRM, e.nome AS Especialidade
                  FROM Medico_Especialidade me
                  JOIN Medico m ON me.fkidMedico = m.idMedico
                  JOIN Usuario u ON m.fkidUsuario = u.idUsuario
                  JOIN Especialidade e ON me.fkidEspecialidade = e.idEspecialidade
                  WHERE (u.Nome LIKE ? OR m.CRM LIKE ?)
                  AND me.idMedico_Especialidade NOT IN (
                      SELECT d.fkidMededico_Especialidade FROM Disponibilidade d WHERE d.fkidClinica = ?
                  )
                  ORDER BY u.Nome, e.nome LIMIT 10";
    $stmt_busca = $conn->prepare($sql_busca);
    if($stmt_busca){
        $stmt_busca->bind_param("ssi", $termoLike, $termoLike, $idClinica);
        $stmt_busca->execute();
        $result_busca = $stmt_busca->get_result();
        while ($row = $result_busca->fetch_object()) { $medicosEncontrados[] = $row; }
        $stmt_busca->close();
    } else { echo "<p class='alert alert-danger'>Erro ao buscar médicos: " . $conn->error . "</p>"; }
}

// --- 5. Buscar Médicos Já Associados à Clínica ---
$medicosAssociados = [];
$sql_associados = "SELECT d.idDisponibilidade, u.Nome, m.CRM, e.nome AS Especialidade
                   FROM Disponibilidade d
                   JOIN Medico_Especialidade me ON d.fkidMededico_Especialidade = me.idMedico_Especialidade
                   JOIN Medico m ON me.fkidMedico = m.idMedico
                   JOIN Usuario u ON m.fkidUsuario = u.idUsuario
                   JOIN Especialidade e ON me.fkidEspecialidade = e.idEspecialidade
                   WHERE d.fkidClinica = ?
                   ORDER BY u.Nome, e.nome";
$stmt_associados = $conn->prepare($sql_associados);
if($stmt_associados){
    $stmt_associados->bind_param("i", $idClinica);
    $stmt_associados->execute();
    $result_associados = $stmt_associados->get_result();
    while ($row = $result_associados->fetch_object()) { $medicosAssociados[] = $row; }
    $stmt_associados->close();
} else { echo "<p class='alert alert-danger'>Erro ao buscar médicos associados: " . $conn->error . "</p>"; }
?>

<div class="container mt-4">
    <div class="card shadow-sm mb-4">
        <div class="card-header bg-success text-white">
            <h4 class="mb-0"><i class="bi bi-person-plus-fill me-2"></i> Associar Médicos à Clínica: <?= htmlspecialchars($nomeClinica) ?></h4>
        </div>
        <div class="card-body">
            <?php
                if (isset($_SESSION['success_message'])) { echo '<div class="alert alert-success">' . htmlspecialchars($_SESSION['success_message']) . '</div>'; unset($_SESSION['success_message']); }
                if (isset($_SESSION['error_message'])) { echo '<div class="alert alert-danger">' . htmlspecialchars($_SESSION['error_message']) . '</div>'; unset($_SESSION['error_message']); }
                if (isset($_SESSION['info_message'])) { echo '<div class="alert alert-info">' . htmlspecialchars($_SESSION['info_message']) . '</div>'; unset($_SESSION['info_message']); }
            ?>
            <p class="text-muted">Busque por médicos já cadastrados (por nome ou CRM) e adicione-os à sua clínica.</p>

            <form method="GET" action="index.php" class="mb-4">
                <input type="hidden" name="page" value="cadastrar-medicos-clinica">
                <div class="input-group">
                    <input type="text" name="busca_medico" class="form-control" placeholder="Digite o Nome ou CRM..." value="<?= htmlspecialchars($termoBusca) ?>" aria-label="Buscar Médico">
                    <button class="btn btn-primary" type="submit"><i class="bi bi-search me-1"></i> Buscar</button>
                </div>
            </form>

            <?php if (!empty($termoBusca)): ?>
                <h5>Resultados da Busca para "<?= htmlspecialchars($termoBusca) ?>":</h5>
                <?php if (!empty($medicosEncontrados)): ?>
                    <ul class="list-group list-group-flush mb-4">
                        <?php foreach ($medicosEncontrados as $medico): ?>
                            <li class="list-group-item d-flex justify-content-between align-items-center flex-wrap">
                                <div class="me-3 mb-2 mb-md-0">
                                    <strong><?= htmlspecialchars($medico->Nome) ?></strong> (<?= htmlspecialchars($medico->CRM) ?>)<br>
                                    <span class="badge bg-secondary"><?= htmlspecialchars($medico->Especialidade) ?></span>
                                </div>
                                <form action="acoes.php" method="POST" class="d-inline">
                                    <input type="hidden" name="acao" value="add_medico_clinica">
                                    <input type="hidden" name="idClinica" value="<?= $idClinica ?>">
                                    <input type="hidden" name="idMedicoEspecialidade" value="<?= $medico->idMedico_Especialidade ?>">
                                    <input type="hidden" name="return_page" value="cadastrar-medicos-clinica&busca_medico=<?= urlencode($termoBusca) ?>">
                                    <button type="submit" class="btn btn-success btn-sm" title="Adicionar médico/especialidade">
                                        <i class="bi bi-plus-lg"></i> Adicionar
                                    </button>
                                </form>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                <?php else: ?>
                    <p class="text-muted">Nenhum médico encontrado ou já associado.</p>
                <?php endif; ?>
                <hr>
            <?php endif; ?>

            <h5>Médicos Atualmente Associados:</h5>
            <?php if (!empty($medicosAssociados)): ?>
                <div class="table-responsive">
                    <table class="table table-striped table-hover table-sm">
                        <thead class="table-light"> <tr> <th>Nome</th> <th>CRM</th> <th>Especialidade</th> <th class="text-end">Ação</th> </tr> </thead>
                        <tbody>
                            <?php foreach ($medicosAssociados as $medicoAss): ?>
                                <tr>
                                    <td><?= htmlspecialchars($medicoAss->Nome) ?></td>
                                    <td><?= htmlspecialchars($medicoAss->CRM) ?></td>
                                    <td><span class="badge bg-info text-dark"><?= htmlspecialchars($medicoAss->Especialidade) ?></span></td>
                                    <td class="text-end">
                                        <form action="acoes.php" method="POST" class="d-inline" onsubmit="return confirm('Remover <?= htmlspecialchars(addslashes($medicoAss->Nome)) ?> (<?= htmlspecialchars(addslashes($medicoAss->Especialidade)) ?>)?')">
                                            <input type="hidden" name="acao" value="remove_medico_clinica">
                                            <input type="hidden" name="idDisponibilidade" value="<?= $medicoAss->idDisponibilidade ?>">
                                            <input type="hidden" name="idClinica" value="<?= $idClinica ?>">
                                            <input type="hidden" name="return_page" value="cadastrar-medicos-clinica">
                                            <button type="submit" class="btn btn-danger btn-sm" title="Remover associação"> <i class="bi bi-trash3-fill"></i> </button>
                                        </form>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php else: ?>
                <p class="text-muted">Nenhum médico associado a esta clínica ainda.</p>
            <?php endif; ?>

             <div class="mt-4 text-center border-top pt-3">
                <a href="?page=painel-clinica" class="btn btn-secondary"> <i class="bi bi-arrow-left-circle me-1"></i> Voltar ao Painel </a>
                <a href="?page=gerenciar-agenda-valores" class="btn btn-primary ms-2"> Definir Horários/Valores <i class="bi bi-arrow-right-circle ms-1"></i> </a>
            </div>
        </div>
    </div>
</div>