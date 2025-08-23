<?php
// usuario-create.php
?>

<h2 class="mb-4">Criar Usuário</h2>

<!-- Abas para escolher o tipo de usuário -->
<ul class="nav nav-tabs" id="userTab" role="tablist">
  <li class="nav-item" role="presentation">
    <button class="nav-link active" id="medico-tab" data-bs-toggle="tab" data-bs-target="#medico" type="button" role="tab" aria-controls="medico" aria-selected="true">Médico</button>
  </li>
  <li class="nav-item" role="presentation">
    <button class="nav-link" id="paciente-tab" data-bs-toggle="tab" data-bs-target="#paciente" type="button" role="tab" aria-controls="paciente" aria-selected="false">Paciente</button>
  </li>
</ul>

<div class="tab-content mt-3" id="userTabContent">
  <!-- Formulário para Médico -->
  <div class="tab-pane fade show active" id="medico" role="tabpanel" aria-labelledby="medico-tab">
    <form action="acoes.php" method="POST">
      <input type="hidden" name="create_usuario" value="1">
      <input type="hidden" name="tipo" value="medico">

      <div class="mb-3">
        <label for="nomeMedico" class="form-label">Nome do Médico</label>
        <input type="text" id="nomeMedico" name="nome" class="form-control" required>
      </div>

      <div class="mb-3">
        <label for="emailMedico" class="form-label">Email</label>
        <input type="email" id="emailMedico" name="email" class="form-control" required>
      </div>

      <div class="mb-3">
        <label for="senhaMedico" class="form-label">Senha</label>
        <input type="password" id="senhaMedico" name="senha" class="form-control" required>
      </div>

      <button type="submit" class="btn btn-primary">Cadastrar Médico</button>
    </form>
  </div>

  <!-- Formulário para Paciente -->
  <div class="tab-pane fade" id="paciente" role="tabpanel" aria-labelledby="paciente-tab">
    <form action="acoes.php" method="POST">
      <input type="hidden" name="create_usuario" value="1">
      <input type="hidden" name="tipo" value="paciente">

      <div class="mb-3">
        <label for="nomePaciente" class="form-label">Nome do Paciente</label>
        <input type="text" id="nomePaciente" name="nome" class="form-control" required>
      </div>

      <div class="mb-3">
        <label for="emailPaciente" class="form-label">Email</label>
        <input type="email" id="emailPaciente" name="email" class="form-control" required>
      </div>

      <div class="mb-3">
        <label for="senhaPaciente" class="form-label">Senha</label>
        <input type="password" id="senhaPaciente" name="senha" class="form-control" required>
      </div>

      <button type="submit" class="btn btn-success">Cadastrar Paciente</button>
    </form>
  </div>
</div>

<!-- Bootstrap JS (necessário para as abas funcionarem) -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
