<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <title>Agendador de consultas</title>
    <link 
        href="https://cdn.jsdelivr.net/npm/bootstrap@5.0.2/dist/css/bootstrap.min.css" 
        rel="stylesheet" 
        integrity="sha384-EVSTQN3/azprG1Anm3QDgpJLIm9Nao0Yz1ztcQTwFspd3yD65VohhpuuCOmLASjC" 
        crossorigin="anonymous">
    <script type="text/javascript">
        function validateForm(){
            var usuarioTela = document.getElementById("usuario").value;
            var senhaTela   = document.getElementById("senha").value;
            if(usuarioTela.length == 0){
                Swal.fire({
                    icon: "error",
                    title: "Oops...",
                    text: "Usuário em branco. Verifique!",
                    confirmButtonColor: '#0d6efd'
                });
                return false;
            }else{
                if(senhaTela.length == 0){
                    Swal.fire({
                        icon: "error",
                        title: "Oops...",
                        text: "Senha em branco. Verifique!",
                        confirmButtonColor: '#0d6efd'
                    });
                    return false;
                }else{
                    return true;
                }
            }
        }
    </script>
</head>
<body class="bg-light">
    <div class="top-content" style="margin-top: 80px;">
        <div class="inner-bg">
            <div class="container">
                <div class="row justify-content-center">
                    <div class="col-md-6 form-box">
                        <div class="form-top card p-4 shadow-sm mb-3">
                            <div class="formt-top-left">
                                <h3 class="text-primary">Sistema de agendamento de consultas</h3>
                                <p class="text-muted">Digite seu Usuário e Senha</p>
                            </div>
                            <div class="form-bottom">
                                <form role="form" 
                                      action="cadastrobanco.php" 
                                      method="POST"
                                      class="login-form"
                                      onSubmit="return validateForm()">
                                    <div class="form-group mb-3">
                                        <label class="form-label" for="usuario">Usuário:</label>
                                        <input type="text" name="usuario" id="usuario"
                                               placeholder="Digite seu usuário..." 
                                               class="form-username form-control">
                                    </div>
                                    <div class="form-group mb-3">
                                        <label class="form-label" for="senha">Senha:</label>
                                        <input type="password" name="senha" id="senha"
                                               placeholder="Digite sua senha..." 
                                               class="form-username form-control">
                                    </div>
                                    <div class="form-group d-grid">
                                        <button type="submit" class="btn btn-primary">Entrar no Sistema</button>
                                    </div>                            
                                </form>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.0.2/dist/js/bootstrap.bundle.min.js" integrity="sha384-MrcW6ZMFYlzcLA8Nl+NtUVF0sA7MsXsP1UyJoMp4YLEuNSfAP+JcXn/tWtIaxVXM" crossorigin="anonymous"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
</body>
</html>