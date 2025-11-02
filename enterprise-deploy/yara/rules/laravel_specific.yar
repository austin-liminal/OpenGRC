rule Suspicious_Laravel_Route_Override
{
    meta:
        description = "Detects suspicious Laravel route modifications"
        author = "OpenGRC Security"
        severity = "high"

    strings:
        $route1 = "Route::any(" nocase
        $route2 = "Route::match(" nocase
        $exec1 = "exec(" nocase
        $exec2 = "system(" nocase
        $exec3 = "eval(" nocase
        $shell = "shell_exec(" nocase

    condition:
        any of ($route*) and any of ($exec*, $shell)
}

rule Suspicious_Laravel_Middleware_Bypass
{
    meta:
        description = "Detects potential Laravel middleware bypass attempts"
        author = "OpenGRC Security"
        severity = "medium"

    strings:
        $middleware1 = "withoutMiddleware(" nocase
        $middleware2 = "->middleware([" nocase
        $auth = "auth" nocase
        $throttle = "throttle" nocase

    condition:
        $middleware1 or ($middleware2 and not $auth and not $throttle)
}

rule Suspicious_Laravel_Artisan_Command
{
    meta:
        description = "Detects suspicious Artisan command creation"
        author = "OpenGRC Security"
        severity = "high"

    strings:
        $artisan1 = "Artisan::call(" nocase
        $artisan2 = "extends Command" nocase
        $exec1 = "exec(" nocase
        $exec2 = "system(" nocase
        $exec3 = "shell_exec(" nocase
        $file_op = "file_put_contents(" nocase

    condition:
        any of ($artisan*) and any of ($exec*, $file_op)
}

rule Malicious_Laravel_Blade_Template
{
    meta:
        description = "Detects malicious code in Laravel Blade templates"
        author = "OpenGRC Security"
        severity = "high"

    strings:
        $blade1 = "@php" nocase
        $blade2 = "<?php"
        $exec1 = "exec(" nocase
        $exec2 = "system(" nocase
        $exec3 = "shell_exec(" nocase
        $eval = "eval(" nocase
        $base64 = "base64_decode(" nocase

    condition:
        any of ($blade*) and (any of ($exec*) or ($eval and $base64))
}

rule Suspicious_Laravel_Config_Override
{
    meta:
        description = "Detects suspicious Laravel config modifications"
        author = "OpenGRC Security"
        severity = "medium"

    strings:
        $config1 = "config(['app.debug' => true" nocase
        $config2 = "config()->set(" nocase
        $config3 = "Config::set(" nocase
        $debug = "'debug' => true" nocase
        $env = "app()->environment(" nocase

    condition:
        any of ($config*) and ($debug or $env)
}

rule Suspicious_Laravel_Database_Raw
{
    meta:
        description = "Detects potentially dangerous Laravel DB::raw usage"
        author = "OpenGRC Security"
        severity = "medium"

    strings:
        $db_raw = "DB::raw(" nocase
        $user_input1 = "$request->" nocase
        $user_input2 = "$_GET[" nocase
        $user_input3 = "$_POST[" nocase
        $concat = "." nocase

    condition:
        $db_raw and any of ($user_input*) and $concat
}

rule Malicious_Laravel_Service_Provider
{
    meta:
        description = "Detects suspicious Laravel service provider modifications"
        author = "OpenGRC Security"
        severity = "high"

    strings:
        $provider1 = "extends ServiceProvider" nocase
        $provider2 = "public function boot(" nocase
        $exec1 = "exec(" nocase
        $exec2 = "system(" nocase
        $exec3 = "shell_exec(" nocase
        $file_get = "file_get_contents('http" nocase
        $eval = "eval(" nocase

    condition:
        any of ($provider*) and (any of ($exec*) or $file_get or $eval)
}
