# INSTRUCCIONES POST-MIGRACIÓN
# Ejecutar DESPUÉS de aplicar update_db_installer_contracts.php

## Paso 1: Actualizar server_process.php

### En el array $aColumnas (línea ~48-62), REEMPLAZAR:
```php
    'c.distancia_drop',         // 26
    'c.instalador',             // 27
    'c.ip',                     // 28
    'c.punto_acceso',           // 29
    'c.valor_conexion_dbm',     // 30
    'c.num_presinto_odn',       // 31
    'c.evidencia_foto',         // 32
    'c.vendedor_texto',         // 33
    'c.sae_plus',               // 34
```

### POR:
```php
    'c.distancia_drop',         // 26
    'c.instalador',             // 27
    'c.evidencia_fibra',        // 28
    'c.ip',                     // 29
    'c.punto_acceso',           // 30
    'c.valor_conexion_dbm',     // 31
    'c.num_presinto_odn',       // 32
    'c.evidencia_foto',         // 33
    'c.firma_cliente',          // 34
    'c.firma_tecnico',          // 35
    'c.vendedor_texto',         // 36
    'c.sae_plus',               // 37
```

### En la sección de output (línea ~245-250), AGREGAR DESPUÉS de evidencia_foto:
```php
    // 34B. FIRMA CLIENTE
    $firmaCliente = $aRow['firma_cliente'] ?? '';
    if (!empty($firmaCliente)) {
        $row[] = "<a href='../../uploads/firmas/{$firmaCliente}' target='_blank' class='btn btn-sm btn-outline-info'><i class='fa-solid fa-signature'></i></a>";
    } else {
        $row[] = '-';
    }

    // 34C. FIRMA TECNICO
    $firmaTecnico = $aRow['firma_tecnico'] ?? '';
    if (!empty($firmaTecnico)) {
        $row[] = "<a href='../../uploads/firmas/{$firmaTecnico}' target='_blank' class='btn btn-sm btn-outline-success'><i class='fa-solid fa-signature'></i></a>";
    } else {
        $row[] = '-';
    }

    // 34D. EVIDENCIA FIBRA  
    $row[] = clean($aRow['evidencia_fibra']);
```

## Paso 2: Actualizar gestion_contratos.php

### En la tabla thead (línea ~129-133), REEMPLAZAR:
```html
                                <!-- Cierre -->
                                <th title="Instalador (Cierre)">Instalador (C)</th>
                                <th title="Sugerencias/Observaciones">Sugerencias</th>
                                <th>Precinto ODN</th>
                                <th>Foto</th>
```

### POR:
```html
                                <!-- Cierre -->
                                <th title="Instalador (Cierre)">Instalador (C)</th>
                                <th title="Evidencia de Fibra">Evidencia Fibra</th>
                                <th title="Sugerencias/Observaciones">Sugerencias</th>
                                <th>Precinto ODN</th>
                                <th>Foto</th>
                                <th>Firma Cliente</th>
                                <th>Firma Técnico</th>
```

## Notas adicionales:
- Los índices de las columnas en $aColumnas cambiarán de 38 a 41
- Asegurarse de actualizar ambos archivos SIMULTÁNEAMENTE para evitar desajustes
