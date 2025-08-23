<?php
require 'conexao.php';

// Buscar agendamentos existentes
$agendamentos = $conn->query("SELECT idagendamento, profissional, data, hora FROM agendamento ORDER BY data, hora");

// Buscar médicos (usuários com fkidmedico válido)
$medicos = $conn->query("
    SELECT idUsuario, Nome 
    FROM usuario 
    WHERE fkidmedico IS NOT NULL AND fkidmedico != 0
");

// Buscar pacientes (usuários com fkidpaciente válido)
$pacientes = $conn->query("
    SELECT idUsuario, Nome 
    FROM usuario 
    WHERE fkidpaciente IS NOT NULL AND fkidpaciente != 0
");
?>

<h2 class="mt-4">Novo Atendimento</h2>

<form action="acoes.php" method="POST">
    <input type="hidden" name="create_atendimento" value="1">

    <!-- Agendamento -->
    <div class="mb-3">
        <label for="fkidagendamento" class="form-label">Agendamento</label>
        <select class="form-select" id="fkidagendamento" name="fkidagendamento" required>
            <option value="" disabled selected>Selecione um agendamento</option>
            <?php while ($a = $agendamentos->fetch_object()): ?>
                <option value="<?= $a->idagendamento ?>">
                    <?= htmlspecialchars($a->profissional) ?> - <?= date('d/m/Y', strtotime($a->data)) ?> às <?= htmlspecialchars($a->hora) ?>
                </option>
            <?php endwhile; ?>
        </select>
    </div>

    <!-- Médico -->
    <div class="mb-3">
        <label for="fkidmedico" class="form-label">Médico Responsável</label>
        <select class="form-select" id="fkidmedico" name="fkidmedico" required>
            <option value="" disabled selected>Selecione um médico</option>
            <?php while ($m = $medicos->fetch_object()): ?>
                <option value="<?= $m->idUsuario ?>"><?= htmlspecialchars($m->Nome) ?></option>
            <?php endwhile; ?>
        </select>
    </div>

    <!-- Paciente -->
    <div class="mb-3">
        <label for="fkidpaciente" class="form-label">Paciente</label>
        <select class="form-select" id="fkidpaciente" name="fkidpaciente" required>
            <option value="" disabled selected>Selecione um paciente</option>
            <?php while ($p = $pacientes->fetch_object()): ?>
                <option value="<?= $p->idUsuario ?>"><?= htmlspecialchars($p->Nome) ?></option>
            <?php endwhile; ?>
        </select>
    </div>

    <!-- Data do Atendimento -->
    <div class="mb-3">
        <label for="data_atendimento" class="form-label">Data e Hora do Atendimento</label>
        <input type="datetime-local" class="form-control" id="data_atendimento" name="data_atendimento" required>
    </div>

    <!-- Observações -->
    <div class="mb-3">
        <label for="observacoes" class="form-label">Observações</label>
        <textarea class="form-control" id="observacoes" name="observacoes" rows="4" required></textarea>
    </div>

    <button type="submit" class="btn btn-primary">Salvar Atendimento</button>
</form>
