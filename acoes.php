<?php
// C:\xampp\htdocs\PBV\acoes.php - Versão Completa, Corrigida e Comentada (Final)

// =========================================================================
// INICIALIZAÇÃO E CONEXÃO
// =========================================================================
// Garante que a sessão PHP esteja iniciada antes de qualquer output.
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}
// Inclui o arquivo de conexão com o banco de dados. Assume que $conn está definido nele.
if (!isset($conn)) {
    require_once('conexao.php'); // Garante que a conexão seja estabelecida
}

// =========================================================================
// FUNÇÃO AUXILIAR: UPLOAD DE FOTO DE PERFIL
// Processa o upload de arquivos de imagem, valida e move para a pasta 'uploads/'.
// =========================================================================
function handleProfilePictureUpload($fileInputName, $userId, $uploadDir = 'uploads/') {
    // Verifica se existe um arquivo enviado com o nome esperado e sem erros de upload.
    if (isset($_FILES[$fileInputName]) && $_FILES[$fileInputName]['error'] === UPLOAD_ERR_OK) {
        $fileTmpPath = $_FILES[$fileInputName]['tmp_name']; // Caminho temporário no servidor
        $fileName = basename($_FILES[$fileInputName]['name']); // Nome original (sanitizado com basename)
        $fileSize = $_FILES[$fileInputName]['size']; // Tamanho em bytes
        $fileNameCmps = explode(".", $fileName);
        $fileExtension = strtolower(end($fileNameCmps)); // Extensão em minúsculas

        // Gera um nome de arquivo único para evitar colisões e caracteres especiais.
        $newFileName = 'user_' . $userId . '_' . md5(time() . $fileName) . '.' . $fileExtension;
        $allowedfileExtensions = ['jpg', 'jpeg', 'png', 'gif', 'webp']; // Tipos de imagem permitidos

        // Valida a extensão do arquivo.
        if (in_array($fileExtension, $allowedfileExtensions)) {
            // Valida o tamanho máximo do arquivo (ex: 5MB).
            if ($fileSize < 5000000) {
                 // Valida o tipo MIME real do arquivo (mais seguro que confiar na extensão).
                 $finfo = finfo_open(FILEINFO_MIME_TYPE);
                 $mime = finfo_file($finfo, $fileTmpPath);
                 finfo_close($finfo);
                 if(strpos($mime, 'image/') === 0){ // Verifica se o MIME começa com 'image/'
                    $dest_path = $uploadDir . $newFileName; // Caminho final do arquivo

                    // Cria o diretório de destino se ele não existir.
                    if (!is_dir($uploadDir)) {
                         if (!mkdir($uploadDir, 0755, true)) { // Usa permissões mais seguras (0755)
                              $_SESSION['error_message'] = 'Falha ao criar diretório de uploads.';
                              error_log('Falha ao criar diretório: ' . $uploadDir);
                              return null; // Erro crítico
                         }
                    }

                    // Move o arquivo do diretório temporário para o destino final.
                    if (move_uploaded_file($fileTmpPath, $dest_path)) {
                        // Sucesso! Retorna o caminho relativo do arquivo salvo.
                        return $dest_path;
                    } else {
                         // Erro ao mover (permissões do diretório $uploadDir?).
                         $_SESSION['error_message'] = 'Erro ao salvar o arquivo. Verifique permissões.';
                         error_log('Falha ao mover upload: ' . $fileTmpPath . ' para ' . $dest_path);
                         return null; // Erro crítico
                    }
                 } else { $_SESSION['error_message'] = 'Tipo de arquivo inválido (MIME). Apenas imagens.'; return null; } // Não é imagem
            } else { $_SESSION['error_message'] = 'Arquivo muito grande (> 5MB).'; return null; } // Tamanho excedido
        } else { $_SESSION['error_message'] = 'Extensão de arquivo não permitida.'; return null; } // Extensão inválida
    // Trata outros erros de upload, exceto "nenhum arquivo enviado".
    } elseif (isset($_FILES[$fileInputName]) && $_FILES[$fileInputName]['error'] !== UPLOAD_ERR_NO_FILE) {
         $_SESSION['error_message'] = 'Erro no upload: Código ' . $_FILES[$fileInputName]['error'];
         error_log('Erro de upload (não crítico): Código ' . $_FILES[$fileInputName]['error']);
         return null; // Erro crítico
    }
    // Nenhum arquivo foi enviado (UPLOAD_ERR_NO_FILE) ou o campo não existe.
    return false; // Não houve erro, apenas nenhum arquivo para processar.
}
// --- Fim da Função Auxiliar ---


