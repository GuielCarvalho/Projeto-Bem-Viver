<?php
// C:\xampp\htdocs\PBV\login.php - ATUALIZADO (com link para login_admin)
// A sessão já foi iniciada em index.php
?>

<div class="row justify-content-center mt-5">
    <div class="col-md-6 col-lg-4">
        <div class="card shadow-lg">
            <div class="card-header bg-primary text-white text-center">
                <h4><i class="bi bi-box-arrow-in-right me-2"></i> Acessar Conta</h4>
            </div>
            <div class="card-body p-4">
                <?php
                // Exibe mensagens de erro (ex: 'Credenciais inválidas' ou 'Admin deve logar em outra pág')
                if (isset($_SESSION['login_error'])) {
                    echo '<div class="alert alert-danger">' . htmlspecialchars($_SESSION['login_error']) . '</div>';
                    unset($_SESSION['login_error']); // Limpa após exibir
                }
                 // Exibe mensagem de sucesso (ex: vindo do cadastro)
                 if (isset($_SESSION['login_success'])) {
                    echo '<div class="alert alert-success">' . htmlspecialchars($_SESSION['login_success']) . '</div>';
                    unset($_SESSION['login_success']); // Limpa após exibir
                }
                ?>
                <form action="acoes.php" method="POST">
                    <input type="hidden" name="acao" value="login_geral">

                    <div class="form-floating mb-3">
                        <input type="email" class="form-control" id="emailLogin" name="email" placeholder="seuemail@dominio.com" required>
                        <label for="emailLogin">Email</label>
                    </div>
                    <div class="form-floating mb-3">
                        <input type="password" class="form-control" id="senhaLogin" name="senha" placeholder="Sua senha" required>
                        <label for="senhaLogin">Senha</label>
                    </div>
                    <div class="d-grid">
                        <button type="submit" class="btn btn-primary btn-lg">Entrar</button>
                    </div>
                </form>
                <div class="text-center mt-3">
                    <a href="?page=usuario-create" class="text-secondary small">Não tem conta? Cadastre-se</a>
                    
                    <br>
                    <a href="?page=login_admin" class="text-muted small mt-2 d-block">
                        <i class="bi bi-shield-lock me-1"></i> Acesso Administrativo
                    </a>
                    </div>
            </div>
        </div>
    </div>
</div>