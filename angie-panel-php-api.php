<?php
class AngieApi
{
    /*
     * @var string Angie Panel 地址，不包含最后的 /
     */
    protected string $url;

    /*
     * @var string API Token，例如 ap_xxxxx
     */
    protected string $token;

    /*
     * @var int CURL 请求超时时间
     */
    protected int $timeout = 30;

    /*
     * 初始化 API 客户端
     *
     * @param string $url     Angie Panel 地址
     * @param string $token   Bearer Token
     * @param int    $timeout CURL 超时时间
     */
    public function __construct(string $url, string $token, int $timeout = 30)
    {
        $this->url = rtrim($url, '/');
        $this->token = $token;
        $this->timeout = $timeout;
    }

    /*
     * 通用 API 请求
     *
     * 所有 API 最终都通过这里发送。
     *
     * @param string $method HTTP 方法
     * @param string $path   API 路径，例如 hosts、certificates/1
     * @param array  $data   POST/PUT/DELETE 请求数据
     * @param array  $query  GET 查询参数
     *
     * @return mixed
     *
     * @throws RuntimeException CURL 或 HTTP 请求失败
     */
    public function request(string $method, string $path, array $data = [], array $query = []): mixed
    {
        $url = $this->url . '/api/' . ltrim($path, '/');
        // GET 参数拼接到 URL
        if ($query) {
            $url .= '?' . http_build_query($query);
        }
        $ch = curl_init($url);
        $options = [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_CUSTOMREQUEST => strtoupper($method),
            CURLOPT_TIMEOUT => $this->timeout,  // 连接和请求超时
            CURLOPT_CONNECTTIMEOUT => 10,
            CURLOPT_HTTPHEADER => ['Authorization: Bearer ' . $this->token, 'x-ap-request: 1', 'Accept: application/json']
        ];

        // GET / HEAD 不发送 JSON Body
        if ($data && !in_array(strtoupper($method), ['GET', 'HEAD'], true)) {
            $options[CURLOPT_POSTFIELDS] = json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
            $options[CURLOPT_HTTPHEADER][] = 'Content-Type: application/json';
        }
        curl_setopt_array($ch, $options);
        $response = curl_exec($ch);
        $errno = curl_errno($ch);
        $error = curl_error($ch);
        $status = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        // CURL 网络错误
        if ($errno) {
            throw new RuntimeException("Angie API CURL error: {$error}",$errno);
        }
        // 尝试解析 JSON
        $result = json_decode($response, true);

        // 如果返回的不是 JSON，则直接保留原始内容
        if (json_last_error() !== JSON_ERROR_NONE) {
            $result = $response;
        }
        // HTTP 非 2xx 统一抛异常
        if ($status < 200 || $status >= 300) {
            $message = is_array($result) ? ($result['message']?? $result['error'] ?? json_encode($result, JSON_UNESCAPED_UNICODE) ) : (string) $result;
            throw new RuntimeException( "Angie API {$status}: {$message}",  $status);
        }

        return $result;
    }

    /*
     * GET 请求
     */
    public function get(string $path, array $query = []): mixed
    {
        return $this->request('GET', $path, [], $query);
    }

    /*
     * POST 请求
     */
    public function post(string $path, array $data = []): mixed
    {
        return $this->request('POST', $path, $data);
    }

    /*
     * PUT 请求
     */
    public function put(string $path, array $data = []): mixed
    {
        return $this->request('PUT', $path, $data);
    }

    /*
     * DELETE 请求
     */
    public function delete(string $path, array $data = []): mixed
    {
        return $this->request('DELETE', $path, $data);
    }

    /*
     * 直接调用任意 API
     *
     * 用于 api.rs 后续增加新接口时，
     * 不需要马上修改这个类。
     */
    public function raw(string $method, string $path, array $data = [], array $query = []): mixed
    {
        return $this->request($method, $path, $data, $query);
    }

    /*
     * =========================================================
     * Auth
     * =========================================================
     */

    /*
     * 获取当前登录状态
     */
    public function authState(): mixed
    {
        return $this->get('auth/state');
    }

    /*
     * 获取当前用户
     */
    public function me(): mixed
    {
        return $this->get('auth/me');
    }

    /*
     * =========================================================
     * Users
     * =========================================================
     */

    /*
     * 获取用户列表
     */
    public function users(array $query = []): mixed
    {
        return $this->get('users', $query);
    }

