<?php
// C:\xampp\htdocs\PBV\medico-gerenciar-horarios.php - FASE 2 - NOVO (Usa dia_semana)

// Garante sessão e conexão
if (session_status() == PHP_SESSION_NONE) { session_start(); }
if (!isset($conn)) { require_once('conexao.php'); }

// --- 1. Verificação de Acesso ---
if (!isset($_SESSION['loggedin']) || $_SESSION['tipo_usuario'] !== 'medico') {
    $_SESSION['error_message'] = 'Acesso negado. Apenas médicos podem gerenciar horários.';
    header('Location: index.php?page=login');
    exit;
}
$idUsuarioMedico = $_SESSION['idUsuario'];

// --- 2. Buscar ID do Médico (idMedico) a partir do Usuário (idUsuario) ---
$idMedico = 0;
$stmt_find_med = $conn->prepare("SELECT idMedico FROM medico WHERE fkidUsuario = ?");
if ($stmt_find_med) {
    $stmt_find_med->bind_param("i", $idUsuarioMedico);
    $stmt_find_med->execute();
    $res_med = $stmt_find_med->get_result();
    if ($res_med->num_rows > 0) {
        $idMedico = (int)$res_med->fetch_object()->idMedico;
    }
    $stmt_find_med->close();
}
if ($idMedico <= 0) {
    echo "<p class='alert alert-danger'>Erro: Registro de médico não encontrado para este usuário.</p>";
    exit;
}

// --- 3. Buscar Associações (Clínicas e Disponibilidades) do Médico ---
$associacoes = []; // Armazena [idClinica => [nome, disponibilidades[]]]
$sql_assoc = "SELECT
                d.idDisponibilidade, c.nome AS nomeClinica, e.nome AS nomeEspecialidade, c.idClinica
              FROM Disponibilidade d
              JOIN Clinica c ON d.fkidClinica = c.idClinica
              JOIN Medico_Especialidade me ON d.fkidMededico_Especialidade = me.idMedico_Especialidade
              JOIN Especialidade e ON me.fkidEspecialidade = e.idEspecialidade
              WHERE me.fkidMedico = ?
              ORDER BY c.nome, e.nome";
$stmt_assoc = $conn->prepare($sql_assoc);
if ($stmt_assoc) {
    $stmt_assoc->bind_param("i", $idMedico);
    $stmt_assoc->execute();
    $result_assoc = $stmt_assoc->get_result();
    while ($row = $result_assoc->fetch_object()) {
        $associacoes[$row->idClinica]['nome'] = $row->nomeClinica;
        $associacoes[$row->idClinica]['disponibilidades'][] = $row;
    }
    $stmt_assoc->close();
} else {
    echo "<p class='alert alert-danger'>Erro ao buscar associações de clínicas: " . $conn->error . "</p>";
}

// --- 4. Buscar Horários Existentes do Médico (para todas as clínicas) ---
$horariosExistentes = []; // Armazena [idDisponibilidade => [horarios[]]]
$sql_horarios = "SELECT
                    dhd.idDia_Hora_Disponivel, dhd.dia_semana, dhd.hora_inicio, dhd.hora_fim,
                    d.idDisponibilidade, c.idClinica
                 FROM Dia_Hora_Disponivel dhd -- Usa a coluna 'dia_semana'
                 JOIN Disponibilidade d ON dhd.fkidDisponibilidade = d.idDisponibilidade
                 JOIN Clinica c ON d.fkidClinica = c.idClinica
                 JOIN Medico_Especialidade me ON d.fkidMededico_Especialidade = me.idMedico_Especialidade
                 WHERE me.fkidMedico = ?
                 ORDER BY c.idClinica, d.idDisponibilidade, dhd.dia_semana, dhd.hora_inicio";
$stmt_horarios = $conn->prepare($sql_horarios);
if ($stmt_horarios) {
    $stmt_horarios->bind_param("i", $idMedico);
    $stmt_horarios->execute();
    $result_horarios = $stmt_horarios->get_result();
    while ($row = $result_horarios->fetch_object()) {
        $horariosExistentes[$row->idDisponibilidade][] = $row;
    }
    $stmt_horarios->close();
} else {
     echo "<p class='alert alert-danger'>Erro ao buscar horários existentes: " . $conn->error . "</p>";
}

// Helper (Função Auxiliar) para converter número do dia em nome
function getDiaSemanaNome($diaNum) {
    $dias = [1 => 'Domingo', 2 => 'Segunda-feira', 3 => 'Terça-feira', 4 => 'Quarta-feira', 5 => 'Quinta-feira', 6 => 'Sexta-feira', 7 => 'Sábado'];
    return $dias[$diaNum] ?? 'Inválido';
}
?>

