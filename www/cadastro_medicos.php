<?php
/* ============================================================
   cadastro_medicos.php - Cadastro de Médicos
   ------------------------------------------------------------
============================================================ */
session_start();
require_once("conexao.php"); // importar o conexao.php para esta página

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
   PROCESSAMENTO DE AÇÕES (POST) - APENAS PROBLEMA 1 (MÉDICOS)
============================================================ */
$msgSucesso = "";
$msgErro = "";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $acao = isset($_POST['acao']) ? $_POST['acao'] : '';
    
    if ($acao === 'novo') {
        $nome          = mysqli_real_escape_string($conexao_bd, $_POST['nome']);
        $crm           = mysqli_real_escape_string($conexao_bd, $_POST['crm']);
        $especialidade = (int)$_POST['especialidade'];
        $telefone      = mysqli_real_escape_string($conexao_bd, $_POST['telefone']);
        $email         = mysqli_real_escape_string($conexao_bd, $_POST['email']);
        $status        = mysqli_real_escape_string($conexao_bd, $_POST['status']);
        
        // CORREÇÃO DO ERRO: Verifica se o CRM já existe no banco antes de tentar inserir
        $checkCrm = "SELECT id FROM medicos WHERE crm = '$crm'";
        $resCheck = mysqli_query($conexao_bd, $checkCrm);
        
        if (mysqli_num_rows($resCheck) > 0) {
            $msgErro = "Este CRM já está cadastrado para outro médico! Tente um número diferente.";
        } else {
            $sql = "INSERT INTO medicos (nome, crm, especialidade_id, telefone, email, status) 
                    VALUES ('$nome', '$crm', $especialidade, '$telefone', '$email', '$status')";
            
            if(mysqli_query($conexao_bd, $sql)) {
                $msgSucesso = "Médico cadastrado com sucesso!";
            } else {
                $msgErro = "Erro ao cadastrar: " . mysqli_error($conexao_bd);
            }
        }
        
    } elseif ($acao === 'editar') {
        $id            = (int)$_POST['id'];
        $nome          = mysqli_real_escape_string($conexao_bd, $_POST['nome']);
        $crm           = mysqli_real_escape_string($conexao_bd, $_POST['crm']);
        $especialidade = (int)$_POST['especialidade'];
        $telefone      = mysqli_real_escape_string($conexao_bd, $_POST['telefone']);
        $email         = mysqli_real_escape_string($conexao_bd, $_POST['email']);
        $status        = mysqli_real_escape_string($conexao_bd, $_POST['status']);
        
        // CORREÇÃO: Garante que na edição o CRM alterado não colida com outro médico
        $checkCrm = "SELECT id FROM medicos WHERE crm = '$crm' AND id != $id";
        $resCheck = mysqli_query($conexao_bd, $checkCrm);
        
        if (mysqli_num_rows($resCheck) > 0) {
            $msgErro = "Não foi possível atualizar. O CRM digitado já pertence a outro profissional.";
        } else {
            $sql = "UPDATE medicos SET 
                        nome = '$nome', 
                        crm = '$crm', 
                        especialidade_id = $especialidade, 
                        telefone = '$telefone', 
                        email = '$email', 
                        status = '$status' 
                    WHERE id = $id";
                    
            if(mysqli_query($conexao_bd, $sql)) {
                $msgSucesso = "Médico updated com sucesso!";
            } else {
                $msgErro = "Erro ao atualizar: " . mysqli_error($conexao_bd);
            }
        }
        
    } elseif ($acao === 'excluir') {
        $id = (int)$_POST['id'];
        
        // CORREÇÃO: Exclusão lógica (Inativação) para preservar chaves e histórico de agendamentos
        $sql = "UPDATE medicos SET status = 'Inativo' WHERE id = $id";
        
        if(mysqli_query($conexao_bd, $sql)) {
            $msgSucesso = "O status do médico foi alterado para Inativo com sucesso!";
        } else {
            $msgErro = "Erro ao alterar o status: " . mysqli_error($conexao_bd);
        }
    }
}


$filtroNome          = trim(isset($_GET['nome'])          ? $_GET['nome']          : '');
$filtroEspecialidade = trim(isset($_GET['especialidade']) ? $_GET['especialidade'] : '');
$filtroStatus        = trim(isset($_GET['status'])        ? $_GET['status']        : '');

