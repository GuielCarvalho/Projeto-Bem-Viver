<?php
  require('conexao.php');
?>

<div class="card-header">
  <h4>
    Lista de Agendamentos
    <a class="btn btn-primary" href="?page=agendamento-create">Novo Agendamento</a>
  </h4>
</div>

<table class="table table-striped">
  <thead>
    <tr>
      <th>Profissional</th>
      <th>Data</th>
      <th>Hora</th>
      <th>Ações</th>
    </tr>
  </thead>
  <tbody>
    <?php
      $sql = "SELECT * FROM agendamento ORDER BY data, hora";
      $res = $conn->query($sql);
      $qtd = $res->num_rows;

      if($qtd > 0){
        while($row = $res->fetch_object()){
    ?>
      <tr>
        <td><?=$row->profissional?></td>
        <td><?=$row->data?></td>
        <td><?=$row->hora?></td>
        <td>
          <a href="?page=agendamento-editar&id=<?=$row->idagendamento?>" class="btn btn-success btn-sm">
            <span class="bi-pencil-fill"></span> Editar
          </a>
          <form action="acoes.php" method="POST" class="d-inline">
            <button onclick="return confirm('Tem certeza que deseja excluir?')" 
                    type="submit" name="delete_agendamento" 
                    value="<?=$row->idagendamento?>" class="btn btn-danger btn-sm">
              <span class="bi-trash3-fill"></span> Excluir
            </button>
          </form>
        </td>
      </tr>
    <?php
        }
      } else {
        echo "<tr><td colspan='4'>Nenhum agendamento encontrado.</td></tr>";
      }
    ?>
  </tbody>
</table>

<a class="btn btn-primary" href="?page=agendamento-create">Novo Agendamento</a>
