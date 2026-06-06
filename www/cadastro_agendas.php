<?php
session_start();
require_once("conexao.php");// importar o conexao.php para esta página

if(!isset($_SESSION['cod_usuario'])){
    header("Location: login.php");
    exit;
}
$cod_usuario = $_SESSION['cod_usuario'];
$nomeUsuario = "";
$emailUsuario = "";
$sql = "SELECT * FROM usuario WHERE cod_usuario = '$cod_usuario'";

$result = mysqli_query($conexao_bd,$sql); //pega o resultado da query e lança num array

if($consulta = mysqli_fetch_assoc($result)){ //leitura do array
    $nomeUsuario  = $consulta['nome'];
    $emailUsuario = $consulta['email'];
}

/* ============================================================
   DADOS DO OPERADOR LOGADO
============================================================ */
$operadorNome  = $nomeUsuario;
$operadorEmail = $emailUsuario;

/* ============================================================
   PROCESSAMENTO DE AÇÕES (POST)
============================================================ */
$msgSucesso = "";
$msgErro = "";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $acao = isset($_POST['acao']) ? $_POST['acao'] : '';
    
    if ($acao === 'novo') {
        $paciente      = mysqli_real_escape_string($conexao_bd, $_POST['paciente']);
        $medico_id     = (int)$_POST['medico_id'];
        $data          = mysqli_real_escape_string($conexao_bd, $_POST['data']);
        $horario       = mysqli_real_escape_string($conexao_bd, $_POST['horario']);
        $status        = mysqli_real_escape_string($conexao_bd, $_POST['status']);
        
        // 1. REGRA: Puxa a especialidade correta do médico selecionado direto do banco
        $sqlEsp = "SELECT especialidade_id FROM medicos WHERE id = $medico_id";
        $resEsp = mysqli_query($conexao_bd, $sqlEsp);
        $dadosEsp = mysqli_fetch_assoc($resEsp);
        $especialidade_id = $dadosEsp['especialidade_id'];

        // 2. REGRA: Verifica se o médico já tem agendamento no mesmo dia e horário
        $sqlCheck = "SELECT id FROM agendamentos WHERE medico_id = $medico_id AND data = '$data' AND horario = '$horario' AND status != 'Cancelado'";
        $resCheck = mysqli_query($conexao_bd, $sqlCheck);

        if (mysqli_num_rows($resCheck) > 0) {
            $msgErro = "Este médico já possui uma consulta agendada para este mesmo dia e horário!";
        } else {
            $sql = "INSERT INTO agendamentos(paciente, medico_id, especialidade_id, data, horario, status) 
                    VALUES('$paciente', $medico_id, $especialidade_id, '$data', '$horario', '$status')";
            if(mysqli_query($conexao_bd, $sql)) {
                $msgSucesso = "Agendamento cadastrado com sucesso!";
            } else {
                $msgErro = "Erro ao agendar: " . mysqli_error($conexao_bd);
            }
        }

    } elseif ($acao === 'editar') {
        $id_agenda     = (int)$_POST['id'];
        $paciente      = mysqli_real_escape_string($conexao_bd, $_POST['paciente']);
        $medico_id     = (int)$_POST['medico_id'];
        $data          = mysqli_real_escape_string($conexao_bd, $_POST['data']);
        $horario       = mysqli_real_escape_string($conexao_bd, $_POST['horario']);
        $status        = mysqli_real_escape_string($conexao_bd, $_POST['status']);
        
        $sqlEsp = "SELECT especialidade_id FROM medicos WHERE id = $medico_id";
        $resEsp = mysqli_query($conexao_bd, $sqlEsp);
        $dadosEsp = mysqli_fetch_assoc($resEsp);
        $especialidade_id = $dadosEsp['especialidade_id'];

        // Verifica choque de horário na edição (ignorando o próprio card sendo editado)
        $sqlCheck = "SELECT id FROM agendamentos WHERE medico_id = $medico_id AND data = '$data' AND horario = '$horario' AND id != $id_agenda AND status != 'Cancelado'";
        $resCheck = mysqli_query($conexao_bd, $sqlCheck);

        if (mysqli_num_rows($resCheck) > 0) {
            $msgErro = "Não foi possível atualizar. O médico já possui uma consulta neste horário!";
        } else {
            $sql = "UPDATE agendamentos SET 
                     paciente = '$paciente',
                     medico_id = $medico_id,
                     especialidade_id = $especialidade_id, 
                     data = '$data',
                     horario = '$horario',
                     status  = '$status'
                    WHERE id = $id_agenda";
            if(mysqli_query($conexao_bd, $sql)) {
                $msgSucesso = "Agendamento atualizado com sucesso!";
            } else {
                $msgErro = "Erro ao atualizar: " . mysqli_error($conexao_bd);
            }
        }

    } elseif ($acao === 'cancelar') {
        $id_agenda = (int)$_POST['id'];
        $sql = "UPDATE agendamentos SET status = 'Cancelado' WHERE id = " . $id_agenda;
        mysqli_query($conexao_bd, $sql);
    }
}

