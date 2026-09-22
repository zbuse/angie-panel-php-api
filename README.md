# angie-panel-php-api

Angie Panel PHP API 客户端。

[Angie](https://en.angie.software/) 是一个非常不错的 Web Server，可以作为 Nginx 的替代方案，除了兼容 Nginx 大量配置和使用方式之外，还提供了更多完善的功能模块。

Angie 本身支持：

- 内置 ACME
- 自动申请 SSL 证书
- 自动续期和维护证书
- DNS 验证
- 泛域名 SSL 证书
- 多种 DNS Provider
- 第三方 DNS Hook
- Lua
- njs
- 更多官方模块
- 更完善的动态配置能力

如果主要需求是大量域名、泛域名 SSL、DNS 验证和自动证书维护，Angie 的 ACME 能力非常方便。
作为穷屌丝上不起nginx和angie的PRO版本，用angie是很好的选择

---

# Angie NS 托管

Angie 可以配合 NS 委派，把大量域名的 `_acme-challenge` 统一交给一台 Angie DNS 服务处理。

这样不需要给每个域名单独配置 DNS API，也不需要每个域名单独设置 DNS Provider。

## 原理

例如准备一个一级域名：

```text
xxxx.net
```

然后使用：

```text
angiens.xxx.net
```

作为统一的 ACME DNS 验证 NS。

结构：

```text
                    ┌──────────────────┐
                    │   Angie Server   │
                    │                  │
                    │  DNS :53/UDP     │
                    │  ACME            │
                    │  SSL Certificate │
                    └────────┬─────────┘
                             │
                             │
                    angiens.xxx.net
                             │
                             ▼
              ┌──────────────────────────┐
              │ _acme-challenge.xxx.net │
              │                          │
              │ NS → angiens.xxx.net    │
              └──────────────────────────┘
```

---

## 1. 准备一级域名

例如：

```text
xxxx.net
```

---

## 2. 设置 A 记录

增加：

```text
angiens.xxx.net A <Angie服务器IP>
```

例如：

```text
angiens.xxx.net A 1.2.3.4
```

这里的 `1.2.3.4` 是 Angie 或 Angie Panel 所在服务器的公网 IP。

同时需要开放：

```text
53/UDP
```

如果 DNS 服务同时需要 TCP 查询，也建议开放：

```text
53/TCP
53/UDP
```

---

## 3. 设置 NS 委派

将：

```text
_acme-challenge.xxx.net
```

的 NS 指向：

```text
angiens.xxx.net
```

即：

```text
_acme-challenge.xxx.net NS angiens.xxx.net
```

这样 ACME 验证时：

```text
xxx.net
    │
    └── _acme-challenge.xxx.net
                │
                └── NS → angiens.xxx.net
                              │
                              ▼
                         Angie DNS
```

ACME DNS Challenge 的 TXT 记录就可以由 Angie 统一处理。

---

# 批量域名使用

上面的：

```text
angiens
```

并不是固定名称。

可以根据自己的需求定义，例如：

```text
dns.xxx.net
ns.xxx.net
acme.xxx.net
angiens.xxx.net
```

只要 A 记录最终指向提供 DNS 服务的 Angie 服务器即可。

例如有大量域名：

```text
aaa.com
bbb.com
ccc.net
ddd.org
eee.cn
```

都可以将各自的：

```text
_acme-challenge
```

NS 委派到统一的：

```text
angiens.xxx.net
```

这样就可以集中处理 ACME DNS 验证。

---

# 泛域名 SSL

例如需要申请：

```text
xxx.net
*.xxx.net
```

ACME 请求：

```text
_acme-challenge.xxx.net
```

由 NS：

```text
_acme-challenge.xxx.net
    ↓
angiens.xxx.net
```

统一处理。

Angie 可以自动维护证书，包括：

```text
申请
续期
更新
加载
```

对于大量站点尤其方便。

---

# Angie

Angie 是由 Angie's Web Server 项目提供的 Web Server。

官方网站：

[Angie 官方网站](https://en.angie.software/?utm_source=chatgpt.com)

ACME 官方文档：

[Angie ACME 文档](https://en.angie.software/angie/docs/configuration/acme/?utm_source=chatgpt.com)

---

# Angie Panel

Angie Panel 是 Angie 的第三方管理面板，可以通过 Web UI 管理 Angie。

项目地址：

[Angie Panel GitHub](https://github.com/maxname/angie-panel/?utm_source=chatgpt.com)

Angie Panel 提供 REST API，因此可以通过 PHP、Python、Shell 等程序进行自动化管理。

本项目就是一个简单的 PHP API 客户端。

---

# PHP API Client

本项目通过 Angie Panel REST API 管理：

- Hosts
- SSL Certificates
- DNS
- Redirect Hosts
- Dead Hosts
- Streams
- SNI Routers
- Access Lists
- Bans
- Geo
- Users
- Tokens
- Settings
- Apply
- Export / Import
- Audit

认证使用：

```http
Authorization: Bearer ap_xxxxxxxxx
x-ap-request: 1
```

---

# 使用

```php
<?php

use AngieApi;

// Angie Panel API 客户端
$api = new AngieApi('https://panel.example.com','ap_xxxxxxxxx');

// 获取 Hosts
print_r($api->hosts());

// 获取证书
print_r($api->certificates());

// 导出配置
print_r($api->export());
```

---

# 创建 ACME 证书

例如申请：

```text
xxx.net
*.xxx.net
```

使用 DNS Challenge：

```php
$data = [
    'name' => '',
    'domains' => ['xxx.net','*.xxx.net',],
    'challenge' => 'dns',
    'key_type' => 'ecdsa',
    'email' => null,
    'staging' => false,
    'dns_provider' => null,
];

print_r(
    $api->createCertificate($data)
);
```

其中：

```php
'challenge' => 'dns'
```

表示使用 DNS Challenge。

---

# 创建 Host

例如将：

```text
xxx.net
```

反向代理到：

```text
127.0.0.1:3000
```

可以：

```php
$result = $api->createHost(['domains' => ['xxx.net',],
    'forward_scheme' => 'http',
    'forward_host' => '127.0.0.1',
    'forward_port' => 3000,
    'websockets_upgrade' => true,
    'certificate_id' => 1,
]);

print_r($result);
```

`certificate_id` 使用 Angie Panel 中对应的证书 ID。

---

# 批量域名

如果需要管理大量域名，可以直接通过 PHP 批量调用 API。

例如：

```php
$domains = [
    'aaa.xxx.net',
    'bbb.xxx.net',
    'ccc.xxx.net',
];

foreach ($domains as $domain) {

    $api->createHost([
        'domains' => [
            $domain,
        ],
        'forward_scheme' => 'http',
        'forward_host' => '127.0.0.1',
        'forward_port' => 3000,
        'websockets_upgrade' => true,
    ]);
}
```

所有配置完成后，再统一：

```php
$api->apply();
```

而不是每增加一个域名就 Apply 一次。

---

# 批量证书

例如：

```php
$domains = [
    'aaa.xxx.net',
    'bbb.xxx.net',
    'ccc.xxx.net',
];

foreach ($domains as $domain) {

    $certificate = $api->createCertificate([
        'name' => '',
        'domains' => [
            $domain,
        ],
        'challenge' => 'dns',
        'key_type' => 'ecdsa',
        'email' => null,
        'staging' => false,
        'dns_provider' => null,
    ]);

    $certificateId = $certificate['id'] ?? null;

    $api->createHost([
        'domains' => [
            $domain,
        ],
        'forward_scheme' => 'http',
        'forward_host' => '127.0.0.1',
        'forward_port' => 3000,
        'websockets_upgrade' => true,
        'certificate_id' => $certificateId,
    ]);
}

$api->apply();
```

这样可以把：

```text
创建证书
    ↓
创建 Host
    ↓
继续下一个域名
    ↓
全部完成
    ↓
Apply
```

放在一次任务中完成。

---

# API 示例

## Hosts

```php
$api->hosts();

$api->host($id);

$api->createHost($data);

$api->updateHost($id, $data);

$api->deleteHost($id);

$api->enableHost($id);

$api->disableHost($id);
```

## Certificates

```php
$api->certificates();

$api->certificate($id);

$api->createCertificate($data);

$api->updateCertificate($id, $data);

$api->deleteCertificate($id);
```

## DNS

```php
$api->dnsProviders();

$api->dnsCredentials();

$api->createDnsCredential($data);

$api->updateDnsCredential($id, $data);

$api->deleteDnsCredential($id);
```

## Redirect Hosts

```php
$api->redirectHosts();

$api->redirectHost($id);

$api->createRedirectHost($data);

$api->updateRedirectHost($id, $data);

$api->deleteRedirectHost($id);

$api->enableRedirectHost($id);

$api->disableRedirectHost($id);
```

## Dead Hosts

```php
$api->deadHosts();

$api->deadHost($id);

$api->createDeadHost($data);

$api->updateDeadHost($id, $data);

$api->deleteDeadHost($id);

$api->enableDeadHost($id);

$api->disableDeadHost($id);
```

## Streams

```php
$api->streams();

$api->stream($id);

$api->createStream($data);

$api->updateStream($id, $data);

$api->deleteStream($id);

$api->enableStream($id);

$api->disableStream($id);
```

## SNI Routers

```php
$api->sniRouters();

$api->sniRouter($id);

$api->createSniRouter($data);

$api->updateSniRouter($id, $data);

$api->deleteSniRouter($id);

$api->enableSniRouter($id);

$api->disableSniRouter($id);
```

## Access Lists

```php
$api->accessLists();

$api->accessList($id);

$api->createAccessList($data);

$api->updateAccessList($id, $data);

$api->deleteAccessList($id);
```

## Bans

```php
$api->bans();

$api->createBan($data);

$api->deleteBan($id);
```

## Geo

```php
$api->geo();

$api->updateGeo($data);
```

## Users

```php
$api->users();

$api->createUser($data);

$api->deleteUser($id);

$api->changePassword($id, $data);

$api->updateUserRole($id, $data);
```

## Tokens

```php
$api->tokens();

$api->createToken($data);

$api->deleteToken($id);
```

## System

```php
$api->status();
```

## Apply

```php
$api->applyPreview();

$api->apply();

$api->applyHistory();
```

## Settings

```php
$api->settings();

$api->updateSettings($data);
```

## Dashboard

```php
$api->dashboard();
```

## Audit

```php
$api->audit();
```

## Export / Import

```php
$api->export();

$api->import($data);
```

---

# 自定义 API

如果 Angie Panel 后续增加了新的 API，而 PHP 客户端暂时没有封装，可以直接使用 `raw()`。

GET：

```php
$result = $api->raw('GET','/api/example');
```

POST：

```php
$result = $api->raw('POST','/api/example',['name' => 'example']);
```

PUT：

```php
$result = $api->raw('PUT','/api/example/1',['name' => 'example',]
);
```

DELETE：

```php
$result = $api->raw('DELETE','/api/example/1');
```

---


# 设计目标

Angie Panel本身提供了apctl管理接口，但只有proxy host管理为主，作为一个菜鸡学习rust成本太高，用php能快速上手，然后看了下源码测试能直接用Bearer方式跳过apctl进行管理.
api中涉及到的具体参数没有详细列出，需要安装好angie panel后在浏览器调试里面查看具体需要的参数。


```text
┌──────────────────────┐
│      PHP 项目        │
│                      │
│ 域名管理             │
│ SSL 管理              │
│ 批量任务             │
│ 自动化部署            │
└──────────┬───────────┘
           │
           │ REST API
           │ Bearer Token
           ▼
┌──────────────────────┐
│     Angie Panel      │
│                      │
│ Hosts                │
│ Certificates         │
│ DNS                  │
│ ACME                 │
│ Apply                │
└──────────┬───────────┘
           │
           ▼
┌──────────────────────┐
│        Angie         │
│                      │
│ Web Server           │
│ ACME                  │
│ SSL                   │
│ DNS                   │
│ Lua / njs             │
└──────────────────────┘
```

这样可以将业务系统和 Web Server 配置管理分离。

例如多域名批量管理，自动创建：

```text
域名
 ↓
SSL
 ↓
Host
 ↓
反向代理
 ↓
Apply
```

---

# 为什么使用 Angie

如果你的需求只是一个传统 Web Server，Nginx 已经足够成熟。

但如果需要：

- 大量域名
- ACME 自动签证书
- 泛域名证书
- DNS Challenge
- 自动续期
- Lua / njs
- 更多模块
- 自动化配置
- API 管理
- DNS NS 委派
- 大量站点自动管理

Angie 提供了比较完整的一套能力。

特别是 ACME + DNS Challenge + NS 委派，可以把大量域名的证书验证集中管理。

---

# 注意事项

## DNS

使用 NS 委派时，需要确认：

```text
53/UDP
```

能够从公网访问。

部分 DNS 查询也可能使用：

```text
53/TCP
```

因此生产环境建议同时开放：

```text
53/UDP
53/TCP
```

---

## DNS 传播

修改 NS 后不要立即认为已经生效。

可以使用：

```bash
dig NS _acme-challenge.xxx.net
```

检查：

```text
_acme-challenge.xxx.net
```

是否已经正确返回：

```text
angiens.xxx.net
```

再检查：

```bash
dig A angiens.xxx.net
```

确认已经指向 Angie DNS 服务器。

---

# 安全建议

Angie DNS 服务直接开放公网时，应注意：

- 只开放必要的 DNS 服务
- 正确配置 DNS Zone
- 避免开放不需要的管理接口
- Angie Panel 管理接口不要直接暴露给所有公网来源
- 生产环境建议使用 HTTPS
- 定期轮换 API Token
- 本项目为自用为主，不保证有bug，如果有更好的方法记得PR一份给我

---

# License

本项目是 Angie Panel 的 PHP API 客户端。

Angie、Angie Panel 以及相关组件的版权和许可证归各自项目所有。

本项目不修改 Angie 或 Angie Panel 本身。
