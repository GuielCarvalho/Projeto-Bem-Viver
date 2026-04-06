<?php
// home.php: Versão FINAL Corrigida com GROUP_CONCAT e Explode PHP

// Garantindo que a sessão e conexão existam
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}
if (!isset($conn)) {
    require_once('conexao.php'); 
}

// Lógica para verificar o estado de login
$loggedin = isset($_SESSION['loggedin']) && $_SESSION['loggedin'] === true;
$tipo_usuario = $_SESSION['tipo_usuario'] ?? null;
?>

<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;600;700&display=swap" rel="stylesheet">
<style>
  body {
    font-family: 'Poppins', sans-serif;
    -webkit-font-smoothing: antialiased;
  }
  .hero-section {
    background: linear-gradient(135deg, #e8f5e9, #c8e6c9);
  }
  html[data-bs-theme="dark"] .hero-section {
    background: linear-gradient(135deg, #1A3E2A, #0C1E10);
    color: #E0E0E0;
  }
  .card-icon {
    font-size: 3rem; 
    color: #4CAF50; 
  }
  html[data-bs-theme="dark"] .card-icon {
    color: #66BB6A; 
  }
  html[data-bs-theme="dark"] .hero-section .text-dark {
    color: #fff !important; 
  }
  .badge-especialidade {
      margin-right: 5px;
      margin-bottom: 5px;
      display: inline-block;
  }
</style>

<section class="py-5 hero-section">
  <div class="container text-center">
    <h1 class="display-4 fw-bold text-success lh-sm">Sua Saúde, Nossa Cidade: <span class="text-dark">BemViver</span></h1>
    <p class="lead mt-3 text-dark-emphasis fs-5">
      Plataforma de <strong>teleconsultas médicas</strong> pensada especialmente para os moradores de São Luís.
    </p>
    <p class="text-dark-emphasis fs-6">
      Reduza filas e tenha acesso a cuidados profissionais e humanizados, adaptados à realidade local.
    </p>
    <div class="mt-4">
    <?php if ($loggedin && $tipo_usuario === 'paciente'): ?>
        <a href="?page=agendamento-create" class="btn btn-success btn-lg shadow-lg">Agendar Minha Consulta Agora</a>
    <?php elseif ($loggedin && ($tipo_usuario === 'medico' || $tipo_usuario === 'admin')): ?>
        <p class="text-dark-emphasis fs-6 mt-4">Acesse a lista de Agendamentos e Atendimentos no menu acima.</p>
    <?php else: ?>
        <a href="?page=login" class="btn btn-success btn-lg shadow-lg">Entrar para Agendar</a>
        <a href="?page=usuario-create" class="btn btn-outline-dark btn-lg shadow-sm ms-3">Criar Conta Grátis</a>
    <?php endif; ?>
    </div>
  </div>
</section>

<section class="py-5 bg-white">
  <div class="container text-center">
    <h2 class="fw-bold text-success mb-4">Por que Escolher o BemViver?</h2>
    <p class="text-dark-emphasis mb-5 fs-6">
      Cuidado médico de qualidade, com foco na sua comodidade e segurança.
    </p>
    <div class="row g-4">
      <div class="col-md-4"><div class="card h-100 shadow-sm border-0"><div class="card-body px-4 py-5"><i class="bi bi-calendar-check-fill card-icon mb-3"></i><h5 class="fw-semibold mb-3">Flexibilidade Total</h5><p class="text-muted text-start fs-6">Marque consultas online nos horários que melhor se encaixam na sua rotina, incluindo opções noturnas e de fim de semana.</p></div></div></div>
      <div class="col-md-4"><div class="card h-100 shadow-sm border-0"><div class="card-body px-4 py-5"><i class="bi bi-geo-alt-fill card-icon mb-3"></i><h5 class="fw-semibold mb-3">Conexão Local</h5><p class="text-muted text-start fs-6">Profissionais de saúde de São Luís que entendem as necessidades e o contexto da população local.</p></div></div></div>
      <div class="col-md-4"><div class="card h-100 shadow-sm border-0"><div class="card-body px-4 py-5"><i class="bi bi-shield-lock-fill card-icon mb-3"></i><h5 class="fw-semibold mb-3">Segurança e Privacidade</h5><p class="text-muted text-start fs-6">Sua teleconsulta é realizada em uma plataforma segura, garantindo a confidencialidade de seus dados médicos (LGPD).</p></div></div></div>
    </div>
  </div>
</section>

<?php
// CONSULTA CORRIGIDA: Usa GROUP_CONCAT para garantir 3 médicos únicos no LIMIT.
// CONSULTA CORRIGIDA COM VIEW (vw_medicoporclinica)
// A View já traz os nomes prontos. Fazemos JOIN com usuario apenas para pegar a foto.
// CONSULTA CORRIGIDA COM VIEW (vw_medicoporclinica)
// A View já traz os nomes prontos. Fazemos JOIN com usuario apenas para pegar a foto.
$sql_destaque = "
    SELECT 
        v.Nome_Medico AS Nome, 
        v.CRM, 
        u.foto_perfil AS FotoDB,
        GROUP_CONCAT(DISTINCT v.Nome_Especialidade SEPARATOR '|') AS Todas_Especialidades
    FROM vw_medicoporclinica v
    JOIN medico m ON v.CRM = m.crm
    JOIN usuario u ON m.fkidUsuario = u.idUsuario
    GROUP BY v.Nome_Medico, v.CRM, u.foto_perfil
    LIMIT 3;
";
    
$res_destaque = $conn->query($sql_destaque);
?>

<section class="py-5 section-destaque" style="background-color: #f1f8e9;">
    <div class="container">
        <h2 class="fw-bold text-center text-success mb-4">Profissionais em Destaque</h2>
        <p class="text-dark-emphasis text-center mb-5 fs-6 text-destaque">
            Conheça os médicos e suas áreas de atuação.
        </p>

        <div class="row g-4">
            <?php if ($res_destaque && $res_destaque->num_rows > 0): ?>
                <?php while ($medico = $res_destaque->fetch_object()): 
                    
                    $base_path = '/PBV/ft perfil/'; 
                    $default_path = 'assets/default_profile.png';
                    $image_file = '';
                    
                    // LÓGICA DE FOTO CONDICIONAL E MAPEAMENTO MANUAL
                    if (!empty($medico->FotoDB)) {
                        $foto_path = htmlspecialchars($medico->FotoDB);
                    } else {
                        // Mapeamento manual para os 3 médicos, usando .jpg
                        if ($medico->Nome === 'Dr. Alice') {
                            $image_file = 'Dr. Alice.jpg';
                        } elseif ($medico->Nome === 'Dr. David') {
                            $image_file = 'Dr. David.jpg';
                        } elseif ($medico->Nome === 'Dr. Fernanda') {
                            // Mapeia a foto 'Dr. David (2)' para a Dr. Fernanda
                            $image_file = 'Dr. David (2).jpg';
                        }
                        
                        $foto_path = $image_file ? $base_path . $image_file : $default_path;
                    }
                    
                    // Explode a string de especialidades para o loop de badges
                    $especialidades = explode('|', $medico->Todas_Especialidades);
                ?>
                <div class="col-md-4">
                    <div class="card h-100 shadow-sm border-0">
                        <div class="card-body d-flex align-items-center">
                            <img src="<?= htmlspecialchars($foto_path) ?>" alt="Foto" class="rounded-circle me-3" style="width: 70px; height: 70px; object-fit: cover;">
                            <div>
                                <h5 class="fw-bold text-success mb-1"><?= htmlspecialchars($medico->Nome) ?></h5>
                                <p class="text-muted mb-0 small">CRM <?= htmlspecialchars($medico->CRM) ?></p>
                                
                                <div class="mt-2">
                                    <?php 
                                    $especialidades_unicas = array_unique($especialidades);
                                    foreach ($especialidades_unicas as $especialidade): ?>
                                        <span class="badge bg-primary text-white badge-especialidade">
                                            <?= htmlspecialchars(trim($especialidade)) ?>
                                        </span>
                                    <?php endforeach; ?>
                                </div>
                                
                            </div>
                        </div>
                        <div class="card-footer bg-light border-0">
                            <span class="text-warning"><i class="bi bi-star-fill me-1"></i> 4.9/5</span>
                        </div>
                    </div>
                </div>
                <?php endwhile; ?>
            <?php else: ?>
                <p class="text-center text-muted">Nenhum especialista encontrado ou sem dados de atendimento.</p>
            <?php endif; ?>
        </div>
    </div>
</section>

<footer class="mt-5 mb-5"> 
    <div class="container">
        <div class="bg-dark text-white py-3 px-4 rounded shadow-lg text-center">
            <p class="mb-0 fs-6">&copy; <?= date('Y') ?> Bem Viver. Todos os direitos reservados.</p>
            <small class="text-white">Plataforma de Consultas em São Luís, MA.</small>
        </div>
    </div>
</footer>