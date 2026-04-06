<?php
// C:\xampp\htdocs\PBV\agendamento-create.php - NOVO FLUXO (Paciente)

if (session_status() == PHP_SESSION_NONE) { session_start(); }
if (!isset($conn)) { require_once('conexao.php'); }

// --- 1. Verificação de Acesso (Paciente) ---
if (!isset($_SESSION['loggedin']) || $_SESSION['tipo_usuario'] !== 'paciente') {
    $_SESSION['error_message'] = 'Faça login como paciente para agendar.';
    header('Location: index.php?page=login');
    exit;
}
$idUsuarioPaciente = $_SESSION['idUsuario'];

// --- 2. Buscar ID do Paciente (idPaciente) ---
$idPaciente = 0;
$stmt_find_pac = $conn->prepare("SELECT idPaciente FROM paciente WHERE fkidUsuario = ?");
if($stmt_find_pac) {
    $stmt_find_pac->bind_param("i", $idUsuarioPaciente); $stmt_find_pac->execute(); $res_pac = $stmt_find_pac->get_result();
    if($res_pac->num_rows > 0) { $idPaciente = (int)$res_pac->fetch_object()->idPaciente; } $stmt_find_pac->close();
}
if ($idPaciente <= 0) { echo "<p class='alert alert-danger'>Erro: Registro de paciente não encontrado.</p>"; exit; }

// --- 3. Lógica de Busca (Recebe dados do formulário via GET) ---
$fkidEspecialidade = isset($_GET['especialidade']) ? (int)$_GET['especialidade'] : 0;
$fkidClinica = isset($_GET['clinica']) ? (int)$_GET['clinica'] : 0;
$fkidDisponibilidade = isset($_GET['fkidDisponibilidade']) ? (int)$_GET['fkidDisponibilidade'] : 0;
$mes_ano_selecionado = isset($_GET['mes_ano']) ? $_GET['mes_ano'] : date('Y-m'); // Mês atual como padrão
$data_selecionada = isset($_GET['data']) ? $_GET['data'] : '';

$slots_disponiveis = [];
$dias_semana_medico = []; // Array JS (0=Dom, 1=Seg...)
$dias_trabalho_medico_db = []; // Array PHP (1=Dom, 2=Seg...)

// Helper
function getDiaSemanaNum($dateStr) { // Converte data (Y-m-d) para número (1=Dom, 2=Seg...)
    return (int)date('N', strtotime($dateStr)) % 7 + 1; // 1=Dom, 2=Seg, ..., 7=Sáb
}
function getDiaSemanaNome($diaNum) { $dias=[1=>'Dom', 2=>'Seg', 3=>'Ter', 4=>'Qua', 5=>'Qui', 6=>'Sex', 7=>'Sáb']; return $dias[$diaNum] ?? '?'; }

// --- 4. SE UM MÉDICO FOI SELECIONADO, BUSCA OS DIAS DA SEMANA QUE ELE TRABALHA ---
if ($fkidDisponibilidade > 0) {
     $sql_dias = "SELECT DISTINCT dia_semana FROM Dia_Hora_Disponivel WHERE fkidDisponibilidade = ?";
     $stmt_dias = $conn->prepare($sql_dias);
     if($stmt_dias) {
        $stmt_dias->bind_param("i", $fkidDisponibilidade);
        $stmt_dias->execute();
        $res_dias = $stmt_dias->get_result();
        while($row_dia = $res_dias->fetch_object()) {
            $dias_trabalho_medico_db[] = $row_dia->dia_semana; // Salva o dia (ex: 2 para Segunda)
            $dias_semana_medico[] = $row_dia->dia_semana - 1; // Para o JS (0=Dom, 1=Seg...)
        }
        $stmt_dias->close();
     }
}

