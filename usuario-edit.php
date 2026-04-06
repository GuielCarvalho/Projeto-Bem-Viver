<?php
// C:\xampp\htdocs\PBV\usuario-edit.php - COMPLETO FASE 1
// Verifica se a conexão existe, se não, inclui
if (!isset($conn)) {
    require_once('conexao.php');
}
// Inicia a sessão se não estiver iniciada
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Apenas administradores do site podem ver esta página
if (!isset($_SESSION['loggedin']) || $_SESSION['tipo_usuario'] !== 'admin') {
    $redirect_page = isset($_SESSION['loggedin']) ? 'home' : 'login';
    echo "<script>alert('Acesso negado.'); location.href='index.php?page=" . $redirect_page . "';</script>";
    exit;
}

// Obtém o ID do usuário a ser editado (da URL)
$id = isset($_REQUEST["id"]) ? (int) $_REQUEST["id"] : 0;

if ($id === 0) {
    echo "<script>alert('ID do usuário inválido ou não fornecido.'); location.href='index.php?page=usuario-listar';</script>";
    exit;
}

// --- Busca os dados do usuário a ser editado ---
$sql_usuario = "SELECT
                    u.idUsuario, u.Nome, u.Email, u.Telefone, u.data_nascimento, u.Sexo, u.foto_perfil, u.tipo_usuario,
                    m.idMedico, m.CRM, m.Biografia,
                    p.CPF,
                    (
                        SELECT GROUP_CONCAT(e.idEspecialidade) -- Seleciona IDs para facilitar a checagem
                        FROM Medico_Especialidade me
                        JOIN Especialidade e ON me.fkidEspecialidade = e.idEspecialidade
                        WHERE me.fkidMedico = m.idMedico
                    ) AS Especialidades_Atuais_IDs -- IDs das especialidades atuais
                FROM usuario u
                LEFT JOIN medico m ON u.idUsuario = m.fkidUsuario
                LEFT JOIN paciente p ON u.idUsuario = p.fkidUsuario
                WHERE u.idUsuario = ?";

$stmt = $conn->prepare($sql_usuario);
if (!$stmt) {
    die("Erro na preparação da consulta: " . $conn->error);
}
$stmt->bind_param("i", $id);
$stmt->execute();
$res = $stmt->get_result();

if (!$res || $res->num_rows == 0) {
    echo "<script>alert('Usuário com ID " . $id . " não encontrado.'); location.href='index.php?page=usuario-listar';</script>";
    exit;
}
$row = $res->fetch_object();
$stmt->close();

// --- Lógica para buscar especialidades (apenas se for médico) ---
$especialidades_disponiveis = []; // Array para guardar [id => nome]
$especialidades_atuais_ids_array = []; // Array de IDs das especialidades atuais

if ($row->tipo_usuario === 'medico') {
    // 1. Busca TODAS as especialidades disponíveis [id => nome]
    $res_especialidades_disponiveis = $conn->query("SELECT idEspecialidade, nome FROM Especialidade ORDER BY nome");
    if ($res_especialidades_disponiveis) {
        while ($esp_row = $res_especialidades_disponiveis->fetch_assoc()) {
            $especialidades_disponiveis[$esp_row['idEspecialidade']] = $esp_row['nome']; // Chave é o ID
        }
    }
    // 2. Converte a string de IDs de especialidades atuais em um array
    $especialidades_atuais_ids_array = !empty($row->Especialidades_Atuais_IDs) ? explode(',', $row->Especialidades_Atuais_IDs) : [];
}
// --- Fim da lógica de especialidades ---
?>

