<?php
// C:\xampp\htdocs\PBV\perfil-editar.php - CORRIGIDO PARA N:N E CHECKBOX

require_once('conexao.php');

if (!isset($_SESSION['loggedin']) || $_SESSION['tipo_usuario'] === 'admin') {
    if ($_SESSION['tipo_usuario'] === 'admin') {
        header('Location: index.php?page=controle');
    } else {
        header('Location: index.php?page=login');
    }
    exit;
}

$idUsuario = $_SESSION['idUsuario'];
$tipoUsuario = $_SESSION['tipo_usuario'];

// Consulta SQL (Com Subconsulta N:N - Igual ao perfil.php)
$sql = "SELECT 
            u.Nome, u.Email, u.Telefone, u.data_nascimento, u.Sexo, u.foto_perfil,
            m.CRM, m.Biografia,
            p.CPF,
            (
                SELECT GROUP_CONCAT(e.nome SEPARATOR ', ')
                FROM Medico_Especialidade me
                JOIN Especialidade e ON me.fkidEspecialidade = e.idEspecialidade
                WHERE me.fkidMedico = m.idMedico
            ) AS Especialidades_Atuais
        FROM usuario u
        LEFT JOIN medico m ON u.idUsuario = m.idMedico 
        LEFT JOIN paciente p ON u.idUsuario = p.idPaciente
        WHERE u.idUsuario = ?";

$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $idUsuario);
$stmt->execute();
$result = $stmt->get_result();
$usuario = $result->fetch_object();
$stmt->close();

if (!$usuario) {
    echo "<p class='alert alert-danger'>Erro ao carregar informações do perfil para edição.</p>";
    exit;
}

// 1. Busca TODAS as especialidades disponíveis na tabela 'Especialidade'
$res_especialidades_disponiveis = $conn->query("SELECT nome FROM Especialidade ORDER BY nome");
$especialidades_disponiveis = [];
if ($res_especialidades_disponiveis) {
    while ($row = $res_especialidades_disponiveis->fetch_assoc()) {
        $especialidades_disponiveis[] = $row['nome'];
    }
}
// 2. Converte a string de especialidades atuais em um array para checagem no formulário
$especialidades_atuais_array = !empty($usuario->Especialidades_Atuais) ? array_map('trim', explode(',', $usuario->Especialidades_Atuais)) : [];
?>

<div class="container mt-4">
    <div class="card shadow-sm">
        <div class="card-header bg-warning text-dark">
            <h4 class="mb-0">Editar Perfil</h4>
        </div>
        <div class="card-body">
            <form action="acoes.php" method="POST" enctype="multipart/form-data">
                <input type="hidden" name="edit_perfil" value="1">
                <input type="hidden" name="idUsuario" value="<?= $idUsuario ?>">
                <input type="hidden" name="tipo_usuario" value="<?= $tipoUsuario ?>">

                <div class="row">
                    <div class="col-md-4 text-center">
                         <?php 
                            $foto_path = $usuario->foto_perfil ? $usuario->foto_perfil : 'assets/default_profile.png';
                         ?>
                        <img id="profileImagePreview" src="<?= htmlspecialchars($foto_path) ?>" alt="Foto de Perfil" class="img-fluid rounded-circle mb-3" style="width: 150px; height: 150px; object-fit: cover;">
                        <div class="mb-3">
                            <label for="foto_perfil" class="form-label">Alterar Foto</label>
                            <input class="form-control" type="file" id="foto_perfil" name="foto_perfil" accept="image/*" onchange="previewImage(event)">
                        </div>
                    </div>
                    
                    <div class="col-md-8">
                        <div class="mb-3">
                            <label for="nome" class="form-label">Nome</label>
                            <input type="text" class="form-control" id="nome" name="nome" value="<?= htmlspecialchars($usuario->Nome) ?>" required>
                        </div>
                        <div class="mb-3">
                            <label for="email" class="form-label">Email</label>
                            <input type="email" class="form-control" id="email" name="email" value="<?= htmlspecialchars($usuario->Email) ?>" required>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="telefone" class="form-label">Telefone</label>
                                <input type="tel" class="form-control" id="telefone" name="telefone" value="<?= htmlspecialchars($usuario->Telefone ?? '') ?>">
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="data_nascimento" class="form-label">Data de Nascimento</label>
                                <input type="date" class="form-control" id="data_nascimento" name="data_nascimento" value="<?= htmlspecialchars($usuario->data_nascimento ?? '') ?>">
                            </div>
                        </div>

                        <div class="mb-3">
                            <label for="sexo" class="form-label">Sexo</label>
                            <select class="form-select" id="sexo" name="sexo">
                                <option value="">-- Não Informar --</option>
                                <option value="1" <?= $usuario->Sexo == 1 ? 'selected' : '' ?>>Masculino</option>
                                <option value="2" <?= $usuario->Sexo == 2 ? 'selected' : '' ?>>Feminino</option>
                                <option value="3" <?= $usuario->Sexo == 3 ? 'selected' : '' ?>>Outro</option>
                            </select>
                        </div>
                        
                        <?php if ($tipoUsuario === 'medico'): ?>
                            <hr>
                            <h6>Dados de Médico</h6>
                            <div class="mb-3">
                                <label for="crm" class="form-label">CRM</label>
                                <input type="text" class="form-control" id="crm" name="crm" value="<?= htmlspecialchars($usuario->CRM ?? '') ?>" required>
                            </div>
                            
                            <div class="mb-3">
                                <label for="especialidades_multi" class="form-label fw-bold">Especialidades (Selecione todas aplicáveis)</label>
                                
                                <div id="especialidades_multi" class="p-2 border rounded bg-light">
                                    <?php foreach ($especialidades_disponiveis as $esp): ?>
                                        <div class="form-check form-check-inline">
                                            <input class="form-check-input" type="checkbox" id="esp_<?= strtolower(str_replace(' ', '_', $esp)) ?>" name="especialidades[]" value="<?= htmlspecialchars($esp) ?>" 
                                            <?= in_array($esp, $especialidades_atuais_array) ? 'checked' : '' ?>>
                                            <label class="form-check-label" for="esp_<?= strtolower(str_replace(' ', '_', $esp)) ?>">
                                                <?= htmlspecialchars($esp) ?>
                                            </label>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            </div>

                            <div class="mb-3">
                                <label for="biografia" class="form-label">Biografia</label>
                                <textarea class="form-control" id="biografia" name="biografia" rows="3"><?= htmlspecialchars($usuario->Biografia ?? '') ?></textarea>
                            </div>
                        
                        <?php elseif ($tipoUsuario === 'paciente'): ?>
                            <hr>
                            <h6>Dados de Paciente</h6>
                            <div class="mb-3">
                                <label for="cpf" class="form-label">CPF</label>
                                <input type="text" class="form-control" id="cpf" name="cpf" value="<?= htmlspecialchars($usuario->CPF ?? '') ?>" required>
                            </div>
                        <?php endif; ?>

                        <div class="mb-3">
                            <label for="nova_senha" class="form-label">Nova Senha (deixe em branco para não alterar)</label>
                            <input type="password" class="form-control" id="nova_senha" name="nova_senha">
                        </div>

                        <button type="submit" class="btn btn-primary">Salvar Alterações</button>
                        <a href="?page=perfil" class="btn btn-secondary">Cancelar</a>
                    </div>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
    function previewImage(event) {
        var reader = new FileReader();
        reader.onload = function() {
            var output = document.getElementById('profileImagePreview');
            output.src = reader.result;
        };
        reader.readAsDataURL(event.target.files[0]);
    }
</script>