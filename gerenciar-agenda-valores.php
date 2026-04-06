<?php
// C:\xampp\htdocs\PBV\gerenciar-agenda-valores.php - FASE 2 - NOVO (Usa dia_semana e duracao_minutos)

if (session_status() == PHP_SESSION_NONE) { session_start(); }
if (!isset($conn)) { require_once('conexao.php'); }

// --- 1. Verificação de Acesso ---
if (!isset($_SESSION['loggedin']) || $_SESSION['tipo_usuario'] !== 'admin_clinica') {
    $_SESSION['error_message'] = 'Acesso negado.'; header('Location: index.php?page=login'); exit;
}
$idAdminUsuario = $_SESSION['idUsuario'];

// --- 2. Buscar ID e Nome da Clínica do Admin Logado ---
$idClinica = 0; $nomeClinica = "Minha Clínica";
$stmt_find_cli = $conn->prepare("SELECT c.idClinica, c.nome FROM Clinica c JOIN ClinicaUsuario cu ON c.idClinica = cu.fkidClinica WHERE cu.fkidUsuario = ?");
if($stmt_find_cli){
    $stmt_find_cli->bind_param("i", $idAdminUsuario); $stmt_find_cli->execute(); $res_cli = $stmt_find_cli->get_result();
    if($res_cli->num_rows > 0){ $clinica_info = $res_cli->fetch_object(); $idClinica = (int)$clinica_info->idClinica; $nomeClinica = $clinica_info->nome; }
    else { header('Location: index.php?page=clinica-create'); exit; } $stmt_find_cli->close();
} else { echo "<p class='alert alert-danger'>Erro DB (buscar clínica): " . $conn->error . "</p>"; exit; }
if ($idClinica <= 0) { echo "<p class='alert alert-danger'>ID da clínica inválido.</p>"; exit; }

// --- 3. Buscar Médicos/Especialidades/Horários Associados à Clínica ---
$agendaClinica = [];
// *** MODIFICADO: Usa dhd.dia_semana e va.duracao_minutos ***
$sql_agenda = "SELECT
                   d.idDisponibilidade, u.Nome AS nomeMedico, e.nome AS nomeEspecialidade,
                   dhd.idDia_Hora_Disponivel, dhd.dia_semana, dhd.hora_inicio, dhd.hora_fim,
                   va.idValorAtendimento, va.valor, va.duracao_minutos
               FROM Disponibilidade d
               JOIN Medico_Especialidade me ON d.fkidMededico_Especialidade = me.idMedico_Especialidade
               JOIN Medico m ON me.fkidMedico = m.idMedico
               JOIN Usuario u ON m.fkidUsuario = u.idUsuario
               JOIN Especialidade e ON me.fkidEspecialidade = e.idEspecialidade
               JOIN Dia_Hora_Disponivel dhd ON dhd.fkidDisponibilidade = d.idDisponibilidade
               LEFT JOIN ValorAtendimento va ON va.fkidDia_Hora_Disponivel = dhd.idDia_Hora_Disponivel
               WHERE d.fkidClinica = ?
               ORDER BY u.Nome, e.nome, dhd.dia_semana, dhd.hora_inicio";
$stmt_agenda = $conn->prepare($sql_agenda);
if($stmt_agenda){
    $stmt_agenda->bind_param("i", $idClinica); $stmt_agenda->execute(); $result_agenda = $stmt_agenda->get_result();
    while($row = $result_agenda->fetch_object()){
        $key = $row->idDisponibilidade;
        if (!isset($agendaClinica[$key])) { $agendaClinica[$key] = ['nomeMedico' => $row->nomeMedico, 'nomeEspecialidade' => $row->nomeEspecialidade, 'horarios' => []]; }
        $agendaClinica[$key]['horarios'][] = $row;
    }
    $stmt_agenda->close();
} else { echo "<p class='alert alert-danger'>Erro DB (buscar agenda): " . $conn->error . "</p>"; }

// Helper dia da semana (versão curta)
function getDiaSemanaNomeCurto($diaNum) { $dias = [1 => 'Dom', 2 => 'Seg', 3 => 'Ter', 4 => 'Qua', 5 => 'Qui', 6 => 'Sex', 7 => 'Sáb']; return $dias[$diaNum] ?? '?'; }
?>

