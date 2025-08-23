<?php
session_start();
require 'conexao.php';

// USUÁRIO - CRIAR
if (isset($_POST['create_usuario'])) {
    $Nome = $_POST['nome'];
    $Email = $_POST['email'];
    $Senha = $_POST['senha'];
    $tipo = $_POST['tipo']; // 'medico' ou 'paciente'

    if (empty($Senha)) {
        echo "<script>alert('A senha é obrigatória.'); location.href='?page=usuario-create';</script>";
        exit;
    }

    // Definindo os valores de fkidmedico e fkidpaciente com base no tipo
    if ($tipo === 'medico') {
        $fkidmedico = 1;
        $fkidpaciente = 0;
    } elseif ($tipo === 'paciente') {
        $fkidmedico = 0;
        $fkidpaciente = 1;
    } else {
        // Caso tipo inválido
        echo "<script>alert('Tipo de usuário inválido.'); location.href='?page=usuario-create';</script>";
        exit;
    }

    $hashedSenha = password_hash($Senha, PASSWORD_DEFAULT);

    $stmt = $conn->prepare("INSERT INTO usuario (Nome, Email, senha, fkidmedico, fkidpaciente) VALUES (?, ?, ?, ?, ?)");
    $stmt->bind_param("sssii", $Nome, $Email, $hashedSenha, $fkidmedico, $fkidpaciente);

    if ($stmt->execute()) {
        header('Location: index.php?page=usuario-listar');
    } else {
        echo "<script>alert('Erro ao cadastrar usuário.'); location.href='?page=usuario-create';</script>";
    }
    exit;
}

// USUÁRIO - EDITAR
if (isset($_POST['edit_usuario'])) {
    $id = $_POST['idusuario'];
    $nome = $_POST['nome'];
    $email = $_POST['email'];
    $telefone = $_POST['telefone'] ?? '';
    $sexo = $_POST['sexo'];
    $senha = $_POST['senha'];
    $tipo = $_POST['tipo'];

    $fkidmedico = $tipo == 'medico' ? $id : null;
    $fkidpaciente = $tipo == 'paciente' ? $id : null;

    if (!empty($senha)) {
        $stmt = $conn->prepare("UPDATE usuario SET Nome=?, Email=?, Sexo=?, Senha=?, fkidmedico=?, fkidpaciente=? WHERE idUsuario=?");
        $stmt->bind_param("ssisiii", $nome, $email, $sexo, $senha, $fkidmedico, $fkidpaciente, $id);
    } else {
        $stmt = $conn->prepare("UPDATE usuario SET Nome=?, Email=?, Sexo=?, fkidmedico=?, fkidpaciente=? WHERE idUsuario=?");
        $stmt->bind_param("ssiiii", $nome, $email, $sexo, $fkidmedico, $fkidpaciente, $id);
    }

    $stmt->execute();
    header('Location: index.php?page=usuario-listar');
    exit;
}


// USUÁRIO - DELETAR
if (isset($_POST['delete_usuario'])) {
    $idusuario = $_POST['delete_usuario'];
    $stmt = $conn->prepare("DELETE FROM usuario WHERE idUsuario = ?");
    $stmt->bind_param("i", $idusuario);
    $stmt->execute();
    header('Location: index.php?page=usuario-listar');
    exit;
}

// AGENDAMENTO - CRIAR
if (isset($_POST['create_agendamento'])) {
    $profissional = $_POST['profissional'];
    $data = $_POST['data'];
    $hora = $_POST['hora'];

    $stmt = $conn->prepare("INSERT INTO agendamento (profissional, data, hora) VALUES (?, ?, ?)");
    $stmt->bind_param("sss", $profissional, $data, $hora);

    if ($stmt->execute()) {
        header("Location: index.php?page=agendamento-listar");
    } else {
        echo "<script>alert('Erro ao salvar agendamento.'); history.back();</script>";
    }
    exit;
}

// AGENDAMENTO - EDITAR
if (isset($_POST["edit_agendamento"])) {
    $id = (int) $_POST["idagendamento"];
    $profissional = $_POST["profissional"];
    $data = $_POST["data"];
    $hora = $_POST["hora"];

    $stmt = $conn->prepare("UPDATE agendamento SET profissional=?, data=?, hora=? WHERE idagendamento=?");
    $stmt->bind_param("sssi", $profissional, $data, $hora, $id);

    if ($stmt->execute()) {
        header('Location: index.php?page=agendamento-listar');
    } else {
        echo "<script>alert('Erro ao atualizar agendamento.'); history.back();</script>";
    }
    exit;
}

