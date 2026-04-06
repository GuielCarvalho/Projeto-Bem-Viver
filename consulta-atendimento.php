<?php
// C:\xampp\htdocs\PBV\consulta-atendimento.php - CORRIGIDO (Função getStatusBadge adicionada)

if (session_status() == PHP_SESSION_NONE) { session_start(); }
if (!isset($conn)) { require_once('conexao.php'); }

// =========================================================================
// FUNÇÃO AUXILIAR (Helper)
// =========================================================================
// Esta função estava faltando e causou o erro.
if (!function_exists('getStatusBadge')) { // Evita erro se a função já for declarada em outro lugar
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
// =========================================================================


// --- 1. Verificação de Acesso (Médico) ---
if (!isset($_SESSION['loggedin']) || $_SESSION['tipo_usuario'] !== 'medico') {
    $_SESSION['error_message'] = 'Acesso negado.'; header('Location: index.php?page=login'); exit;
}
$idUsuarioMedico = $_SESSION['idUsuario'];

// --- 2. Validar ID da Consulta ---
$idConsulta = isset($_GET['idConsulta']) ? (int)$_GET['idConsulta'] : 0;
if ($idConsulta <= 0) { $_SESSION['error_message'] = 'ID da consulta inválido.'; header('Location: index.php?page=medico-agenda'); exit; }

// --- 3. Buscar Dados da Consulta e Verificar Propriedade (Médico) ---
$consulta = null;
$sql = "SELECT c.*, up.Nome AS nomePaciente, up.email AS emailPaciente, up.telefone AS telefonePaciente,
               cl.nome AS nomeClinica, e.nome AS nomeEspecialidade
        FROM Consulta c
        JOIN Paciente p ON c.fkidPaciente = p.idPaciente
        JOIN Usuario up ON p.fkidUsuario = up.idUsuario
        JOIN ValorAtendimento va ON c.fkidValorAtendimento = va.idValorAtendimento
        JOIN Dia_Hora_Disponivel dhd ON va.fkidDia_Hora_Disponivel = dhd.idDia_Hora_Disponivel
        JOIN Disponibilidade d ON dhd.fkidDisponibilidade = d.idDisponibilidade
        JOIN Clinica cl ON d.fkidClinica = cl.idClinica
        JOIN Medico_Especialidade me ON d.fkidMededico_Especialidade = me.idMedico_Especialidade
        JOIN Medico m ON me.fkidMedico = m.idMedico
        JOIN Especialidade e ON me.fkidEspecialidade = e.idEspecialidade
        WHERE c.idConsulta = ? AND m.fkidUsuario = ?";
$stmt = $conn->prepare($sql);
if(!$stmt) { echo "<p class='alert alert-danger'>Erro DB (prep consulta): " . $conn->error . "</p>"; exit; }
$stmt->bind_param("ii", $idConsulta, $idUsuarioMedico);
$stmt->execute(); $result = $stmt->get_result();
if ($result->num_rows === 0) {
    $_SESSION['error_message'] = 'Consulta não encontrada ou não pertence a você.';
    header('Location: index.php?page=medico-agenda'); exit;
}
$consulta = $result->fetch_object();
$stmt->close();
?>

<div class="container mt-4">
    <div class="row">
        <div class="col-md-4">
            <div class="card shadow-sm">
                <div class="card-header bg-secondary text-white">
                    <h5 class="mb-0">Ações Rápidas</h5>
                </div>
                <div class="card-body">
                    <form action="acoes.php" method="POST">
                        <input type="hidden" name="acao" value="update_consulta_status_medico">
                        <input type="hidden" name="idConsulta" value="<?= $consulta->idConsulta ?>">
                        <input type="hidden" name="return_page" value="consulta-atendimento&idConsulta=<?= $consulta->idConsulta ?>">

                        <label for="novo_status" class="form-label">Alterar Status:</label>
                        <select name="novo_status" id="novo_status" class="form-select mb-3">
                            <option value="">-- Selecione --</option>
                            <?php if ($consulta->status == 'SOLICITADO'): ?>
                                <option value="CONFIRMADO">Confirmar Consulta</option>
                            <?php endif; ?>
                            <?php if ($consulta->status == 'CONFIRMADO'): ?>
                                <option value="EM_ANDAMENTO">Iniciar Atendimento</option>
                            <?php endif; ?>
                             <?php if ($consulta->status == 'EM_ANDAMENTO'): ?>
                                <option value="REALIZADO">Finalizar Atendimento</option>
                            <?php endif; ?>
                            <?php if (!in_array($consulta->status, ['REALIZADO', 'CANCELADO_MEDICO', 'CANCELADO_PACIENTE'])): ?>
                                <option value="CANCELADO_MEDICO">Cancelar Consulta</option>
                                <option value="NAO_COMPARECEU">Paciente Não Compareceu</option>
                            <?php endif; ?>
                        </select>
                        
                        <?php if ($consulta->status == 'EM_ANDAMENTO'): ?>
                            <p class="text-warning small"><i class="bi bi-exclamation-triangle"></i> Para finalizar, selecione "Finalizar Atendimento" e salve as observações abaixo.</p>
                        <?php endif; ?>

                        <div class="d-grid">
                            <button type="submit" class="btn btn-primary">Atualizar Status</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <div class="col-md-8">
            <div class="card shadow-sm">
                <div class="card-header">
                    <h5 class="mb-0">Detalhes do Atendimento</h5>
                </div>
                <div class="card-body">
                    <?php
                        if (isset($_SESSION['success_message'])) { echo '<div class="alert alert-success">' . htmlspecialchars($_SESSION['success_message']) . '</div>'; unset($_SESSION['success_message']); }
                        if (isset($_SESSION['error_message'])) { echo '<div class="alert alert-danger">' . htmlspecialchars($_SESSION['error_message']) . '</div>'; unset($_SESSION['error_message']); }
                    ?>
                
                    <div class="row mb-3">
                        <div class="col-6"><strong>Status Atual:</strong> 
                            <?= getStatusBadge($consulta->status) ?>
                        </div>
                        <div class="col-6"><strong>Data/Hora:</strong> <?= date('d/m/Y H:i', strtotime($consulta->data_hora_agendada)) ?></div>
                    </div>
                    <div class="row mb-3">
                        <div class="col-6"><strong>Paciente:</strong> <?= htmlspecialchars($consulta->nomePaciente) ?></div>
                        <div class="col-6"><strong>Telefone:</strong> <?= htmlspecialchars($consulta->telefonePaciente) ?></div>
                    </div>
                    <div class="row mb-3">
                        <div class="col-6"><strong>Clínica:</strong> <?= htmlspecialchars($consulta->nomeClinica) ?></div>
                        <div class="col-6"><strong>Especialidade:</strong> <?= htmlspecialchars($consulta->nomeEspecialidade) ?></div>
                    </div>

                    <hr>
                    
                    <form action="acoes.php" method="POST">
                        <input type="hidden" name="acao" value="update_consulta_notas">
                        <input type="hidden" name="idConsulta" value="<?= $consulta->idConsulta ?>">
                        <input type="hidden" name="return_page" value="consulta-atendimento&idConsulta=<?= $consulta->idConsulta ?>">

                        <div class="mb-3">
                            <label for="observacoes_medico" class="form-label fw-bold">Observações do Atendimento (Prontuário)</label>
                            <textarea class="form-control" id="observacoes_medico" name="observacoes_medico" rows="8">
    <?= htmlspecialchars($consulta->observacoes_medico ?? '') ?>
</textarea>
                        </div>
                        
                       <div class="d-grid gap-2">
    <button type="submit" class="btn btn-success">
        <i class="bi bi-save-fill me-1"></i> Salvar Observações
    </button>
</div>
                    </form>

                    <hr>
                    <a href="?page=medico-agenda" class="btn btn-secondary btn-sm">
                        <i class="bi bi-arrow-left"></i> Voltar para Agenda
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>