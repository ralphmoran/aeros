<?php

namespace Aeros\Src\Classes;

use phpseclib3\Net\SSH2;

// https://phpseclib.com/docs/connect

class Network
{
    public static function ssh()
    {
        $expected = 'ssh-ed25519 AAAAC3NzaC1lZDI1NTE5AAAAIPhzhUOqH8WV6A1E4TqRU0iukhjNLFoiIGrSwbBCC56s';

        $ssh = new SSH2('192.168.56.11', 22);

        if ($expected != $ssh->getServerPublicHostKey()) {
            throw new \Exception('Host key verification failed');
        }
    }

    /**
     * Returns the client IP address.
     *
     * @param boolean $checkProxy
     * @return string
     */
    public static function getClientIp($checkProxy = true): string
    {
        if ($checkProxy && $_SERVER['HTTP_CLIENT_IP'] != null) {
            return $_SERVER['HTTP_CLIENT_IP'];
        }

        if ($checkProxy && $_SERVER['HTTP_X_FORWARDED_FOR'] != null) {
            return $_SERVER['HTTP_X_FORWARDED_FOR'];
        }

        return $_SERVER['REMOTE_ADDR'];
    }
}
