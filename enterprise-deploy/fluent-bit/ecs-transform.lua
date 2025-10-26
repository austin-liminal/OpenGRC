--[[
    ECS (Elastic Common Schema) Transformation Script for Fluent Bit

    This Lua script transforms Apache access log fields into ECS-compliant format.
    It maps parsed Apache log fields to their corresponding ECS field names.

    ECS Version: 8.11.0

    Field Mappings:
    - client_ip       -> source.ip, client.ip
    - response_code   -> http.response.status_code
    - http_method     -> http.request.method
    - http_version    -> http.version
    - url             -> url.path, url.original
    - referrer        -> http.request.referrer
    - user_agent      -> user_agent.original
    - bytes           -> http.response.body.bytes
    - client_geo      -> communication.source.geo (if available)
--]]

function transform_to_ecs(tag, timestamp, record)
    -- Map client_geo to communication.source.geo
    if record["client_geo"] ~= nil then
        record["communication"] = record["communication"] or {}
        record["communication"]["source"] = record["communication"]["source"] or {}
        record["communication"]["source"]["geo"] = record["client_geo"]
    end

    -- Map response_code to http.response.status_code
    if record["response_code"] ~= nil then
        record["http"] = record["http"] or {}
        record["http"]["response"] = record["http"]["response"] or {}
        record["http"]["response"]["status_code"] = tonumber(record["response_code"])
    end

    -- Map http_method to http.request.method
    if record["http_method"] ~= nil then
        record["http"] = record["http"] or {}
        record["http"]["request"] = record["http"]["request"] or {}
        record["http"]["request"]["method"] = record["http_method"]
    end

    -- Map http_version to http.version
    if record["http_version"] ~= nil then
        record["http"] = record["http"] or {}
        record["http"]["version"] = record["http_version"]
    end

    -- Map url to url.path and url.original
    if record["url"] ~= nil then
        local url_str = record["url"]
        record["url"] = {}
        record["url"]["path"] = url_str
        record["url"]["original"] = url_str
    end

    -- Map referrer to http.request.referrer
    if record["referrer"] ~= nil then
        record["http"] = record["http"] or {}
        record["http"]["request"] = record["http"]["request"] or {}
        record["http"]["request"]["referrer"] = record["referrer"]
    end

    -- Map user_agent to user_agent.original (preserving user_agent_parsed if it exists)
    if record["user_agent"] ~= nil then
        local ua_str = record["user_agent"]
        record["user_agent"] = {}
        record["user_agent"]["original"] = ua_str
    end

    -- Map client_ip to source.ip and client.ip
    if record["client_ip"] ~= nil then
        record["source"] = record["source"] or {}
        record["source"]["ip"] = record["client_ip"]
        record["source"]["address"] = record["client_ip"]
        record["client"] = record["client"] or {}
        record["client"]["ip"] = record["client_ip"]
        record["client"]["address"] = record["client_ip"]
    end

    -- Map bytes to http.response.body.bytes
    if record["bytes"] ~= nil then
        record["http"] = record["http"] or {}
        record["http"]["response"] = record["http"]["response"] or {}
        record["http"]["response"]["body"] = record["http"]["response"]["body"] or {}
        record["http"]["response"]["body"]["bytes"] = tonumber(record["bytes"])
    end

    -- Copy user_agent_parsed to user_agent if it exists
    if record["user_agent_parsed"] ~= nil then
        if record["user_agent"] == nil or type(record["user_agent"]) ~= "table" then
            record["user_agent"] = {}
        end
        for k, v in pairs(record["user_agent_parsed"]) do
            record["user_agent"][k] = v
        end
    end

    -- Return code 2 means: record modified, keep the same timestamp
    return 2, timestamp, record
end