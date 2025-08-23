<?php
require('conexao.php');
?>

<div class="card-header">
  <h4>
    Lista de Atendimentos
    <a class="btn btn-primary" href="?page=atendimento-create">Novo Atendimento</a>
  </h4>
</div>

<table class="table table-striped">
  <thead>
    <tr>
      <th>Profissional</th>
      <th>Paciente</th>
      <th>Data do Atendimento</th>
      <th>Observações</th>
      <th>Ações</th>
    </tr>
  </thead>
  <tbody>
    <?php
    $sql = "SELECT 
          a.idatendimento,
          m.Nome AS medico,
          p.Nome AS paciente,
          a.data_atendimento,
          a.observacoes
      FROM atendimento a
      JOIN usuario m ON a.fkidmedico = m.idUsuario
      JOIN usuario p ON a.fkidpaciente = p.idUsuario
      ORDER BY a.data_atendimento DESC
    ";

    $res = $conn->query($sql);

    if ($res && $res->num_rows > 0) {
      while ($row = $res->fetch_object()) {
    ?>
        <tr>
          <td><?= htmlspecialchars($row->medico) ?></td>
          <td><?= htmlspecialchars($row->paciente) ?></td>
          <td><?= date('d/m/Y H:i', strtotime($row->data_atendimento)) ?></td>
          <td><?= nl2br(htmlspecialchars($row->observacoes)) ?></td>
          <td>
            <a href="?page=atendimento-edit&idatendimento=<?= $row->idatendimento ?>" class="btn btn-success btn-sm">
              <span class="bi-pencil-fill"></span> Editar
            </a>
            <form action="acoes.php" method="POST" class="d-inline" onsubmit="return confirm('Tem certeza que deseja excluir este atendimento?');">
              <input type="hidden" name="delete_atendimento" value="<?= $row->idatendimento ?>">
              <button type="submit" class="btn btn-danger btn-sm">
                <span class="bi-trash3-fill"></span> Excluir
              </button>
            </form>
          </td>
        </tr>
    <?php
      }
    } else {
      echo "<tr><td colspan='5' class='text-center'>Nenhum atendimento encontrado.</td></tr>";
    }
    ?>
  </tbody>
</table>

<a class="btn btn-primary mt-3" href="?page=atendimento-create">Novo Atendimento</a>
