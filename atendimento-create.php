<?php
require 'conexao.php';
session_start();

// Buscar agendamentos
$agendamentos = $conn->query("SELECT idagendamento, profissional, data, hora FROM agendamento");

// Buscar médicos
$medicos = $conn->query("SELECT idUsuario, Nome FROM usuario WHERE fkidmedico IS NOT NULL");

// Buscar pacientes
$pacientes = $conn->query("SELECT idUsuario, Nome FROM usuario WHERE fkidpaciente IS NOT NULL");
?>

<h1>Novo Atendimento</h1>
<form action="acoes.php" method="post">
    <label>Data e Hora do Atendimento:</label><br>
    <input type="datetime-local" name="data_atendimento" required><br><br>

    <label>Observações:</label><br>
    <textarea name="observacoes" rows="4" cols="50"></textarea><br><br>

    <label>Agendamento:</label><br>
    <select name="fkidagendamento" required>
        <option value="">Selecione</option>
        <?php while ($a = $agendamentos->fetch_assoc()): ?>
            <option value="<?= $a['idagendamento'] ?>">
                <?= $a['profissional'] ?> - <?= $a['data'] ?> <?= $a['hora'] ?>
            </option>
        <?php endwhile; ?>
    </select><br><br>

    <label>Médico:</label><br>
    <select name="fkidmedico" required>
        <option value="">Selecione</option>
        <?php while ($m = $medicos->fetch_assoc()): ?>
            <option value="<?= $m['idUsuario'] ?>"><?= $m['Nome'] ?></option>
        <?php endwhile; ?>
    </select><br><br>

    <label>Paciente:</label><br>
    <select name="fkidpaciente" required>
        <option value="">Selecione</option>
        <?php while ($p = $pacientes->fetch_assoc()): ?>
            <option value="<?= $p['idUsuario'] ?>"><?= $p['Nome'] ?></option>
        <?php endwhile; ?>
    </select><br><br>

    <button type="submit" name="create_atendimento">Salvar Atendimento</button>
</form>
