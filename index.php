<!DOCTYPE html>
<html lang="pt-br">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title>BemViver - Plataforma de Terapia Online</title>

  <!-- Bootstrap CSS -->
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet" />
  <!-- Bootstrap Icons -->
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css" rel="stylesheet" />

  <style>
    body {
      min-height: 100vh;
      display: flex;
      flex-direction: column;
    }
    main {
      flex: 1;
    }
  </style>
</head>
<body>

  
<?php
            include('navbar.php');
          ?>
  <main style="margin-top: 70px;">

  </mai
  <div class="container-fluid">
        <div class="container mt-4">
          <div class="row">
            <div class="col mt-12">
              <div class="card">
                <div class="card-header">
    <?php
            print(@$_REQUEST["page"]);
                    switch(@$_REQUEST["page"]){
                      case "usuario-listar":
                        include('usuario-listar.php');
                        break;
                      case "usuario-create":
                        include('usuario-create.php');
                        break;
                       case "usuario-editar":
                         include('usuario-edit.php');
                         break;
                       case "agendamento-listar":
                        include('agendamento-listar.php');
                        break;
                       case "agendamento-create":
                        include('agendamento-create.php');
                        break;  
                       case "agendamento-editar":
                         include('agendamento-edit.php');
                         break;
                       case "atendimento-listar":
                         include('atendimento-listar.php');
                          break;
                       case "atendimento-create":
                          include('atendimento-create.php');
                          break;
                        case "atendimento-edit":
                          include('atendimento-edit.php');
                           break;
                        default:
                           include('home.php');
                           break;

                    }
    ?>
</div>  
              </div>
            </div>
          </div>
        </div>
      </div>
  </main>
<footer class="bg-dark text-white text-center py-3">
<p cass="mb-0">© 2025 BemViver | Todos os direitos reservados</p>
</footer>

</body>
</html>
