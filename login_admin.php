<?php
// C:\xampp\htdocs\PBV\login_admin.php
// A sessão já foi iniciada em index.php
// Limpa mensagens de erro/sucesso anteriores (opcional)
unset($_SESSION['error_message']);
unset($_SESSION['success_message']);
?>

<div class="row justify-content-center mt-5">
    <div class="col-md-6 col-lg-4">
        <div class="card shadow-lg border-danger"> 
            <div class="card-header bg-danger text-white text-center"> 
                <h4><i class="bi bi-shield-lock-fill me-2"></i> Acesso Administrador</h4>
            </div>
            <div class="card-body p-4">
                 <?php
                // Exibe mensagens de erro/sucesso da sessão, se houver
                if (isset($_SESSION['login_error_admin'])) {
                    echo '<div class="alert alert-danger">' . htmlspecialchars($_SESSION['login_error_admin']) . '</div>';
                    unset($_SESSION['login_error_admin']); // Limpa após exibir
                }
                ?>
                <form action="acoes.php" method="POST">
                    <input type="hidden" name="acao" value="login_admin_site">

                    <div class="form-floating mb-3">
                        <input type="email" class="form-control" id="emailLoginAdmin" name="email" placeholder="admin@dominio.com" required>
                        <label for="emailLoginAdmin">Email (Admin)</label>
                    </div>
                    <div class="form-floating mb-3">
                        <input type="password" class="form-control" id="senhaLoginAdmin" name="senha" placeholder="Senha" required>
                        <label for="senhaLoginAdmin">Senha</label>
                    </div>
                    <div class="d-grid">
                        <button type="submit" class="btn btn-danger btn-lg">Acessar Controle</button>  
                    </div>
                </form>
                 <div class="text-center mt-3">
                    <a href="?page=login" class="text-secondary small">Acessar como usuário comum</a>
                </div>
            </div>
        </div>
    </div>
</div>