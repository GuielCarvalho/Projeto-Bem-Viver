<?php
// C:\xampp\htdocs\PBV\usuario-create.php - MODIFICADO (Dropdowns + Checkbox Especialidades)

if (!isset($conn)) { require_once('conexao.php'); } // Garante conexão
?>

<div class="card mt-4 shadow-lg">
    <div class="card-header bg-primary text-white">
        <h4 class="mb-0"><i class="bi bi-person-plus-fill me-2"></i> Criar Sua Conta Bem Viver</h4>
    </div>
    <div class="card-body">
        <form id="cadastroForm" action="acoes.php" method="POST">
            <input type="hidden" name="acao" value="create_usuario">

            <div class="mb-4">
                <label for="tipo" class="form-label text-primary fw-bold"><i class="bi bi-person-badge"></i> 1. Tipo de Conta</label>
                <select class="form-select" id="tipo" name="tipo_usuario" required onchange="toggleFields()">
                    <option value="">-- Selecione --</option>
                    <option value="medico">Médico (Oferecer consultas)</option>
                    <option value="paciente">Paciente (Agendar consultas)</option>
                    <option value="admin_clinica">Administrador (Cadastrar minha clínica)</option>
                </select>
            </div>
            <hr>

            <h5 class="mt-4 text-secondary"><i class="bi bi-person-lines-fill"></i> 2. Dados Pessoais e Acesso</h5>
            <div class="row g-3 p-3 mb-4 border rounded bg-light">
                 <div class="col-md-6"> <label for="nome" class="form-label">Nome Completo</label> <input type="text" id="nome" name="nome" class="form-control" placeholder="Seu nome" required> </div>
                 <div class="col-md-6"> <label for="email" class="form-label">Email</label> <input type="email" id="email" name="email" class="form-control" placeholder="seu.email@dominio.com" required> </div>
                 <div class="col-md-6"> <label for="senha" class="form-label">Senha</label> <input type="password" id="senha" name="senha" class="form-control" required> </div>
                 <div class="col-md-6"> <label for="telefone" class="form-label">Telefone</label> <input type="tel" id="telefone" name="telefone" class="form-control" placeholder="(98) 9XXXX-XXXX"> </div>
                 <div class="col-md-6"> <label for="data_nascimento" class="form-label">Data de Nascimento</label> <input type="date" id="data_nascimento" name="data_nascimento" class="form-control"> </div>
                 <div class="col-md-6"> <label for="sexo" class="form-label">Sexo</label> <select class="form-select" id="sexo" name="sexo"> <option value="">-- N/I --</option> <option value="1">Masculino</option> <option value="2">Feminino</option> <option value="3">Outro</option> </select> </div>
            </div>

            <div id="clinica-fields" style="display: none;" class="mt-4 p-4 border border-success rounded bg-light">
                 <h5 class="text-success"><i class="bi bi-building"></i> 3. Informações da Clínica</h5>
                 <div class="row g-3"> <div class="col-md-12"> <label for="nome_clinica" class="form-label">Nome da Clínica</label> <input type="text" id="nome_clinica" name="nome_clinica" class="form-control" placeholder="Ex: Clínica Bem Estar"> </div> <div class="col-md-12"> <label for="localidade_clinica" class="form-label">Localidade</label> <input type="text" id="localidade_clinica" name="localidade_clinica" class="form-control" placeholder="Ex: Av. dos Holandeses, 100"> </div> <div class="col-md-6"> <label for="cnpj_clinica" class="form-label">CNPJ (Opcional)</label> <input type="text" id="cnpj_clinica" name="cnpj_clinica" class="form-control" placeholder="00.000.000/0000-00"> </div> </div>
            </div>

            <div id="medico-fields" style="display: none;" class="mt-4 p-4 border border-info rounded bg-light">
                <h5 class="text-info"><i class="bi bi-hospital"></i> 3. Informações de Médico</h5>
                <div class="row g-3">
                    <div class="col-md-6">
                        <label for="crm" class="form-label">CRM</label>
                        <input type="text" id="crm" name="crm" class="form-control" placeholder="CRM/UF">
                        <small class="text-muted">Campo obrigatório.</small>
                    </div>

                    <?php
                        // --- Busca especialidades UMA VEZ para ambos os dropdowns ---
                        $especialidades_options = '';
                        $sql_esp_list = "SELECT idEspecialidade, nome FROM Especialidade ORDER BY nome";
                        $result_esp_list = $conn->query($sql_esp_list);
                        if ($result_esp_list && $result_esp_list->num_rows > 0) {
                            while ($esp = $result_esp_list->fetch_object()) {
                                $especialidades_options .= "<option value='" . htmlspecialchars($esp->idEspecialidade) . "'>" . htmlspecialchars($esp->nome) . "</option>";
                            }
                        } else {
                             $especialidades_options = "<option value='' disabled>Nenhuma especialidade cadastrada</option>";
                        }
                    ?>

                    <div class="col-md-6">
                        <label for="especialidade_id_1" class="form-label fw-bold">Especialidade Principal</label>
                        <select class="form-select" id="especialidade_id_1" name="especialidade_id_1">
                            <option value="">-- Selecione --</option>
                            <?= $especialidades_options ?> 
                        </select>
                        <small class="text-muted">Campo obrigatório.</small>
                    </div>

                    <div class="col-12 mt-3">
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" id="add_second_specialty" name="add_second_specialty" value="1" onchange="toggleSecondSpecialty()">
                            <label class="form-check-label" for="add_second_specialty">
                                Possui uma segunda especialidade?
                            </label>
                        </div>
                    </div>

                    <div id="second-specialty-fields" class="col-md-6" style="display: none;">
                        <label for="especialidade_id_2" class="form-label fw-bold">Segunda Especialidade</label>
                        <select class="form-select" id="especialidade_id_2" name="especialidade_id_2">
                             <option value="">-- Selecione --</option>
                            <?= $especialidades_options ?> 
                        </select>
                        <small class="text-muted">Obrigatório se a caixa acima for marcada.</small>
                    </div>

                    <div class="col-12 mt-3">
                        <label for="biografia" class="form-label">Biografia (Opcional)</label>
                        <textarea id="biografia" name="biografia" class="form-control" rows="3" placeholder="Sua experiência..."></textarea>
                    </div>
                </div>
            </div>

            <div id="paciente-fields" style="display: none;" class="mt-4 p-4 border border-primary rounded bg-light">
                <h5 class="text-primary"><i class="bi bi-file-person"></i> 3. Informações de Paciente</h5>
                 <div class="row g-3"> <div class="col-md-6"> <label for="cpf" class="form-label">CPF</label> <input type="text" id="cpf" name="cpf" class="form-control" placeholder="000.000.000-00"> </div> </div>
            </div>

            <div class="mt-4"> <button type="submit" class="btn btn-primary btn-lg w-100">Cadastrar</button> </div>
            <div class="text-center mt-3"> <a href="?page=login" class="text-secondary small">Já tenho conta. Login</a> </div>
        </form>
    </div>
