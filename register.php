<?php
// El registro público está deshabilitado.
// Redirigir a cualquier usuario que intente acceder a esta página.
header('Location: login.php');
exit();