// --- 5. SE UMA DATA FOI SELECIONADA (E o médico trabalha nesse dia da semana), BUSCA SLOTS ---
if ($fkidDisponibilidade > 0 && !empty($data_selecionada)) {
    $dia_semana_selecionado = getDiaSemanaNum($data_selecionada);

    // Validação extra: O dia selecionado está nos dias que o médico trabalha?
    if (in_array($dia_semana_selecionado, $dias_trabalho_medico_db)) {
        
        // 5.1. Busca as REGRAS de horário e os PREÇOS/DURAÇÃO
        $sql_regras = "SELECT va.idValorAtendimento, va.valor, va.duracao_minutos, dhd.hora_inicio, dhd.hora_fim
                       FROM ValorAtendimento va
                       JOIN Dia_Hora_Disponivel dhd ON va.fkidDia_Hora_Disponivel = dhd.idDia_Hora_Disponivel
                       WHERE dhd.fkidDisponibilidade = ? AND dhd.dia_semana = ?
                       AND va.valor IS NOT NULL AND va.duracao_minutos > 0";
        $stmt_regras = $conn->prepare($sql_regras);
        if ($stmt_regras) {
            $stmt_regras->bind_param("ii", $fkidDisponibilidade, $dia_semana_selecionado);
            $stmt_regras->execute(); $result_regras = $stmt_regras->get_result();

            // 5.2. Busca Slots Ocupados (na tabela Consulta)
            $slots_ocupados = [];
            $sql_ocupados = "SELECT DATE_FORMAT(data_hora_agendada, '%H:%i:%s') AS hora_ocupada
                             FROM Consulta
                             WHERE DATE(data_hora_agendada) = ?
                             AND fkidValorAtendimento IN (
                                 SELECT va.idValorAtendimento FROM ValorAtendimento va
                                 JOIN Dia_Hora_Disponivel dhd ON va.fkidDia_Hora_Disponivel = dhd.idDia_Hora_Disponivel
                                 WHERE dhd.fkidDisponibilidade = ?
                             )
                             AND status NOT IN ('CANCELADO_PACIENTE', 'CANCELADO_MEDICO', 'NAO_COMPARECEU')";
            $stmt_ocupados = $conn->prepare($sql_ocupados);
            if($stmt_ocupados) {
                $stmt_ocupados->bind_param("si", $data_selecionada, $fkidDisponibilidade);
                $stmt_ocupados->execute(); $res_ocupados = $stmt_ocupados->get_result();
                while($row_ocupado = $res_ocupados->fetch_object()) { $slots_ocupados[] = $row_ocupado->hora_ocupada; }
                $stmt_ocupados->close();
            }

            // 5.3. Gerar Slots Livres
            while ($regra = $result_regras->fetch_object()) {
                $inicio = strtotime($regra->hora_inicio); $fim = strtotime($regra->hora_fim); $duracao_seg = $regra->duracao_minutos * 60;
                $slot_atual = $inicio;
                while (($slot_atual + $duracao_seg) <= $fim) {
                    $hora_slot_str = date('H:i:s', $slot_atual);
                    $data_hora_slot_str = $data_selecionada . ' ' . $hora_slot_str;
                    if (!in_array($hora_slot_str, $slots_ocupados)) {
                        $slots_disponiveis[] = [
                            'fkidValorAtendimento' => $regra->idValorAtendimento,
                            'data_hora_agendada' => $data_hora_slot_str,
                            'hora_formatada' => date('H:i', $slot_atual),
                            'valor' => $regra->valor,
                            'duracao' => $regra->duracao_minutos
                        ];
                    }
                    $slot_atual += $duracao_seg;
                }
            }
            $stmt_regras->close();
        }
    } else if (!empty($data_selecionada)) {
        // Se a data foi selecionada mas o médico não trabalha nesse dia
        $_SESSION['error_message'] = "O médico selecionado não atende neste dia da semana. Escolha um dos dias habilitados.";
        $data_selecionada = ''; // Limpa a data inválida
    }
}
?>

