<?php
// C:\xampp\htdocs\PBV\usuario-listar.php - COMPLETO FASE 1
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
    echo "<script>alert('Acesso negado.'); location.href='index.php?page=" . $redirect_page . "';</script>"; // Feedback para o usuário
    exit;
}
?>

<div class="card mt-4 shadow-sm">
    <div class="card-header bg-secondary text-white">
        <h4>
            <i class="bi bi-people-fill me-2"></i> Lista de Usuários
            <a class="btn btn-primary btn-sm float-end" href="?page=usuario-create"> 
               <i class="bi bi-plus-circle-fill me-1"></i> Novo Usuário
            </a>
        </h4>
    </div>
    <div class="card-body">
        <div class="table-responsive"> 
            <table class="table table-striped table-hover">
                <thead class="table-dark">
                    <tr>
                        <th>ID</th>
                        <th>Nome</th>
                        <th>Email</th>
                        <th>Tipo</th>
                        <th class="text-center">Ações</th> 
                    </tr>
                </thead>
                <tbody>
                    <?php
                    $sql = "SELECT idUsuario, Nome, Email, tipo_usuario FROM usuario ORDER BY Nome";
                    $res = $conn->query($sql);

                    if ($res && $res->num_rows > 0) {
                        while ($row = $res->fetch_object()) {
                    ?>
                        <tr>
                            <td><?= htmlspecialchars($row->idUsuario) ?></td>
                            <td><?= htmlspecialchars($row->Nome) ?></td>
                            <td><?= htmlspecialchars($row->Email) ?></td>
                            <td>
                                <?php
                                $badgeClass = 'bg-secondary';
                                $iconClass = 'bi-question-circle-fill';
                                $typeName = 'Desconhecido';

                                switch ($row->tipo_usuario) {
                                    case 'medico':
                                        $badgeClass = 'bg-info text-dark';
                                        $iconClass = 'bi-person-badge-fill';
                                        $typeName = 'Médico';
                                        break;
                                    case 'paciente':
                                        $badgeClass = 'bg-success';
                                        $iconClass = 'bi-person-fill';
                                        $typeName = 'Paciente';
                                        break;
                                    case 'admin_clinica':
                                        $badgeClass = 'bg-warning text-dark';
                                        $iconClass = 'bi-building-gear';
                                        $typeName = 'Admin Clínica';
                                        break;
                                    case 'admin':
                                        $badgeClass = 'bg-danger';
                                        $iconClass = 'bi-person-gear';
                                        $typeName = 'Admin Site'; // Nome mais claro
                                        break;
                                }
                                ?>
                                <span title="<?= $typeName ?>" class="badge <?= $badgeClass ?>">
                                    <i class="bi <?= $iconClass ?> me-1"></i> <?= $typeName ?>
                                </span>
                            </td>
                            <td class="text-center"> 
                                <a href="?page=usuario-edit&id=<?= $row->idUsuario ?>" class="btn btn-success btn-sm me-1" title="Editar">
                                    <i class="bi bi-pencil-fill"></i>
                                </a>
                                <form action="acoes.php" method="POST" class="d-inline" onsubmit="return confirm('Tem certeza que deseja excluir o usuário <?= htmlspecialchars(addslashes($row->Nome)) ?> (ID: <?= $row->idUsuario ?>)? Esta ação não pode ser desfeita.')">
                                    <input type="hidden" name="acao" value="delete_usuario"> 
                                    <input type="hidden" name="idUsuario" value="<?= $row->idUsuario ?>"> 
                                    <button type="submit" class="btn btn-danger btn-sm" title="Excluir">
                                        <i class="bi bi-trash3-fill"></i>
                                    </button>
                                </form>
                            </td>
                        </tr>
                    <?php
                        }
                    } else {
                        // Verifica se houve erro na consulta ou se não há usuários
                        $errorMsg = $conn->error ? "Erro na consulta: " . $conn->error : "Nenhum usuário encontrado.";
                        echo '<tr><td colspan="5" class="text-center text-muted">' . $errorMsg . '</td></tr>';
                    }
                    ?>
                </tbody>
            </table>
        </div> 
    </div>
</div>