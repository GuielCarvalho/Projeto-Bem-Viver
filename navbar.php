<?php
// C:\xampp\htdocs\PBV\navbar.php - ATUALIZADO (Links Admin Corrigidos)

// A sessão já deve ter sido iniciada no index.php
// Pega a página atual para lógica 'active' do Bootstrap
$page = $_REQUEST['page'] ?? 'home';
?>

<nav class="navbar navbar-expand-lg navbar-dark bg-primary shadow-sm">
    <div class="container">
        <a class="navbar-brand fw-bold" href="index.php">
            <i class="bi bi-heart-pulse-fill"></i> Bem Viver
        </a>
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav" aria-controls="navbarNav" aria-expanded="false" aria-label="Toggle navigation">
            <span class="navbar-toggler-icon"></span>
        </button>
        <div class="collapse navbar-collapse" id="navbarNav">

            <ul class="navbar-nav me-auto">
                <?php if (isset($_SESSION['loggedin']) && $_SESSION['loggedin'] === true): ?>

                    <?php // --- LINKS DO PACIENTE --- ?>
                    <?php if ($_SESSION['tipo_usuario'] === 'paciente'): ?>
                        <li class="nav-item">
                            <a class="nav-link <?= ($page == 'consultas-listar' ? 'active' : '') ?>" href="?page=consultas-listar">Meus Agendamentos</a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link <?= ($page == 'agendamento-create' ? 'active' : '') ?>" href="?page=agendamento-create">Agendar Consulta</a>
                        </li>

                    <?php // --- LINKS DO MÉDICO --- ?>
                    <?php elseif ($_SESSION['tipo_usuario'] === 'medico'): ?>
                        <li class="nav-item">
                            <a class="nav-link <?= ($page == 'medico-agenda' ? 'active' : '') ?>" href="?page=medico-agenda">Minha Agenda</a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link <?= ($page == 'medico-gerenciar-horarios' ? 'active' : '') ?>" href="?page=medico-gerenciar-horarios">Meus Horários</a>
                        </li>

                    <?php // --- LINKS DO ADMIN DA CLÍNICA --- ?>
                    <?php elseif ($_SESSION['tipo_usuario'] === 'admin_clinica'): ?>
                        <li class="nav-item">
                            <a class="nav-link <?= ($page == 'painel-clinica' ? 'active' : '') ?>" href="?page=painel-clinica">Painel</a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link <?= ($page == 'clinica-edit' ? 'active' : '') ?>" href="?page=clinica-edit">Editar Clínica</a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link <?= ($page == 'cadastrar-medicos-clinica' ? 'active' : '') ?>" href="?page=cadastrar-medicos-clinica">Gerenciar Médicos</a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link <?= ($page == 'gerenciar-agenda-valores' ? 'active' : '') ?>" href="?page=gerenciar-agenda-valores">Horários/Valores</a>
                        </li>
                         <li class="nav-item">
                            <a class="nav-link <?= ($page == 'consultas-clinica' ? 'active' : '') ?>" href="?page=consultas-clinica">Ver Consultas</a>
                        </li>

                    <?php // --- LINKS DO ADMIN DO SITE (CORRIGIDO) --- ?>
                    <?php elseif ($_SESSION['tipo_usuario'] === 'admin'): ?>
                        <li class="nav-item">
                            <a class="nav-link <?= ($page == 'controle' ? 'active' : '') ?>" href="?page=controle">Painel</a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link <?= (in_array($page, ['usuario-listar', 'usuario-edit', 'usuario-create']) ? 'active' : '') ?>" href="?page=usuario-listar">Usuários</a>
                        </li>
                         <li class="nav-item">
                            <a class="nav-link <?= (in_array($page, ['clinica-listar', 'clinica-edit-admin']) ? 'active' : '') ?>" href="?page=clinica-listar">Clínicas</a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link <?= ($page == 'consultas-geral' ? 'active' : '') ?>" href="?page=consultas-geral">Consultas</a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link <?= ($page == 'especialidades-admin' ? 'active' : '') ?>" href="?page=especialidades-admin">Especialidades</a>
                        </li>
                    <?php endif; ?>

                <?php endif; ?>
            </ul>

            <ul class="navbar-nav ms-auto">
                <?php if (isset($_SESSION['loggedin']) && $_SESSION['loggedin'] === true): ?>

                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle text-white d-flex align-items-center" href="#" id="navbarUserDropdown" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                            <?php
                                // Define a foto de perfil ou um padrão
                                $foto_url = $_SESSION['foto_perfil'] ?? 'assets/default_profile.png';
                                if (empty($foto_url) || !file_exists(htmlspecialchars($foto_url))) {
                                     $foto_url = 'assets/default_profile.png'; // Caminho padrão
                                }
                            ?>
                            <img src="<?= htmlspecialchars($foto_url) ?>" alt="Perfil" class="rounded-circle" style="width: 30px; height: 30px; object-fit: cover; margin-right: 8px;">
                            Olá, <?= htmlspecialchars(explode(' ', $_SESSION['nome'])[0]) // Mostra só o primeiro nome ?>
                        </a>
                        <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="navbarUserDropdown">
                            <li><a class="dropdown-item" href="?page=perfil"><i class="bi bi-person-fill me-2"></i> Meu Perfil</a></li>
                            <li><a class="dropdown-item" href="?page=perfil-editar"><i class="bi bi-pencil-fill me-2"></i> Editar Perfil</a></li>
                            <li><hr class="dropdown-divider"></li>
                            <li><a class="dropdown-item text-danger" href="?page=logout"><i class="bi bi-box-arrow-right me-2"></i> Sair</a></li>
                        </ul>
                    </li>

                <?php else: // Usuário Deslogado ?>
                    <li class="nav-item">
                        <a class="nav-link <?= ($page == 'login' ? 'active' : '') ?>" href="?page=login">Entrar</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link btn btn-outline-light btn-sm px-3" href="?page=usuario-create">Cadastrar</a>
                    </li>
                <?php endif; ?>
            </ul>
        </div>
    </div>
</nav>