<h2>Agendar Consulta</h2>

<form action="acoes.php" method="POST">
    <div class="form-floating mb-3">
        <select class="form-select" id="profissional" name="profissional" required>
            <option value="">-- Selecione --</option>
            <option value="Psicólogo">Psicólogo</option>
            <option value="Clínico Geral">Clínico Geral</option>
            <option value="Nutricionista">Nutricionista</option>
            
        </select>
        <label for="profissional">Profissional de saúde</label>
    </div>

    <div class="form-floating mb-3">
        <input type="date" class="form-control" id="data" name="data" placeholder="Data da consulta" required>
        <label for="data">Data</label>
    </div>

    <div class="form-floating mb-3">
        <select class="form-select" id="hora" name="hora" required>
            <option value="">-- Selecione um horário --</option>
            <option value="08:00">08:00</option>
            <option value="09:00">09:00</option>
            <option value="10:00">10:00</option>
            <option value="11:00">11:00</option>
            <option value="14:00">14:00</option>
            <option value="15:00">15:00</option>
            <option value="16:00">16:00</option>
            <option value="17:00">17:00</option>
            <option value="18:00">18:00</option>
            <option value="19:00">19:00</option>
            <option value="20:00">20:00</option>
        </select>
        <label for="hora">Hora</label>
    </div>

    <input type="submit" name="create_agendamento" class="btn btn-primary" value="Agendar">
</form>

<div class="col-md-6 mt-3">
    <a href="?page=agendamento-listar" class="btn btn-secondary">Voltar para Listagem</a>
</div>
