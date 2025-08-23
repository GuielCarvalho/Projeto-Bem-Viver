<?php
require('conexao.php');
?>

<div class="card-header">
    <h4>
        Lista de Usuário
        <a class="btn btn-primary" href="?page=usuario-create">Novo Usuário</a>
    </h4>
    <table class="table table-striped">
        <thead>
            <tr>
                <th>Nome</th>
                <th>Email</th>
                <th>Tipo</th>
                <th>Ações</th>
            </tr>
        </thead>
        <tbody>
        <?php
            $sql = "SELECT idUsuario, Nome, Email, fkidmedico, fkidpaciente FROM usuario ORDER BY Nome";
            $res = $conn->query($sql);

            if ($res->num_rows > 0) {
                while ($row = $res->fetch_object()) {
                    // Define o tipo baseado nas chaves estrangeiras
                    if (!is_null($row->fkidmedico) && $row->fkidmedico != 0) {
                        $tipo = "Médico";
                    } else if (!is_null($row->fkidpaciente) && $row->fkidpaciente != 0) {
                        $tipo = "Paciente";
                    } else {
                        $tipo = "Não definido";
                    }
        ?>
            <tr>
                <td><?= htmlspecialchars($row->Nome) ?></td>
                <td><?= htmlspecialchars($row->Email) ?></td>
                <td>
                    <?php if ($tipo === "Médico") { ?>
                        <span title="Médico" class="text-primary">
                            <i class="bi bi-person-badge-fill"></i> Médico
                        </span>
                    <?php } else if ($tipo === "Paciente") { ?>
                        <span title="Paciente" class="text-success">
                            <i class="bi bi-person-fill"></i> Paciente
                        </span>
                    <?php } else { ?>
                        <span class="text-muted">Não definido</span>
                    <?php } ?>
                </td>
                <td>
                    <a href="?page=usuario-editar&id=<?= $row->idUsuario ?>" class="btn btn-success btn-sm">
                        <span class="bi-pencil-fill"></span> Editar
                    </a>
                    <form action="acoes.php" method="POST" class="d-inline">
                        <button onclick="return confirm('Tem certeza que deseja excluir?')" 
                            type="submit" name="delete_usuario" 
                            value="<?= $row->idUsuario ?>" class="btn btn-danger btn-sm">
                            <span class="bi-trash3-fill"></span> Excluir
                        </button>
                    </form>
                </td>
            </tr>
        <?php
                }
            } else {
                echo '<tr><td colspan="4">Nenhum usuário encontrado.</td></tr>';
            }
        ?>
        </tbody>
    </table>
</div>
