<?php
// C:\xampp\htdocs\PBV\perfil.php - Corrigido

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

// Consulta SQL (Com Subconsulta N:N para Especialidades)
// Removida m.especialidade da lista de campos principais
$sql = "SELECT 
            u.Nome, u.Email, u.Telefone, u.data_nascimento, u.Sexo, u.foto_perfil,
            m.CRM, m.Biografia,
            -- Chama a função do banco para esconder os números
BuscarEMascararCPF(p.idPaciente) AS CPF_Mascarado,
            (
                SELECT GROUP_CONCAT(e.nome SEPARATOR ', ')
                FROM Medico_Especialidade me
                JOIN Especialidade e ON me.fkidEspecialidade = e.idEspecialidade
                WHERE me.fkidMedico = m.idMedico
            ) AS Especialidades_Listadas
        FROM usuario u
        LEFT JOIN medico m ON u.idUsuario = m.idMedico 
        LEFT JOIN paciente p ON u.idUsuario = p.fkidUsuario
        WHERE u.idUsuario = ?";

$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $idUsuario);
$stmt->execute();
$result = $stmt->get_result();
$usuario = $result->fetch_object();
$stmt->close();

if (!$usuario) {
    echo "<p class='alert alert-danger'>Erro ao carregar informações do perfil.</p>";
    exit;
}

function getSexoLabel($sexo) {
    switch ($sexo) {
        case 1: return 'Masculino';
        case 2: return 'Feminino';
        case 3: return 'Outro';
        default: return 'Não informado';
    }
}
?>

<div class="container mt-4">
    <div class="card shadow-sm">
        <div class="card-header bg-primary text-white">
            <h4 class="mb-0">Meu Perfil</h4>
        </div>
        <div class="card-body">
            <div class="row">
                <div class="col-md-4 text-center">
                    <?php 
                        $foto_url = $usuario->foto_perfil ? htmlspecialchars($usuario->foto_perfil) : 'assets/default_profile.png';
                    ?>
                    <img src="<?= $foto_url ?>" alt="Foto de Perfil" class="img-fluid rounded-circle mb-3" style="width: 150px; height: 150px; object-fit: cover;">
                    
                    <h4 class="card-title mb-1"><?= htmlspecialchars($usuario->Nome) ?></h4>
                    
                    <p class="card-text mb-2">
                        <span class="badge bg-<?= $tipoUsuario === 'medico' ? 'info' : 'success' ?> fs-6">
                            <?= ucfirst($tipoUsuario) ?>
                        </span>
                    </p>
                    
                    <a href="?page=perfil-editar" class="btn btn-warning btn-sm mb-3">
                        <i class="bi bi-pencil-fill me-1"></i> Editar Perfil
                    </a>
                </div>
                
                <div class="col-md-8">
                    <ul class="list-group list-group-flush">
                        <li class="list-group-item"><strong>Email:</strong> <?= htmlspecialchars($usuario->Email) ?></li>
                        <li class="list-group-item"><strong>Telefone:</strong> <?= htmlspecialchars($usuario->Telefone ?? 'N/A') ?></li>
                        <li class="list-group-item"><strong>Data de Nascimento:</strong> <?= $usuario->data_nascimento ? date('d/m/Y', strtotime($usuario->data_nascimento)) : 'N/A' ?></li>
                        <li class="list-group-item"><strong>Sexo:</strong> <?= getSexoLabel($usuario->Sexo) ?></li>

                        <?php if ($tipoUsuario === 'medico'): ?>
                            <li class="list-group-item"><strong>CRM:</strong> <?= htmlspecialchars($usuario->CRM ?? 'N/A') ?></li>
                            <li class="list-group-item"><strong>Especialidades:</strong> <?= htmlspecialchars($usuario->Especialidades_Listadas ?? 'N/A') ?></li>
                            <li class="list-group-item"><strong>Biografia:</strong> <?= nl2br(htmlspecialchars($usuario->Biografia ?? 'N/A')) ?></li>
                        <?php elseif ($tipoUsuario === 'paciente'): ?>
                           <li class="list-group-item">
    <strong>CPF (Protegido):</strong> 
    <?= htmlspecialchars($usuario->CPF_Mascarado ?? 'N/A') ?>
</li>
                        <?php endif; ?>
                    </ul>
                </div>
            </div>
        </div>
    </div>
</div>