<?php
// C:\xampp\htdocs\PBV\consultas-geral.php - FASE 2 - NOVO ARQUIVO (Admin)

if (session_status() == PHP_SESSION_NONE) { session_start(); }
if (!isset($conn)) { require_once('conexao.php'); }

// --- 1. Verificação de Acesso ---
if (!isset($_SESSION['loggedin']) || $_SESSION['tipo_usuario'] !== 'admin') {
    $_SESSION['error_message'] = 'Acesso negado.'; header('Location: index.php?page=login'); exit;
}

// --- 2. Buscar TODAS as Consultas ---
$sql_consultas = "SELECT
                    c.idConsulta, c.data_hora_agendada, c.status,
                    up.Nome AS nomePaciente, um.Nome AS nomeMedico,
                    cl.nome AS nomeClinica
                  FROM Consulta c
                  LEFT JOIN Paciente p ON c.fkidPaciente = p.idPaciente
                  LEFT JOIN Usuario up ON p.fkidUsuario = up.idUsuario
                  LEFT JOIN ValorAtendimento va ON c.fkidValorAtendimento = va.idValorAtendimento
                  LEFT JOIN Dia_Hora_Disponivel dhd ON va.fkidDia_Hora_Disponivel = dhd.idDia_Hora_Disponivel
                  LEFT JOIN Disponibilidade d ON dhd.fkidDisponibilidade = d.idDisponibilidade
                  LEFT JOIN Clinica cl ON d.fkidClinica = cl.idClinica
                  LEFT JOIN Medico_Especialidade me ON d.fkidMededico_Especialidade = me.idMedico_Especialidade
                  LEFT JOIN Medico m ON me.fkidMedico = m.idMedico
                  LEFT JOIN Usuario um ON m.fkidUsuario = um.idUsuario
                  ORDER BY c.data_hora_agendada DESC
                  LIMIT 100"; // Limita a 100 para não sobrecarregar

$stmt_consultas = $conn->prepare($sql_consultas);
$consultas = [];
if ($stmt_consultas) {
    $stmt_consultas->execute();
    $result_consultas = $stmt_consultas->get_result();
    while ($row = $result_consultas->fetch_object()) {
        $consultas[] = $row;
    }
    $stmt_consultas->close();
} else {
    echo "<p class='alert alert-danger'>Erro ao buscar consultas: " . $conn->error . "</p>";
}

// --- 3. Helper para formatar o Status ---
if (!function_exists('getStatusBadge')) {
    function getStatusBadge($status) {
        switch ($status) {
            case 'CONFIRMADO': return "<span class='badge bg-primary'>Confirmado</span>";
            case 'EM_ANDAMENTO': return "<span class='badge bg-warning text-dark'>Em Andamento</span>";
            case 'SOLICITADO': return "<span class='badge bg-info text-dark'>Solicitado</span>";
            case 'REALIZADO': return "<span class='badge bg-success'>Realizado</span>";
            case 'CANCELADO_MEDICO': case 'CANCELADO_PACIENTE': return "<span class='badge bg-danger'>Cancelado</span>";
            case 'NAO_COMPARECEU': return "<span class='badge bg-secondary'>Não Compareceu</span>";
            default: return "<span class='badge bg-light text-dark'>" . htmlspecialchars($status) . "</span>";
        }
    }
}
?>

<div class="container mt-4">
    <div class="card shadow-sm">
        <div class="card-header bg-secondary text-white">
            <h4 class="mb-0"><i class="bi bi-calendar-check-fill me-2"></i> Histórico Geral de Consultas (Admin)</h4>
        </div>
        <div class="card-body">
            <p class="text-muted">Mostrando as 100 consultas mais recentes do sistema.</p>
            <div class="table-responsive">
                <table class="table table-striped table-hover table-sm">
                    <thead class="table-light">
                        <tr>
                            <th>ID</th>
                            <th>Data/Hora</th>
                            <th>Paciente</th>
                            <th>Médico</th>
                            <th>Clínica</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($consultas)): ?>
                            <tr><td colspan="6" class="text-center text-muted">Nenhuma consulta encontrada no sistema.</td></tr>
                        <?php else: ?>
                            <?php foreach ($consultas as $c): ?>
                            <tr>
                                <td><?= $c->idConsulta ?></td>
                                <td><?= date('d/m/Y H:i', strtotime($c->data_hora_agendada)) ?></td>
                                <td><?= htmlspecialchars($c->nomePaciente ?? 'N/A') ?></td>
                                <td><?= htmlspecialchars($c->nomeMedico ?? 'N/A') ?></td>
                                <td><?= htmlspecialchars($c->nomeClinica ?? 'N/A') ?></td>
                                <td><?= getStatusBadge($c->status) ?></td>
                            </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>