<?php
// test_search_api.php
require_once 'paginas/conexion.php';

echo "Testing buscar_contratos.php with a query...\n";

// We'll simulate a request to buscar_contratos.php
// But since it's a separate file, we'll just check if it returns what we expect if we run it.
// Alternatively, we can just check the database content first to find a valid query.

$res = $conn->query("SELECT nombre_completo, cedula FROM contratos WHERE estado = 'ACTIVO' LIMIT 1");
if ($row = $res->fetch_assoc()) {
    $q = 'V12721951';
    echo "Searching for: $q\n";
    
    $_GET['q'] = $q;
    ob_start();
    chdir('paginas/principal');
    include 'buscar_contratos.php';
    chdir('../../');
    $output = ob_get_clean();
    
    $data = json_decode($output, true);
    if (!empty($data)) {
        echo "Found " . count($data) . " results.\n";
        echo "Example Result:\n";
        print_r($data[0]);
        
        if (isset($data[0]['telefono'])) {
            echo "SUCCESS: 'telefono' field is present.\n";
        } else {
            echo "FAILURE: 'telefono' field is MISSING.\n";
        }
    } else {
        echo "FAILURE: No results found for '$q'.\n";
    }
} else {
    echo "No active contracts found to test with.\n";
}
