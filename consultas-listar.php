<?php
// C:\xampp\htdocs\PBV\consultas-listar.php - NOVO ARQUIVO (Substitui agendamento-listar.php)

if (session_status() == PHP_SESSION_NONE) { session_start(); }
if (!isset($conn)) { require_once('conexao.php'); }

// --- 1. Verificação de Acesso (Paciente) ---
if (!isset($_SESSION['loggedin']) || $_SESSION['tipo_usuario'] !== 'paciente') {
    $_SESSION['error_message'] = 'Acesso negado. Faça login como paciente.';
    header('Location: index.php?page=login');
    exit;
}
$idUsuarioPaciente = $_SESSION['idUsuario'];

// --- 2. Buscar ID do Paciente (idPaciente) a partir do Usuário (idUsuario) ---
$idPaciente = 0;
$stmt_find_pac = $conn->prepare("SELECT idPaciente FROM paciente WHERE fkidUsuario = ?");
if ($stmt_find_pac) {
    $stmt_find_pac->bind_param("i", $idUsuarioPaciente);
    $stmt_find_pac->execute();
    $res_pac = $stmt_find_pac->get_result();
    if ($res_pac->num_rows > 0) { $idPaciente = (int)$res_pac->fetch_object()->idPaciente; }
    $stmt_find_pac->close();
}
if ($idPaciente <= 0) {
    echo "<p class='alert alert-danger'>Erro: Registro de paciente não encontrado para este usuário.</p>";
    exit;
}

// --- 3. Buscar Consultas (Passadas e Futuras) ---
// Esta query busca os dados da consulta e junta com tabelas de médico, clínica, etc.
$sql_consultas = "SELECT
                    c.idConsulta, c.data_hora_agendada, c.status, c.observacoes_medico,
                    um.Nome AS nomeMedico,
                    cl.nome AS nomeClinica,
                    e.nome AS nomeEspecialidade,
                    -- O banco formata o dinheiro (ex: R$ 150,00)
FormatarDinheiro(va.valor) AS valor_formatado, va.duracao_minutos
                  FROM Consulta c
                  JOIN ValorAtendimento va ON c.fkidValorAtendimento = va.idValorAtendimento
                  JOIN Dia_Hora_Disponivel dhd ON va.fkidDia_Hora_Disponivel = dhd.idDia_Hora_Disponivel
                  JOIN Disponibilidade d ON dhd.fkidDisponibilidade = d.idDisponibilidade
                  JOIN Clinica cl ON d.fkidClinica = cl.idClinica
                  JOIN Medico_Especialidade me ON d.fkidMededico_Especialidade = me.idMedico_Especialidade
                  JOIN Medico m ON me.fkidMedico = m.idMedico
                  JOIN Usuario um ON m.fkidUsuario = um.idUsuario -- Usuário do Médico
                  JOIN Especialidade e ON me.fkidEspecialidade = e.idEspecialidade
                  WHERE c.fkidPaciente = ?
                  ORDER BY 
                    CASE 
                        WHEN c.status IN ('CONFIRMADO', 'SOLICITADO', 'EM_ANDAMENTO') THEN 1
                        ELSE 2
                    END,
                    c.data_hora_agendada DESC"; // Ordena por status e data

$stmt_consultas = $conn->prepare($sql_consultas);
$consultas_futuras = [];
$consultas_passadas = [];
$hoje = date("Y-m-d H:i:s");