    /*
     * 创建用户
     */
    public function createUser(array $data): mixed
    {
        return $this->post('users', $data);
    }

    /*
     * 删除用户
     */
    public function deleteUser(int|string $id): mixed
    {
        return $this->delete("users/{$id}");
    }

    /*
     * 修改当前用户密码
     */
    public function changePassword(array $data): mixed
    {
        return $this->post('users/me/password', $data);
    }

    /*
     * 修改用户角色
     */
    public function updateUserRole(int|string $id, array $data): mixed
    {
        return $this->put("users/{$id}/role", $data);
    }

    /*
     * =========================================================
     * Tokens
     * =========================================================
     */

    /*
     * 获取 API Token 列表
     */
    public function tokens(array $query = []): mixed
    {
        return $this->get('tokens', $query);
    }

    /*
     * 创建 API Token
     */
    public function createToken(array $data): mixed
    {
        return $this->post('tokens', $data);
    }

    /*
     * 删除 API Token
     */
    public function deleteToken(int|string $id): mixed
    {
        return $this->delete("tokens/{$id}");
    }

    /*
     * =========================================================
     * DNS Provider / Credentials
     * =========================================================
     */

    /*
     * 获取支持的 DNS Provider
     */
    public function dnsProviders(): mixed
    {
        return $this->get('dns-providers');
    }

    /*
     * 获取 DNS 凭据列表
     */
    public function dnsCredentials(array $query = []): mixed
    {
        return $this->get('dns-credentials', $query);
    }

    /*
     * 创建 DNS 凭据
     */
    public function createDnsCredential(array $data): mixed
    {
        return $this->post('dns-credentials', $data);
    }

    /*
     * 修改 DNS 凭据
     */
    public function updateDnsCredential(int|string $id,array $data): mixed {
        return $this->put("dns-credentials/{$id}", $data);
    }

    /*
     * 删除 DNS 凭据
     */
    public function deleteDnsCredential(int|string $id): mixed
    {
        return $this->delete("dns-credentials/{$id}");
    }

    /*
     * =========================================================
     * System
     * =========================================================
     */

    /*
     * 获取 Angie 系统状态
     */
    public function status(): mixed
    {
        return $this->get('system/status');
    }

    /*
     * 获取/检查 Angie 配置
     */
    public function configTest(): mixed
    {
        return $this->get('system/configtest');
    }

    /*
     * 执行配置测试
     */
    public function runConfigTest(array $data = []): mixed
    {
        return $this->post('system/configtest', $data);
    }

    /*
     * =========================================================
     * Hosts
     * =========================================================
     */

    /*
     * 获取站点列表
     *
     * $api->hosts(['page' => 1]);
     */
    public function hosts(array $query = []): mixed
    {
        return $this->get('hosts', $query);
    }

    /*
     * 获取单个站点
     */
    public function host(int|string $id): mixed
    {
        return $this->get("hosts/{$id}");
    }

    /*
     * 创建站点
     */
    public function createHost(array $data): mixed
    {
        return $this->post('hosts', $data);
    }

    /*
     * 修改站点
     */
    public function updateHost(int|string $id, array $data): mixed
    {
        return $this->put("hosts/{$id}", $data);
    }

    /*
     * 删除站点
     */
    public function deleteHost(int|string $id): mixed
    {
        return $this->delete("hosts/{$id}");
    }

    /*
     * 获取站点健康状态
     */
    public function hostHealth(int|string $id): mixed
    {
        return $this->get("hosts/{$id}/health");
    }

    /*
     * 启用站点
     */
    public function enableHost(int|string $id): mixed
    {
        return $this->post("hosts/{$id}/enable");
    }

    /*
     * 禁用站点
     */
    public function disableHost(int|string $id): mixed
    {
        return $this->post("hosts/{$id}/disable");
    }

    /*
     * =========================================================
     * Certificates
     * =========================================================
     */

    /*
     * 获取证书列表
     */
    public function certificates(array $query = []): mixed
    {
        return $this->get('certificates', $query);
    }

    /*
     * 获取单个证书
     */
    public function certificate(int|string $id): mixed
    {
        return $this->get("certificates/{$id}");
    }

    /*
     * 创建证书
     */
    public function createCertificate(array $data): mixed
    {
        return $this->post('certificates', $data);
    }

    /*
     * 修改证书
     */
    public function updateCertificate(int|string $id, array $data): mixed
    {
        return $this->put("certificates/{$id}", $data);
    }