// AGENDAMENTO - DELETAR
if (isset($_POST['delete_agendamento'])) {
    $idagendamento = (int) $_POST['delete_agendamento'];
    $stmt = $conn->prepare("DELETE FROM agendamento WHERE idagendamento = ?");
    $stmt->bind_param("i", $idagendamento);
    $stmt->execute();
    header('Location: index.php?page=agendamento-listar');
    exit;
}
// ATENDIMENTO - CRIAR
if (isset($_POST['create_atendimento'])) {
    $data_atendimento = $_POST['data_atendimento'];
    $observacoes = $_POST['observacoes'];
    $fkidagendamento = $_POST['fkidagendamento'];
    $fkidmedico = $_POST['fkidmedico'];
    $fkidpaciente = $_POST['fkidpaciente'];

    if (empty($data_atendimento) || empty($fkidagendamento) || empty($fkidmedico) || empty($fkidpaciente)) {
        echo "<script>alert('Preencha todos os campos obrigatórios.'); history.back();</script>";
        exit;
    }

    $stmt = $conn->prepare("
        INSERT INTO atendimento 
        (data_atendimento, observacoes, fkidagendamento, fkidmedico, fkidpaciente) 
        VALUES (?, ?, ?, ?, ?)
    ");

    $stmt->bind_param("ssiii", $data_atendimento, $observacoes, $fkidagendamento, $fkidmedico, $fkidpaciente);

    if ($stmt->execute()) {
        echo "<script>alert('Atendimento registrado com sucesso!'); location.href='index.php?page=atendimento-listar';</script>";
    } else {
        $erro = addslashes($stmt->error);
        echo "<script>alert('Erro ao registrar atendimento: {$erro}'); history.back();</script>";
    }

    exit;
}

// ATENDIMENTO - EDITAR
if (isset($_POST['edit_atendimento'])) {
    require 'conexao.php';

    $idatendimento = intval($_POST['idatendimento']);
    $fkidagendamento = intval($_POST['fkidagendamento']);
    $fkidmedico = intval($_POST['fkidmedico']);
    $fkidpaciente = intval($_POST['fkidpaciente']);
    $data_atendimento = $_POST['data_atendimento']; // datetime-local já vem no formato ISO
    $observacoes = $_POST['observacoes'];

    // Prepare a query UPDATE
    $stmt = $conn->prepare("UPDATE atendimento SET fkidagendamento = ?, fkidmedico = ?, fkidpaciente = ?, data_atendimento = ?, observacoes = ? WHERE idatendimento = ?");
    if ($stmt === false) {
        die('Erro na preparação: ' . $conn->error);
    }

    $stmt->bind_param("iisssi", $fkidagendamento, $fkidmedico, $fkidpaciente, $data_atendimento, $observacoes, $idatendimento);

    if ($stmt->execute()) {
        // Redirecionar para lista com mensagem de sucesso
        header("Location: atendimento-listar.php?msg=editado");
        exit;
    } else {
        echo "Erro ao atualizar atendimento: " . $stmt->error;
    }

    $stmt->close();
    $conn->close();
}


// ATENDIMENTO - DELETAR

if (isset($_POST['delete_atendimento'])) {
    $idatendimento = intval($_POST['delete_atendimento']);

    // Primeiro, buscar o id do agendamento vinculado a esse atendimento
    $stmt = $conn->prepare("SELECT fkidagendamento FROM atendimento WHERE idatendimento = ?");
    $stmt->bind_param("i", $idatendimento);
    $stmt->execute();
    $stmt->bind_result($fkidagendamento);
    $stmt->fetch();
    $stmt->close();

    if ($fkidagendamento) {
        // Deletar o atendimento
        $stmt = $conn->prepare("DELETE FROM atendimento WHERE idatendimento = ?");
        $stmt->bind_param("i", $idatendimento);
        $stmt->execute();
        $stmt->close();

        // Verificar se existem outros atendimentos vinculados a esse agendamento
        $stmt = $conn->prepare("SELECT COUNT(*) FROM atendimento WHERE fkidagendamento = ?");
        $stmt->bind_param("i", $fkidagendamento);
        $stmt->execute();
        $stmt->bind_result($count);
        $stmt->fetch();
        $stmt->close();

        // Se não existir mais nenhum atendimento vinculado, deletar o agendamento
        if ($count == 0) {
            $stmt = $conn->prepare("DELETE FROM agendamento WHERE idagendamento = ?");
            $stmt->bind_param("i", $fkidagendamento);
            $stmt->execute();
            $stmt->close();
        }
    }

    // Redirecionar após a operação
    header('Location: index.php?page=atendimento-listar');
    exit;
}
?>