</div>

<script>
    function toggleFields() {
        var tipoSelect = document.getElementById('tipo'); if (!tipoSelect) return;
        var tipo = tipoSelect.value;
        var medicoFields = document.getElementById('medico-fields');
        var pacienteFields = document.getElementById('paciente-fields');
        var clinicaFields = document.getElementById('clinica-fields');
        var secondSpecialtyDiv = document.getElementById('second-specialty-fields');
        var addSpecialtyCheckbox = document.getElementById('add_second_specialty');

        // Inputs required
        var crmInput = document.getElementById('crm');
        var especialidade1Select = document.getElementById('especialidade_id_1');
        var especialidade2Select = document.getElementById('especialidade_id_2');
        var cpfInput = document.getElementById('cpf');
        var nomeClinicaInput = document.getElementById('nome_clinica');
        var localidadeClinicaInput = document.getElementById('localidade_clinica');

        // Reseta required
        if(crmInput) crmInput.required = false;
        if(especialidade1Select) especialidade1Select.required = false;
        if(especialidade2Select) especialidade2Select.required = false;
        if(cpfInput) cpfInput.required = false;
        if(nomeClinicaInput) nomeClinicaInput.required = false;
        if(localidadeClinicaInput) localidadeClinicaInput.required = false;

        // Esconde blocos
        if(medicoFields) medicoFields.style.display = 'none';
        if(pacienteFields) pacienteFields.style.display = 'none';
        if(clinicaFields) clinicaFields.style.display = 'none';
        if(secondSpecialtyDiv) secondSpecialtyDiv.style.display = 'none'; // Garante que o segundo select esconda
        if(addSpecialtyCheckbox) addSpecialtyCheckbox.checked = false; // Desmarca o checkbox

        // Mostra bloco e define required
        if (tipo === 'medico') {
            if(medicoFields) medicoFields.style.display = 'block';
            if(crmInput) crmInput.required = true;
            if(especialidade1Select) especialidade1Select.required = true; // Principal é sempre required para médico
            // A segunda especialidade será required pelo toggleSecondSpecialty se o checkbox for marcado
        } else if (tipo === 'paciente') {
            if(pacienteFields) pacienteFields.style.display = 'block';
            if(cpfInput) cpfInput.required = true;
        } else if (tipo === 'admin_clinica') {
            if(clinicaFields) clinicaFields.style.display = 'block';
            if(nomeClinicaInput) nomeClinicaInput.required = true;
            if(localidadeClinicaInput) localidadeClinicaInput.required = true;
        }
    }

    // Função para mostrar/esconder e tornar obrigatório o segundo select
    function toggleSecondSpecialty() {
        var checkbox = document.getElementById('add_second_specialty');
        var secondSpecialtyDiv = document.getElementById('second-specialty-fields');
        var secondSpecialtySelect = document.getElementById('especialidade_id_2');

        if (!checkbox || !secondSpecialtyDiv || !secondSpecialtySelect) return; // Sai se elementos não existem

        if (checkbox.checked) {
            secondSpecialtyDiv.style.display = 'block';
            secondSpecialtySelect.required = true;
        } else {
            secondSpecialtyDiv.style.display = 'none';
            secondSpecialtySelect.required = false;
            secondSpecialtySelect.value = ''; // Limpa seleção se desmarcado
        }
    }

    // Garante que a função rode quando a página carregar
    document.addEventListener('DOMContentLoaded', toggleFields);
</script>