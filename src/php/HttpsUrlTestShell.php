<?php

/**
 * Check any HTTPS URL to validate it
 *
 *  - checkdomain verifies the SSL cert only (working, slow)
 *  - checkcurl   verifies the URL can be loaded via CURL (not working)
 *
 */

App::uses('HttpSocket', 'Utility');
App::uses('AppShell', 'Console');
class HttpsUrlTestShell extends Shell {

    public function help() {
        $this->out('./cake HttpsUrlTest <url>');

        $this->out('');
        $this->out('  eg: ');
        $this->out(sprintf(
            '    ./cake HttpsUrlTest "%s"',
            'https://intranet.manage-website.com/wdpfonts/fonts/proxima_nova/stylesheet.css'
        ));
    }

    public function main($url = null) {
        if (empty($url)) {
            $url = array_shift($this->args);
        }
        if (empty($url)) {
            $this->error('Bad URL: empty');
        }
        if (substr($url, 0, 8) != 'https://') {
            $this->error('Bad URL: should start with https://');
        }
        $this->out();
        $this->out($url);
        $this->out();
        if (!$this->checkdomain($url)) {
            // should have already errored with why
            $this->error(" --> Bad (unknown error)");
        }
        $this->out('Success');
    }

    // use CLI openssl to check the certs for a domain
    public function checkdomain($url) {
        $parts = parse_url($url);

        $cmd = sprintf(
            'openssl s_client -connect %s:%s -showcerts',
            $parts['host'],
            (empty($parts['port']) ? 443 : $parts['port'])
        );

        // slow/blocking
        //   connects, and waits for a while, to ensure it gets all possible certs
        exec($cmd, $output, $return_var);
        //debug(compact('cmd', 'output', 'return_var'));

        if ($return_var != 0) {
            $this->error('Bad return_var - command failed');
        }

        $output = implode("\n", $output);

        if (strpos($output, 'BEGIN CERTIFICATE') === false) {
            $this->error('Bad output, no "BEGIN CERTIFICATE" found in output');
        }

        // support for wildcard domains, only care about the parent
        $domainParts = explode('.', $parts['host']);
        $parent = array_pop($domainParts);
        $parent = array_pop($domainParts) . '.' . $parent;
        if (strpos($output, $parent) === false) {
            $this->error(sprintf(
                'Bad output, parent domain "%s" not found in output',
                $parent
            ));
        }

        // we think it's valid
        return true;
    }

    // TODO : curl approach, not working ATM... :(
    public function checkcurl($url) {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_HEADER, 1);
        curl_setopt($ch, CURLOPT_NOBODY, 1);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_TIMEOUT, 10);
        //curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 2);
        //curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);
        curl_setopt($ch, CURLOPT_URL, $url);
        $response = curl_exec($ch);
        $lastHttpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        //debug(compact('response', 'lastHttpCode'));
        if (!in_array($lastHttpCode, [200, 206])) {
            $this->error(sprintf(
                'Bad curl request, the status code was "%s"',
                $lastHttpCode
            ));
        }
    }
}