<div class="container mt-4">
    <div class="card shadow-sm mb-4">
        <div class="card-header bg-warning text-dark">
             <h4 class="mb-0"><i class="bi bi-tags-fill me-2"></i> Gerenciar Valores e Durações - <?= htmlspecialchars($nomeClinica) ?></h4>
        </div>
        <div class="card-body">
            <?php
                if (isset($_SESSION['success_message'])) { echo '<div class="alert alert-success">' . htmlspecialchars($_SESSION['success_message']) . '</div>'; unset($_SESSION['success_message']); }
                if (isset($_SESSION['error_message'])) { echo '<div class="alert alert-danger">' . htmlspecialchars($_SESSION['error_message']) . '</div>'; unset($_SESSION['error_message']); }
            ?>
            <p class="text-muted">Visualize os horários definidos pelos médicos para sua clínica e defina o valor (R$) e a duração (minutos) de cada consulta. Horários sem valor/duração não estarão disponíveis para agendamento.</p>

            <?php if (empty($agendaClinica)): ?>
                <div class="alert alert-info">Nenhum médico definiu horários para esta clínica ainda. <a href="?page=cadastrar-medicos-clinica&idClinica=<?= $idClinica ?>">Associe médicos</a> ou peça para eles cadastrarem seus horários.</div>
            <?php else: ?>
                <form action="acoes.php" method="POST">
                    <input type="hidden" name="acao" value="save_clinica_valores">
                    <input type="hidden" name="idClinica" value="<?= $idClinica ?>">
                    <input type="hidden" name="return_page" value="gerenciar-agenda-valores">

                    <?php foreach ($agendaClinica as $idDisp => $dataDisp): ?>
                        <div class="card mb-3">
                            <div class="card-header bg-light">
                                <h5 class="mb-0"> <i class="bi bi-person-badge"></i> <?= htmlspecialchars($dataDisp['nomeMedico']) ?> <small class="text-muted">- <?= htmlspecialchars($dataDisp['nomeEspecialidade']) ?></small> </h5>
                            </div>
                            <div class="card-body p-0">
                                <div class="table-responsive">
                                    <table class="table table-sm table-striped mb-0">
                                        <thead class="table-secondary">
                                            <tr>
                                                <th>Dia da Semana</th> 
                                                <th>Horário</th>
                                                <th style="width: 150px;">Valor (R$)</th>
                                                <th style="width: 150px;">Duração (min)</th> 
                                            </tr>
                                        </thead>
                                        <tbody>
                                        <?php foreach ($dataDisp['horarios'] as $horario):
                                            $idDiaHora = $horario->idDia_Hora_Disponivel;
                                            $idValorAtual = $horario->idValorAtendimento ?? 0;
                                        ?>
                                            <tr>
                                                <td><?= getDiaSemanaNomeCurto($horario->dia_semana) ?></td>
                                                <td><?= date("H:i", strtotime($horario->hora_inicio)) ?> - <?= date("H:i", strtotime($horario->hora_fim)) ?></td>
                                                <td>
                                                    <input type="hidden" name="fkidDia_Hora_Disponivel[<?= $idDiaHora ?>]" value="<?= $idDiaHora ?>">
                                                    <input type="hidden" name="idValorAtendimento[<?= $idDiaHora ?>]" value="<?= $idValorAtual ?>">
                                                    <input type="number" name="valor[<?= $idDiaHora ?>]" class="form-control form-control-sm"
                                                           value="<?= htmlspecialchars($horario->valor ?? '') ?>" placeholder="Ex: 150.00" step="0.01" min="0">
                                                </td>
                                                <td>
                                                    <input type="number" name="duracao_minutos[<?= $idDiaHora ?>]" class="form-control form-control-sm"
                                                           value="<?= htmlspecialchars($horario->duracao_minutos ?? '') ?>" placeholder="Ex: 60" min="5" step="5">
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>

                    <div class="mt-4 text-center">
                        <button type="submit" class="btn btn-success btn-lg"> <i class="bi bi-save-fill me-1"></i> Salvar Todos os Valores e Durações </button>
                    </div>
                </form>
            <?php endif; ?>

            <div class="mt-4 text-center border-top pt-3">
                 <a href="?page=painel-clinica" class="btn btn-secondary"> <i class="bi bi-arrow-left-circle me-1"></i> Voltar ao Painel </a>
                 <a href="?page=cadastrar-medicos-clinica&idClinica=<?= $idClinica ?>" class="btn btn-outline-primary ms-2"> <i class="bi bi-person-plus me-1"></i> Gerenciar Médicos </a>
            </div>
        </div>
    </div>
</div>