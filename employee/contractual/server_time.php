
<?php
header('Content-Type: application/json');
echo json_encode(['currentTime' => date('Y-m-d H:i:s')]);
?>
