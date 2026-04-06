<?php
// C:\xampp\htdocs\PBV\consultas-clinica.php - FASE 2 - NOVO ARQUIVO

// Garante sessão e conexão
if (session_status() == PHP_SESSION_NONE) { session_start(); }
if (!isset($conn)) { require_once('conexao.php'); }

// --- 1. Verificação de Acesso (Admin Clínica) ---
if (!isset($_SESSION['loggedin']) || $_SESSION['tipo_usuario'] !== 'admin_clinica') {
    $_SESSION['error_message'] = 'Acesso negado. Faça login como administrador de clínica.';
    header('Location: index.php?page=login');
    exit;
}
$idAdminUsuario = $_SESSION['idUsuario'];

// --- 2. Buscar ID e Nome da Clínica do Admin Logado (da SESSÃO) ---
$idClinica = isset($_SESSION['idClinica']) ? (int)$_SESSION['idClinica'] : 0;
if ($idClinica <= 0) {
    // Se não encontrou o ID da clínica na sessão, busca no banco
    $stmt_find_cli = $conn->prepare("SELECT c.idClinica, c.nome FROM Clinica c JOIN ClinicaUsuario cu ON c.idClinica = cu.fkidClinica WHERE cu.fkidUsuario = ?");
    if($stmt_find_cli){
        $stmt_find_cli->bind_param("i", $idAdminUsuario); $stmt_find_cli->execute(); $res_cli = $stmt_find_cli->get_result();
        if($res_cli->num_rows > 0){ 
            $clinica_info = $res_cli->fetch_object();
            $idClinica = (int)$clinica_info->idClinica;
            $_SESSION['idClinica'] = $idClinica; // Salva na sessão para futuro
            $nomeClinica = $clinica_info->nome;
        } else {
            header('Location: index.php?page=clinica-create'); exit; // Força criar clínica
        } $stmt_find_cli->close();
    } else { echo "<p class='alert alert-danger'>Erro DB (buscar clínica): " . $conn->error . "</p>"; exit; }
} else {
    // Pega o nome da clínica da sessão se já tiver o ID
    $nomeClinica = $_SESSION['nomeClinica'] ?? 'Minha Clínica'; // Pega nome da sessão se existir
    // (Seria bom ter o nome da clínica na sessão também, salvo no login)
}


// --- 3. Buscar Consultas (Passadas e Futuras) da CLÍNICA ---
$sql_consultas = "SELECT
                    c.idConsulta, c.data_hora_agendada, c.status,
                    up.Nome AS nomePaciente,  -- Nome do Usuário Paciente
                    um.Nome AS nomeMedico,    -- Nome do Usuário Médico
                    e.nome AS nomeEspecialidade
                  FROM Consulta c
                  JOIN Paciente p ON c.fkidPaciente = p.idPaciente
                  JOIN Usuario up ON p.fkidUsuario = up.idUsuario -- Join para nome do Paciente
                  JOIN ValorAtendimento va ON c.fkidValorAtendimento = va.idValorAtendimento
                  JOIN Dia_Hora_Disponivel dhd ON va.fkidDia_Hora_Disponivel = dhd.idDia_Hora_Disponivel
                  JOIN Disponibilidade d ON dhd.fkidDisponibilidade = d.idDisponibilidade
                  JOIN Medico_Especialidade me ON d.fkidMededico_Especialidade = me.idMedico_Especialidade
                  JOIN Medico m ON me.fkidMedico = m.idMedico
                  JOIN Usuario um ON m.fkidUsuario = um.idUsuario -- Join para nome do Médico
                  JOIN Especialidade e ON me.fkidEspecialidade = e.idEspecialidade
                  WHERE d.fkidClinica = ? -- O FILTRO PRINCIPAL!
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
    $stmt_consultas->bind_param("i", $idClinica);
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
    echo "<p class='alert alert-danger'>Erro ao buscar a agenda da clínica: " . $conn->error . "</p>";
}

// --- 4. Helper para formatar o Status ---
if (!function_exists('getStatusBadge')) { // Evita erro se já foi definida em outro arquivo
    function getStatusBadge($status) {
        switch ($status) {
            case 'CONFIRMADO': return "<span class='badge bg-primary'>Confirmado</span>";
            case 'EM_ANDAMENTO': return "<span class='badge bg-warning text-dark'>Em Andamento</span>";
            case 'SOLICITADO': return "<span class='badge bg-info text-dark'>Solicitado</span>";
            case 'REALIZADO': return "<span class='badge bg-success'>Realizado</span>";
            case 'CANCELADO_MEDICO':
            case 'CANCELADO_PACIENTE': return "<span class='badge bg-danger'>Cancelado</span>";
            case 'NAO_COMPARECEU': return "<span class='badge bg-secondary'>Não Compareceu</span>";
            default: return "<span class='badge bg-light text-dark'>" . htmlspecialchars($status) . "</span>";
        }
    }
}
?>

<div class="container mt-4">
    <div class="card shadow-sm mb-4">
        <div class="card-header bg-dark text-white">
            <h4 class="mb-0"><i class="bi bi-calendar-week me-2"></i> Consultas da Clínica (<?= htmlspecialchars($nomeClinica) ?>)</h4>
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
                            <th>Paciente</th>
                            <th>Médico</th>
                            <th>Especialidade</th>
                            <th>Status</th>
                            <th>Ação</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($consultas_futuras)): ?>
                            <tr><td colspan="6" class="text-center text-muted">Nenhuma próxima consulta encontrada para esta clínica.</td></tr>
                        <?php else: ?>
                            <?php foreach ($consultas_futuras as $c): ?>
                            <tr>
                                <td><strong><?= date('d/m/Y \à\s H:i', strtotime($c->data_hora_agendada)) ?></strong></td>
                                <td><?= htmlspecialchars($c->nomePaciente) ?></td>
                                <td><?= htmlspecialchars($c->nomeMedico) ?></td>
                                <td><span class="badge bg-secondary"><?= htmlspecialchars($c->nomeEspecialidade) ?></span></td>
                                <td><?= getStatusBadge($c->status) ?></td>
                                <td>
                                    <?php if ($c->status == 'SOLICITADO' || $c->status == 'CONFIRMADO'): ?>
                                    <form action="acoes.php" method="POST" class="d-inline" onsubmit="return confirm('Tem certeza que deseja CANCELAR esta consulta?')">
                                        <input type="hidden" name="acao" value="cancelar_consulta_clinica">
                                        <input type="hidden" name="idConsulta" value="<?= $c->idConsulta ?>">
                                        <input type="hidden" name="return_page" value="consultas-clinica">
                                        <button type="submit" class="btn btn-danger btn-sm" title="Cancelar Consulta">
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
                            <th>Paciente</th>
                            <th>Médico</th>
                            <th>Especialidade</th>
                            <th>Status</th>
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
                                <td><?= htmlspecialchars($c->nomeMedico) ?></td>
                                <td><span class="badge bg-secondary"><?= htmlspecialchars($c->nomeEspecialidade) ?></span></td>
                                <td><?= getStatusBadge($c->status) ?></td>
                            </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
            
            <div class="mt-4 text-center border-top pt-3">
                 <a href="?page=painel-clinica" class="btn btn-secondary">
                     <i class="bi bi-arrow-left-circle me-1"></i> Voltar ao Painel
                 </a>
            </div>

        </div>
    </div>
</div>