<div class="container mt-4">
    <div class="card shadow-sm">
        <div class="card-header bg-primary text-white">
            <h4 class="mb-0"><i class="bi bi-calendar-plus-fill me-2"></i> Agendar Consulta</h4>
        </div>
        <div class="card-body">
            
            <?php
                if (isset($_SESSION['success_message'])) { echo '<div class="alert alert-success">' . htmlspecialchars($_SESSION['success_message']) . '</div>'; unset($_SESSION['success_message']); }
                if (isset($_SESSION['error_message'])) { echo '<div class="alert alert-danger">' . htmlspecialchars($_SESSION['error_message']) . '</div>'; unset($_SESSION['error_message']); }
            ?>

            <form action="index.php" method="GET" id="formAgendamento">
                <input type="hidden" name="page" value="agendamento-create">
                
                <div class="row g-3">
                    <div class="col-md-4">
                        <label for="especialidade" class="form-label fw-bold">1. Especialidade:</label>
                        <select name="especialidade" id="especialidade" class="form-select" onchange="this.form.submit()">
                            <option value="">-- Escolha --</option>
                            <?php
                            $res_esp = $conn->query("SELECT idEspecialidade, nome FROM Especialidade ORDER BY nome");
                            if ($res_esp) {
                                while ($esp = $res_esp->fetch_object()) {
                                    $selected = ($fkidEspecialidade == $esp->idEspecialidade) ? 'selected' : '';
                                    echo "<option value='{$esp->idEspecialidade}' $selected>" . htmlspecialchars($esp->nome) . "</option>";
                                }
                            }
                            ?>
                        </select>
                    </div>

                    <?php if ($fkidEspecialidade > 0): ?>
                        <div class="col-md-4">
                            <label for="clinica" class="form-label fw-bold">2. Clínica:</label>
                            <select name="clinica" id="clinica" class="form-select" onchange="this.form.submit()">
                                <option value="">-- Todas as Clínicas --</option>
                                <?php
                                $sql_cli = "SELECT DISTINCT c.idClinica, c.nome FROM Clinica c
                                            JOIN Disponibilidade d ON c.idClinica = d.fkidClinica
                                            JOIN Medico_Especialidade me ON d.fkidMededico_Especialidade = me.idMedico_Especialidade
                                            WHERE me.fkidEspecialidade = ? ORDER BY c.nome";
                                $stmt_cli = $conn->prepare($sql_cli);
                                if ($stmt_cli) {
                                    $stmt_cli->bind_param("i", $fkidEspecialidade);
                                    $stmt_cli->execute(); $res_cli = $stmt_cli->get_result();
                                    while ($cli = $res_cli->fetch_object()) {
                                        $selected = ($fkidClinica == $cli->idClinica) ? 'selected' : '';
                                        echo "<option value='{$cli->idClinica}' $selected>" . htmlspecialchars($cli->nome) . "</option>";
                                    } $stmt_cli->close();
                                }
                                ?>
                            </select>
                        </div>
                    <?php endif; ?>
                    
                    <?php if ($fkidClinica > 0): ?>
                        <div class="col-md-4">
                            <label for="fkidDisponibilidade" class="form-label fw-bold">3. Médico:</label>
                            <select name="fkidDisponibilidade" id="fkidDisponibilidade" class="form-select" onchange="this.form.submit()">
                                <option value="">-- Todos os Médicos --</option>
                                <?php
                                $sql_meds = "SELECT d.idDisponibilidade, u.Nome
                                             FROM Disponibilidade d
                                             JOIN Medico_Especialidade me ON d.fkidMededico_Especialidade = me.idMedico_Especialidade
                                             JOIN Medico m ON me.fkidMedico = m.idMedico
                                             JOIN Usuario u ON m.fkidUsuario = u.idUsuario
                                             WHERE me.fkidEspecialidade = ? AND d.fkidClinica = ?
                                             ORDER BY u.Nome";
                                $stmt_meds = $conn->prepare($sql_meds);
                                if ($stmt_meds) {
                                    $stmt_meds->bind_param("ii", $fkidEspecialidade, $fkidClinica);
                                    $stmt_meds->execute(); $res_meds = $stmt_meds->get_result();
                                    while ($med = $res_meds->fetch_object()) {
                                        $selected = ($fkidDisponibilidade == $med->idDisponibilidade) ? 'selected' : '';
                                        echo "<option value='{$med->idDisponibilidade}' $selected>" . htmlspecialchars($med->Nome) . "</option>";
                                    } $stmt_meds->close();
                                }
                                ?>
                            </select>
                        </div>
                    <?php endif; ?>

                    <?php if ($fkidDisponibilidade > 0): ?>
                        <div class="col-md-4">
                            <label for="mes_ano" class="form-label fw-bold">4. Mês Desejado:</label>
                            <input type="month" name="mes_ano" id="mes_ano" class="form-control" 
                                   value="<?= htmlspecialchars($mes_ano_selecionado) ?>" 
                                   min="<?= date('Y-m') ?>" required 
                                   onchange="this.form.submit()">
                        </div>
                    <?php endif; ?>

                    <?php if ($fkidDisponibilidade > 0 && !empty($mes_ano_selecionado)): ?>
                        <div class="col-md-4">
                            <label for="data" class="form-label fw-bold">5. Dia Desejado:</label>
                            <select name="data" id="data" class="form-select" onchange="this.form.submit()">
                                <option value="">-- Selecione o dia --</option>
                                <?php
                                // --- LÓGICA DO CALENDÁRIO LIMITADO ---
                                // Pega o ano e mês do input
                                $partes = explode('-', $mes_ano_selecionado);
                                $ano = (int)$partes[0];
                                $mes = (int)$partes[1];
                                $dias_no_mes = cal_days_in_month(CAL_GREGORIAN, $mes, $ano);
                                $hoje_timestamp = strtotime('today'); // Ignora hora

                                // Itera por todos os dias do mês selecionado
                                for ($dia = 1; $dia <= $dias_no_mes; $dia++) {
                                    $data_atual_str = sprintf('%04d-%02d-%02d', $ano, $mes, $dia);
                                    $data_atual_timestamp = strtotime($data_atual_str);
                                    
                                    // Pula dias que já passaram
                                    if ($data_atual_timestamp < $hoje_timestamp) {
                                        continue;
                                    }
                                    
                                    // Pega o dia da semana (1=Dom, 2=Seg...)
                                    $dia_semana_atual = getDiaSemanaNum($data_atual_str);
                                    
                                    // Se o dia da semana atual ESTÁ na lista de dias que o médico trabalha...
                                    if (in_array($dia_semana_atual, $dias_trabalho_medico_db)) {
                                        $nome_dia_semana = getDiaSemanaNome($dia_semana_atual);
                                        $label = "$nome_dia_semana - Dia $dia";
                                        $selected = ($data_selecionada == $data_atual_str) ? 'selected' : '';
                                        echo "<option value='{$data_atual_str}' $selected>" . htmlspecialchars($label) . "</option>";
                                    }
                                }
                                ?>
                            </select>
                        </div>
                    <?php endif; ?>

                </div>
            </form>
            
            <?php if (!empty($slots_disponiveis)): ?>
                <hr>
                <h5 class="mt-4">6. Horários disponíveis para <strong class="text-success"><?= htmlspecialchars(date('d/m/Y', strtotime($data_selecionada))) ?></strong>:</h5>
                <div class="d-flex flex-wrap gap-2">
                    <?php foreach ($slots_disponiveis as $slot): ?>
                        <form action="acoes.php" method="POST" onsubmit="return confirm('Confirmar agendamento para <?= $slot['hora_formatada'] ?> por R$<?= number_format($slot['valor'], 2, ',', '.') ?>?')">
                            <input type="hidden" name="acao" value="create_consulta">
                            <input type="hidden" name="fkidPaciente" value="<?= $idPaciente ?>">
                            <input type="hidden" name="fkidValorAtendimento" value="<?= $slot['fkidValorAtendimento'] ?>">
                            <input type="hidden" name="data_hora_agendada" value="<?= $slot['data_hora_agendada'] ?>">
                            
                            <button type="submit" class="btn btn-success">
                                <?= $slot['hora_formatada'] ?><br>
                                <small>(R$ <?= number_format($slot['valor'], 2, ',', '.') ?>)</small>
                            </button>
                        </form>
                    <?php endforeach; ?>
                </div>
            <?php elseif ($fkidDisponibilidade > 0 && !empty($data_selecionada)): ?>
                 <hr>
                 <div class="alert alert-warning mt-4">
                     Nenhum horário livre encontrado para este dia.
                     <br><small>Possíveis motivos: O médico não atende neste dia da semana, os horários já foram todos agendados, ou a clínica ainda não definiu preço/duração.</small>
                 </div>
            <?php endif; ?>

        </div>
    </div>
</div>