<div class="container mt-4">
    <div class="card shadow-sm mb-4">
        <div class="card-header bg-info text-dark">
            <h4 class="mb-0"><i class="bi bi-clock-fill me-2"></i> Gerenciar Meus Horários por Clínica</h4>
        </div>
        <div class="card-body">
            <?php
                if (isset($_SESSION['success_message'])) { echo '<div class="alert alert-success">' . htmlspecialchars($_SESSION['success_message']) . '</div>'; unset($_SESSION['success_message']); }
                if (isset($_SESSION['error_message'])) { echo '<div class="alert alert-danger">' . htmlspecialchars($_SESSION['error_message']) . '</div>'; unset($_SESSION['error_message']); }
            ?>

            <p class="text-muted">Defina aqui os dias da semana e horários recorrentes que você atenderá em cada clínica. O sistema impedirá o cadastro de horários que se sobreponham, mesmo entre clínicas diferentes.</p>

            <?php if (empty($associacoes)): ?>
                <div class="alert alert-warning">Você ainda não está associado a nenhuma clínica. Peça ao administrador de uma clínica para adicioná-lo.</div>
            <?php else: ?>
                <?php foreach ($associacoes as $idClinica => $clinicaData): ?>
                    <div class="card mb-3">
                        <div class="card-header">
                            <h5><i class="bi bi-hospital"></i> <?= htmlspecialchars($clinicaData['nome']) ?></h5>
                        </div>
                        <div class="card-body">
                            <?php foreach ($clinicaData['disponibilidades'] as $disp): ?>
                                <div class="border rounded p-3 mb-3 bg-light">
                                     <h6>Especialidade: <span class="badge bg-secondary"><?= htmlspecialchars($disp->nomeEspecialidade) ?></span></h6>

                                     <form action="acoes.php" method="POST" class="row g-3 align-items-end mb-3 border-bottom pb-3">
                                         <input type="hidden" name="acao" value="add_medico_horario">
                                         <input type="hidden" name="fkidDisponibilidade" value="<?= $disp->idDisponibilidade ?>">
                                         <input type="hidden" name="idClinica" value="<?= $idClinica ?>">
                                         <input type="hidden" name="return_page" value="medico-gerenciar-horarios">

                                         <div class="col-md-3">
                                             <label for="dia_semana_<?= $disp->idDisponibilidade ?>" class="form-label small">Dia da Semana</label>
                                             <select id="dia_semana_<?= $disp->idDisponibilidade ?>" name="dia_semana" class="form-select form-select-sm" required>
                                                 <option value="">Selecione</option>
                                                 <option value="2">Segunda-feira</option>
                                                 <option value="3">Terça-feira</option>
                                                 <option value="4">Quarta-feira</option>
                                                 <option value="5">Quinta-feira</option>
                                                 <option value="6">Sexta-feira</option>
                                                 <option value="7">Sábado</option>
                                                 <option value="1">Domingo</option>
                                             </select>
                                         </div>
                                         <div class="col-md-3">
                                             <label for="hora_inicio_<?= $disp->idDisponibilidade ?>" class="form-label small">Horário de Início</label>
                                             <input type="time" id="hora_inicio_<?= $disp->idDisponibilidade ?>" name="hora_inicio" class="form-control form-control-sm" required step="1800"> </div>
                                         <div class="col-md-3">
                                             <label for="hora_fim_<?= $disp->idDisponibilidade ?>" class="form-label small">Horário de Fim</label>
                                             <input type="time" id="hora_fim_<?= $disp->idDisponibilidade ?>" name="hora_fim" class="form-control form-control-sm" required step="1800">
                                         </div>
                                         <div class="col-md-3">
                                             <button type="submit" class="btn btn-primary btn-sm w-100">
                                                 <i class="bi bi-plus-circle"></i> Adicionar Horário
                                             </button>
                                         </div>
                                     </form>

                                     <h6>Horários Cadastrados:</h6>
                                     <?php if (isset($horariosExistentes[$disp->idDisponibilidade]) && !empty($horariosExistentes[$disp->idDisponibilidade])): ?>
                                         <ul class="list-group list-group-flush">
                                             <?php foreach ($horariosExistentes[$disp->idDisponibilidade] as $horario): ?>
                                                 <li class="list-group-item d-flex justify-content-between align-items-center ps-0">
                                                     <span>
                                                         <i class="bi bi-calendar-week me-1"></i> <?= getDiaSemanaNome($horario->dia_semana) ?>:
                                                         <i class="bi bi-clock ms-2 me-1"></i> <?= date("H:i", strtotime($horario->hora_inicio)) ?> - <?= date("H:i", strtotime($horario->hora_fim)) ?>
                                                     </span>
                                                     <form action="acoes.php" method="POST" class="d-inline" onsubmit="return confirm('Excluir este horário?')">
                                                         <input type="hidden" name="acao" value="delete_medico_horario">
                                                         <input type="hidden" name="idDia_Hora_Disponivel" value="<?= $horario->idDia_Hora_Disponivel ?>">
                                                         <input type="hidden" name="return_page" value="medico-gerenciar-horarios">
                                                         <button type="submit" class="btn btn-danger btn-sm" title="Excluir Horário">
                                                             <i class="bi bi-trash3-fill"></i>
                                                         </button>
                                                     </form>
                                                 </li>
                                             <?php endforeach; ?>
                                         </ul>
                                     <?php else: ?>
                                         <p class="text-muted small">Nenhum horário cadastrado para esta especialidade nesta clínica.</p>
                                     <?php endif; ?>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>

             <div class="mt-4 text-center border-top pt-3">
                <a href="?page=home" class="btn btn-secondary">
                    <i class="bi bi-house-door me-1"></i> Voltar para Home
                </a>
            </div>
        </div>
    </div>
</div>