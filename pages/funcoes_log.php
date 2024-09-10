<?php
function registrarLog($conn, $usuario, $acao, $tabela, $comando_sql = null, $dadosAntigos = null, $dadosNovos = null) {
    $ip = $_SERVER['REMOTE_ADDR'];

    $sql = "INSERT INTO logs (usuario, acao, tabela, comando_sql, ip, dados_antigos, dados_novos) 
            VALUES (?, ?, ?, ?, ?, ?, ?)";
    $stmt = $conn->prepare($sql);
    $stmt->execute([$usuario, $acao, $tabela, $comando_sql, $ip, $dadosAntigos, $dadosNovos]);
}
?>