/* ============================================================
   FILTROS DE BUSCA
============================================================ */
$filtroPaciente = trim(isset($_GET['paciente']) ? $_GET['paciente'] : '');
$filtroMedico   = trim(isset($_GET['medico'])   ? $_GET['medico']   : '');
$filtroStatus   = trim(isset($_GET['status'])   ? $_GET['status']   : '');
$filtroDataIni  = trim(isset($_GET['data_ini']) ? $_GET['data_ini'] : '');
$filtroDataFim  = trim(isset($_GET['data_fim']) ? $_GET['data_fim'] : '');

/* ============================================================
   BUSCA DOS AGENDAMENTOS REAIS
============================================================ */
$agendamentos = array();
$sql = "SELECT * FROM vw_agendamentos WHERE 1=1";
if ($filtroPaciente !== '') {
    $sql .= " AND paciente LIKE '%" . mysqli_real_escape_string($conexao_bd, $filtroPaciente) . "%'";
}
if ($filtroMedico !== '') {
    $sql .= " AND medico = '" . mysqli_real_escape_string($conexao_bd, $filtroMedico) . "'";
}
if ($filtroStatus !== '') {
    $sql .= " AND status = '" . mysqli_real_escape_string($conexao_bd, $filtroStatus) . "'";
}
if ($filtroDataIni !== '') {
    $sql .= " AND data >= '" . mysqli_real_escape_string($conexao_bd, $filtroDataIni) . "'";
}
if ($filtroDataFim !== '') {
    $sql .= " AND data <= '" . mysqli_real_escape_string($conexao_bd, $filtroDataFim) . "'";
}
$sql .= " ORDER BY data ASC, horario ASC";

$result = mysqli_query($conexao_bd, $sql);
while ($row = mysqli_fetch_assoc($result)) {
    $agendamentos[] = [
        'id'            => $row['id'],
        'data'          => $row['data'],
        'horario'       => $row['horario'],
        'paciente'      => $row['paciente'],
        'medico'        => $row['medico'],
        'especialidade' => $row['especialidade'],
        'status'        => $row['status']
    ];
}

