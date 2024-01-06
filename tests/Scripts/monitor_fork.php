<?php
$pid = (int)$argv[1] ?? 0;
sleep(2);
if (posix_kill($pid, 0)) {
    posix_kill($pid, SIGKILL);
}