<div class="card mt-4 shadow-sm">
    <div class="card-header bg-success text-white">
        <h4 class="mb-0"><i class="bi bi-pencil-square me-2"></i> Editar Usuário (ID: <?= $id ?>)</h4>
    </div>
    <div class="card-body">
        <form action="acoes.php" method="POST" enctype="multipart/form-data"> 
            <input type="hidden" name="acao" value="edit_usuario"> 
            <input type="hidden" name="idUsuario" value="<?= $row->idUsuario ?>">
            <input type="hidden" name="tipo_usuario" value="<?= $row->tipo_usuario ?>">
            <?php if ($row->tipo_usuario === 'medico' && isset($row->idMedico)): ?>
                <input type="hidden" name="idMedico" value="<?= $row->idMedico ?>">
            <?php endif; ?>

            <h5 class="text-secondary mb-3"><i class="bi bi-person-fill"></i> Dados Gerais</h5>
            <div class="row">
                 <div class="col-md-3 text-center mb-3"> 
                    <?php $foto_url = !empty($row->foto_perfil) ? htmlspecialchars($row->foto_perfil) : 'assets/default_profile.png'; ?>
                    <img id="profileImagePreviewAdmin" src="<?= $foto_url ?>" alt="Foto" class="img-fluid rounded-circle mb-2" style="width: 120px; height: 120px; object-fit: cover;">
                    <label for="foto_perfil_admin" class="form-label small">Alterar Foto</label>
                    <input class="form-control form-control-sm" type="file" id="foto_perfil_admin" name="foto_perfil" accept="image/*" onchange="previewImageAdmin(event)">
                 </div>
                 <div class="col-md-9"> 
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="nomeUsuario" class="form-label">Nome</label>
                            <input type="text" id="nomeUsuario" name="nome" class="form-control" value="<?= htmlspecialchars($row->Nome) ?>" required>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="emailUsuario" class="form-label">Email</label>
                            <input type="email" id="emailUsuario" name="email" class="form-control" value="<?= htmlspecialchars($row->Email) ?>" required>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="telefoneUsuario" class="form-label">Telefone</label>
                            <input type="text" id="telefoneUsuario" name="telefone" class="form-control" value="<?= htmlspecialchars($row->Telefone ?? '') ?>">
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="nascimentoUsuario" class="form-label">Data de Nascimento</label>
                            <input type="date" id="nascimentoUsuario" name="data_nascimento" class="form-control" value="<?= htmlspecialchars($row->data_nascimento ?? '') ?>">
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="sexoUsuario" class="form-label">Sexo</label>
                            <select class="form-select" id="sexoUsuario" name="sexo">
                                <option value="">Não Informar</option>
                                <option value="1" <?= $row->Sexo == 1 ? 'selected' : '' ?>>Masculino</option>
                                <option value="2" <?= $row->Sexo == 2 ? 'selected' : '' ?>>Feminino</option>
                                <option value="3" <?= $row->Sexo == 3 ? 'selected' : '' ?>>Outro</option>
                            </select>
                        </div>
                         <div class="col-md-6 mb-3">
                            <label for="tipoUsuarioDisplay" class="form-label">Tipo de Usuário</label>
                            <input type="text" id="tipoUsuarioDisplay" class="form-control" value="<?= htmlspecialchars(ucfirst(str_replace('_', ' ', $row->tipo_usuario))) ?>" disabled readonly title="O tipo de usuário não pode ser alterado aqui.">
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="nova_senha" class="form-label">Nova Senha</label>
                            <input type="password" id="nova_senha" name="nova_senha" class="form-control" placeholder="Deixe em branco para não alterar">
                             <small class="text-muted">A senha atual não será exibida.</small>
                        </div>
                    </div>
                 </div>
            </div>

            <?php if ($row->tipo_usuario === 'medico'): ?>
                <hr>
                <h5 class="text-info mt-3 mb-3"><i class="bi bi-hospital"></i> Informações de Médico (ID Médico: <?= $row->idMedico ?? 'N/A' ?>)</h5>
                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label for="crmMedico" class="form-label">CRM</label>
                        <input type="text" id="crmMedico" name="crm" class="form-control" value="<?= htmlspecialchars($row->CRM ?? '') ?>" required>
                    </div>
                 </div>
                 <div class="mb-3">
                        <label class="form-label fw-bold">Especialidades</label>
                        <div class="p-2 border rounded bg-light" style="max-height: 150px; overflow-y: auto;">
                            <?php if (!empty($especialidades_disponiveis)): ?>
                                <?php foreach ($especialidades_disponiveis as $esp_id => $esp_nome): ?>
                                    <div class="form-check form-check-inline">
                                        <input class="form-check-input" type="checkbox"
                                               id="esp_<?= $esp_id ?>"
                                               name="especialidades_ids[]" value="<?= $esp_id ?>" 
                                               <?= in_array($esp_id, $especialidades_atuais_ids_array) ? 'checked' : '' ?>>
                                        <label class="form-check-label"
                                               for="esp_<?= $esp_id ?>">
                                            <?= htmlspecialchars($esp_nome) ?>
                                        </label>
                                    </div>
                                <?php endforeach; ?>
                             <?php else: ?>
                                 <p class="text-muted small">Nenhuma especialidade cadastrada no sistema. <a href="?page=especialidade-create">Cadastrar nova?</a></p> 
                             <?php endif; ?>
                        </div>
                        <small class="text-muted">Marque todas as especialidades que o médico atende.</small>
                 </div>
                 <div class="mb-3">
                    <label for="bioMedico" class="form-label">Biografia</label>
                    <textarea id="bioMedico" name="biografia" class="form-control" rows="3"><?= htmlspecialchars($row->Biografia ?? '') ?></textarea>
                </div>

            <?php elseif ($row->tipo_usuario === 'paciente'): ?>
                <hr>
                <h5 class="text-success mt-3 mb-3"><i class="bi bi-file-person"></i> Informações de Paciente</h5>
                 <div class="row">
                    <div class="col-md-6 mb-3">
                        <label for="cpfPaciente" class="form-label">CPF</label>
                        <input type="text" id="cpfPaciente" name="cpf" class="form-control" value="<?= htmlspecialchars($row->CPF ?? '') ?>" required>
                    </div>
                </div>
             <?php elseif ($row->tipo_usuario === 'admin_clinica'): ?>
                <hr>
                <h5 class="text-warning mt-3 mb-3"><i class="bi bi-building-gear"></i> Informações de Admin Clínica</h5>
                <p class="text-muted">Dados específicos da clínica gerenciada por este usuário devem ser editados no <a href="?page=clinica-listar">gerenciamento de clínicas</a>.</p>
            <?php endif; ?>

            <div class="mt-4">
                <button type="submit" class="btn btn-primary"><i class="bi bi-save-fill me-1"></i> Salvar Alterações</button>
                <a href="?page=usuario-listar" class="btn btn-secondary">Cancelar</a>
            </div>
        </form>
    </div>
</div>


<script>
    function previewImageAdmin(event) {
        var reader = new FileReader();
        reader.onload = function(){
            var output = document.getElementById('profileImagePreviewAdmin');
            output.src = reader.result;
        };
        reader.readAsDataURL(event.target.files[0]);
    }
</script>