/* ============================================================
   BUSCA DINÂMICA DE MÉDICOS (Dados reais puxados da View)
============================================================ */
$medicos = array();
$sql = "SELECT * FROM vw_medicos WHERE 1=1";

if ($filtroNome !== '') {
    $sql .= " AND nome LIKE '%" . mysqli_real_escape_string($conexao_bd, $filtroNome) . "%'";
}
if ($filtroEspecialidade !== '') {
    $sql .= " AND especialidade = '" . mysqli_real_escape_string($conexao_bd, $filtroEspecialidade) . "'";
}
if ($filtroStatus !== '') {
    $sql .= " AND status = '" . mysqli_real_escape_string($conexao_bd, $filtroStatus) . "'";
}
$sql .= " ORDER BY id ASC";

$result = mysqli_query($conexao_bd, $sql);
while ($row = mysqli_fetch_assoc($result)) {
    $medicos[] = $row;
}

/* ============================================================
   ESPECIALIDADES DISPONÍVEIS
   TODO: Substituir por consulta ao banco:
   $especialidades = buscarEspecialidades();
============================================================ */
$especialidades = array('Cardiologia', 'Dermatologia', 'Ginecologia', 'Neurologia', 'Ortopedia', 'Pediatria');
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>MediAgenda - Cadastro de Médicos</title>
    <link rel="icon" type="image/x-icon" href="img/favicon.ico">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.0.2/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-EVSTQN3/azprG1Anm3QDgpJLIm9Nao0Yz1ztcQTwFspd3yD65VohhpuuCOmLASjC" crossorigin="anonymous">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    
    <style>
        :root {
            --azul-primario: #0d6efd;
            --azul-escuro:   #084298;
            --azul-claro:    #e7f1ff;
            --cinza-fundo:   #f5f7fa;
            --cinza-borda:   #e3e6ea;
            --texto-escuro:  #1f2d3d;
            --sidebar-larg:  250px;
        }

        body {
            background-color: var(--cinza-fundo);
            font-family: 'Segoe UI', Tahoma, sans-serif;
            color: var(--texto-escuro);
            overflow-x: hidden;
        }

        /* ==================== NAVBAR SUPERIOR ==================== */
        .navbar-topo {
            background: linear-gradient(90deg, var(--azul-primario) 0%, var(--azul-escuro) 100%);
            height: 60px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.08);
            position: fixed;
            top: 0; left: 0; right: 0;
            z-index: 1030;
        }
        .navbar-topo .navbar-brand {
            color: #fff;
            font-weight: 600;
            font-size: 1.25rem;
        }
        .navbar-topo .navbar-brand i {
            margin-right: 8px;
        }
        .btn-sanduiche {
            background: transparent;
            border: none;
            color: #fff;
            font-size: 1.3rem;
            padding: 6px 12px;
            border-radius: 6px;
            transition: background 0.2s;
        }
        .btn-sanduiche:hover {
            background: rgba(255,255,255,0.15);
        }
        .operador-toggle {
            background: transparent;
            border: none;
            color: #fff;
            display: flex;
            align-items: center;
            gap: 8px;
            padding: 6px 12px;
            border-radius: 30px;
            transition: background 0.2s;
        }
        .operador-toggle:hover, .operador-toggle:focus {
            background: rgba(255,255,255,0.15);
            color: #fff;
        }
        .operador-toggle i.fa-circle-user {
            font-size: 1.6rem;
        }
        .dropdown-menu-operador {
            min-width: 220px;
            border-radius: 10px;
            box-shadow: 0 4px 16px rgba(0,0,0,0.12);
            border: none;
        }
        .dropdown-menu-operador .dropdown-item i {
            width: 22px;
            color: var(--azul-primario);
        }

        /* ==================== SIDEBAR LATERAL ==================== */
        .sidebar {
            position: fixed;
            top: 60px;
            left: 0;
            width: var(--sidebar-larg);
            height: calc(100vh - 60px);
            background: #fff;
            border-right: 1px solid var(--cinza-borda);
            padding: 20px 0;
            transition: transform 0.3s ease;
            z-index: 1020;
            overflow-y: auto;
        }
        .sidebar.oculta {
            transform: translateX(calc(var(--sidebar-larg) * -1));
        }
        .sidebar .nav-link {
            color: var(--texto-escuro);
            padding: 12px 20px;
            border-left: 3px solid transparent;
            transition: all 0.2s;
            display: flex;
            align-items: center;
            gap: 12px;
        }
        .sidebar .nav-link i {
            width: 22px;
            color: var(--azul-primario);
            font-size: 1.05rem;
        }
        .sidebar .nav-link:hover {
            background: var(--azul-claro);
            border-left-color: var(--azul-primario);
            color: var(--azul-escuro);
        }
        .sidebar .nav-link.ativo {
            background: var(--azul-claro);
            border-left-color: var(--azul-primario);
            color: var(--azul-escuro);
            font-weight: 600;
        }

        /* Overlay (em mobile, escurece o fundo quando sidebar aberta) */
        .sidebar-overlay {
            display: none;
            position: fixed;
            top: 60px; left: 0; right: 0; bottom: 0;
            background: rgba(0,0,0,0.4);
            z-index: 1010;
        }
        .sidebar-overlay.ativo {
            display: block;
        }

        /* ==================== CONTEÚDO PRINCIPAL ==================== */
        .conteudo-principal {
            margin-top: 60px;
            margin-left: var(--sidebar-larg);
            padding: 25px;
            transition: margin-left 0.3s ease;
            min-height: calc(100vh - 60px);
        }
        .conteudo-principal.expandido {
            margin-left: 0;
        }

        @media (max-width: 991.98px) {
            .sidebar {
                transform: translateX(calc(var(--sidebar-larg) * -1));
            }
            .sidebar.aberta {
                transform: translateX(0);
                box-shadow: 2px 0 12px rgba(0,0,0,0.15);
            }
            .conteudo-principal {
                margin-left: 0;
            }
        }

        /* ==================== CABEÇALHO DA PÁGINA ==================== */
        .page-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            flex-wrap: wrap;
            gap: 12px;
            margin-bottom: 22px;
        }
        .page-header h2 {
            font-size: 1.4rem;
            font-weight: 700;
            color: var(--azul-escuro);
            margin: 0;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        .page-header h2 i {
            color: var(--azul-primario);
        }

        /* ==================== CARD GENÉRICO ==================== */
        .card-pagina {
            background: #fff;
            border-radius: 12px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
            border: 1px solid var(--cinza-borda);
            padding: 20px 24px;
            margin-bottom: 20px;
        }
        .card-pagina .card-titulo {
            font-weight: 600;
            font-size: 0.95rem;
            color: var(--azul-escuro);
            margin-bottom: 16px;
            display: flex;
            align-items: center;
            gap: 8px;
        }
        .card-pagina .card-titulo i {
            color: var(--azul-primario);
        }

        /* ==================== TABELA ==================== */
        .tabela-medicos {
            width: 100%;
            border-collapse: separate;
            border-spacing: 0;
            font-size: 0.88rem;
        }
        .tabela-medicos thead th {
            background: var(--azul-claro);
            color: var(--azul-escuro);
            font-weight: 600;
            padding: 10px 14px;
            border-bottom: 2px solid var(--cinza-borda);
            white-space: nowrap;
        }
        .tabela-medicos tbody tr {
            transition: background 0.15s;
        }
        .tabela-medicos tbody tr:hover {
            background: #f8fbff;
        }
        .tabela-medicos tbody td {
            padding: 10px 14px;
            border-bottom: 1px solid var(--cinza-borda);
            vertical-align: middle;
        }
        .tabela-medicos tbody tr:last-child td {
            border-bottom: none;
        }

        /* ==================== BADGES DE STATUS ==================== */
        .badge-status {
            display: inline-block;
            padding: 4px 10px;
            border-radius: 20px;
            font-size: 0.78rem;
            font-weight: 600;
        }
        .badge-ativo {
            background: #d1e7dd;
            color: #0a3622;
        }
        .badge-inativo {
            background: #f8d7da;
            color: #58151c;
        }

        /* ==================== AVATAR DO MÉDICO ==================== */
        .avatar-medico {
            width: 34px;
            height: 34px;
            border-radius: 50%;
            background: var(--azul-claro);
            color: var(--azul-primario);
            display: inline-flex;
            align-items: center;
            justify-content: center;
            font-weight: 700;
            font-size: 0.82rem;
            margin-right: 8px;
            flex-shrink: 0;
        }

        /* ==================== MODAL ==================== */
        .modal-form .modal-header {
            background: var(--azul-primario);
            color: #fff;
        }
        .modal-form .modal-header .btn-close {
            filter: invert(1);
        }
        .modal-form label {
            font-weight: 500;
            font-size: 0.88rem;
            margin-bottom: 4px;
        }
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
                <i class="fa-solid fa-circle-user"></i><span class="d-none d-md-inline"><?php echo htmlspecialchars($operadorNome) ?></span><i class="fa-solid fa-chevron-down" style="font-size: 0.75rem;"></i>
            </button>
            <ul class="dropdown-menu dropdown-menu-end dropdown-menu-operador" aria-labelledby="dropdownOperador">
                <li><a class="dropdown-item" href="#"><i class="fa-solid fa-user"></i><?php echo htmlspecialchars($operadorNome) ?></a></li>
                <li><a class="dropdown-item" href="#"><i class="fa-solid fa-envelope"></i><?php echo htmlspecialchars($operadorEmail) ?></a></li>
                <li><hr class="dropdown-divider"></li>
                <li><a class="dropdown-item" href="#"><i class="fa-solid fa-gear"></i>Configurações</a></li>
                <li><a class="dropdown-item" href="logout.php"><i class="fa-solid fa-right-from-bracket"></i>Sair</a></li>
            </ul>
        </div>
    </nav>

    <aside class="sidebar" id="sidebar">
        <ul class="nav flex-column">
            <li class="nav-item"><a class="nav-link" href="principal.php"><i class="fa-solid fa-calendar-days"></i> Calendário</a></li>
            <li class="nav-item"><a class="nav-link" href="cadastro_agendas.php"><i class="fa-solid fa-calendar-plus"></i> Agendamentos</a></li>
            <li class="nav-item"><a class="nav-link ativo" href="cadastro_medicos.php"><i class="fa-solid fa-user-doctor"></i> Cadastro de Médicos</a></li>
            <li class="nav-item"><a class="nav-link" href="#"><i class="fa-solid fa-list-check"></i> Cadastro de Especialidades</a></li>
        </ul>
    </aside>

    <div class="sidebar-overlay" id="sidebarOverlay"></div>

    <main class="conteudo-principal" id="conteudoPrincipal">
        <div class="page-header">
            <h2><i class="fa-solid fa-user-doctor"></i> Cadastro de Médicos</h2>
            <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#modalFormMedico"><i class="fa-solid fa-plus me-1"></i> Novo Médico</button>
        </div>

        <div class="card-pagina">
            <div class="card-titulo"><i class="fa-solid fa-magnifying-glass"></i> Filtros</div>
            <form method="GET" action="cadastro_medicos.php">
                <div class="row g-3">
                    <div class="col-md-4">
                        <label for="filtroNome">Nome</label>
                        <input type="text" class="form-control form-control-sm" id="filtroNome" name="nome" placeholder="Nome do médico" value="<?php echo htmlspecialchars($filtroNome) ?>">
                    </div>
                    <div class="col-md-4">
                        <label for="filtroEspecialidade">Especialidade</label>
                        <select class="form-select" id="filtroEspecialidade" name="especialidade">
                            <option value="">Todas</option>
                            <?php foreach ($especialidades as $esp): ?>
                                <option value="<?php echo htmlspecialchars($esp) ?>" <?php echo ($filtroEspecialidade === $esp) ? 'selected' : '' ?>><?php echo htmlspecialchars($esp) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-2">
                        <label for="filtroStatus">Status</label>
                        <select class="form-select form-select-sm" id="filtroStatus" name="status">
                            <option value="">Todos</option>
                            <option value="Ativo"   <?php echo ($filtroStatus === 'Ativo')   ? 'selected' : '' ?>>Ativo</option>
                            <option value="Inativo" <?php echo ($filtroStatus === 'Inativo') ? 'selected' : '' ?>>Inativo</option>
                        </select>
                    </div>
                </div>
                <div class="d-flex gap-2 mt-3">
                    <button type="submit" class="btn btn-primary btn-sm"><i class="fa-solid fa-magnifying-glass me-1"></i> Filtrar</button>
                    <a href="cadastro_medicos.php" class="btn btn-outline-secondary btn-sm"><i class="fa-solid fa-xmark me-1"></i> Limpar</a>
                </div>
            </form>
        </div>

        <div class="card-pagina">
            <div class="card-titulo d-flex justify-content-between align-items-center">
                <span><i class="fa-solid fa-table-list"></i> Médicos</span>
                <span id="contadorRegistros" class="text-muted" style="font-size:0.82rem; font-weight:400;"><?php echo count($medicos) ?> registro(s) encontrado(s)</span>
            </div>
            <div class="table-responsive">
                <table class="tabela-medicos">
                    <thead>
                        <tr>
                            <th>#</th><th>Nome</th><th>CRM</th><th>Especialidade</th><th>Telefone</th><th>E-mail</th><th>Status</th><th class="text-center">Ações</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($medicos)): ?>
                            <tr><td colspan="8" class="text-center text-muted py-4"><i class="fa-solid fa-user-xmark me-2"></i>Nenhum médico encontrado.</td></tr>
                        <?php else: ?>
                            <?php foreach ($medicos as $med):
                                $partes   = explode(' ', $med['nome']); $iniciais = '';
                                foreach ($partes as $p) {
                                    $letra = ltrim($p, 'Dr. Dra. ');
                                    if ($letra !== '') { $iniciais .= mb_strtoupper(mb_substr($letra, 0, 1)); if (mb_strlen($iniciais) === 2) break; }
                                }
                                $classeBadge = ($med['status'] === 'Ativo') ? 'badge-ativo' : 'badge-inativo';
                            ?>
                            <tr>
                                <td class="text-muted"><?php echo $med['id'] ?></td>
                                <td><div class="d-flex align-items-center"><span class="avatar-medico"><?php echo htmlspecialchars($iniciais) ?></span><?php echo htmlspecialchars($med['nome']) ?></div></td>
                                <td><?php echo htmlspecialchars($med['crm']) ?></td>
                                <td><?php echo htmlspecialchars($med['especialidade']) ?></td>
                                <td><?php echo htmlspecialchars($med['telefone']) ?></td>
                                <td><?php echo htmlspecialchars($med['email']) ?></td>
                                <td><span class="badge-status <?php echo $classeBadge ?>"><?php echo htmlspecialchars($med['status']) ?></span></td>
                                <td class="text-center" style="white-space:nowrap;">
                                    <button class="btn btn-sm btn-outline-primary py-0 px-2 btn-editar" title="Editar" data-id="<?php echo $med['id'] ?>" data-nome="<?php echo htmlspecialchars($med['nome']) ?>" data-crm="<?php echo htmlspecialchars($med['crm']) ?>" data-especialidade="<?php echo htmlspecialchars($med['especialidade']) ?>" data-telefone="<?php echo htmlspecialchars($med['telefone']) ?>" data-email="<?php echo htmlspecialchars($med['email']) ?>" data-status="<?php echo htmlspecialchars($med['status']) ?>"><i class="fa-solid fa-pen"></i></button>
                                    <button class="btn btn-sm btn-outline-danger py-0 px-2 btn-excluir" title="Excluir médico" data-id="<?php echo $med['id'] ?>" data-nome="<?php echo htmlspecialchars($med['nome']) ?>"><i class="fa-solid fa-trash"></i></button>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </main>

    <div class="modal fade modal-form" id="modalFormMedico" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="modalFormTitulo"><i class="fa-solid fa-user-plus me-2"></i>Novo Médico</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fechar"></button>
                </div>
                <form id="formMedico" method="POST" action="cadastro_medicos.php">
                    <input type="hidden" name="acao" id="formAcao" value="novo">
                    <input type="hidden" name="id"   id="formId"   value="">
                    <div class="modal-body">
                        <div class="row g-3">
                            <div class="col-md-8">
                                <label for="formNome">Nome completo <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" id="formNome" name="nome" placeholder="Ex: Dr. Carlos Lima" required>
                            </div>
                            <div class="col-md-4">
                                <label for="formCrm">CRM <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" id="formCrm" name="crm" placeholder="Ex: CRM/SP 12345" required>
                            </div>
                            <div class="col-md-6">
                                <label for="formEspecialidade">Especialidade <span class="text-danger">*</span></label>
                                <select class="form-select" id="formEspecialidade" name="especialidade" required>
                                    <option value="">Selecione...</option>
                                    <option value="1">Cardiologia</option>
                                    <option value="2">Dermatologia</option>
                                    <option value="3">Ginecologia</option>
                                    <option value="4">Neurologia</option>
                                    <option value="5">Ortopedia</option>
                                    <option value="6">Pediatria</option>
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label for="formTelefone">Telefone</label>
                                <input type="text" class="form-control" id="formTelefone" name="telefone" placeholder="(00) 00000-0000">
                            </div>
                            <div class="col-md-8">
                                <label for="formEmail">E-mail</label>
                                <input type="email" class="form-control" id="formEmail" name="email" placeholder="medico@clinica.com">
                            </div>
                            <div class="col-md-4">
                                <label for="formStatus">Status</label>
                                <select class="form-select" id="formStatus" name="status">
                                    <option value="Ativo">Ativo</option>
                                    <option value="Inativo">Inativo</option>
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

    <form id="formExcluir" method="POST" action="cadastro_medicos.php" style="display:none;">
        <input type="hidden" name="acao" value="excluir">
        <input type="hidden" name="id" id="excluirId" value="">
    </form>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.0.2/dist/js/bootstrap.bundle.min.js" 
            integrity="sha384-MrcW6ZMFYlzcLA8Nl+NtUVF0sA7MsXsP1UyJoMp4YLEuNSfAP+JcXn/tWtIaxVXM" crossorigin="anonymous"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script>
        var btnSanduiche      = document.getElementById('btnSanduiche');
        var sidebar           = document.getElementById('sidebar');
        var conteudoPrincipal = document.getElementById('conteudoPrincipal');
        var sidebarOverlay    = document.getElementById('sidebarOverlay');
        btnSanduiche.addEventListener('click', function() { if (window.innerWidth <= 991.98) { sidebar.classList.toggle('aberta'); sidebarOverlay.classList.toggle('ativo'); } else { sidebar.classList.toggle('oculta'); conteudoPrincipal.classList.toggle('expandido'); } });
        sidebarOverlay.addEventListener('click', function() { sidebar.classList.remove('aberta'); sidebarOverlay.classList.remove('ativo'); });

        var modalFormMedicoEl = document.getElementById('modalFormMedico');
        var modalFormMedico   = new bootstrap.Modal(modalFormMedicoEl);
        var modoEdicao        = false;

        modalFormMedicoEl.addEventListener('show.bs.modal', function() {
            if (!modoEdicao) {
                document.getElementById('modalFormTitulo').innerHTML = '<i class="fa-solid fa-user-plus me-2"></i>Novo Médico';
                document.getElementById('formAcao').value = 'novo';
                document.getElementById('formId').value   = '';
                document.getElementById('formMedico').reset();
            }
            modoEdicao = false;
        });

        document.querySelector('.tabela-medicos').addEventListener('click', function(e) {
            var btnEditar  = e.target.closest('.btn-editar');
            var btnExcluir = e.target.closest('.btn-excluir');

            if (btnEditar) {
                modoEdicao = true;
                document.getElementById('modalFormTitulo').innerHTML = '<i class="fa-solid fa-pen me-2"></i>Editar Médico';
                document.getElementById('formAcao').value           = 'editar';
                document.getElementById('formId').value             = btnEditar.dataset.id;
                document.getElementById('formNome').value           = btnEditar.dataset.nome;
                document.getElementById('formCrm').value            = btnEditar.dataset.crm;
                document.getElementById('formTelefone').value       = btnEditar.dataset.telefone;
                document.getElementById('formEmail').value          = btnEditar.dataset.email;
                document.getElementById('formStatus').value         = btnEditar.dataset.status;

                var sel = document.getElementById('formEspecialidade');
                for (var i = 0; i < sel.options.length; i++) {
                    if (sel.options[i].text === btnEditar.dataset.especialidade) { sel.selectedIndex = i; break; }
                }
                modalFormMedico.show();
            }

            if (btnExcluir) {
                Swal.fire({
                    title: 'Inativar médico?',
                    html: 'Deseja alterar o status do cadastro de <strong>' + btnExcluir.dataset.nome + '</strong> para Inativo?',
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonColor: '#dc3545',
                    cancelButtonColor:  '#6c757d',
                    confirmButtonText:  'Sim, inativar',
                    cancelButtonText:   'Voltar'
                }).then(function(result) {
                    if (result.isConfirmed) {
                        document.getElementById('excluirId').value = btnExcluir.dataset.id;
                        document.getElementById('formExcluir').submit();
                    }
                });
            }
        });

        <?php if($msgSucesso !== ""): ?>
            Swal.fire({ icon: 'success', title: 'Salvo!', text: '<?php echo $msgSucesso; ?>', confirmButtonColor: '#0d6efd', timer: 2000, showConfirmButton: false });
        <?php endif; ?>
        <?php if($msgErro !== ""): ?>
            Swal.fire({ icon: 'error', title: 'Erro!', text: '<?php echo $msgErro; ?>', confirmButtonColor: '#0d6efd' });
        <?php endif; ?>
    </script>
</body>
</html>