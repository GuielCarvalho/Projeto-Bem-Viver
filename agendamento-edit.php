<?php
    require('conexao.php');

    // Verificação do ID

    $id = (int) $_REQUEST["id"];
    $sql = "SELECT * FROM agendamento WHERE idagendamento = $id";
    $res = $conn->query($sql);

    if (!$res || $res->num_rows == 0) {
        echo "<script>alert('Agendamento não encontrado.'); location.href='?page=agendamento-listar';</script>";
        exit;
    }

    $row = $res->fetch_object();
?>

<h2>Editar Agendamento</h2>

<form action="acoes.php" method="POST">
    <input type="hidden" name="idagendamento" value="<?= $row->idagendamento ?>">

    <div class="form-floating mb-3">
        <select class="form-select" id="profissional" name="profissional" required>
            <option value="">-- Selecione --</option>
            <option value="Psicólogo" <?= $row->profissional == "Psicólogo" ? "selected" : "" ?>>Psicólogo</option>
            <option value="Clínico Geral" <?= $row->profissional == "Clínico Geral" ? "selected" : "" ?>>Clínico Geral</option>
            <option value="Nutricionista" <?= $row->profissional == "Nutricionista" ? "selected" : "" ?>>Nutricionista</option>
        </select>
        <label for="profissional">Profissional de saúde</label>
    </div>

    <div class="form-floating mb-3">
        <input type="date" class="form-control" id="data" name="data" value="<?= $row->data ?>" required>
        <label for="data">Data</label>
    </div>

    <div class="form-floating mb-3">
        <select class="form-select" id="hora" name="hora" required>
            <option value="">-- Selecione um horário --</option>
            <?php
                $horarios = ["08:00", "09:00", "10:00", "11:00", "14:00", "15:00", "16:00", "17:00", "18:00", "19:00", "20:00"];
                foreach ($horarios as $hora) {
                    $selected = ($hora == $row->hora) ? "selected" : "";
                    echo "<option value='$hora' $selected>$hora</option>";
                }
            ?>
        </select>
        <label for="hora">Hora</label>
    </div>

    <input type="submit" name="edit_agendamento" class="btn btn-primary" value="Atualizar">
</form>

<div class="col-md-6 mt-3">
    <a href="?page=agendamento-listar" class="btn btn-secondary">Voltar para Listagem</a>
</div>
