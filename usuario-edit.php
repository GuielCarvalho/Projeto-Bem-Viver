<?php
require('conexao.php');

$sql = "SELECT * FROM usuario WHERE idUsuario = '" . $_REQUEST["id"] . "'";
$res = $conn->query($sql);
$row = $res->fetch_object();
?>

<h2>Atualize o seu cadastro</h2>

<form action="acoes.php" method="POST">
    <input type="hidden" name="idusuario" value="<?= $row->idUsuario ?>">

    <div class="form-floating mb-3">
        <input type="text" class="form-control" id="nome" name="nome"
               placeholder="Digite seu nome" value="<?= $row->Nome ?>" required>
        <label for="nome">Nome</label>
    </div>

    <div class="form-floating mb-3">
        <input type="email" class="form-control" id="email" name="email"
               placeholder="Digite seu email" value="<?= $row->Email ?>" required>
        <label for="email">Email</label>
    </div>

    <div class="form-floating mb-3">
        <input type="password" class="form-control" id="senha" name="senha"
               placeholder="Digite uma nova senha (ou deixe em branco)">
        <label for="senha">Nova Senha (deixe em branco para manter)</label>
    </div>

    <div class="form-floating mb-3">
        <select class="form-select" id="sexo" name="sexo" required>
            <option value="1" <?= $row->Sexo == 1 ? 'selected' : '' ?>>Masculino</option>
            <option value="2" <?= $row->Sexo == 2 ? 'selected' : '' ?>>Feminino</option>
            <option value="3" <?= $row->Sexo == 3 ? 'selected' : '' ?>>Outro</option>
        </select>
        <label for="sexo">Sexo</label>
    </div>

    <div class="form-floating mb-3">
        <select class="form-select" id="tipo" name="tipo">
            <option value="">-- Selecione --</option>
            <option value="paciente" <?= $row->fkidpaciente ? 'selected' : '' ?>>Paciente</option>
            <option value="medico" <?= $row->fkidmedico ? 'selected' : '' ?>>Médico</option>
        </select>
        <label for="tipo">Tipo de Usuário</label>
    </div>

    <input type="submit" class="btn btn-primary" name="edit_usuario" value="Salvar">
</form>

<div class="col-md-6 mt-3">
    <a href="?page=usuario-listar" class="btn btn-secondary">Voltar para a listagem</a>
</div>
