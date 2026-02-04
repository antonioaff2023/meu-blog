<?php

echo 'Teste';
if (isset($_GET['id']) && is_numeric($_GET['id'])) {
    
    $sermao_id = $_GET['id'];
 
    echo 'ID do sermão: ' . $sermao_id;
}
