<?php
// C:\xampp\htdocs\PBV\controle.php - NOVO ARQUIVO

// Garante sessão e conexão
if (session_status() == PHP_SESSION_NONE) { session_start(); }
if (!isset($conn)) { require_once('conexao.php'); }

// --- 1. Verificação de Acesso ---
if (!isset($_SESSION['loggedin']) || $_SESSION['tipo_usuario'] !== 'admin') {
    $_SESSION['error_message'] = 'Acesso negado. Área restrita.';
    header('Location: index.php?page=login');
    exit;
}

/// (Opcional) Contagens rápidas usando VIEWS e Tabelas

// 1. Usuários e Clínicas (Tabelas normais)
$count_users = $conn->query("SELECT COUNT(*) AS total FROM usuario")->fetch_object()->total ?? 0;
$count_clinicas = $conn->query("SELECT COUNT(*) AS total FROM Clinica")->fetch_object()->total ?? 0;

// 2. Consultas Confirmadas (USANDO SUA VIEW 'vw_consultamarcada')
// Substitui a linha 17 antiga
$res_cons = $conn->query("SELECT COUNT(*) AS total FROM vw_consultamarcada WHERE Status = 'CONFIRMADO'");
$count_consultas = $res_cons->fetch_object()->total ?? 0;

// 3. Faturamento Total (USANDO SUA VIEW 'vw_faturamentomensal')
// Essa é nova! Vamos mostrar quanto dinheiro a clínica movimentou.
$res_fat = $conn->query("SELECT SUM(Faturamento_Total) AS total FROM vw_faturamentomensal");
$faturamento_total = $res_fat->fetch_object()->total ?? 0;

// 4. Especialidades
$count_especialidades = $conn->query("SELECT COUNT(*) AS total FROM Especialidade")->fetch_object()->total ?? 0;

?>

<div class="container mt-4">
    
    <h2 class="mb-3">Painel de Controle do Administrador</h2>
    <p class="lead text-muted">Bem-vindo(a), <?= htmlspecialchars($_SESSION['nome']) ?>. Gerencie todo o sistema.</p>

    <div class="row g-4 mt-3">
        
        
        <div class="col-md-6 col-lg-4">
            <div class="card h-100 shadow-sm text-center border-success">
                <div class="card-body">
                    <i class="bi bi-cash-coin display-4 text-success mb-3"></i>
                    <h5 class="card-title">Faturamento Total</h5>
                    <p class="card-text small">Movimentação financeira total.</p>
                    <h3 class="text-success">R$ <?= number_format($faturamento_total, 2, ',', '.') ?></h3>
                </div>
            </div>
        </div>
        <div class="col-md-6 col-lg-4">
            <div class="card h-100 shadow-sm text-center">
                <div class="card-body">
                    <i class="bi bi-people-fill display-4 text-primary mb-3"></i>
                    <h5 class="card-title">Gerenciar Usuários</h5>
                    <p class="card-text small">Listar, editar e remover pacientes, médicos e administradores de clínicas.</p>
                    <a href="?page=usuario-listar" class="btn btn-primary">Ver Usuários (<?= $count_users ?>)</a>
                </div>
            </div>
        </div>
        <div class="col-md-6 col-lg-4">
            <div class="card h-100 shadow-sm text-center">
                <div class="card-body">
                    <i class="bi bi-hospital-fill display-4 text-success mb-3"></i>
                    <h5 class="card-title">Gerenciar Clínicas</h5>
                    <p class="card-text small">Visualizar e gerenciar todas as clínicas cadastradas na plataforma.</p>
                    <a href="?page=clinica-listar" class="btn btn-success">Ver Clínicas (<?= $count_clinicas ?>)</a>
                </div>
            </div>
        </div>
        
        <div class="col-md-6 col-lg-4">
            <div class="card h-100 shadow-sm text-center">
                <div class="card-body">
                    <i class="bi bi-bookmark-star-fill display-4 text-warning mb-3"></i>
                    <h5 class="card-title">Gerenciar Especialidades</h5>
                    <p class="card-text small">Adicionar ou remover as especialidades médicas disponíveis no sistema.</p>
                    <a href="?page=especialidades-admin" class="btn btn-warning">Ver Especialidades (<?= $count_especialidades ?>)</a>
                </div>
            </div>
        </div>
        
        <div class="col-md-6 col-lg-4">
            <div class="card h-100 shadow-sm text-center">
                <div class="card-body">
                    <i class="bi bi-calendar-check-fill display-4 text-info mb-3"></i>
                    <h5 class="card-title">Todas as Consultas</h5>
                    <p class="card-text small">Visualizar o histórico completo de consultas de toda a plataforma.</p>
                    <a href="?page=consultas-geral" class="btn btn-info">Ver Consultas (<?= $count_consultas ?> Agendadas)</a>
                </div>
            </div>
        </div>

    </div>
</div>