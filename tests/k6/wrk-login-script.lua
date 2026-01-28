-- wrk script untuk test login endpoint
-- Usage: wrk -t4 -c100 -d30s -s tests/k6/wrk-login-script.lua https://levl-backend.site/api/v1/auth/login

local payload = '{"login":"superadmin.demo@test.com","password":"password"}'

function request()
    wrk.method = "POST"
    wrk.headers["Content-Type"] = "application/json"
    wrk.body = payload
    return wrk.format(nil)
end

function response(status, headers, body)
    if status == 200 then
        io.write("✓ ")
    else
        io.write("✗ ")
    end
end