if ($stmt_consultas) {
    $stmt_consultas->bind_param("i", $idPaciente);
    $stmt_consultas->execute();
    $result_consultas = $stmt_consultas->get_result();
    while ($row = $result_consultas->fetch_object()) {
        // Separa consultas que ainda não aconteceram ou estão em andamento
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

// Helper para formatar o Status (pode ser movido para um arquivo de funções)
if (!function_exists('getStatusBadge')) { // Evita redeclaração se já existir
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
}
?>

<div class="container mt-4">
    <div class="card shadow-sm mb-4">
        <div class="card-header bg-primary text-white">
            <h4 class="mb-0"><i class="bi bi-calendar-heart me-2"></i> Meus Agendamentos</h4>
        </div>
        <div class="card-body">
            
            <?php
                if (isset($_SESSION['success_message'])) { echo '<div class="alert alert-success">' . htmlspecialchars($_SESSION['success_message']) . '</div>'; unset($_SESSION['success_message']); }
                if (isset($_SESSION['error_message'])) { echo '<div class="alert alert-danger">' . htmlspecialchars($_SESSION['error_message']) . '</div>'; unset($_SESSION['error_message']); }
            ?>

            <h5 class="text-primary">Próximas Consultas</h5>
            <div class="table-responsive mb-4">
                <table class="table table-hover table-striped">
                    <thead class="table-light">
                        <tr>
                            <th>Data/Hora</th>
                            <th>Médico</th>
                            <th>Especialidade</th>
                            <th>Valor</th>
                            <th>Clínica</th>
                            <th>Status</th>
                            <th>Ação</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($consultas_futuras)): ?>
                            <tr><td colspan="6" class="text-center text-muted">Nenhuma próxima consulta encontrada. <a href="?page=agendamento-create">Agendar agora?</a></td></tr>
                        <?php else: ?>
                            <?php foreach ($consultas_futuras as $c): ?>
                            <tr>
                                <td><strong><?= date('d/m/Y \à\s H:i', strtotime($c->data_hora_agendada)) ?></strong></td>
                                <td><?= htmlspecialchars($c->nomeMedico) ?></td>
                                <td><span class="badge bg-secondary"><?= htmlspecialchars($c->nomeEspecialidade) ?></span></td>
                                <td><?= htmlspecialchars($c->valor_formatado) ?></td>
                                <td><?= htmlspecialchars($c->nomeClinica) ?></td>
                                <td><?= getStatusBadge($c->status) ?></td>
                                <td>
                                    <?php if ($c->status == 'SOLICITADO' || $c->status == 'CONFIRMADO'): ?>
                                    <form action="acoes.php" method="POST" class="d-inline" onsubmit="return confirm('Tem certeza que deseja cancelar esta consulta?')">
                                        <input type="hidden" name="acao" value="cancelar_consulta_paciente">
                                        <input type="hidden" name="idConsulta" value="<?= $c->idConsulta ?>">
                                        <button type="submit" class="btn btn-danger btn-sm">
                                            Cancelar
                                        </button>
                                    </form>
                                    <?php endif; ?>
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
                            <th>Médico</th>
                            <th>Especialidade</th>
                            <th>Status</th>
                            <th>Observações</th>
                        </tr>
                    </thead>
                    <tbody>
                         <?php if (empty($consultas_passadas)): ?>
                            <tr><td colspan="5" class="text-center text-muted">Nenhum histórico encontrado.</td></tr>
                        <?php else: ?>
                            <?php foreach ($consultas_passadas as $c): ?>
                            <tr class="<?= in_array($c->status, ['CANCELADO_MEDICO', 'CANCELADO_PACIENTE', 'NAO_COMPARECEU']) ? 'text-muted' : '' ?>">
                                <td><?= date('d/m/Y H:i', strtotime($c->data_hora_agendada)) ?></td>
                                <td><?= htmlspecialchars($c->nomeMedico) ?></td>
                                <td><span class="badge bg-secondary"><?= htmlspecialchars($c->nomeEspecialidade) ?></span></td>
                                <td><?= getStatusBadge($c->status) ?></td>
                                <td>
                                    <?php if ($c->status == 'REALIZADO' && !empty($c->observacoes_medico)): ?>
                                        <span title="<?= htmlspecialchars($c->observacoes_medico) ?>">Ver notas</span>
                                    <?php else: ?>
                                        N/A
                                    <?php endif; ?>
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