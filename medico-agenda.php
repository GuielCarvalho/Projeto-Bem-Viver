<?php
// C:\xampp\htdocs\PBV\medico-agenda.php - FASE 2 - NOVO ARQUIVO

// Garante sessão e conexão
if (session_status() == PHP_SESSION_NONE) { session_start(); }
if (!isset($conn)) { require_once('conexao.php'); }

// --- 1. Verificação de Acesso ---
if (!isset($_SESSION['loggedin']) || $_SESSION['tipo_usuario'] !== 'medico') {
    $_SESSION['error_message'] = 'Acesso negado. Apenas médicos podem ver a agenda.';
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
    if ($res_med->num_rows > 0) { $idMedico = (int)$res_med->fetch_object()->idMedico; }
    $stmt_find_med->close();
}
if ($idMedico <= 0) {
    echo "<p class='alert alert-danger'>Erro: Registro de médico não encontrado.</p>";
    exit;
}
// --- NOVO: Usando a FUNCTION TotalConsultasMedico ---
$total_atendimentos = 0;
// Chama a função que você criou no banco de dados
$res_func = $conn->query("SELECT TotalConsultasMedico($idMedico) as total");
if($res_func) {
    $total_atendimentos = $res_func->fetch_object()->total ?? 0;
}
$sql_consultas = "SELECT
                    c.idConsulta, c.data_hora_agendada, c.status,
                    up.Nome AS nomePaciente, up.telefone AS telefonePaciente,
                    cl.nome AS nomeClinica,
                    e.nome AS nomeEspecialidade,
                    va.valor, va.duracao_minutos
                  FROM Consulta c
                  JOIN ValorAtendimento va ON c.fkidValorAtendimento = va.idValorAtendimento
                  JOIN Dia_Hora_Disponivel dhd ON va.fkidDia_Hora_Disponivel = dhd.idDia_Hora_Disponivel
                  JOIN Disponibilidade d ON dhd.fkidDisponibilidade = d.idDisponibilidade
                  JOIN Clinica cl ON d.fkidClinica = cl.idClinica
                  JOIN Medico_Especialidade me ON d.fkidMededico_Especialidade = me.idMedico_Especialidade
                  JOIN Paciente p ON c.fkidPaciente = p.idPaciente
                  JOIN Usuario up ON p.fkidUsuario = up.idUsuario
                  JOIN Especialidade e ON me.fkidEspecialidade = e.idEspecialidade
                  WHERE me.fkidMedico = ?
                  ORDER BY 
                    CASE c.status
                        WHEN 'CONFIRMADO' THEN 1
                        WHEN 'EM_ANDAMENTO' THEN 2
                        WHEN 'SOLICITADO' THEN 3
                        ELSE 4
                    END,
                    c.data_hora_agendada DESC"; // Ordena por status e data

$stmt_consultas = $conn->prepare($sql_consultas);
$consultas_futuras = [];
$consultas_passadas = [];
$hoje = date("Y-m-d H:i:s");

if ($stmt_consultas) {
    $stmt_consultas->bind_param("i", $idMedico);
    $stmt_consultas->execute();
    $result_consultas = $stmt_consultas->get_result();
    while ($row = $result_consultas->fetch_object()) {
        if ($row->data_hora_agendada >= $hoje && !in_array($row->status, ['REALIZADO', 'CANCELADO_MEDICO', 'CANCELADO_PACIENTE', 'NAO_COMPARECEU'])) {
            $consultas_futuras[] = $row;
        } else {
            $consultas_passadas[] = $row;
        }
    }
    $stmt_consultas->close();
} else {
    echo "<p class='alert alert-danger'>Erro ao buscar sua agenda: " . $conn->error . "</p>";
}

// Helper para formatar o Status
function getStatusBadge($status) {
    switch ($status) {
        case 'CONFIRMADO': return "<span class='badge bg-primary'>Confirmado</span>";
        case 'EM_ANDAMENTO': return "<span class='badge bg-warning text-dark'>Em Andamento</span>";
        case 'SOLICITADO': return "<span class='badge bg-info text-dark'>Solicitado</span>";
        case 'REALIZADO': return "<span class='badge bg-success'>Realizado</span>";
        case 'CANCELADO_MEDICO':
        case 'CANCELADO_PACIENTE': return "<span class='badge bg-danger'>Cancelado</span>";
        case 'NAO_COMPARECEU': return "<span class='badge bg-secondary'>Não Compareceu</span>";
        default: return "<span class='badge bg-light text-dark'>$status</span>";
    }
}
?>

