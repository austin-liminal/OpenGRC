rule Malicious_PHP_Obfuscated
{
    meta:
        description = "Detects heavily obfuscated PHP code"
        author = "OpenGRC Security"
        severity = "high"

    strings:
        $obf1 = /\$[a-zA-Z_\x7f-\xff][a-zA-Z0-9_\x7f-\xff]*\s*=\s*['"][a-zA-Z0-9+\/]{100,}['"]/
        $obf2 = /chr\s*\(\s*\d+\s*\)\s*\.\s*chr\s*\(\s*\d+\s*\)/
        $obf3 = /base64_decode\s*\(\s*['"][A-Za-z0-9+\/=]{50,}['"]\s*\)/
        $obf4 = /gzinflate\s*\(\s*base64_decode/
        $obf5 = /eval\s*\(\s*gzuncompress/

    condition:
        2 of them
}

rule Suspicious_PHP_Variable_Functions
{
    meta:
        description = "Detects variable function calls (potential code execution)"
        author = "OpenGRC Security"
        severity = "medium"

    strings:
        $var_func = /\$[a-zA-Z_][a-zA-Z0-9_]*\s*\([^)]*\)/
        $get = "$_GET["
        $post = "$_POST["
        $request = "$_REQUEST["
        $cookie = "$_COOKIE["

    condition:
        $var_func and any of ($get, $post, $request, $cookie)
}

rule Malicious_File_Upload_Handler
{
    meta:
        description = "Detects suspicious file upload handlers"
        author = "OpenGRC Security"
        severity = "high"

    strings:
        $upload1 = "move_uploaded_file(" nocase
        $upload2 = "$_FILES" nocase
        $exec1 = "exec(" nocase
        $exec2 = "system(" nocase
        $exec3 = "passthru(" nocase
        $exec4 = "shell_exec(" nocase

        // Suspicious file extensions
        $ext1 = ".php" nocase
        $ext2 = ".phtml" nocase
        $ext3 = ".php5" nocase

    condition:
        ($upload1 and $upload2) and (any of ($exec*) or 2 of ($ext*))
}

rule Crypto_Miner_Indicators
{
    meta:
        description = "Detects cryptocurrency mining indicators"
        author = "OpenGRC Security"
        severity = "high"

    strings:
        $crypto1 = "coinhive" nocase
        $crypto2 = "cryptonight" nocase
        $crypto3 = "monero" nocase
        $crypto4 = "stratum+tcp://" nocase
        $crypto5 = "pool.minexmr" nocase
        $crypto6 = "CryptoNight" nocase
        $crypto7 = "miner.start" nocase

    condition:
        any of them
}

rule Reverse_Shell_Indicators
{
    meta:
        description = "Detects reverse shell code patterns"
        author = "OpenGRC Security"
        severity = "critical"

    strings:
        $rev1 = "fsockopen(" nocase
        $rev2 = "pfsockopen(" nocase
        $rev3 = "socket_create(" nocase
        $rev4 = "proc_open(" nocase
        $rev5 = "pcntl_exec(" nocase

        $shell1 = "/bin/sh" nocase
        $shell2 = "/bin/bash" nocase
        $shell3 = "cmd.exe" nocase

        $connect = "STDIN" nocase
        $connect2 = "STDOUT" nocase

    condition:
        any of ($rev*) and (any of ($shell*) or any of ($connect*))
}

rule Suspicious_Database_Operations
{
    meta:
        description = "Detects suspicious database operations"
        author = "OpenGRC Security"
        severity = "medium"

    strings:
        $sql1 = "DROP TABLE" nocase
        $sql2 = "TRUNCATE TABLE" nocase
        $sql3 = "DELETE FROM" nocase
        $sql4 = "mysql_query(" nocase
        $sql5 = "mysqli_query(" nocase

        $user_input1 = "$_GET[" nocase
        $user_input2 = "$_POST[" nocase
        $user_input3 = "$_REQUEST[" nocase

    condition:
        any of ($sql*) and any of ($user_input*)
}

rule Malicious_File_Inclusion
{
    meta:
        description = "Detects potential file inclusion vulnerabilities being exploited"
        author = "OpenGRC Security"
        severity = "high"

    strings:
        $include1 = "include(" nocase
        $include2 = "require(" nocase
        $include3 = "include_once(" nocase
        $include4 = "require_once(" nocase

        $user_input1 = "$_GET[" nocase
        $user_input2 = "$_POST[" nocase
        $user_input3 = "$_REQUEST[" nocase
        $user_input4 = "$_SERVER['HTTP_" nocase

        $remote1 = "http://" nocase
        $remote2 = "https://" nocase
        $remote3 = "ftp://" nocase
        $remote4 = "data://" nocase
        $remote5 = "php://" nocase

    condition:
        any of ($include*) and (any of ($user_input*) or any of ($remote*))
}