    /*
     * 删除证书
     */
    public function deleteCertificate(int|string $id): mixed
    {
        return $this->delete("certificates/{$id}");
    }

    /*
     * 证书预检查
     */
    public function certificatePrecheck(int|string $id, array $data = []): mixed
    {
        return $this->post("certificates/{$id}/precheck", $data);
    }

    /*
     * =========================================================
     * Access Lists
     * =========================================================
     */

    /*
     * 获取访问控制列表
     */
    public function accessLists(array $query = []): mixed
    {
        return $this->get('access-lists', $query);
    }

    /*
     * 获取单个访问控制列表
     */
    public function accessList(int|string $id): mixed
    {
        return $this->get("access-lists/{$id}");
    }

    /*
     * 创建访问控制列表
     */
    public function createAccessList(array $data): mixed
    {
        return $this->post('access-lists', $data);
    }

    /*
     * 修改访问控制列表
     */
    public function updateAccessList(
        int|string $id,
        array $data
    ): mixed {
        return $this->put("access-lists/{$id}", $data);
    }

    /*
     * 删除访问控制列表
     */
    public function deleteAccessList(int|string $id): mixed
    {
        return $this->delete("access-lists/{$id}");
    }

    /*
     * =========================================================
     * Redirect Hosts
     * =========================================================
     */

    /*
     * 获取重定向站点
     */
    public function redirectHosts(array $query = []): mixed
    {
        return $this->get('redirect-hosts', $query);
    }

    /*
     * 获取单个重定向站点
     */
    public function redirectHost(int|string $id): mixed
    {
        return $this->get("redirect-hosts/{$id}");
    }

    /*
     * 创建重定向站点
     */
    public function createRedirectHost(array $data): mixed
    {
        return $this->post('redirect-hosts', $data);
    }

    /*
     * 修改重定向站点
     */
    public function updateRedirectHost(
        int|string $id,
        array $data
    ): mixed {
        return $this->put("redirect-hosts/{$id}", $data);
    }

    /*
     * 删除重定向站点
     */
    public function deleteRedirectHost(int|string $id): mixed
    {
        return $this->delete("redirect-hosts/{$id}");
    }

    /*
     * 启用重定向站点
     */
    public function enableRedirectHost(int|string $id): mixed
    {
        return $this->post("redirect-hosts/{$id}/enable");
    }

    /*
     * 禁用重定向站点
     */
    public function disableRedirectHost(int|string $id): mixed
    {
        return $this->post("redirect-hosts/{$id}/disable");
    }

    /*
     * =========================================================
     * Dead Hosts
     * =========================================================
     */

    /*
     * 获取 Dead Host 列表
     */
    public function deadHosts(array $query = []): mixed
    {
        return $this->get('dead-hosts', $query);
    }

    /*
     * 获取单个 Dead Host
     */
    public function deadHost(int|string $id): mixed
    {
        return $this->get("dead-hosts/{$id}");
    }

    /*
     * 创建 Dead Host
     */
    public function createDeadHost(array $data): mixed
    {
        return $this->post('dead-hosts', $data);
    }

    /*
     * 修改 Dead Host
     */
    public function updateDeadHost(
        int|string $id,
        array $data
    ): mixed {
        return $this->put("dead-hosts/{$id}", $data);
    }

    /*
     * 删除 Dead Host
     */
    public function deleteDeadHost(int|string $id): mixed
    {
        return $this->delete("dead-hosts/{$id}");
    }

    /*
     * 启用 Dead Host
     */
    public function enableDeadHost(int|string $id): mixed
    {
        return $this->post("dead-hosts/{$id}/enable");
    }

    /*
     * 禁用 Dead Host
     */
    public function disableDeadHost(int|string $id): mixed
    {
        return $this->post("dead-hosts/{$id}/disable");
    }

    /*
     * =========================================================
     * Streams
     * =========================================================
     */

    /*
     * 获取 Stream 列表
     */
    public function streams(array $query = []): mixed
    {
        return $this->get('streams', $query);
    }

    /*
     * 获取单个 Stream
     */
    public function stream(int|string $id): mixed
    {
        return $this->get("streams/{$id}");
    }

    /*
     * 创建 Stream
     */
    public function createStream(array $data): mixed
    {
        return $this->post('streams', $data);
    }

    /*
     * 修改 Stream
     */
    public function updateStream(
        int|string $id,
        array $data
    ): mixed {
        return $this->put("streams/{$id}", $data);
    }