/* ============================================================
   MÉDICOS ATIVOS - CORRIGIDO (Puxando dinamicamente todos do banco)
============================================================ */
$medicos = [];
$sqlM = "SELECT id, nome, especialidade FROM vw_medicos WHERE status = 'Ativo' ORDER BY nome ASC";
$resM = mysqli_query($conexao_bd, $sqlM);
while ($rowM = mysqli_fetch_assoc($resM)) {
    $medicos[] = $rowM;
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>MediAgenda - Cadastro de Agendas</title>
    <link rel="icon" type="image/x-icon" href="img/favicon.ico">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.0.2/dist/css/bootstrap.min.css" rel="stylesheet"
          integrity="sha384-EVSTQN3/azprG1Anm3QDgpJLIm9Nao0Yz1ztcQTwFspd3yD65VohhpuuCOmLASjC" crossorigin="anonymous">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">

    <style>
        :root {
            --azul-primario: #0d6efd; --azul-escuro:   #084298; --azul-claro:    #e7f1ff;
            --cinza-fundo:   #f5f7fa; --cinza-borda:   #e3e6ea; --texto-escuro:  #1f2d3d; --sidebar-larg:  250px;
        }
        body { background-color: var(--cinza-fundo); font-family: 'Segoe UI', Tahoma, sans-serif; color: var(--texto-escuro); overflow-x: hidden; }
        .navbar-topo { background: linear-gradient(90deg, var(--azul-primario) 0%, var(--azul-escuro) 100%); height: 60px; box-shadow: 0 2px 8px rgba(0,0,0,0.08); position: fixed; top: 0; left: 0; right: 0; z-index: 1030; }
        .navbar-topo .navbar-brand { color: #fff; font-weight: 600; font-size: 1.25rem; }
        .navbar-topo .navbar-brand i { margin-right: 8px; }
        .btn-sanduiche { background: transparent; border: none; color: #fff; font-size: 1.3rem; padding: 6px 12px; border-radius: 6px; transition: background 0.2s; }
        .btn-sanduiche:hover { background: rgba(255,255,255,0.15); }
        .operador-toggle { background: transparent; border: none; color: #fff; display: flex; align-items: center; gap: 8px; padding: 6px 12px; border-radius: 30px; transition: background 0.2s; }
        .operador-toggle:hover, .operador-toggle:focus { background: rgba(255,255,255,0.15); color: #fff; }
        .operador-toggle i.fa-circle-user { font-size: 1.6rem; }
        .dropdown-menu-operador { min-width: 220px; border-radius: 10px; box-shadow: 0 4px 16px rgba(0,0,0,0.12); border: none; }
        .sidebar { position: fixed; top: 60px; left: 0; width: var(--sidebar-larg); height: calc(100vh - 60px); background: #fff; border-right: 1px solid var(--cinza-borda); padding: 20px 0; transition: transform 0.3s ease; z-index: 1020; overflow-y: auto; }
        .sidebar.oculta { transform: translateX(calc(var(--sidebar-larg) * -1)); }
        .sidebar .nav-link { color: var(--texto-escuro); padding: 12px 20px; border-left: 3px solid transparent; transition: all 0.2s; display: flex; align-items: center; gap: 12px; }
        .sidebar .nav-link i { width: 22px; color: var(--azul-primario); font-size: 1.05rem; }
        .sidebar .nav-link:hover { background: var(--azul-claro); border-left-color: var(--azul-primario); color: var(--azul-escuro); }
        .sidebar .nav-link.ativo { background: var(--azul-claro); border-left-color: var(--azul-primario); color: var(--azul-escuro); font-weight: 600; }
        .sidebar-overlay { display: none; position: fixed; top: 60px; left: 0; right: 0; bottom: 0; background: rgba(0,0,0,0.4); z-index: 1010; }
        .sidebar-overlay.ativo { display: block; }
        .conteudo-principal { margin-top: 60px; margin-left: var(--sidebar-larg); padding: 25px; transition: margin-left 0.3s ease; min-height: calc(100vh - 60px); }
        .conteudo-principal.expandido { margin-left: 0; }
        @media (max-width: 991.98px) { .sidebar { transform: translateX(calc(var(--sidebar-larg) * -1)); } .sidebar.aberta { transform: translateX(0); box-shadow: 2px 0 12px rgba(0,0,0,0.15); } .conteudo-principal { margin-left: 0; } }
        .page-header { display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: 12px; margin-bottom: 22px; }
        .page-header h2 { font-size: 1.4rem; font-weight: 700; color: var(--azul-escuro); margin: 0; display: flex; align-items: center; gap: 10px; }
        .card-pagina { background: #fff; border-radius: 12px; box-shadow: 0 2px 10px rgba(0,0,0,0.05); border: 1px solid var(--cinza-borda); padding: 20px 24px; margin-bottom: 20px; }
        .card-pagina .card-titulo { font-weight: 600; font-size: 0.95rem; color: var(--azul-escuro); margin-bottom: 16px; display: flex; align-items: center; gap: 8px; }
        .tabela-agendamentos { width: 100%; border-collapse: separate; border-spacing: 0; font-size: 0.88rem; }
        .tabela-agendamentos thead th { background: var(--azul-claro); color: var(--azul-escuro); font-weight: 600; padding: 10px 14px; border-bottom: 2px solid var(--cinza-borda); white-space: nowrap; }
        .tabela-agendamentos tbody tr { transition: background 0.15s; }
        .tabela-agendamentos tbody tr:hover { background: #f8fbff; }
        .tabela-agendamentos tbody td { padding: 10px 14px; border-bottom: 1px solid var(--cinza-borda); vertical-align: middle; }
        .badge-status { display: inline-block; padding: 4px 10px; border-radius: 20px; font-size: 0.78rem; font-weight: 600; }
        .badge-confirmado { background: #d1e7dd; color: #0a3622; }
        .badge-pendente { background: #fff3cd; color: #664d03; }
        .badge-cancelado { background: #f8d7da; color: #58151c; }
        .modal-form .modal-header { background: var(--azul-primario); color: #fff; }
        .modal-form .modal-header .btn-close { filter: invert(1); }
    </style>
</head>
<body>

    <nav class="navbar-topo d-flex align-items-center justify-content-between px-3">
        <div class="d-flex align-items-center gap-2">
            <button class="btn-sanduiche" id="btnSanduiche" title="Menu"><i class="fa-solid fa-bars"></i></button>
            <a class="navbar-brand mb-0 d-flex align-items-center" href="principal.php"><i class="fa-solid fa-stethoscope"></i><span>MediAgenda</span></a>
        </div>
        <div class="dropdown">
            <button class="operador-toggle" type="button" id="dropdownOperador" data-bs-toggle="dropdown" aria-expanded="false">
                <i class="fa-solid fa-circle-user"></i><span class="d-none d-md-inline"><?php echo $operadorNome ?></span><i class="fa-solid fa-chevron-down" style="font-size: 0.75rem;"></i>
            </button>
            <ul class="dropdown-menu dropdown-menu-end dropdown-menu-operador" aria-labelledby="dropdownOperador">
                <li><a class="dropdown-item" href="#"><i class="fa-solid fa-user"></i><?php echo htmlspecialchars($operadorNome) ?></a></li>
                <li><a class="dropdown-item" href="#"><i class="fa-solid fa-envelope"></i><?php echo htmlspecialchars($operadorEmail) ?></a></li>
                <li><hr class="dropdown-divider"></li>
                <li><a class="dropdown-item" href="logout.php"><i class="fa-solid fa-right-from-bracket"></i>Sair</a></li>
            </ul>
        </div>
    </nav>

    <aside class="sidebar" id="sidebar">
        <ul class="nav flex-column">
            <li class="nav-item"><a class="nav-link" href="principal.php"><i class="fa-solid fa-calendar-days"></i> Calendário</a></li>
            <li class="nav-item"><a class="nav-link ativo" href="cadastro_agendas.php"><i class="fa-solid fa-calendar-plus"></i> Agendamentos</a></li>
            <li class="nav-item"><a class="nav-link" href="cadastro_medicos.php"><i class="fa-solid fa-user-doctor"></i> Cadastro de Médicos</a></li>
            <li class="nav-item"><a class="nav-link" href="cadastro_especialidades.php"><i class="fa-solid fa-list-check"></i> Cadastro de Especialidades</a></li>
        </ul>
    </aside>
    <div class="sidebar-overlay" id="sidebarOverlay"></div>

    <main class="conteudo-principal" id="conteudoPrincipal">
        <div class="page-header">
            <h2><i class="fa-solid fa-calendar-days"></i> Cadastro de Agendas</h2>
            <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#modalFormAgenda"><i class="fa-solid fa-plus me-1"></i> Novo Agendamento</button>
        </div>

        <div class="card-pagina">
            <div class="card-titulo"><i class="fa-solid fa-magnifying-glass"></i> Filtros</div>
            <form method="GET" action="cadastro_agendas.php">
                <div class="row g-3">
                    <div class="col-md-3">
                        <label for="filtroPaciente">Paciente</label>
                        <input type="text" class="form-control form-control-sm" id="filtroPaciente" name="paciente" value="<?php echo htmlspecialchars($filtroPaciente) ?>">
                    </div>
                    <div class="col-md-3">
                        <label for="filtroMedico">Médico</label>
                        <select class="form-select form-select-sm" id="filtroMedico" name="medico">
                            <option value="">Todos</option>
                            <?php foreach ($medicos as $m): ?>
                                <option value="<?php echo htmlspecialchars($m['nome']) ?>" <?php echo ($filtroMedico === $m['nome']) ? 'selected' : '' ?>><?php echo htmlspecialchars($m['nome']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-2">
                        <label for="filtroStatus">Status</label>
                        <select class="form-select form-select-sm" id="filtroStatus" name="status">
                            <option value="">Todos</option>
                            <option value="Confirmado" <?php echo ($filtroStatus === 'Confirmado') ? 'selected' : '' ?>>Confirmado</option>
                            <option value="Pendente"   <?php echo ($filtroStatus === 'Pendente')   ? 'selected' : '' ?>>Pendente</option>
                            <option value="Cancelado"  <?php echo ($filtroStatus === 'Cancelado')  ? 'selected' : '' ?>>Cancelado</option>
                        </select>
                    </div>
                    <div class="col-md-2">
                        <label for="filtroDataIni">Data inicial</label>
                        <input type="date" class="form-control form-control-sm" id="filtroDataIni" name="data_ini" value="<?php echo htmlspecialchars($filtroDataIni) ?>">
                    </div>
                    <div class="col-md-2">
                        <label for="filtroDataFim">Data final</label>
                        <input type="date" class="form-control form-control-sm" id="filtroDataFim" name="data_fim" value="<?php echo htmlspecialchars($filtroDataFim) ?>">
                    </div>
                </div>
                <div class="d-flex gap-2 mt-3">
                    <button type="submit" class="btn btn-primary btn-sm"><i class="fa-solid fa-magnifying-glass me-1"></i> Filtrar</button>
                    <a href="cadastro_agendas.php" class="btn btn-outline-secondary btn-sm"><i class="fa-solid fa-xmark me-1"></i> Limpar</a>
                </div>
            </form>
        </div>

        <div class="card-pagina">
            <div class="card-titulo d-flex justify-content-between align-items-center">
                <span><i class="fa-solid fa-table-list"></i> Agendamentos</span>
                <span id="contadorRegistros" class="text-muted" style="font-size:0.82rem; font-weight:400;"><?php echo count($agendamentos) ?> registro(s) encontrado(s)</span>
            </div>
            <div class="table-responsive">
                <table class="tabela-agendamentos">
                    <thead>
                        <tr>
                            <th>#</th><th>Data</th><th>Horário</th><th>Paciente</th><th>Médico</th><th>Especialidade</th><th>Status</th><th class="text-center">Ações</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($agendamentos)): ?>
                            <tr><td colspan="8" class="text-center text-muted py-4"><i class="fa-solid fa-calendar-xmark me-2"></i>Nenhum agendamento encontrado.</td></tr>
                        <?php else: ?>
                            <?php foreach ($agendamentos as $ag): 
                                $dataFormatada = date('d/m/Y', strtotime($ag['data']));
                                $classeBadge = ($ag['status'] === 'Confirmado') ? 'badge-confirmado' : (($ag['status'] === 'Pendente') ? 'badge-pendente' : 'badge-cancelado');
                            ?>
                            <tr>
                                <td class="text-muted"><?php echo $ag['id'] ?></td>
                                <td><?php echo $dataFormatada ?></td>
                                <td><?php echo htmlspecialchars($ag['horario']) ?></td>
                                <td><?php echo htmlspecialchars($ag['paciente']) ?></td>
                                <td><?php echo htmlspecialchars($ag['medico']) ?></td>
                                <td><?php echo htmlspecialchars($ag['especialidade']) ?></td>
                                <td><span class="badge-status <?php echo $classeBadge ?>"><?php echo htmlspecialchars($ag['status']) ?></span></td>
                                <td class="text-center" style="white-space:nowrap;">
                                    <button class="btn btn-sm btn-outline-primary py-0 px-2 btn-editar" title="Editar"
                                            data-id="<?php echo $ag['id'] ?>" data-paciente="<?php echo htmlspecialchars($ag['paciente']) ?>"
                                            data-medico="<?php echo htmlspecialchars($ag['medico']) ?>" data-data="<?php echo $ag['data'] ?>"
                                            data-horario="<?php echo htmlspecialchars($ag['horario']) ?>" data-status="<?php echo htmlspecialchars($ag['status']) ?>">
                                        <i class="fa-solid fa-pen"></i>
                                    </button>
                                    <button class="btn btn-sm btn-outline-danger py-0 px-2 btn-cancelar" title="Cancelar agendamento" data-id="<?php echo $ag['id'] ?>" data-paciente="<?php echo htmlspecialchars($ag['paciente']) ?>"><i class="fa-solid fa-ban"></i></button>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </main>

    <div class="modal fade modal-form" id="modalFormAgenda" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="modalFormTitulo"><i class="fa-solid fa-calendar-plus me-2"></i>Novo Agendamento</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fechar"></button>
                </div>

                <form id="formAgenda" action="cadastro_agendas.php" method="POST"> 
                    <input type="hidden" name="acao" id="formAcao" value="novo">
                    <input type="hidden" name="id"   id="formId"   value="">

                    <div class="modal-body">
                        <div class="row g-3">
                            <div class="col-12">
                                <label for="formPaciente">Paciente <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" id="formPaciente" name="paciente" placeholder="Nome completo" required>
                            </div>
                            <div class="col-md-6">
                                <label for="formMedico">Médico <span class="text-danger">*</span></label>
                                <select class="form-select" id="formFormMedico" name="medico_id" required onchange="atualizarEspecialidadeModal()">
                                    <option value="">Selecione...</option>
                                    <?php foreach ($medicos as $m): ?>
                                        <option value="<?php echo $m['id'] ?>" data-especialidade="<?php echo htmlspecialchars($m['especialidade']) ?>"><?php echo htmlspecialchars($m['nome']) ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label for="formEspecialidade">Especialidade</label>
                                <input type="text" class="form-control" id="formEspecialidade" placeholder="Selecione o médico..." readonly>
                            </div>
                            <div class="col-md-6">
                                <label for="formData">Data <span class="text-danger">*</span></label>
                                <input type="date" class="form-control" id="formData" name="data" required>
                            </div>
                            <div class="col-md-6">
                                <label for="formHorario">Horário <span class="text-danger">*</span></label>
                                <input type="time" class="form-control" id="formHorario" name="horario" required>
                            </div>
                            <div class="col-12">
                                <label for="formStatus">Status</label>
                                <select class="form-select" id="formStatus" name="status">
                                    <option value="Pendente">Pendente</option>
                                    <option value="Confirmado">Confirmado</option>
                                    <option value="Cancelado">Cancelado</option>
                                </select>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Fechar</button>
                        <button type="submit" class="btn btn-primary"><i class="fa-solid fa-floppy-disk me-1"></i> Salvar</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <form id="formCancelar" method="POST" action="cadastro_agendas.php" style="display:none;">
        <input type="hidden" name="acao" value="cancelar">
        <input type="hidden" name="id" id="cancelarId" value="">
    </form>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.0.2/dist/js/bootstrap.bundle.min.js" integrity="sha384-MrcW6ZMFYlzcLA8Nl+NtUVF0sA7MsXsP1UyJoMp4YLEuNSfAP+JcXn/tWtIaxVXM" crossorigin="anonymous"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script>
        // SIDEBAR
        var btnSanduiche = document.getElementById('btnSanduiche');
        var sidebar = document.getElementById('sidebar');
        var conteudoPrincipal = document.getElementById('conteudoPrincipal');
        var sidebarOverlay = document.getElementById('sidebarOverlay');
        btnSanduiche.addEventListener('click', function() { if (window.innerWidth <= 991.98) { sidebar.classList.toggle('aberta'); sidebarOverlay.classList.toggle('ativo'); } else { sidebar.classList.toggle('oculta'); conteudoPrincipal.classList.toggle('expandido'); } });
        sidebarOverlay.addEventListener('click', function() { sidebar.classList.remove('aberta'); sidebarOverlay.classList.remove('ativo'); });

        // NOVO: Função JavaScript que preenche a especialidade lendo o atributo do select de médicos
        function atualizarEspecialidadeModal() {
            var selectMedico = document.getElementById('formFormMedico');
            var inputEspecialidade = document.getElementById('formEspecialidade');
            var opcaoSelecionada = selectMedico.options[selectMedico.selectedIndex];
            
            if (opcaoSelecionada.value !== "") {
                inputEspecialidade.value = opcaoSelecionada.getAttribute('data-especialidade');
            } else {
                inputEspecialidade.value = "";
            }
        }

        var modalFormAgendaEl = document.getElementById('modalFormAgenda');
        var modalFormAgenda   = new bootstrap.Modal(modalFormAgendaEl);
        var modoEdicao        = false;

        modalFormAgendaEl.addEventListener('show.bs.modal', function() {
            if (!modoEdicao) {
                document.getElementById('modalFormTitulo').innerHTML = '<i class="fa-solid fa-calendar-plus me-2"></i>Novo Agendamento';
                document.getElementById('formAcao').value = 'novo';
                document.getElementById('formId').value   = '';
                document.getElementById('formEspecialidade').value = '';
                document.getElementById('formAgenda').reset();
            }
            modoEdicao = false;
        });

        // ACTIONS DA TABELA
        document.querySelector('.tabela-agendamentos').addEventListener('click', function(e) {
            var btnEditar   = e.target.closest('.btn-editar');
            var btnCancelar = e.target.closest('.btn-cancelar');
           
            if (btnEditar) {
                modoEdicao = true;
                document.getElementById('modalFormTitulo').innerHTML = '<i class="fa-solid fa-pen me-2"></i>Editar Agendamento';
                document.getElementById('formAcao').value          = 'editar';
                document.getElementById('formId').value            = btnEditar.dataset.id;
                document.getElementById('formPaciente').value      = btnEditar.dataset.paciente;
                document.getElementById('formData').value          = btnEditar.dataset.data;
                document.getElementById('formHorario').value       = btnEditar.dataset.horario;
                document.getElementById('formStatus').value        = btnEditar.dataset.status;

                var sel = document.getElementById('formFormMedico');
                for (var i = 0; i < sel.options.length; i++) {
                    if (sel.options[i].text === btnEditar.dataset.medico) {
                        sel.selectedIndex = i;
                        break;
                    }
                }
                atualizarEspecialidadeModal(); // Preenche a especialidade na edição
                modalFormAgenda.show();
            }

            if (btnCancelar) {
                Swal.fire({
                    title: 'Cancelar agendamento?',
                    html: 'Deseja cancelar a consulta de <strong>' + btnCancelar.dataset.paciente + '</strong>?',
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonColor: '#dc3545',
                    cancelButtonColor:  '#6c757d',
                    confirmButtonText:  'Sim, cancelar',
                    cancelButtonText:   'Voltar'
                }).then(function(result) {
                    if (result.isConfirmed) {
                        document.getElementById('cancelarId').value = btnCancelar.dataset.id;
                        document.getElementById('formCancelar').submit();
                    }
                });
            }
        });

        // BANDEIRAS DE ALERTA DO PHP (SWEETALERT)
        <?php if($msgSucesso !== ""): ?>
            Swal.fire({ icon: 'success', title: 'Salvo!', text: '<?php echo $msgSucesso; ?>', confirmButtonColor: '#0d6efd', timer: 2000, showConfirmButton: false });
        <?php endif; ?>

        <?php if($msgErro !== ""): ?>
            Swal.fire({ icon: 'error', title: 'Horário Ocupado!', text: '<?php echo $msgErro; ?>', confirmButtonColor: '#0d6efd' });
        <?php endif; ?>
    </script>
</body>
</html>