// =========================================================================
// PROCESSAMENTO CENTRAL DAS AÇÕES VIA POST
// Verifica se o formulário enviou o campo 'acao'.
// =========================================================================
if (isset($_POST['acao'])) {
    $acao = $_POST['acao']; // Identifica a ação solicitada.

    // Direciona para o bloco de código correspondente à ação.
    switch ($acao) {

        // =========================================================================
        // AÇÃO: CRIAR USUÁRIO (de usuario-create.php) - Com Dropdowns Especialidade
        // =========================================================================
        case 'create_usuario':
            // --- 1. Recebimento e Validação ---
            $tipo = $_POST['tipo_usuario'] ?? null; $nome = trim($_POST['nome'] ?? '');
            $email = trim($_POST['email'] ?? ''); $senha = $_POST['senha'] ?? null;
            $telefone = $_POST['telefone'] ?? null; $data_nascimento = !empty($_POST['data_nascimento']) ? $_POST['data_nascimento'] : null;
            $sexo = !empty($_POST['sexo']) ? (int)$_POST['sexo'] : null; $cpf = $_POST['cpf'] ?? null; $crm = trim($_POST['crm'] ?? '');
            $biografia = $_POST['biografia'] ?? null; $nome_clinica = trim($_POST['nome_clinica'] ?? '');
            $localidade_clinica = trim($_POST['localidade_clinica'] ?? ''); $cnpj_clinica = !empty($_POST['cnpj_clinica']) ? $_POST['cnpj_clinica'] : null;
            // *** RECEBE IDs DAS ESPECIALIDADES DOS DROPDOWNS ***
            $especialidade_id_1 = isset($_POST['especialidade_id_1']) ? (int)$_POST['especialidade_id_1'] : 0;
            $add_second_specialty = isset($_POST['add_second_specialty']); // Checkbox foi marcado?
            $especialidade_id_2 = ($add_second_specialty && isset($_POST['especialidade_id_2'])) ? (int)$_POST['especialidade_id_2'] : 0;

            // Validações
            if (empty($tipo) || empty($nome) || empty($email) || empty($senha)) { echo "<script>alert('Dados essenciais ausentes.'); history.back();</script>"; exit; }
            if (!filter_var($email, FILTER_VALIDATE_EMAIL)) { echo "<script>alert('Email inválido.'); history.back();</script>"; exit; }
            if ($tipo === 'paciente' && empty($cpf)) { echo "<script>alert('CPF obrigatório.'); history.back();</script>"; exit; }
            // *** VALIDAÇÃO MÉDICO ATUALIZADA para Dropdowns ***
            if ($tipo === 'medico') {
                if (empty($crm)) { echo "<script>alert('CRM obrigatório.'); history.back();</script>"; exit; }
                if ($especialidade_id_1 <= 0) { echo "<script>alert('Selecione Especialidade Principal.'); history.back();</script>"; exit; } // Valida ID > 0
                if ($add_second_specialty && $especialidade_id_2 <= 0) { echo "<script>alert('Selecione Segunda Especialidade.'); history.back();</script>"; exit; } // Valida ID > 0 se checkbox marcado
                if ($add_second_specialty && $especialidade_id_1 === $especialidade_id_2) { echo "<script>alert('Especialidades devem ser diferentes.'); history.back();</script>"; exit; } // Valida se são diferentes
            }
            if ($tipo === 'admin_clinica' && (empty($nome_clinica) || empty($localidade_clinica))) { echo "<script>alert('Nome/Localidade Clínica obrigatórios.'); history.back();</script>"; exit; }

            // --- 2. Preparação ---
            $senhaHash = password_hash($senha, PASSWORD_DEFAULT);

            // --- 3. Banco de Dados (Transação) ---
            // --- INICIO DA SUBSTITUIÇÃO: Cadastro com Procedure ---
            // (Cole isso logo após a linha $senhaHash = ...)
            
            $conn->begin_transaction(); 
            $idUsuario = null; 
            $idClinica = null; 
            
            try {
                // === CENÁRIO 1: É PACIENTE? USA A PROCEDURE! ===
                if ($tipo === 'paciente') {
                    // A procedure pede: nome, email, senha, cpf, nascimento, sexo, telefone
                    $sql_proc = "CALL CadastrarPacienteCompleto(?, ?, ?, ?, ?, ?, ?)";
                    $stmt = $conn->prepare($sql_proc);
                    if (!$stmt) { throw new Exception("Erro prep proc: " . $conn->error); }

                    // s=string, s=string, s=string, s=string, s=string, i=int, s=string
                    $stmt->bind_param("sssssis", $nome, $email, $senhaHash, $cpf, $data_nascimento, $sexo, $telefone);

                    if (!$stmt->execute()) {
                        throw new Exception("Erro ao cadastrar: " . $stmt->error);
                    }
                    $stmt->close();
                    
                    // Se chegou aqui, a procedure fez tudo (commit lá dentro)
                    $_SESSION['login_success'] = 'Paciente cadastrado com sucesso via Procedure!';
                    header('Location: index.php?page=login');
                    exit;
                }

                // === CENÁRIO 2: NÃO É PACIENTE (Médico ou Admin) - Mantemos a lógica manual ===
                
                // 1. Insere Usuário Genérico
                $sql_user = "INSERT INTO usuario (Nome, Email, senha, tipo_usuario, Telefone, Sexo, data_nascimento) VALUES (?, ?, ?, ?, ?, ?, ?)";
                $stmt_user = $conn->prepare($sql_user);
                $stmt_user->bind_param("ssssiss", $nome, $email, $senhaHash, $tipo, $telefone, $sexo, $data_nascimento);
                if (!$stmt_user->execute()) { 
                    if ($conn->errno == 1062) throw new Exception("Email já cadastrado.");
                    throw new Exception("Erro insert user: " . $stmt_user->error);
                }
                $idUsuario = $conn->insert_id;
                $stmt_user->close();

                // 2. Insere Dados Específicos
                if ($tipo === 'medico') {
                    $sql_med = "INSERT INTO medico (CRM, Biografia, fkidUsuario) VALUES (?, ?, ?)";
                    $stmt_med = $conn->prepare($sql_med);
                    $stmt_med->bind_param("ssi", $crm, $biografia, $idUsuario);
                    if (!$stmt_med->execute()) { 
                        if ($conn->errno == 1062) throw new Exception("CRM já cadastrado.");
                        throw new Exception("Erro insert medico: " . $stmt_med->error); 
                    }
                    $idMedico = $conn->insert_id;
                    $stmt_med->close();
                    
                    // Insere Especialidades
                    $sql_me = "INSERT INTO Medico_Especialidade (fkidMedico, fkidEspecialidade) VALUES (?, ?)"; 
                    $stmt_me = $conn->prepare($sql_me);
                    $stmt_me->bind_param("ii", $idMedico, $especialidade_id_1); 
                    $stmt_me->execute();
                    if ($add_second_specialty && $especialidade_id_2 > 0) { 
                        $stmt_me->bind_param("ii", $idMedico, $especialidade_id_2); 
                        $stmt_me->execute(); 
                    }
                    $stmt_me->close();

                } elseif ($tipo === 'admin_clinica') {
                    // Insere Clínica
                    $sql_cli = "INSERT INTO Clinica (nome, localidade, cnpj) VALUES (?, ?, ?)";
                    $stmt_cli = $conn->prepare($sql_cli);
                    $stmt_cli->bind_param("sss", $nome_clinica, $localidade_clinica, $cnpj_clinica);
                    if (!$stmt_cli->execute()) throw new Exception("Erro insert clinica: " . $stmt_cli->error);
                    $idClinica = $conn->insert_id;
                    $stmt_cli->close();

                    // Liga Admin à Clínica
                    $sql_cu = "INSERT INTO ClinicaUsuario (fkidClinica, fkidUsuario) VALUES (?, ?)";
                    $stmt_cu = $conn->prepare($sql_cu);
                    $stmt_cu->bind_param("ii", $idClinica, $idUsuario);
                    $stmt_cu->execute();
                    $stmt_cu->close();
                    
                    // Login automático
                    $_SESSION['loggedin'] = true; 
                    $_SESSION['idUsuario'] = $idUsuario; 
                    $_SESSION['idClinica'] = $idClinica;
                    $_SESSION['tipo_usuario'] = 'admin_clinica';
                    $_SESSION['nome'] = $nome;
                    header('Location: index.php?page=cadastrar-medicos-clinica');
                    exit;
                }

                $conn->commit();
                $_SESSION['login_success'] = 'Cadastro realizado! Faça login.';
                header('Location: index.php?page=login');
                exit;

            } catch (Exception $e) {
                $conn->rollback();
                // Verifica erros das Triggers (Data Nascimento ou CPF)
                $msg = $e->getMessage();
                if (strpos($msg, 'futuro') !== false) { $msg = "Erro: Data de nascimento inválida (Futuro)."; }
                if (strpos($msg, 'CPF deve ter') !== false) { $msg = "Erro: O CPF deve ter exatamente 14 caracteres."; }
                
                echo "<script>alert('" . addslashes($msg) . "'); history.back();</script>";
                exit;
            }
            break; // Fim case 'create_usuario'


        // =========================================================================
        // AÇÃO: LOGIN GERAL (de login.php) - MODIFICADO (Bloqueia Admin)
        // =========================================================================
        case 'login_geral':
            $email = $_POST['email'] ?? null; $senha = $_POST['senha'] ?? null;
            if (empty($email) || empty($senha)) { $_SESSION['login_error'] = 'Email/Senha obrigatórios.'; header('Location: index.php?page=login'); exit; }
            $sql = "SELECT idUsuario, Nome, Email, senha, tipo_usuario, foto_perfil FROM usuario WHERE Email = ?"; $stmt = $conn->prepare($sql);
            if (!$stmt) { $_SESSION['login_error'] = 'Erro interno.'; error_log("Err prepare login_geral: " . $conn->error); header('Location: index.php?page=login'); exit; }
            $stmt->bind_param("s", $email); $stmt->execute(); $result = $stmt->get_result();
            if ($result->num_rows === 1) {
                $usuario = $result->fetch_object(); $stmt->close();
                
                // *** BLOQUEIA LOGIN DE 'admin' AQUI ***
                if ($usuario->tipo_usuario === 'admin') {
                    $_SESSION['login_error'] = 'Login de admin deve ser feito na página de Acesso Administrador.';
                    header('Location: index.php?page=login');
                    exit;
                }

                if (password_verify($senha, $usuario->senha)) {
                    session_regenerate_id(true); $_SESSION['loggedin'] = true; $_SESSION['idUsuario'] = $usuario->idUsuario; $_SESSION['nome'] = $usuario->Nome; $_SESSION['tipo_usuario'] = $usuario->tipo_usuario; $_SESSION['foto_perfil'] = $usuario->foto_perfil; unset($_SESSION['login_error'], $_SESSION['login_error_admin']);
                    $redirect_page = 'home';
                    switch ($usuario->tipo_usuario) {
                        case 'paciente': $redirect_page = 'home'; break;
                        case 'medico': $redirect_page = 'home'; break;
                        case 'admin_clinica':
                            $idClinicaAdmin = 0; $sql_chk_cli = "SELECT fkidClinica FROM ClinicaUsuario WHERE fkidUsuario = ?"; $stmt_chk_cli = $conn->prepare($sql_chk_cli);
                            if ($stmt_chk_cli) { $stmt_chk_cli->bind_param("i", $usuario->idUsuario); $stmt_chk_cli->execute(); $res_cli = $stmt_chk_cli->get_result(); if ($res_cli->num_rows > 0) { $idClinicaAdmin = $res_cli->fetch_object()->fkidClinica; $redirect_page = 'painel-clinica'; } else { $redirect_page = 'clinica-create'; } $stmt_chk_cli->close();
                            } else { error_log("Err check ClinicaUsuario: " . $conn->error); $redirect_page = 'home'; }
                            $_SESSION['idClinica'] = $idClinicaAdmin;
                            break;
                    } header('Location: index.php?page=' . $redirect_page); exit;
                } else { $_SESSION['login_error'] = 'Credenciais inválidas.'; header('Location: index.php?page=login'); exit; }
            } else { if(isset($stmt)) $stmt->close(); $_SESSION['login_error'] = 'Credenciais inválidas.'; header('Location: index.php?page=login'); exit; }
            break; // Fim case 'login_geral'


        // =========================================================================
        // AÇÃO: LOGIN ADMIN DO SITE (de login_admin.php) - MODIFICADO (Acesso Teste)
        // =========================================================================
        case 'login_admin_site':
            $email = $_POST['email'] ?? null; $senha = $_POST['senha'] ?? null;
            if (empty($email) || empty($senha)) { $_SESSION['login_error_admin'] = 'Email/Senha obrigatórios.'; header('Location: index.php?page=login_admin'); exit; }

            // --- ACESSO DE TESTE (REMOVER EM PRODUÇÃO) ---
            if ($email === 'admin@teste.com' && $senha === '123') {
                session_regenerate_id(true); $_SESSION['loggedin'] = true; $_SESSION['idUsuario'] = 0; // ID Fictício
                $_SESSION['nome'] = 'Admin Teste'; $_SESSION['tipo_usuario'] = 'admin'; $_SESSION['foto_perfil'] = null;
                unset($_SESSION['login_error'], $_SESSION['login_error_admin']); header('Location: index.php?page=controle'); exit;
            }
            // --- FIM ACESSO DE TESTE ---

            $sql = "SELECT idUsuario, Nome, Email, senha, tipo_usuario, foto_perfil FROM usuario WHERE Email = ?"; $stmt = $conn->prepare($sql);
            if (!$stmt) { $_SESSION['login_error_admin'] = 'Erro interno.'; error_log("Err prep login_admin: " . $conn->error); header('Location: index.php?page=login_admin'); exit; }
            $stmt->bind_param("s", $email); $stmt->execute(); $result = $stmt->get_result();
            if ($result->num_rows === 1) { $usuario = $result->fetch_object(); $stmt->close();
                if ($usuario->tipo_usuario !== 'admin') { $_SESSION['login_error_admin'] = 'Acesso negado.'; header('Location: index.php?page=login_admin'); exit; }
                if (password_verify($senha, $usuario->senha)) {
                    session_regenerate_id(true); $_SESSION['loggedin'] = true; $_SESSION['idUsuario'] = $usuario->idUsuario; $_SESSION['nome'] = $usuario->Nome; $_SESSION['tipo_usuario'] = $usuario->tipo_usuario; $_SESSION['foto_perfil'] = $usuario->foto_perfil; unset($_SESSION['login_error'], $_SESSION['login_error_admin']);
                    header('Location: index.php?page=controle'); exit;
                } else { $_SESSION['login_error_admin'] = 'Credenciais inválidas.'; header('Location: index.php?page=login_admin'); exit; }
            } else { if(isset($stmt)) $stmt->close(); $_SESSION['login_error_admin'] = 'Credenciais inválidas.'; header('Location: index.php?page=login_admin'); exit; }
            break; // Fim case 'login_admin_site'


        // =========================================================================
        // AÇÃO: EDITAR USUÁRIO (PELO ADMIN - de usuario-edit.php)
        // =========================================================================
        case 'edit_usuario':
            if (!isset($_SESSION['loggedin']) || $_SESSION['tipo_usuario'] !== 'admin') { echo "<script>alert('Acesso negado.'); location.href='index.php';</script>"; exit; }
            if (!isset($_POST['idUsuario']) || !is_numeric($_POST['idUsuario'])) { echo "<script>alert('ID inválido.'); location.href='index.php?page=usuario-listar';</script>"; exit; }
            $idUsuario=(int)$_POST['idUsuario']; $tipo=$_POST['tipo_usuario']; $nome=$_POST['nome']; $email=$_POST['email']; $telefone=$_POST['telefone']??null; $data_nascimento=!empty($_POST['data_nascimento'])?$_POST['data_nascimento']:null; $sexo=!empty($_POST['sexo'])?(int)$_POST['sexo']:null; $nova_senha=$_POST['nova_senha']??''; $crm=$_POST['crm']??null; $biografia=$_POST['biografia']??null; $especialidades_ids=$_POST['especialidades_ids']??[]; $cpf=$_POST['cpf']??null; $idMedico=isset($_POST['idMedico'])?(int)$_POST['idMedico']:0;
            $foto_path = handleProfilePictureUpload('foto_perfil', $idUsuario); if ($foto_path === null) { echo "<script>alert('" . addslashes($_SESSION['error_message']) . "'); history.back();</script>"; unset($_SESSION['error_message']); exit; }
            $conn->begin_transaction(); try {
                $sql_upd_u="UPDATE usuario SET Nome=?, Email=?, Telefone=?, data_nascimento=?, Sexo=?"; $p_u=[$nome,$email,$telefone,$data_nascimento,$sexo]; $t_u="ssssi"; if(!empty($nova_senha)){$sh=password_hash($nova_senha,PASSWORD_DEFAULT);$sql_upd_u.=", senha=?"; $p_u[]=$sh;$t_u.="s";} if($foto_path!==false){$sql_upd_u.=", foto_perfil=?"; $p_u[]=$foto_path;$t_u.="s";} $sql_upd_u.=" WHERE idUsuario=?"; $p_u[]=$idUsuario; $t_u.="i"; $stmt_u=$conn->prepare($sql_upd_u); if(!$stmt_u){throw new Exception("Err prep upd u: ".$conn->error);} $stmt_u->bind_param($t_u,...$p_u); if(!$stmt_u->execute()){throw new Exception("Err upd u: ".$stmt_u->error);} $stmt_u->close();
                if($tipo==='paciente'){ if(empty($cpf)){throw new Exception('CPF obrigatório.');} $s_p="UPDATE paciente SET CPF=? WHERE fkidUsuario=?"; $st_p=$conn->prepare($s_p); if(!$st_p){throw new Exception("Err prep upd p: ".$conn->error);} $st_p->bind_param("si",$cpf,$idUsuario); if(!$st_p->execute()){throw new Exception('Err upd CPF: '.$st_p->error);} $st_p->close();}
                elseif($tipo==='medico'){ if(empty($crm)||$idMedico===0){throw new Exception('CRM/ID Médico inválido.');} $s_m="UPDATE medico SET CRM=?, Biografia=? WHERE idMedico=?"; $st_m=$conn->prepare($s_m); if(!$st_m){throw new Exception("Err prep upd m: ".$conn->error);} $st_m->bind_param("ssi",$crm,$biografia,$idMedico); if(!$st_m->execute()){throw new Exception('Err upd m: '.$st_m->error);} $st_m->close();
                    $st_d=$conn->prepare("DELETE FROM Medico_Especialidade WHERE fkidMedico = ?"); if(!$st_d){throw new Exception("Err prep del ME: ".$conn->error);} $st_d->bind_param("i",$idMedico); if(!$st_d->execute()){throw new Exception('Err clear esp: '.$st_d->error);} $st_d->close();
                    if(!empty($especialidades_ids)&&is_array($especialidades_ids)){ $s_i="INSERT INTO Medico_Especialidade (fkidMedico, fkidEspecialidade) VALUES (?, ?)"; $st_i=$conn->prepare($s_i); if(!$st_i){throw new Exception("Err prep ins ME: ".$conn->error);} foreach($especialidades_ids as $idE){$idE=(int)$idE; if($idE>0){$st_i->bind_param("ii",$idMedico,$idE); if(!$st_i->execute()){error_log('Err ins esp ID '.$idE.': '.$st_i->error);}}} $st_i->close(); } }
                $conn->commit(); $_SESSION['success_message'] = 'Usuário atualizado!'; header('Location: index.php?page=usuario-listar'); exit;
            }catch(Exception $e){$conn->rollback(); error_log("ERR EDIT USER (Admin): ".$e->getMessage()); echo "<script>alert('Erro: ".addslashes($e->getMessage())."'); history.back();</script>"; } exit; break;


        // =========================================================================
        // AÇÃO: DELETAR USUÁRIO (PELO ADMIN)
        // =========================================================================
         case 'delete_usuario':
             if (!isset($_SESSION['loggedin']) || $_SESSION['tipo_usuario'] !== 'admin') { echo "<script>alert('Acesso negado.'); location.href='index.php';</script>"; exit; } if (!isset($_POST['idUsuario']) || !is_numeric($_POST['idUsuario'])) { echo "<script>alert('ID inválido.'); location.href='index.php?page=usuario-listar';</script>"; exit; } $idUsuario = (int)$_POST['idUsuario']; if (isset($_SESSION['idUsuario']) && $_SESSION['idUsuario'] == $idUsuario) { echo "<script>alert('Não pode excluir própria conta.'); location.href='index.php?page=usuario-listar';</script>"; exit; }
             $sql_find="SELECT u.tipo_usuario, m.idMedico FROM usuario u LEFT JOIN medico m ON u.idUsuario = m.fkidUsuario WHERE u.idUsuario = ?"; $stmt_find=$conn->prepare($sql_find); if(!$stmt_find){die("Err prep find type del: ".$conn->error);} $stmt_find->bind_param("i",$idUsuario); $stmt_find->execute(); $res=$stmt_find->get_result(); if($res->num_rows===0){echo "<script>alert('Usuário não encontrado.'); location.href='index.php?page=usuario-listar';</script>"; exit;} $user=$res->fetch_object(); $tipo=$user->tipo_usuario; $idMedico=$user->idMedico; $stmt_find->close();
             $conn->begin_transaction(); try {
                 if ($tipo==='medico'&&$idMedico){ $stmt_gmi=$conn->prepare("SELECT idMedico_Especialidade FROM Medico_Especialidade WHERE fkidMedico = ?"); if(!$stmt_gmi){throw new Exception("Err prep gmi del: ".$conn->error);} $stmt_gmi->bind_param("i",$idMedico); $stmt_gmi->execute(); $res_mi=$stmt_gmi->get_result(); $mi_ids=[]; while($r=$res_mi->fetch_assoc()){$mi_ids[]=$r['idMedico_Especialidade'];} $stmt_gmi->close(); if(!empty($mi_ids)){$ph=implode(',',array_fill(0,count($mi_ids),'?'));$ty=str_repeat('i',count($mi_ids)); $stmt_dd=$conn->prepare("DELETE FROM Disponibilidade WHERE fkidMededico_Especialidade IN ($ph)"); if(!$stmt_dd){throw new Exception("Err prep del Disp: ".$conn->error);} $stmt_dd->bind_param($ty,...$mi_ids); $stmt_dd->execute(); $stmt_dd->close();} $stmt_dm=$conn->prepare("DELETE FROM Medico_Especialidade WHERE fkidMedico = ?"); if(!$stmt_dm){throw new Exception("Err prep del ME: ".$conn->error);} $stmt_dm->bind_param("i",$idMedico); $stmt_dm->execute(); $stmt_dm->close(); $stmt_dmd=$conn->prepare("DELETE FROM medico WHERE idMedico = ?"); if(!$stmt_dmd){throw new Exception("Err prep del m: ".$conn->error);} $stmt_dmd->bind_param("i",$idMedico); $stmt_dmd->execute(); $stmt_dmd->close(); }
                 elseif($tipo==='paciente'){ $stmt_dp=$conn->prepare("DELETE FROM paciente WHERE fkidUsuario = ?"); if(!$stmt_dp){throw new Exception("Err prep del p: ".$conn->error);} $stmt_dp->bind_param("i",$idUsuario); $stmt_dp->execute(); $stmt_dp->close();}
                 elseif($tipo==='admin_clinica'){ /* Assume CASCADE da FK Usuario->ClinicaUsuario */ }
                 $stmt_du=$conn->prepare("DELETE FROM usuario WHERE idUsuario = ?"); if(!$stmt_du){throw new Exception("Err prep del u: ".$conn->error);} $stmt_du->bind_param("i",$idUsuario); $stmt_du->execute(); if($stmt_du->affected_rows===0){throw new Exception("Usuário não deletado.");} $stmt_du->close();
                 $conn->commit(); $_SESSION['success_message'] = 'Usuário deletado!'; header('Location: index.php?page=usuario-listar'); exit;
             }catch(mysqli_sql_exception $e){$conn->rollback(); $err=$e->getMessage(); if($e->getCode()==1451){$err="Não pode excluir. Registros associados (ex: Consultas).";} error_log("ERR DEL USER $idUsuario (FK?): ".$e->getMessage()); echo "<script>alert('Erro: ".addslashes($err)."'); location.href='index.php?page=usuario-listar';</script>";
             }catch(Exception $e){$conn->rollback(); error_log("ERR GEN DEL USER $idUsuario: ".$e->getMessage()); echo "<script>alert('Erro geral: ".addslashes($e->getMessage())."'); location.href='index.php?page=usuario-listar';</script>"; } exit; break;


        // =========================================================================
        // AÇÃO: EDITAR PERFIL (PELO PRÓPRIO USUÁRIO)
        // =========================================================================
        case 'edit_perfil':
            if(!isset($_SESSION['loggedin'])||!isset($_SESSION['idUsuario'])){echo "<script>alert('Sessão inválida.'); location.href='index.php?page=login';</script>";exit;}
            $idUsuario=$_SESSION['idUsuario']; $tipoUsuario=$_SESSION['tipo_usuario']; $nome=$_POST['nome']??null; $email=$_POST['email']??null; $telefone=$_POST['telefone']??null; $data_nascimento=!empty($_POST['data_nascimento'])?$_POST['data_nascimento']:null; $sexo=!empty($_POST['sexo'])?(int)$_POST['sexo']:null; $nova_senha=$_POST['nova_senha']??''; $crm=$_POST['crm']??null; $biografia=$_POST['biografia']??null; $especialidades_nomes=$_POST['especialidades']??[]; $cpf=$_POST['cpf']??null;
            if(empty($nome)||empty($email)){echo "<script>alert('Nome/Email obrigatórios.'); history.back();</script>";exit;} if($tipoUsuario==='paciente'&&empty($cpf)){echo "<script>alert('CPF obrigatório.'); history.back();</script>";exit;} if($tipoUsuario==='medico'&&empty($crm)){echo "<script>alert('CRM obrigatório.'); history.back();</script>";exit;}
            $foto_path=handleProfilePictureUpload('foto_perfil',$idUsuario); if($foto_path===null){echo "<script>alert('".addslashes($_SESSION['error_message'])."'); history.back();</script>";unset($_SESSION['error_message']);exit;}
            $conn->begin_transaction(); try {
                $sql_upd_u="UPDATE usuario SET Nome=?, Email=?, Telefone=?, data_nascimento=?, Sexo=?"; $p_u=[$nome,$email,$telefone,$data_nascimento,$sexo]; $t_u="ssssi"; if(!empty($nova_senha)){$sh=password_hash($nova_senha,PASSWORD_DEFAULT);$sql_upd_u.=", senha=?";$p_u[]=$sh;$t_u.="s";} if($foto_path!==false){$sql_upd_u.=", foto_perfil=?";$p_u[]=$foto_path;$t_u.="s";} $sql_upd_u.=" WHERE idUsuario=?";$p_u[]=$idUsuario;$t_u.="i"; $stmt_u=$conn->prepare($sql_upd_u); if(!$stmt_u){throw new Exception("Err prep upd u(p): ".$conn->error);} $stmt_u->bind_param($t_u,...$p_u); if(!$stmt_u->execute()){throw new Exception("Err upd u(p): ".$stmt_u->error);} $stmt_u->close();
                if($tipoUsuario==='paciente'){ $s_p="UPDATE paciente SET CPF=? WHERE fkidUsuario=?"; $st_p=$conn->prepare($s_p); if(!$st_p){throw new Exception("Err prep upd p(p): ".$conn->error);} $st_p->bind_param("si",$cpf,$idUsuario); if(!$st_p->execute()){throw new Exception('Err upd CPF(p): '.$st_p->error);} $st_p->close(); }
                elseif($tipoUsuario==='medico'){ $stmt_fm=$conn->prepare("SELECT idMedico FROM medico WHERE fkidUsuario = ?"); $idMedico=0; if($stmt_fm){$stmt_fm->bind_param("i",$idUsuario);$stmt_fm->execute();$res_m=$stmt_fm->get_result();if($res_m->num_rows>0){$idMedico=(int)$res_m->fetch_assoc()['idMedico'];}$stmt_fm->close();}else{throw new Exception("Err find IDm(p): ".$conn->error);} if($idMedico===0){throw new Exception("Registro Médico não encontrado.");} $s_m="UPDATE medico SET CRM=?, Biografia=? WHERE idMedico=?"; $st_m=$conn->prepare($s_m); if(!$st_m){throw new Exception("Err prep upd m(p): ".$conn->error);} $st_m->bind_param("ssi",$crm,$biografia,$idMedico); if(!$st_m->execute()){throw new Exception('Err upd m(p): '.$st_m->error);} $st_m->close();
                    $st_d=$conn->prepare("DELETE FROM Medico_Especialidade WHERE fkidMedico = ?"); if(!$st_d){throw new Exception("Err prep del ME(p): ".$conn->error);} $st_d->bind_param("i",$idMedico); if(!$st_d->execute()){throw new Exception('Err clear esp(p): '.$st_d->error);} $st_d->close();
                    if(!empty($especialidades_nomes)&&is_array($especialidades_nomes)){ $s_i="INSERT INTO Medico_Especialidade (fkidMedico, fkidEspecialidade) VALUES (?, ?)"; $st_i=$conn->prepare($s_i); if(!$st_i){throw new Exception("Err prep ins ME(p): ".$conn->error);} $s_fe="SELECT idEspecialidade FROM Especialidade WHERE nome = ?"; $st_fe=$conn->prepare($s_fe); if(!$st_fe){throw new Exception("Err prep find esp ID(p): ".$conn->error);} foreach($especialidades_nomes as $espNome){$espNome=trim($espNome);if(empty($espNome))continue; $st_fe->bind_param("s",$espNome);$st_fe->execute();$res_id=$st_fe->get_result(); if($res_id->num_rows>0){$idE=(int)$res_id->fetch_assoc()['idEspecialidade'];if($idE>0){$st_i->bind_param("ii",$idMedico,$idE);if(!$st_i->execute()){error_log('Err ins esp(p) '.htmlspecialchars($espNome).': '.$st_i->error);}}}else{error_log('Esp(p) '.htmlspecialchars($espNome).' não encontrada;');}} $st_fe->close(); $st_i->close(); } }
                $conn->commit(); $_SESSION['nome']=$nome; if($foto_path!==false){$_SESSION['foto_perfil']=$foto_path;} echo "<script>alert('Perfil atualizado!'); location.href='index.php?page=perfil';</script>";
            }catch(Exception $e){ $conn->rollback(); error_log("ERR EDIT PERFIL (ID: $idUsuario): ".$e->getMessage()); echo "<script>alert('Erro: ".addslashes($e->getMessage())."'); history.back();</script>"; } exit; break;


         // =========================================================================
         // AÇÃO: CRIAR CLÍNICA (OBSOLETA - Integrada em 'create_usuario')
         // =========================================================================
         /*
         case 'create_clinica':
             // Esta ação está agora dentro do 'case create_usuario'.
             // Se você tiver um formulário separado 'clinica-create.php', ele deve ser processado aqui.
             // Vamos manter a lógica que fiz para 'clinica-create.php' caso você a use.
             if (!isset($_SESSION['loggedin']) || $_SESSION['tipo_usuario'] !== 'admin_clinica') { $_SESSION['error_message'] = 'Acesso negado.'; header('Location: index.php?page=login'); exit; } $idAdminUsuario = $_SESSION['idUsuario'];
             $nome = $_POST['nome'] ?? null; $localidade = $_POST['localidade'] ?? null; $cnpj = !empty($_POST['cnpj']) ? $_POST['cnpj'] : null; if (empty($nome) || empty($localidade)) { $_SESSION['error_message_form'] = 'Nome/Localidade obrigatórios.'; header('Location: index.php?page=clinica-create'); exit; }
             $sql_chk = "SELECT 1 FROM ClinicaUsuario WHERE fkidUsuario = ?"; $stmt_chk = $conn->prepare($sql_chk); if (!$stmt_chk) { $_SESSION['error_message_form'] = 'Erro interno: ' . $conn->error; header('Location: index.php?page=clinica-create'); exit; } $stmt_chk->bind_param("i", $idAdminUsuario); $stmt_chk->execute(); $res_chk = $stmt_chk->get_result(); $stmt_chk->close(); if ($res_chk->num_rows > 0) { $_SESSION['info_message'] = 'Clínica já associada.'; header('Location: index.php?page=painel-clinica'); exit; }
             $conn->begin_transaction(); $idClinica = null; try {
                 $sql_ins_c = "INSERT INTO Clinica (nome, localidade, cnpj) VALUES (?, ?, ?)"; $stmt_ins_c = $conn->prepare($sql_ins_c); if (!$stmt_ins_c) { throw new Exception("Err prep ins c: " . $conn->error); } $stmt_ins_c->bind_param("sss", $nome, $localidade, $cnpj); if (!$stmt_ins_c->execute()) { throw new Exception("Err ins c: " . $stmt_ins_c->error); } $idClinica = $conn->insert_id; $stmt_ins_c->close();
                 $sql_ins_cu = "INSERT INTO ClinicaUsuario (fkidClinica, fkidUsuario) VALUES (?, ?)"; $stmt_ins_cu = $conn->prepare($sql_ins_cu); if (!$stmt_ins_cu) { throw new Exception("Err prep ins CU: " . $conn->error); } $stmt_ins_cu->bind_param("ii", $idClinica, $idAdminUsuario); if (!$stmt_ins_cu->execute()) { throw new Exception("Err ins CU: " . $stmt_ins_cu->error); } $stmt_ins_cu->close();
                 $conn->commit(); $_SESSION['idClinica'] = $idClinica; $_SESSION['success_message'] = 'Clínica cadastrada!'; header('Location: index.php?page=painel-clinica'); exit;
             } catch (Exception $e) { $conn->rollback(); if ($idClinica !== null) { $conn->query("DELETE FROM Clinica WHERE idClinica = " . $idClinica); } error_log("ERR CREATE CLINICA: " . $e->getMessage()); $_SESSION['error_message_form'] = "Erro: " . $e->getMessage(); header('Location: index.php?page=clinica-create'); exit; }
             break;
         */

        // =========================================================================
        // AÇÃO: ADMIN DA CLÍNICA EDITA A CLÍNICA (de clinica-edit.php)
        // =========================================================================
        case 'edit_clinica':
            if (!isset($_SESSION['loggedin']) || $_SESSION['tipo_usuario'] !== 'admin_clinica') { $_SESSION['error_message'] = 'Acesso negado.'; header('Location: index.php?page=login'); exit; } $idAdminUsuario = $_SESSION['idUsuario'];
            $idClinica = isset($_POST['idClinica']) ? (int)$_POST['idClinica'] : 0; $nome = trim($_POST['nome'] ?? ''); $localidade = trim($_POST['localidade'] ?? ''); $cnpj = !empty(trim($_POST['cnpj'])) ? trim($_POST['cnpj']) : null; $return_page = 'clinica-edit';
            if ($idClinica <= 0 || empty($nome) || empty($localidade)) { $_SESSION['error_message_form'] = 'Dados inválidos.'; header('Location: index.php?page=' . $return_page); exit; }
            $sql_check_owner = "SELECT 1 FROM ClinicaUsuario WHERE fkidClinica = ? AND fkidUsuario = ?"; $stmt_check_owner = $conn->prepare($sql_check_owner); if(!$stmt_check_owner){ $_SESSION['error_message_form'] = 'Erro BD (chk owner).'; error_log("Err prep chk own edit cli: ".$conn->error); header('Location: index.php?page='.$return_page); exit; } $stmt_check_owner->bind_param("ii", $idClinica, $idAdminUsuario); $stmt_check_owner->execute(); $result_owner = $stmt_check_owner->get_result(); $stmt_check_owner->close(); if ($result_owner->num_rows === 0) { $_SESSION['error_message'] = 'Sem permissão.'; header('Location: index.php?page=painel-clinica'); exit; }
            try {
                $sql_update = "UPDATE Clinica SET nome = ?, localidade = ?, cnpj = ? WHERE idClinica = ?"; $stmt_update = $conn->prepare($sql_update); if (!$stmt_update) { throw new Exception("Erro BD (prep upd): " . $conn->error); } $stmt_update->bind_param("sssi", $nome, $localidade, $cnpj, $idClinica);
                if ($stmt_update->execute()) { $_SESSION['success_message'] = 'Dados da clínica atualizados!'; } else { throw new Exception("Erro ao salvar: " . $stmt_update->error); } $stmt_update->close();
                header('Location: index.php?page=painel-clinica'); exit;
            } catch (Exception $e) { error_log("ERR EDIT CLINICA (ID: $idClinica): " . $e->getMessage()); $_SESSION['error_message_form'] = "Erro: " . $e->getMessage(); header('Location: index.php?page=' . $return_page); exit; }
            break; // Fim case 'edit_clinica'


        // =========================================================================
        // AÇÃO: ADMIN DO SITE EDITA QUALQUER CLÍNICA (de clinica-edit-admin.php)
        // =========================================================================
        case 'edit_clinica_admin':
            if (!isset($_SESSION['loggedin']) || $_SESSION['tipo_usuario'] !== 'admin') { $_SESSION['error_message'] = 'Acesso negado.'; header('Location: index.php?page=login'); exit; }
            $idClinica = isset($_POST['idClinica']) ? (int)$_POST['idClinica'] : 0; $nome = trim($_POST['nome'] ?? ''); $localidade = trim($_POST['localidade'] ?? ''); $cnpj = !empty(trim($_POST['cnpj'])) ? trim($_POST['cnpj']) : null; $return_page = 'clinica-edit-admin&id=' . $idClinica;
            if ($idClinica <= 0 || empty($nome) || empty($localidade)) { $_SESSION['error_message_form'] = 'Dados inválidos.'; header('Location: index.php?page=' . $return_page); exit; }
            try {
                $sql_update = "UPDATE Clinica SET nome = ?, localidade = ?, cnpj = ? WHERE idClinica = ?"; $stmt_update = $conn->prepare($sql_update);
                if (!$stmt_update) { throw new Exception("Erro BD (prep upd): " . $conn->error); } $stmt_update->bind_param("sssi", $nome, $localidade, $cnpj, $idClinica);
                if ($stmt_update->execute()) { $_SESSION['success_message'] = 'Clínica (ID: '.$idClinica.') atualizada!'; } else { throw new Exception("Erro ao salvar: " . $stmt_update->error); } $stmt_update->close();
                header('Location: index.php?page=clinica-listar'); exit;
            } catch (Exception $e) { error_log("ERR ADMIN EDIT CLINICA (ID: $idClinica): " . $e->getMessage()); $_SESSION['error_message_form'] = "Erro: " . $e->getMessage(); header('Location: index.php?page=' . $return_page); exit; }
            break; // Fim case 'edit_clinica_admin'
            

        // =========================================================================
        // AÇÃO: ADICIONAR MÉDICO À CLÍNICA (de cadastrar-medicos-clinica.php)
        // =========================================================================
        case 'add_medico_clinica':
            if (!isset($_SESSION['loggedin']) || $_SESSION['tipo_usuario'] !== 'admin_clinica') { $_SESSION['error_message'] = 'Acesso negado.'; header('Location: index.php?page=login'); exit; } $idAdminUsuario = $_SESSION['idUsuario'];
            $idClinica = isset($_POST['idClinica']) ? (int)$_POST['idClinica'] : 0; $idMedicoEspecialidade = isset($_POST['idMedicoEspecialidade']) ? (int)$_POST['idMedicoEspecialidade'] : 0; $return_page = $_POST['return_page'] ?? 'home';
            if ($idClinica <= 0 || $idMedicoEspecialidade <= 0) { $_SESSION['error_message'] = 'Dados inválidos.'; header('Location: index.php?page=' . $return_page); exit; }
            $sql_chk_o = "SELECT 1 FROM ClinicaUsuario WHERE fkidClinica = ? AND fkidUsuario = ?"; $stmt_chk_o = $conn->prepare($sql_chk_o); if(!$stmt_chk_o){ $_SESSION['error_message'] = 'Erro BD (chk own).'; error_log("Err prep chk own add med: ".$conn->error); header('Location: index.php?page='.$return_page); exit; } $stmt_chk_o->bind_param("ii", $idClinica, $idAdminUsuario); $stmt_chk_o->execute(); $res_o = $stmt_chk_o->get_result(); $stmt_chk_o->close(); if ($res_o->num_rows === 0) { $_SESSION['error_message'] = 'Sem permissão.'; header('Location: index.php?page=home'); exit; }
            $sql_chk_me = "SELECT 1 FROM Medico_Especialidade WHERE idMedico_Especialidade = ?"; $stmt_chk_me = $conn->prepare($sql_chk_me); if(!$stmt_chk_me){ $_SESSION['error_message'] = 'Erro BD (chk ME).'; error_log("Err prep chk ME add med: ".$conn->error); header('Location: index.php?page='.$return_page); exit; } $stmt_chk_me->bind_param("i", $idMedicoEspecialidade); $stmt_chk_me->execute(); $res_me = $stmt_chk_me->get_result(); $stmt_chk_me->close(); if ($res_me->num_rows === 0) { $_SESSION['error_message'] = 'Médico/Especialidade inválido.'; header('Location: index.php?page='.$return_page); exit; }
            $sql_chk_d = "SELECT 1 FROM Disponibilidade WHERE fkidClinica = ? AND fkidMededico_Especialidade = ?"; $stmt_chk_d = $conn->prepare($sql_chk_d); if(!$stmt_chk_d){ $_SESSION['error_message'] = 'Erro BD (chk disp).'; error_log("Err prep chk disp add med: ".$conn->error); header('Location: index.php?page='.$return_page); exit; } $stmt_chk_d->bind_param("ii", $idClinica, $idMedicoEspecialidade); $stmt_chk_d->execute(); $res_d = $stmt_chk_d->get_result(); $stmt_chk_d->close(); if ($res_d->num_rows > 0) { $_SESSION['info_message'] = 'Já associado.'; header('Location: index.php?page=' . $return_page); exit; }
            $sql_ins_d = "INSERT INTO Disponibilidade (fkidClinica, fkidMededico_Especialidade) VALUES (?, ?)"; $stmt_ins_d = $conn->prepare($sql_ins_d);
            if (!$stmt_ins_d) { $_SESSION['error_message'] = 'Erro BD (ins disp).'; error_log("Err prep ins disp add med: ".$conn->error); } else { $stmt_ins_d->bind_param("ii", $idClinica, $idMedicoEspecialidade); if ($stmt_ins_d->execute()) { $_SESSION['success_message'] = 'Associado com sucesso!'; } else { $_SESSION['error_message'] = 'Erro: ' . $stmt_ins_d->error; error_log('Err exec ins disp: '.$stmt_ins_d->error); } $stmt_ins_d->close(); }
            header('Location: index.php?page=' . $return_page); exit; break;


        // =========================================================================
        // AÇÃO: REMOVER MÉDICO DA CLÍNICA
        // =========================================================================
        case 'remove_medico_clinica':
            if (!isset($_SESSION['loggedin']) || $_SESSION['tipo_usuario'] !== 'admin_clinica') { $_SESSION['error_message'] = 'Acesso negado.'; header('Location: index.php?page=login'); exit; } $idAdminUsuario = $_SESSION['idUsuario'];
            $idDisponibilidade = isset($_POST['idDisponibilidade']) ? (int)$_POST['idDisponibilidade'] : 0; $idClinica_check = isset($_POST['idClinica']) ? (int)$_POST['idClinica'] : 0; $return_page = $_POST['return_page'] ?? 'home';
            if ($idDisponibilidade <= 0 || $idClinica_check <= 0) { $_SESSION['error_message'] = 'Dados inválidos.'; header('Location: index.php?page=' . $return_page); exit; }
            $sql_chk_do = "SELECT 1 FROM Disponibilidade d JOIN ClinicaUsuario cu ON d.fkidClinica = cu.fkidClinica WHERE d.idDisponibilidade = ? AND d.fkidClinica = ? AND cu.fkidUsuario = ?"; $stmt_chk_do = $conn->prepare($sql_chk_do); if(!$stmt_chk_do){ $_SESSION['error_message'] = 'Erro BD (chk own rem).'; error_log("Err prep chk own rem med: ".$conn->error); header('Location: index.php?page='.$return_page); exit; } $stmt_chk_do->bind_param("iii", $idDisponibilidade, $idClinica_check, $idAdminUsuario); $stmt_chk_do->execute(); $res_do = $stmt_chk_do->get_result(); $stmt_chk_do->close(); if ($res_do->num_rows === 0) { $_SESSION['error_message'] = 'Sem permissão.'; header('Location: index.php?page=home'); exit; }
            $conn->begin_transaction(); try {
                // Deletar Disponibilidade (Assume ON DELETE CASCADE para Dia_Hora_Disponivel -> ValorAtendimento -> Consulta)
                $sql_del_d = "DELETE FROM Disponibilidade WHERE idDisponibilidade = ?"; $stmt_del_d = $conn->prepare($sql_del_d);
                if (!$stmt_del_d) { throw new Exception("Err prep del disp: ".$conn->error); } $stmt_del_d->bind_param("i", $idDisponibilidade);
                if ($stmt_del_d->execute()) { if ($stmt_del_d->affected_rows > 0) { $_SESSION['success_message'] = 'Associação removida!'; } else { $_SESSION['info_message'] = 'Não encontrada.'; }} else { throw new Exception('Erro ao remover: ' . $stmt_del_d->error); } $stmt_del_d->close(); $conn->commit();
             } catch (mysqli_sql_exception $e) { $conn->rollback(); $err=$e->getMessage(); if ($e->getCode()==1451){$err="Não pode remover. Horários/Consultas dependentes existem.";} $_SESSION['error_message']='Erro: '.$err; error_log("ERR REM MED CLI (FK?): ".$e->getMessage());
             } catch (Exception $e) { $conn->rollback(); $_SESSION['error_message']='Erro geral: '.$e->getMessage(); error_log("ERR GEN REM MED CLI: ".$e->getMessage()); }
             header('Location: index.php?page=' . $return_page); exit; break;


        // =========================================================================
        // AÇÃO: MÉDICO ADICIONA HORÁRIO
        // =========================================================================
        case 'add_medico_horario':
            if (!isset($_SESSION['loggedin']) || $_SESSION['tipo_usuario'] !== 'medico') { $_SESSION['error_message'] = 'Acesso negado.'; header('Location: index.php?page=login'); exit; } $idUsuarioMedico = $_SESSION['idUsuario']; $return_page = $_POST['return_page'] ?? 'home';
            $fkidDisponibilidade = isset($_POST['fkidDisponibilidade']) ? (int)$_POST['fkidDisponibilidade'] : 0; $dia_semana = isset($_POST['dia_semana']) ? (int)$_POST['dia_semana'] : 0; $hora_inicio = $_POST['hora_inicio'] ?? ''; $hora_fim = $_POST['hora_fim'] ?? '';
            if ($fkidDisponibilidade <= 0 || $dia_semana < 1 || $dia_semana > 7 || empty($hora_inicio) || empty($hora_fim)) { $_SESSION['error_message'] = 'Dados inválidos.'; header('Location: index.php?page=' . $return_page); exit; }
            if (strtotime($hora_fim) <= strtotime($hora_inicio)) { $_SESSION['error_message'] = 'Hora fim deve ser após hora início.'; header('Location: index.php?page=' . $return_page); exit; }
            $conn->begin_transaction(); try {
                $sql_get_med = "SELECT me.fkidMedico, m.fkidUsuario FROM Disponibilidade d JOIN Medico_Especialidade me ON d.fkidMededico_Especialidade = me.idMedico_Especialidade JOIN Medico m ON me.fkidMedico = m.idMedico WHERE d.idDisponibilidade = ?"; $stmt_get_med = $conn->prepare($sql_get_med); if(!$stmt_get_med){ throw new Exception("Erro BD (get med): " . $conn->error); } $stmt_get_med->bind_param("i", $fkidDisponibilidade); $stmt_get_med->execute(); $res_med = $stmt_get_med->get_result(); if ($res_med->num_rows === 0) { throw new Exception("Associação não encontrada."); } $medico_info = $res_med->fetch_object(); $idMedico = (int)$medico_info->fkidMedico; $fkidUsuario_check = (int)$medico_info->fkidUsuario; $stmt_get_med->close();
                if ($fkidUsuario_check !== $idUsuarioMedico) { throw new Exception("Sem permissão."); }
                // VALIDAÇÃO DE SOBREPOSIÇÃO
                $sql_overlap = "SELECT dhd.idDia_Hora_Disponivel, c.nome AS nomeClinica, dhd.hora_inicio, dhd.hora_fim FROM Dia_Hora_Disponivel dhd JOIN Disponibilidade d ON dhd.fkidDisponibilidade = d.idDisponibilidade JOIN Medico_Especialidade me ON d.fkidMededico_Especialidade = me.idMedico_Especialidade JOIN Clinica c ON d.fkidClinica = c.idClinica WHERE me.fkidMedico = ? AND dhd.dia_semana = ? AND dhd.hora_inicio < ? AND dhd.hora_fim > ?"; $stmt_overlap = $conn->prepare($sql_overlap); if(!$stmt_overlap){ throw new Exception("Erro BD (check overlap): " . $conn->error); } $stmt_overlap->bind_param("iiss", $idMedico, $dia_semana, $hora_fim, $hora_inicio); $stmt_overlap->execute(); $res_overlap = $stmt_overlap->get_result();
                if ($res_overlap->num_rows > 0) { $conflito = $res_overlap->fetch_object(); if (!function_exists('getDiaSemanaNome')) { function getDiaSemanaNome($diaNum) { $dias=[1=>'Dom', 2=>'Seg', 3=>'Ter', 4=>'Qua', 5=>'Qui', 6=>'Sex', 7=>'Sáb']; return $dias[$diaNum] ?? '?'; } } $msg = sprintf("Conflito! Horário sobrepõe com '%s' (%s das %s às %s).", htmlspecialchars($conflito->nomeClinica), getDiaSemanaNome($dia_semana), date("H:i", strtotime($conflito->hora_inicio)), date("H:i", strtotime($conflito->hora_fim))); throw new Exception($msg); } $stmt_overlap->close();
                // Inserir Horário
                $sql_insert = "INSERT INTO Dia_Hora_Disponivel (fkidDisponibilidade, dia_semana, hora_inicio, hora_fim) VALUES (?, ?, ?, ?)"; $stmt_insert = $conn->prepare($sql_insert); if(!$stmt_insert){ throw new Exception("Erro BD (insert horario): " . $conn->error); } $stmt_insert->bind_param("iiss", $fkidDisponibilidade, $dia_semana, $hora_inicio, $hora_fim); if (!$stmt_insert->execute()) { if ($conn->errno == 1062) { throw new Exception("Este horário exato já está cadastrado."); } else { throw new Exception("Erro BD (exec insert horario): " . $stmt_insert->error); }} $stmt_insert->close(); $conn->commit(); $_SESSION['success_message'] = 'Novo horário adicionado!';
            } catch (Exception $e) { $conn->rollback(); error_log("ERR ADD MED HORARIO (User: $idUsuarioMedico): " . $e->getMessage()); $_SESSION['error_message'] = "Erro: " . $e->getMessage(); }
            header('Location: index.php?page=' . $return_page); exit; break;


        // =========================================================================
        // AÇÃO: MÉDICO DELETA HORÁRIO
        // =========================================================================
        case 'delete_medico_horario':
            if (!isset($_SESSION['loggedin']) || $_SESSION['tipo_usuario'] !== 'medico') { $_SESSION['error_message'] = 'Acesso negado.'; header('Location: index.php?page=login'); exit; } $idUsuarioMedico = $_SESSION['idUsuario']; $return_page = $_POST['return_page'] ?? 'home';
            $idDia_Hora_Disponivel = isset($_POST['idDia_Hora_Disponivel']) ? (int)$_POST['idDia_Hora_Disponivel'] : 0; if ($idDia_Hora_Disponivel <= 0) { $_SESSION['error_message'] = 'ID inválido.'; header('Location: index.php?page=' . $return_page); exit; }
            $conn->begin_transaction(); try {
                $sql_check_owner = "SELECT 1 FROM Dia_Hora_Disponivel dhd JOIN Disponibilidade d ON dhd.fkidDisponibilidade = d.idDisponibilidade JOIN Medico_Especialidade me ON d.fkidMededico_Especialidade = me.idMedico_Especialidade JOIN Medico m ON me.fkidMedico = m.idMedico WHERE dhd.idDia_Hora_Disponivel = ? AND m.fkidUsuario = ?"; $stmt_check = $conn->prepare($sql_check_owner); if(!$stmt_check){ throw new Exception("Erro BD (check owner del hor): " . $conn->error); } $stmt_check->bind_param("ii", $idDia_Hora_Disponivel, $idUsuarioMedico); $stmt_check->execute(); $res_check = $stmt_check->get_result(); $stmt_check->close(); if ($res_check->num_rows === 0) { throw new Exception("Permissão negada."); }
                $sql_delete = "DELETE FROM Dia_Hora_Disponivel WHERE idDia_Hora_Disponivel = ?"; $stmt_delete = $conn->prepare($sql_delete); if(!$stmt_delete){ throw new Exception("Erro BD (delete horario): " . $conn->error); } $stmt_delete->bind_param("i", $idDia_Hora_Disponivel); if (!$stmt_delete->execute()) { throw new Exception("Erro ao executar deleção: " . $stmt_delete->error); } $stmt_delete->close(); $conn->commit(); $_SESSION['success_message'] = 'Horário removido.';
            } catch (mysqli_sql_exception $e) { $conn->rollback(); $err = $e->getMessage(); if ($e->getCode() == 1451) { $err = "Não pode excluir. Consultas/Valores associados."; } $_SESSION['error_message'] = 'Erro: ' . $err; error_log("ERR DEL MED HORARIO (FK?): " . $e->getMessage());
            } catch (Exception $e) { $conn->rollback(); $_SESSION['error_message'] = 'Erro: ' . $e->getMessage(); error_log("ERR GEN DEL MED HORARIO: " . $e->getMessage()); }
            header('Location: index.php?page=' . $return_page); exit; break;


        // =========================================================================
        // AÇÃO: CLÍNICA SALVA VALORES/DURAÇÕES
        // =========================================================================
        case 'save_clinica_valores':
            if (!isset($_SESSION['loggedin']) || $_SESSION['tipo_usuario'] !== 'admin_clinica') { $_SESSION['error_message'] = 'Acesso negado.'; header('Location: index.php?page=login'); exit; } $idAdminUsuario = $_SESSION['idUsuario']; $idClinica = isset($_POST['idClinica']) ? (int)$_POST['idClinica'] : 0; $return_page = $_POST['return_page'] ?? 'home';
            $fkidDia_Hora_Disponivel_array = $_POST['fkidDia_Hora_Disponivel'] ?? []; $idValorAtendimento_array = $_POST['idValorAtendimento'] ?? []; $valor_array = $_POST['valor'] ?? []; $duracao_minutos_array = $_POST['duracao_minutos'] ?? [];
            if ($idClinica <= 0 || empty($fkidDia_Hora_Disponivel_array)) { $_SESSION['error_message'] = 'Dados inválidos.'; header('Location: index.php?page=' . $return_page); exit; }
            $sql_check_owner = "SELECT 1 FROM ClinicaUsuario WHERE fkidClinica = ? AND fkidUsuario = ?"; $stmt_check_owner = $conn->prepare($sql_check_owner); if(!$stmt_check_owner){ $_SESSION['error_message'] = 'Erro BD (chk own save val).'; error_log("Err prep chk own save val: ".$conn->error); header('Location: index.php?page='.$return_page); exit; } $stmt_check_owner->bind_param("ii", $idClinica, $idAdminUsuario); $stmt_check_owner->execute(); $res_o = $stmt_check_owner->get_result(); $stmt_check_owner->close(); if ($res_o->num_rows === 0) { $_SESSION['error_message'] = 'Sem permissão.'; header('Location: index.php?page=home'); exit; }
            $conn->begin_transaction(); try {
                $stmt_insert = $conn->prepare("INSERT INTO ValorAtendimento (fkidDia_Hora_Disponivel, valor, duracao_minutos) VALUES (?, ?, ?)");
                $stmt_update = $conn->prepare("UPDATE ValorAtendimento SET valor = ?, duracao_minutos = ? WHERE idValorAtendimento = ?");
                $stmt_delete = $conn->prepare("DELETE FROM ValorAtendimento WHERE idValorAtendimento = ?");
                $stmt_check_dhd_owner = $conn->prepare("SELECT 1 FROM Dia_Hora_Disponivel dhd JOIN Disponibilidade d ON dhd.fkidDisponibilidade = d.idDisponibilidade WHERE dhd.idDia_Hora_Disponivel = ? AND d.fkidClinica = ?");
                if (!$stmt_insert || !$stmt_update || !$stmt_delete || !$stmt_check_dhd_owner) { throw new Exception("Erro BD (prep stmts save val): " . $conn->error); } $erros = [];
                foreach ($fkidDia_Hora_Disponivel_array as $idDiaHora => $fkidDiaHora_val) {
                    $idDiaHora = (int)$idDiaHora; $fkidDiaHora = (int)$fkidDiaHora_val; $idValorAtual = isset($idValorAtendimento_array[$idDiaHora]) ? (int)$idValorAtendimento_array[$idDiaHora] : 0; $valor = isset($valor_array[$idDiaHora]) ? trim($valor_array[$idDiaHora]) : ''; $duracao = isset($duracao_minutos_array[$idDiaHora]) ? trim($duracao_minutos_array[$idDiaHora]) : '';
                    $stmt_check_dhd_owner->bind_param("ii", $idDiaHora, $idClinica); $stmt_check_dhd_owner->execute(); $res_dhd_owner = $stmt_check_dhd_owner->get_result(); if ($res_dhd_owner->num_rows === 0) { $erros[] = "Permissão negada (Horário ID $idDiaHora)."; continue; }
                    if ($valor !== '' && $duracao !== '') { $valor_float = (float)$valor; $duracao_int = (int)$duracao; if ($valor_float < 0 || $duracao_int <= 0) { $erros[] = "Horário $idDiaHora: Valor/Duração inválidos."; continue; } if ($idValorAtual > 0) { $stmt_update->bind_param("ddi", $valor_float, $duracao_int, $idValorAtual); if (!$stmt_update->execute()) { $erros[] = "Erro update Horário $idDiaHora: " . $stmt_update->error; } } else { $stmt_insert->bind_param("idi", $idDiaHora, $valor_float, $duracao_int); if (!$stmt_insert->execute()) { $erros[] = "Erro insert Horário $idDiaHora: " . $stmt_insert->error; } } }
                    elseif (($valor === '' || $duracao === '') && $idValorAtual > 0) { $stmt_delete->bind_param("i", $idValorAtual); if (!$stmt_delete->execute()) { $erros[] = "Erro delete Horário $idDiaHora: " . $stmt_delete->error; } }
                } $stmt_insert->close(); $stmt_update->close(); $stmt_delete->close(); $stmt_check_dhd_owner->close();
                if (!empty($erros)) { throw new Exception("Ocorreram erros: " . implode('; ', $erros)); }
                $conn->commit(); $_SESSION['success_message'] = 'Valores e durações salvos!';
            } catch (Exception $e) { $conn->rollback(); error_log("ERR SAVE CLINICA VALORES (Cl: $idClinica): " . $e->getMessage()); $_SESSION['error_message'] = "Erro: " . $e->getMessage(); }
            header('Location: index.php?page=' . $return_page); exit; break;


        // =========================================================================
        // AÇÃO: PACIENTE CRIA CONSULTA
        // =========================================================================
        case 'create_consulta':
            if (!isset($_SESSION['loggedin']) || $_SESSION['tipo_usuario'] !== 'paciente') { $_SESSION['error_message'] = 'Acesso negado.'; header('Location: index.php?page=login'); exit; } $idUsuarioPaciente = $_SESSION['idUsuario'];
            $fkidPaciente = isset($_POST['fkidPaciente']) ? (int)$_POST['fkidPaciente'] : 0; $fkidValorAtendimento = isset($_POST['fkidValorAtendimento']) ? (int)$_POST['fkidValorAtendimento'] : 0; $data_hora_agendada = $_POST['data_hora_agendada'] ?? '';
            $stmt_check_pac = $conn->prepare("SELECT 1 FROM paciente WHERE idPaciente = ? AND fkidUsuario = ?"); if(!$stmt_check_pac) { echo "<script>alert('Erro BD (chk pac).'); history.back();</script>"; exit; } $stmt_check_pac->bind_param("ii", $fkidPaciente, $idUsuarioPaciente); $stmt_check_pac->execute(); $res_check_pac = $stmt_check_pac->get_result(); $stmt_check_pac->close(); if ($res_check_pac->num_rows === 0) { echo "<script>alert('Erro validação paciente.'); location.href='index.php?page=logout';</script>"; exit; }
            if ($fkidPaciente <= 0 || $fkidValorAtendimento <= 0 || empty($data_hora_agendada) || strtotime($data_hora_agendada) === false) { echo "<script>alert('Dados de agendamento inválidos.'); history.back();</script>"; exit; }
           $conn->begin_transaction(); 
            try {
                // 1. Prepara a chamada da Procedure
                // Os '?' são obrigatórios, mas os valores reais vão na linha de baixo (bind_param)
                $sql_proc = "CALL AgendarConsulta(?, ?, ?)";
                
                $stmt_insert = $conn->prepare($sql_proc);
                if(!$stmt_insert) { 
                    throw new Exception("Erro ao preparar: " . $conn->error); 
                }

                // 2. AQUI ENVIAMOS OS DADOS REAIS
                // "iis" = Inteiro, Inteiro, String
                // $fkidPaciente, $fkidValorAtendimento, $data_hora_agendada
                $stmt_insert->bind_param("iis", $fkidPaciente, $fkidValorAtendimento, $data_hora_agendada);

                // 3. Executa a procedure
                if (!$stmt_insert->execute()) { 
                    throw new Exception("Erro ao agendar: " . $stmt_insert->error); 
                } 
                $stmt_insert->close();

                // Se a Trigger não bloqueou, confirma a gravação
                $conn->commit(); 
                $_SESSION['success_message'] = 'Consulta agendada com sucesso via Procedure!'; 
                header('Location: index.php?page=consultas-listar'); 
                exit;

            } catch (Exception $e) { 
                $conn->rollback(); // Cancela tudo se der erro
                
                // Verifica se o erro foi a nossa TRIGGER de data passada
                if (strpos($e->getMessage(), 'datas passadas') !== false) {
                    $_SESSION['error_message'] = "Erro: Você tentou agendar para uma data que já passou!";
                } else {
                    $_SESSION['error_message'] = "Erro no agendamento: " . $e->getMessage();
                }
                
                header('Location: index.php?page=agendamento-create'); 
                exit; 
            }
            break; // Fim case 'create_consulta'


        // =========================================================================
        // AÇÃO: MÉDICO ATUALIZA STATUS DA CONSULTA
        // =========================================================================
        case 'update_consulta_status_medico':
            if (!isset($_SESSION['loggedin']) || $_SESSION['tipo_usuario'] !== 'medico') { $_SESSION['error_message'] = 'Acesso negado.'; header('Location: index.php?page=login'); exit; } $idUsuarioMedico = $_SESSION['idUsuario'];
            $idConsulta = isset($_POST['idConsulta']) ? (int)$_POST['idConsulta'] : 0; $novo_status = $_POST['novo_status'] ?? ''; $return_page = $_POST['return_page'] ?? 'medico-agenda'; $valid_statuses = ['CONFIRMADO', 'EM_ANDAMENTO', 'REALIZADO', 'CANCELADO_MEDICO', 'NAO_COMPARECEU'];
            if ($idConsulta <= 0 || !in_array($novo_status, $valid_statuses)) { $_SESSION['error_message'] = 'Dados inválidos.'; header('Location: index.php?page=' . $return_page); exit; }
            $sql_check_owner = "SELECT 1 FROM Consulta c JOIN ValorAtendimento va ON c.fkidValorAtendimento = va.idValorAtendimento JOIN Dia_Hora_Disponivel dhd ON va.fkidDia_Hora_Disponivel = dhd.idDia_Hora_Disponivel JOIN Disponibilidade d ON dhd.fkidDisponibilidade = d.idDisponibilidade JOIN Medico_Especialidade me ON d.fkidMededico_Especialidade = me.idMedico_Especialidade JOIN Medico m ON me.fkidMedico = m.idMedico WHERE c.idConsulta = ? AND m.fkidUsuario = ?";
            $stmt_check = $conn->prepare($sql_check_owner); if(!$stmt_check){ $_SESSION['error_message'] = 'Erro BD (chk owner status).'; header('Location: index.php?page='.$return_page); exit; } $stmt_check->bind_param("ii", $idConsulta, $idUsuarioMedico); $stmt_check->execute(); $res_check = $stmt_check->get_result(); $stmt_check->close(); if ($res_check->num_rows === 0) { $_SESSION['error_message'] = 'Consulta não encontrada.'; header('Location: index.php?page=medico-agenda'); exit; }
            $sql_update = "UPDATE Consulta SET status = ? WHERE idConsulta = ?"; $stmt_update = $conn->prepare($sql_update);
            if(!$stmt_update){ $_SESSION['error_message'] = 'Erro BD (update status).'; error_log("Err prep upd status med: ".$conn->error); }
            else { $stmt_update->bind_param("si", $novo_status, $idConsulta); if ($stmt_update->execute()) { $_SESSION['success_message'] = 'Status atualizado: ' . $novo_status; } else { $_SESSION['error_message'] = 'Erro ao atualizar: ' . $stmt_update->error; } $stmt_update->close(); }
            header('Location: index.php?page=' . $return_page); exit; break;

            
        // =========================================================================
        // AÇÃO: MÉDICO ATUALIZA NOTAS DA CONSULTA
        // =========================================================================
        case 'update_consulta_notas':
            if (!isset($_SESSION['loggedin']) || $_SESSION['tipo_usuario'] !== 'medico') { $_SESSION['error_message'] = 'Acesso negado.'; header('Location: index.php?page=login'); exit; } $idUsuarioMedico = $_SESSION['idUsuario'];
            $idConsulta = isset($_POST['idConsulta']) ? (int)$_POST['idConsulta'] : 0; $observacoes = $_POST['observacoes_medico'] ?? null; $return_page = $_POST['return_page'] ?? 'medico-agenda';
            if ($idConsulta <= 0 || $observacoes === null) { $_SESSION['error_message'] = 'Dados inválidos.'; header('Location: index.php?page=' . $return_page); exit; }
            $sql_check_owner = "SELECT 1 FROM Consulta c JOIN ValorAtendimento va ON c.fkidValorAtendimento = va.idValorAtendimento JOIN Dia_Hora_Disponivel dhd ON va.fkidDia_Hora_Disponivel = dhd.idDia_Hora_Disponivel JOIN Disponibilidade d ON dhd.fkidDisponibilidade = d.idDisponibilidade JOIN Medico_Especialidade me ON d.fkidMededico_Especialidade = me.idMedico_Especialidade JOIN Medico m ON me.fkidMedico = m.idMedico WHERE c.idConsulta = ? AND m.fkidUsuario = ?";
            $stmt_check = $conn->prepare($sql_check_owner); if(!$stmt_check){ $_SESSION['error_message'] = 'Erro BD (chk owner notas).'; header('Location: index.php?page='.$return_page); exit; } $stmt_check->bind_param("ii", $idConsulta, $idUsuarioMedico); $stmt_check->execute(); $res_check = $stmt_check->get_result(); $stmt_check->close(); if ($res_check->num_rows === 0) { $_SESSION['error_message'] = 'Consulta não encontrada.'; header('Location: index.php?page=medico-agenda'); exit; }
           // --- INICIO DA SUBSTITUIÇÃO: Procedure SalvarProntuario ---
            $conn->begin_transaction();
            try {
                // Chama a PROCEDURE que você criou no banco
                // Ela contém a regra de segurança (IF status...)
                $sql_proc = "CALL SalvarProntuario(?, ?)";
                
                $stmt = $conn->prepare($sql_proc);
                if(!$stmt){ throw new Exception("Erro prep: ".$conn->error); }
                
                // i=inteiro (ID), s=string (Texto do prontuário)
                $stmt->bind_param("is", $idConsulta, $observacoes);
                
                if (!$stmt->execute()) {
                    // Se a Procedure bloquear, o erro vem pra cá
                    throw new Exception($stmt->error); 
                }
                $stmt->close();
                
                $conn->commit();
                $_SESSION['success_message'] = 'Prontuário salvo via Procedure com sucesso!';
                
            } catch (Exception $e) {
                $conn->rollback();
                // Limpa a mensagem de erro técnica do MySQL para ficar bonita
                $msgErro = $e->getMessage();
                if (strpos($msgErro, 'Só é possível editar') !== false) {
                    $msgErro = "Erro: Você só pode editar o prontuário de atendimentos em andamento ou realizados.";
                }
                $_SESSION['error_message'] = $msgErro;
            }
            // --- FIM DA SUBSTITUIÇÃO ---


        // =========================================================================
        // AÇÃO: PACIENTE CANCELA CONSULTA
        // =========================================================================
        case 'cancelar_consulta_paciente':
            if (!isset($_SESSION['loggedin']) || $_SESSION['tipo_usuario'] !== 'paciente') { $_SESSION['error_message'] = 'Acesso negado.'; header('Location: index.php?page=login'); exit; } $idUsuarioPaciente = $_SESSION['idUsuario'];
            $idConsulta = isset($_POST['idConsulta']) ? (int)$_POST['idConsulta'] : 0;
            if ($idConsulta <= 0) { $_SESSION['error_message'] = 'ID da consulta inválido.'; header('Location: index.php?page=consultas-listar'); exit; }
            $idPaciente = 0; $stmt_find_pac = $conn->prepare("SELECT idPaciente FROM paciente WHERE fkidUsuario = ?"); if($stmt_find_pac) { $stmt_find_pac->bind_param("i", $idUsuarioPaciente); $stmt_find_pac->execute(); $res_pac = $stmt_find_pac->get_result(); if($res_pac->num_rows > 0) { $idPaciente = (int)$res_pac->fetch_object()->idPaciente; } $stmt_find_pac->close(); } if ($idPaciente <= 0) { $_SESSION['error_message'] = 'Erro: Registro de paciente não encontrado.'; header('Location: index.php?page=consultas-listar'); exit; }
            $sql_check_owner = "SELECT 1 FROM Consulta WHERE idConsulta = ? AND fkidPaciente = ? AND status IN ('SOLICITADO', 'CONFIRMADO')"; $stmt_check = $conn->prepare($sql_check_owner);
            if(!$stmt_check){ $_SESSION['error_message'] = 'Erro BD (chk owner cancel).'; header('Location: index.php?page=consultas-listar'); exit; } $stmt_check->bind_param("ii", $idConsulta, $idPaciente); $stmt_check->execute(); $res_check = $stmt_check->get_result(); $stmt_check->close();
            if ($res_check->num_rows === 0) { $_SESSION['error_message'] = 'Não é possível cancelar esta consulta.'; header('Location: index.php?page=consultas-listar'); exit; }
            $sql_update = "UPDATE Consulta SET status = 'CANCELADO_PACIENTE' WHERE idConsulta = ? AND fkidPaciente = ?"; $stmt_update = $conn->prepare($sql_update);
            if(!$stmt_update){ $_SESSION['error_message'] = 'Erro BD (update cancel pac).'; }
            else { $stmt_update->bind_param("ii", $idConsulta, $idPaciente); if ($stmt_update->execute()) { $_SESSION['success_message'] = 'Consulta cancelada.'; } else { $_SESSION['error_message'] = 'Erro ao cancelar: ' . $stmt_update->error; } $stmt_update->close(); }
            header('Location: index.php?page=consultas-listar'); exit; break;


        // =========================================================================
        // AÇÃO: ADMIN CLÍNICA CANCELA CONSULTA
        // =========================================================================
        case 'cancelar_consulta_clinica':
            if (!isset($_SESSION['loggedin']) || $_SESSION['tipo_usuario'] !== 'admin_clinica') { $_SESSION['error_message'] = 'Acesso negado.'; header('Location: index.php?page=login'); exit; } $idAdminUsuario = $_SESSION['idUsuario']; $idClinicaSessao = $_SESSION['idClinica'] ?? 0;
            $idConsulta = isset($_POST['idConsulta']) ? (int)$_POST['idConsulta'] : 0; $return_page = $_POST['return_page'] ?? 'painel-clinica';
            if ($idConsulta <= 0 || $idClinicaSessao <= 0) { $_SESSION['error_message'] = 'Dados inválidos.'; header('Location: index.php?page=' . $return_page); exit; }
            $sql_check_owner = "SELECT 1 FROM Consulta c JOIN ValorAtendimento va ON c.fkidValorAtendimento = va.idValorAtendimento JOIN Dia_Hora_Disponivel dhd ON va.fkidDia_Hora_Disponivel = dhd.idDia_Hora_Disponivel JOIN Disponibilidade d ON dhd.fkidDisponibilidade = d.idDisponibilidade WHERE c.idConsulta = ? AND d.fkidClinica = ?";
            $stmt_check = $conn->prepare($sql_check_owner); if(!$stmt_check){ $_SESSION['error_message'] = 'Erro BD (chk owner cancel cli).'; header('Location: index.php?page='.$return_page); exit; } $stmt_check->bind_param("ii", $idConsulta, $idClinicaSessao); $stmt_check->execute(); $res_check = $stmt_check->get_result(); $stmt_check->close();
            if ($res_check->num_rows === 0) { $_SESSION['error_message'] = 'Consulta não pertence a esta clínica.'; header('Location: index.php?page='.$return_page); exit; }
            $sql_update = "UPDATE Consulta SET status = 'CANCELADO_MEDICO' WHERE idConsulta = ? AND status IN ('SOLICITADO', 'CONFIRMADO', 'EM_ANDAMENTO')"; $stmt_update = $conn->prepare($sql_update);
            if(!$stmt_update){ $_SESSION['error_message'] = 'Erro BD (update cancel cli).'; }
            else { $stmt_update->bind_param("i", $idConsulta); if ($stmt_update->execute()) { if ($stmt_update->affected_rows > 0) { $_SESSION['success_message'] = 'Consulta cancelada pela clínica.'; } else { $_SESSION['info_message'] = 'Não foi possível cancelar (status inválido).'; }} else { $_SESSION['error_message'] = 'Erro ao cancelar: ' . $stmt_update->error; } $stmt_update->close(); }
            header('Location: index.php?page=' . $return_page); exit; break;


        // =========================================================================
        // AÇÃO: ADMIN DELETA CLÍNICA
        // =========================================================================
        case 'delete_clinica_admin':
            if (!isset($_SESSION['loggedin']) || $_SESSION['tipo_usuario'] !== 'admin') { $_SESSION['error_message'] = 'Acesso negado.'; header('Location: index.php?page=login'); exit; }
            $idClinica = isset($_POST['idClinica']) ? (int)$_POST['idClinica'] : 0; if ($idClinica <= 0) { $_SESSION['error_message'] = 'ID inválido.'; header('Location: index.php?page=clinica-listar'); exit; }
            $conn->begin_transaction(); try {
                // Deleta Consultas primeiro (pois elas têm RESTRICT em ValorAtendimento)
                $sql_find_va = "SELECT va.idValorAtendimento FROM ValorAtendimento va JOIN Dia_Hora_Disponivel dhd ON va.fkidDia_Hora_Disponivel = dhd.idDia_Hora_Disponivel JOIN Disponibilidade d ON dhd.fkidDisponibilidade = d.idDisponibilidade WHERE d.fkidClinica = ?";
                $stmt_find_va = $conn->prepare($sql_find_va); if(!$stmt_find_va){ throw new Exception("Erro BD (find VA): ".$conn->error); } $stmt_find_va->bind_param("i", $idClinica); $stmt_find_va->execute(); $res_va = $stmt_find_va->get_result(); $va_ids = []; while($row_va = $res_va->fetch_assoc()) { $va_ids[] = $row_va['idValorAtendimento']; } $stmt_find_va->close();
                if(!empty($va_ids)) { $placeholders_va = implode(',', array_fill(0, count($va_ids), '?')); $types_va = str_repeat('i', count($va_ids)); $stmt_del_con = $conn->prepare("DELETE FROM Consulta WHERE fkidValorAtendimento IN ($placeholders_va)"); if($stmt_del_con) { $stmt_del_con->bind_param($types_va, ...$va_ids); $stmt_del_con->execute(); $stmt_del_con->close(); } }
                // Deleta a clínica (CASCADE deve cuidar de ClinicaUsuario, Disponibilidade, Dia_Hora_Disponivel, ValorAtendimento)
                $sql_delete = "DELETE FROM Clinica WHERE idClinica = ?"; $stmt_delete = $conn->prepare($sql_delete);
                if (!$stmt_delete) { throw new Exception("Erro BD (prepare delete): " . $conn->error); } $stmt_delete->bind_param("i", $idClinica);
                if (!$stmt_delete->execute()) { throw new Exception($stmt_delete->error); } if ($stmt_delete->affected_rows === 0) { throw new Exception("Clínica (ID: $idClinica) não encontrada."); } $stmt_delete->close();
                $conn->commit(); $_SESSION['success_message'] = 'Clínica (ID: '.$idClinica.') e dados associados (consultas, horários) removidos.';
            } catch (mysqli_sql_exception $e) { $conn->rollback(); $err = $e->getMessage(); if ($e->getCode() == 1451) { $err = "Não foi possível excluir. Verifique dependências (FK Restrict)."; } $_SESSION['error_message'] = 'Erro: ' . $err; error_log("ERR ADMIN DEL CLINICA (FK?): " . $e->getMessage());
            } catch (Exception $e) { $conn->rollback(); $_SESSION['error_message'] = 'Erro: ' . $e->getMessage(); error_log("ERR GEN ADMIN DEL CLINICA: " . $e->getMessage()); }
            header('Location: index.php?page=clinica-listar'); exit; break;


        // =========================================================================
        // AÇÃO: ADMIN ADICIONA ESPECIALIDADE
        // =========================================================================
        case 'add_especialidade':
            if (!isset($_SESSION['loggedin']) || $_SESSION['tipo_usuario'] !== 'admin') { $_SESSION['error_message'] = 'Acesso negado.'; header('Location: index.php?page=login'); exit; }
            $nome = trim($_POST['nome'] ?? ''); if (empty($nome)) { $_SESSION['error_message'] = 'Nome não pode ser vazio.'; header('Location: index.php?page=especialidades-admin'); exit; }
            try {
                $sql_insert = "INSERT INTO Especialidade (nome) VALUES (?)"; $stmt_insert = $conn->prepare($sql_insert);
                if (!$stmt_insert) { throw new Exception($conn->error); } $stmt_insert->bind_param("s", $nome);
                if ($stmt_insert->execute()) { $_SESSION['success_message'] = 'Especialidade "' . htmlspecialchars($nome) . '" adicionada.'; }
                else { if ($conn->errno == 1062) { throw new Exception("Especialidade '" . htmlspecialchars($nome) . "' já existe."); } else { throw new Exception($stmt_insert->error); }}
                $stmt_insert->close();
            } catch (Exception $e) { $_SESSION['error_message'] = 'Erro: ' . $e->getMessage(); }
            header('Location: index.php?page=especialidades-admin'); exit; break;


        // =========================================================================
        // AÇÃO: ADMIN DELETA ESPECIALIDADE
        // =========================================================================
        case 'delete_especialidade':
            if (!isset($_SESSION['loggedin']) || $_SESSION['tipo_usuario'] !== 'admin') { $_SESSION['error_message'] = 'Acesso negado.'; header('Location: index.php?page=login'); exit; }
            $idEspecialidade = isset($_POST['idEspecialidade']) ? (int)$_POST['idEspecialidade'] : 0; if ($idEspecialidade <= 0) { $_SESSION['error_message'] = 'ID inválido.'; header('Location: index.php?page=especialidades-admin'); exit; }
            try {
                $sql_delete = "DELETE FROM Especialidade WHERE idEspecialidade = ?"; $stmt_delete = $conn->prepare($sql_delete);
                if (!$stmt_delete) { throw new Exception($conn->error); } $stmt_delete->bind_param("i", $idEspecialidade);
                if ($stmt_delete->execute()) { $_SESSION['success_message'] = 'Especialidade removida.'; } else { throw new Exception($stmt_delete->error); }
                $stmt_delete->close();
            } catch (mysqli_sql_exception $e) { $err = $e->getMessage(); if ($e->getCode() == 1451) { $err = "Não pode excluir. Médicos associados."; } $_SESSION['error_message'] = 'Erro: ' . $err; error_log("ERR ADMIN DEL ESP (FK?): " . $e->getMessage());
            } catch (Exception $e) { $_SESSION['error_message'] = 'Erro: ' . $e->getMessage(); }
            header('Location: index.php?page=especialidades-admin'); exit; break;


        // =========================================================================
        // AÇÕES DE AGENDAMENTO (OBSOLETAS)
        // =========================================================================
        case 'create_agendamento': // Obsoleto
        case 'edit_agendamento': // Obsoleto
        case 'delete_agendamento': // Obsoleto
            echo "<script>alert('ERRO: Funcionalidade desatualizada.'); history.back();</script>";
            exit;
            break;

        // =========================================================================
        // AÇÃO PADRÃO
        // =========================================================================
        default:
            // Ação desconhecida
            echo "<script>alert('Ação desconhecida: " . htmlspecialchars($acao) . "'); location.href='index.php';</script>";
            break;
    } // Fim do switch($acao)

} else {
    // Acesso direto ao acoes.php (sem POST)
     header('Location: index.php');
     exit;
} // Fim if (isset($_POST['acao']))

// Fecha a conexão
if (isset($conn) && $conn instanceof mysqli) {
    $conn->close();
}
?>