--[[
    Laravel to ECS Transformation Script for Fluent Bit

    This script transforms Laravel log fields into ECS-compliant format.
    It parses the JSON context and maps fields to ECS schema.

    ECS Version: 8.11.0
--]]

function transform_laravel_to_ecs(tag, timestamp, record)
    -- Parse JSON context if it exists
    if record["context"] ~= nil and record["context"] ~= "" then
        local cjson = require("cjson")
        local success, context_data = pcall(cjson.decode, record["context"])

        if success and type(context_data) == "table" then
            -- Map user_id to user.id
            if context_data["user_id"] ~= nil then
                record["user"] = record["user"] or {}
                record["user"]["id"] = tostring(context_data["user_id"])
            end

            -- Map email to user.email
            if context_data["email"] ~= nil then
                record["user"] = record["user"] or {}
                record["user"]["email"] = context_data["email"]
            end

            -- Map ip to source.ip and client.ip
            if context_data["ip"] ~= nil then
                record["source"] = record["source"] or {}
                record["source"]["ip"] = context_data["ip"]
                record["source"]["address"] = context_data["ip"]
                record["client"] = record["client"] or {}
                record["client"]["ip"] = context_data["ip"]
                record["client"]["address"] = context_data["ip"]
            end

            -- Map forwarded_for to http.request.x_forwarded_for
            if context_data["forwarded_for"] ~= nil then
                record["http"] = record["http"] or {}
                record["http"]["request"] = record["http"]["request"] or {}
                record["http"]["request"]["x_forwarded_for"] = context_data["forwarded_for"]
            end

            -- Map host to url.domain
            if context_data["host"] ~= nil then
                record["url"] = record["url"] or {}
                record["url"]["domain"] = context_data["host"]
            end

            -- Map referer to http.request.referrer
            if context_data["referer"] ~= nil then
                record["http"] = record["http"] or {}
                record["http"]["request"] = record["http"]["request"] or {}
                record["http"]["request"]["referrer"] = context_data["referer"]
            end

            -- Map user_agent to user_agent.original
            if context_data["user_agent"] ~= nil then
                record["user_agent"] = record["user_agent"] or {}
                record["user_agent"]["original"] = context_data["user_agent"]
            end

            -- Map content-length to http.request.body.bytes
            if context_data["content-length"] ~= nil then
                local content_length = tonumber(context_data["content-length"])
                if content_length ~= nil then
                    record["http"] = record["http"] or {}
                    record["http"]["request"] = record["http"]["request"] or {}
                    record["http"]["request"]["body"] = record["http"]["request"]["body"] or {}
                    record["http"]["request"]["body"]["bytes"] = content_length
                end
            end

            -- Store any additional context fields in labels
            record["labels"] = record["labels"] or {}
            for k, v in pairs(context_data) do
                -- Skip fields we've already mapped
                if k ~= "user_id" and k ~= "email" and k ~= "ip" and
                   k ~= "forwarded_for" and k ~= "host" and k ~= "referer" and
                   k ~= "user_agent" and k ~= "content-length" then
                    record["labels"][k] = tostring(v)
                end
            end
        end
    end

    -- Map log level to log.level (ECS standard)
    if record["level"] ~= nil then
        record["log"] = record["log"] or {}
        record["log"]["level"] = string.lower(record["level"])
    end

    -- Map environment to service.environment
    if record["environment"] ~= nil then
        record["service"] = record["service"] or {}
        record["service"]["environment"] = record["environment"]
    end

    -- Trim message
    if record["message"] ~= nil then
        record["message"] = string.match(record["message"], "^%s*(.-)%s*$")
    end

    -- Remove original parsed fields to avoid duplicates
    record["context"] = nil
    record["level"] = nil
    record["environment"] = nil

    -- Return code 2 means: record modified, keep the same timestamp
    return 2, timestamp, record
end