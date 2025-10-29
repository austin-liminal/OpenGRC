rule Webshell_Generic_PHP
{
    meta:
        description = "Detects common PHP webshell patterns"
        author = "OpenGRC Security"
        date = "2025-01-27"
        severity = "high"

    strings:
        $php_exec1 = "exec(" nocase
        $php_exec2 = "shell_exec(" nocase
        $php_exec3 = "system(" nocase
        $php_exec4 = "passthru(" nocase
        $php_eval = "eval(" nocase
        $php_assert = "assert(" nocase
        $php_base64 = "base64_decode(" nocase
        $php_create_function = "create_function(" nocase

        $susp1 = "$_GET" nocase
        $susp2 = "$_POST" nocase
        $susp3 = "$_REQUEST" nocase
        $susp4 = "$_SERVER['HTTP_" nocase

    condition:
        any of ($php_exec*) and any of ($susp*)
        or ($php_eval and $php_base64 and any of ($susp*))
        or ($php_assert and $php_base64 and any of ($susp*))
        or ($php_create_function and any of ($susp*))
}

rule Webshell_C99_Family
{
    meta:
        description = "Detects C99 webshell variants"
        author = "OpenGRC Security"
        severity = "critical"

    strings:
        $c99_1 = "c99shell" nocase
        $c99_2 = "c99sh" nocase
        $c99_3 = "Safe-mode: OFF" nocase
        $c99_4 = "uname -a" nocase
        $c99_5 = "php.ini" nocase
        $c99_6 = "Safe Mode" nocase
        $func1 = "shell_exec"
        $func2 = "passthru"

    condition:
        2 of ($c99_*) or (any of ($c99_*) and all of ($func*))
}

rule Webshell_Generic_Suspicious
{
    meta:
        description = "Detects suspicious PHP file operations with user input"
        author = "OpenGRC Security"
        severity = "medium"

    strings:
        $file_op1 = "file_put_contents(" nocase
        $file_op2 = "fwrite(" nocase
        $file_op3 = "file_get_contents(" nocase
        $file_op4 = "move_uploaded_file(" nocase

        $user_input1 = "$_FILES" nocase
        $user_input2 = "$_GET" nocase
        $user_input3 = "$_POST" nocase

        $encode1 = "base64_decode(" nocase
        $encode2 = "gzinflate(" nocase
        $encode3 = "str_rot13(" nocase

    condition:
        any of ($file_op*) and any of ($user_input*) and any of ($encode*)
}

rule Webshell_WSO
{
    meta:
        description = "Detects WSO webshell variants"
        author = "OpenGRC Security"
        severity = "critical"

    strings:
        $wso1 = "WSO " nocase
        $wso2 = "Web Shell" nocase
        $wso3 = "wso_version" nocase
        $wso4 = "FilesMan" nocase
        $wso5 = "chmod(" nocase
        $wso6 = "mkdir(" nocase

    condition:
        3 of them
}

rule Backdoor_PHP_Function_Chain
{
    meta:
        description = "Detects chained dangerous PHP functions"
        author = "OpenGRC Security"
        severity = "high"

    strings:
        $chain1 = /eval\s*\(\s*base64_decode/
        $chain2 = /eval\s*\(\s*gzinflate/
        $chain3 = /eval\s*\(\s*str_rot13/
        $chain4 = /assert\s*\(\s*base64_decode/
        $chain5 = /system\s*\(\s*base64_decode/

    condition:
        any of them
}