<div class="container mt-4">
    <div class="card shadow-sm mb-4">
        <div class="card-header bg-primary text-white">
            <h4 class="mb-0"><i class="bi bi-calendar-check me-2"></i> Minha Agenda de Consultas</h4>
        </div>
        <div class="card-body">
            
            <?php
                if (isset($_SESSION['success_message'])) { echo '<div class="alert alert-success">' . htmlspecialchars($_SESSION['success_message']) . '</div>'; unset($_SESSION['success_message']); }
                if (isset($_SESSION['error_message'])) { echo '<div class="alert alert-danger">' . htmlspecialchars($_SESSION['error_message']) . '</div>'; unset($_SESSION['error_message']); }
            ?>
<div class="alert alert-info shadow-sm d-flex align-items-center mb-4" role="alert">
                <i class="bi bi-trophy-fill fs-4 me-3"></i>
                <div>
                    <strong>Total de Atendimentos Realizados:</strong> 
                    <span class="fs-5 badge bg-white text-info border ms-2"><?= $total_atendimentos ?></span>
                    <small class="ms-1 text-muted"> (Cálculo feito via Function SQL)</small>
                </div>
            </div>
            <h5 class="text-primary">Próximas Consultas</h5>
            <div class="table-responsive mb-4">
                <table class="table table-hover table-striped">
                    <thead class="table-light">
                        <tr>
                            <th>Data/Hora</th>
                            <th>Paciente</th>
                            <th>Clínica</th>
                            <th>Especialidade</th>
                            <th>Status</th>
                            <th>Ação</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($consultas_futuras)): ?>
                            <tr><td colspan="6" class="text-center text-muted">Nenhuma próxima consulta encontrada.</td></tr>
                        <?php else: ?>
                            <?php foreach ($consultas_futuras as $c): ?>
                            <tr>
                                <td><strong><?= date('d/m/Y \à\s H:i', strtotime($c->data_hora_agendada)) ?></strong></td>
                                <td><?= htmlspecialchars($c->nomePaciente) ?></td>
                                <td><?= htmlspecialchars($c->nomeClinica) ?></td>
                                <td><span class="badge bg-secondary"><?= htmlspecialchars($c->nomeEspecialidade) ?></span></td>
                                <td><?= getStatusBadge($c->status) ?></td>
                                <td>
                                    <a href="?page=consulta-atendimento&idConsulta=<?= $c->idConsulta ?>" class="btn btn-primary btn-sm">
                                        Gerenciar
                                    </a>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>

            <h5 class="text-secondary">Histórico de Consultas</h5>
            <div class="table-responsive">
                <table class="table table-hover table-striped table-sm">
                    <thead class="table-light">
                         <tr>
                            <th>Data/Hora</th>
                            <th>Paciente</th>
                            <th>Clínica</th>
                            <th>Status</th>
                            <th>Ação</th>
                        </tr>
                    </thead>
                    <tbody>
                         <?php if (empty($consultas_passadas)): ?>
                            <tr><td colspan="5" class="text-center text-muted">Nenhum histórico encontrado.</td></tr>
                        <?php else: ?>
                            <?php foreach ($consultas_passadas as $c): ?>
                            <tr class="<?= in_array($c->status, ['CANCELADO_MEDICO', 'CANCELADO_PACIENTE', 'NAO_COMPARECEU']) ? 'text-muted' : '' ?>">
                                <td><?= date('d/m/Y H:i', strtotime($c->data_hora_agendada)) ?></td>
                                <td><?= htmlspecialchars($c->nomePaciente) ?></td>
                                <td><?= htmlspecialchars($c->nomeClinica) ?></td>
                                <td><?= getStatusBadge($c->status) ?></td>
                                <td>
                                    <a href="?page=consulta-atendimento&idConsulta=<?= $c->idConsulta ?>" class="btn btn-outline-secondary btn-sm">
                                        Ver
                                    </a>
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