    /*
     * 删除 Stream
     */
    public function deleteStream(int|string $id): mixed
    {
        return $this->delete("streams/{$id}");
    }

    /*
     * 启用 Stream
     */
    public function enableStream(int|string $id): mixed
    {
        return $this->post("streams/{$id}/enable");
    }

    /*
     * 禁用 Stream
     */
    public function disableStream(int|string $id): mixed
    {
        return $this->post("streams/{$id}/disable");
    }

    /*
     * 启用 Stream Context
     */
    public function enableStreamContext(array $data = []): mixed
    {
        return $this->post('streams/enable-context', $data);
    }

    /*
     * =========================================================
     * SNI Routers
     * =========================================================
     */

    /*
     * 获取 SNI Router 列表
     */
    public function sniRouters(array $query = []): mixed
    {
        return $this->get('sni-routers', $query);
    }

    /*
     * 获取单个 SNI Router
     */
    public function sniRouter(int|string $id): mixed
    {
        return $this->get("sni-routers/{$id}");
    }

    /*
     * 创建 SNI Router
     */
    public function createSniRouter(array $data): mixed
    {
        return $this->post('sni-routers', $data);
    }

    /*
     * 修改 SNI Router
     */
    public function updateSniRouter(
        int|string $id,
        array $data
    ): mixed {
        return $this->put("sni-routers/{$id}", $data);
    }

    /*
     * 删除 SNI Router
     */
    public function deleteSniRouter(int|string $id): mixed
    {
        return $this->delete("sni-routers/{$id}");
    }

    /*
     * 启用 SNI Router
     */
    public function enableSniRouter(int|string $id): mixed
    {
        return $this->post("sni-routers/{$id}/enable");
    }

    /*
     * 禁用 SNI Router
     */
    public function disableSniRouter(int|string $id): mixed
    {
        return $this->post("sni-routers/{$id}/disable");
    }

    /*
     * =========================================================
     * Bans
     * =========================================================
     */

    /*
     * 获取封禁列表
     */
    public function bans(array $query = []): mixed
    {
        return $this->get('bans', $query);
    }

    /*
     * 创建封禁
     */
    public function createBan(array $data): mixed
    {
        return $this->post('bans', $data);
    }

    /*
     * 删除封禁
     */
    public function deleteBan(int|string $id): mixed
    {
        return $this->delete("bans/{$id}");
    }

    /*
     * =========================================================
     * Geo
     * =========================================================
     */

    /*
     * 获取 Geo 配置
     */
    public function geo(): mixed
    {
        return $this->get('geo');
    }

    /*
     * 修改 Geo 配置
     */
    public function updateGeo(array $data): mixed
    {
        return $this->put('geo', $data);
    }

    /*
     * =========================================================
     * Audit
     * =========================================================
     */

    /*
     * 获取操作审计日志
     */
    public function audit(array $query = []): mixed
    {
        return $this->get('audit', $query);
    }

    /*
     * =========================================================
     * Apply
     * =========================================================
     */

    /*
     * 获取应用配置前的预览
     *
     * 可以在真正 apply 前检查配置变化。
     */
    public function applyPreview(): mixed
    {
        return $this->get('apply/preview');
    }

    /*
     * 应用配置
     */
    public function apply(): mixed
    {
        return $this->post('apply');
    }

    /*
     * 获取 Apply 历史
     */
    public function applyHistory(array $query = []): mixed
    {
        return $this->get('apply/history', $query);
    }

    /*
     * =========================================================
     * Settings
     * =========================================================
     */

    /*
     * 获取 Panel 设置
     */
    public function settings(): mixed
    {
        return $this->get('settings');
    }

    /*
     * 修改 Panel 设置
     */
    public function updateSettings(array $data): mixed
    {
        return $this->put('settings', $data);
    }

    /*
     * =========================================================
     * Dashboard
     * =========================================================
     */

    /*
     * 获取 Dashboard 数据
     */
    public function dashboard(array $query = []): mixed
    {
        return $this->get('dashboard', $query);
    }

    /*
     * =========================================================
     * Export / Import
     * =========================================================
     */

    /*
     * 导出 Panel 配置
     */
    public function export(array $query = []): mixed
    {
        return $this->get('export', $query);
    }

    /*
     * 导入 Panel 配置
     */
    public function import(array $data): mixed
    {
        return $this->post('import', $data);
    }
}
