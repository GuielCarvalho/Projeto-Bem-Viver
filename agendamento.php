<?php
  require('conexao.php');
?>

<h2>Agendar Consulta</h2>
<form action="acoes.php" method="POST">
  <div class="form-floating mb-3">
    <select class="form-select" id="profissional" name="profissional" required>
      <option value="">-- Selecione --</option>
      <option>Psicólogo</option>
      <!-- Adicione mais opções se necessário -->
    </select>
    <label for="profissional">Profissional de saúde</label>
  </div>

  <div class="form-floating mb-3">
    <input type="date" class="form-control" id="data" name="data" required>
    <label for="data">Data</label>
  </div>

  <div class="form-floating mb-3">
    <select class="form-select" id="hora" name="hora" required>
      <option value="">-- Selecione um horário --</option>
      <option>08:00</option>
      <option>09:00</option>
      <option>10:00</option>
      <!-- continue com outros horários -->
    </select>
    <label for="hora">Hora</label>
  </div>

  <input type="submit" name="create_agendamento" class="btn btn-success w-100" value="Agendar">
</form>
