# TLS-Extension-ALPN

[![PHP Version](https://img.shields.io/badge/php-%5E8.1-blue)](https://www.php.net)
[![License](https://img.shields.io/badge/license-MIT-green)](LICENSE)
[![Build Status](https://img.shields.io/badge/build-passing-brightgreen)](https://github.com/tourze/php-monorepo)
[![Coverage](https://img.shields.io/badge/coverage-100%25-brightgreen)](https://github.com/tourze/php-monorepo)

[English](README.md) | [中文](README.zh-CN.md)

基于 RFC 7301 的应用层协议协商（ALPN）TLS 扩展的完整实现。此包在 TLS 握手过程中启用安全协议协商，
支持 HTTP/2、HTTP/3、gRPC 等现代协议。

## 目录

- [特性](#特性)
- [安装](#安装)
- [快速开始](#快速开始)
- [基本用法](#基本用法)
  - [创建 ALPN 扩展](#创建-alpn-扩展)
  - [协议协商](#协议协商)
  - [协议管理](#协议管理)
  - [二进制编码/解码](#二进制编码解码)
- [配置](#配置)
- [高级用法](#高级用法)
  - [协议分析](#协议分析)
  - [批量协商](#批量协商)
  - [错误处理](#错误处理)
- [常用协议](#常用协议)
- [预定义协议集](#预定义协议集)
- [API 参考](#api-参考)
- [系统要求](#系统要求)
- [许可证](#许可证)

## 特性

- **完整的 ALPN 实现** - 完全符合 RFC 7301 的 ALPN 扩展
- **协议管理** - 添加、移除、验证协议，支持优先级处理
- **灵活的协商** - 支持服务器优先和客户端优先策略
- **协议集** - 常见用例的预定义协议集合
- **二进制编码/解码** - TLS 传输格式支持
- **协议分析** - 兼容性检查和配置验证
- **异常处理** - 详细错误报告和特定异常类型

## 安装

```bash
composer require tourze/tls-extension-alpn
```

## 快速开始

```php
use Tourze\TLSExtensionALPN\ALPNExtension;
use Tourze\TLSExtensionALPN\Protocol\CommonProtocols;

// 创建客户端扩展并设置首选协议
$clientExtension = ALPNExtension::forClient([
    CommonProtocols::HTTP_2,
    CommonProtocols::HTTP_1_1
]);

// 协商协议选择
$clientProtocols = ['h2', 'http/1.1'];
$serverProtocols = ['h2', 'http/1.0'];
$selectedProtocol = ALPNExtension::negotiate($clientProtocols, $serverProtocols);
echo $selectedProtocol; // 'h2'

// 使用协议管理器进行高级配置
$manager = \Tourze\TLSExtensionALPN\ALPNManager::createWebConfiguration();
$webExtension = $manager->createExtension('web');
```

## 基本用法

### 创建 ALPN 扩展

```php
use Tourze\TLSExtensionALPN\ALPNExtension;
use Tourze\TLSExtensionALPN\Protocol\CommonProtocols;

// 创建客户端 ALPN 扩展
$clientExtension = ALPNExtension::forClient([
    CommonProtocols::HTTP_2,
    CommonProtocols::HTTP_1_1
]);

// 创建服务器 ALPN 扩展（单个协议）
$serverExtension = ALPNExtension::forServer(CommonProtocols::HTTP_2);

// 手动配置
$extension = new ALPNExtension();
$extension->addProtocol(CommonProtocols::HTTP_2)
          ->addProtocol(CommonProtocols::HTTP_1_1)
          ->addProtocol(CommonProtocols::GRPC);
```

### 协议协商

```php
use Tourze\TLSExtensionALPN\ALPNExtension;
use Tourze\TLSExtensionALPN\ALPNNegotiator;

// 静态协商（服务器优先）
$clientProtocols = ['h2', 'http/1.1', 'grpc'];
$serverProtocols = ['grpc', 'h2', 'http/1.0'];

$selectedProtocol = ALPNExtension::negotiate($clientProtocols, $serverProtocols);
// 返回: 'grpc' (服务器最高优先级的共同协议)

// 使用协商器（客户端优先）
$negotiator = new ALPNNegotiator(ALPNNegotiator::STRATEGY_CLIENT_PREFERENCE);
$selectedProtocol = $negotiator->negotiate($clientProtocols, $serverProtocols);
// 返回: 'h2' (客户端最高优先级的共同协议)
```

### 协议管理

```php
use Tourze\TLSExtensionALPN\ALPNManager;

// 创建预配置的管理器
$webManager = ALPNManager::createWebConfiguration();
$apiManager = ALPNManager::createApiConfiguration();

// 使用预定义协议集
$extension = $webManager->createExtension('web');
// 包含: ['h2', 'http/1.1', 'http/1.0', 'h3', 'h2c', 'websocket']

// 注册自定义协议集
$manager = new ALPNManager();
$manager->registerProtocolSet('custom', [
    CommonProtocols::HTTP_3,
    CommonProtocols::HTTP_2,
    CommonProtocols::GRPC
]);

// 使用协议集名称进行协商
$selectedProtocol = $manager->negotiate('custom', 'web');
```

### 二进制编码/解码

```php
use Tourze\TLSExtensionALPN\ALPNExtension;

// 编码为二进制格式
$extension = new ALPNExtension(['h2', 'http/1.1']);
$binaryData = $extension->encode();

// 从二进制格式解码
$decodedExtension = ALPNExtension::decode($binaryData);
$protocols = $decodedExtension->getProtocols(); // ['h2', 'http/1.1']
```

## 配置

### 基本配置

```php
use Tourze\TLSExtensionALPN\ALPNExtension;
use Tourze\TLSExtensionALPN\Protocol\CommonProtocols;

// 使用自定义协议配置扩展
$extension = new ALPNExtension([
    CommonProtocols::HTTP_2,
    CommonProtocols::HTTP_1_1,
    CommonProtocols::GRPC
]);

// 配置协商策略
$negotiator = new ALPNNegotiator(ALPNNegotiator::STRATEGY_SERVER_PREFERENCE);
```

### 管理器配置

```php
use Tourze\TLSExtensionALPN\ALPNManager;

// 创建自定义管理器配置
$manager = new ALPNManager();

// 注册自定义协议集
$manager->registerProtocolSet('production', [
    CommonProtocols::HTTP_2,
    CommonProtocols::HTTP_1_1
]);

$manager->registerProtocolSet('development', [
    CommonProtocols::HTTP_2,
    CommonProtocols::HTTP_1_1,
    CommonProtocols::HTTP_1_0
]);

// 使用环境特定配置
$protocolSet = $_ENV['APP_ENV'] === 'production' ? 'production' : 'development';
$extension = $manager->createExtension($protocolSet);
```

### 协议验证配置

```php
// 配置协议验证规则
$manager->setValidationRules([
    'require_modern_protocols' => true,
    'allow_deprecated' => false,
    'min_protocol_count' => 2
]);

// 创建扩展前验证
$validationResult = $manager->validateConfiguration(['h2', 'http/1.1']);
if ($validationResult->isValid()) {
    $extension = new ALPNExtension($validationResult->getValidatedProtocols());
}
```

## 高级用法

### 协议分析

```php
use Tourze\TLSExtensionALPN\ALPNManager;

$manager = new ALPNManager();

// 验证配置
$validation = $manager->validateConfiguration([
    'spdy/3.1',  // 已废弃
    'h2',
    'custom-protocol'  // 未知
]);

// 分析协议
$analysis = $manager->analyzeProtocols(['h2', 'http/1.1', 'spdy/3']);
```

### 批量协商

```php
use Tourze\TLSExtensionALPN\ALPNNegotiator;

$negotiator = new ALPNNegotiator();
$results = $negotiator->negotiateBatch([
    ['client' => ['h2', 'http/1.1'], 'server' => ['http/1.1', 'h2']],
    ['client' => ['grpc'], 'server' => ['grpc', 'h2']],
]);
// 返回: ['http/1.1', 'grpc']
```

### 错误处理

```php
use Tourze\TLSExtensionALPN\ALPNExtension;
use Tourze\TLSExtensionALPN\Exception\ALPNException;

try {
    $extension = new ALPNExtension(['']);
} catch (ALPNException $e) {
    // "Protocol name cannot be empty"
}

try {
    $selected = ALPNExtension::negotiate(['h2'], ['http/1.1']);
} catch (ALPNException $e) {
    // "ALPN negotiation failed. Client protocols: [h2], Server protocols: [http/1.1]"
}
```

## 常用协议

包含预定义的常用协议常量：

```php
use Tourze\TLSExtensionALPN\Protocol\CommonProtocols;

CommonProtocols::HTTP_1_1     // 'http/1.1'
CommonProtocols::HTTP_2       // 'h2'
CommonProtocols::HTTP_3       // 'h3'
CommonProtocols::GRPC         // 'grpc'
CommonProtocols::WEBSOCKET    // 'websocket'
CommonProtocols::MQTT         // 'mqtt'
// 还有更多...
```

## 预定义协议集

- `web` - Web 协议（HTTP/1.1、HTTP/2、HTTP/3、WebSocket）
- `email` - 邮件协议（IMAP、POP3、SMTP）
- `modern` - 现代协议（HTTP/2、HTTP/3、gRPC、WebSocket）
- `all` - 所有已知协议

## API 参考

### ALPNExtension

- `forClient(array $protocols)` - 创建客户端扩展
- `forServer(string $protocol)` - 创建服务器扩展
- `addProtocol(string $protocol)` - 添加协议
- `removeProtocol(string $protocol)` - 移除协议
- `getProtocols()` - 获取协议列表
- `encode()` - 编码为二进制格式
- `decode(string $data)` - 从二进制格式解码
- `negotiate(array $client, array $server)` - 静态协商

### ALPNNegotiator

- `negotiate(array $client, array $server)` - 协商协议
- `negotiateBatch(array $pairs)` - 批量协商
- `checkCompatibility(array $protocols)` - 检查兼容性

### ALPNManager

- `createWebConfiguration()` - Web 优化的管理器
- `createApiConfiguration()` - API 优化的管理器
- `registerProtocolSet(string $name, array $protocols)` - 注册协议集
- `createExtension(string $setName)` - 从协议集创建扩展
- `validateConfiguration(array $protocols)` - 验证配置
- `analyzeProtocols(array $protocols)` - 分析协议

## 系统要求

- PHP 8.1+
- Symfony 6.4+

## 许可证

MIT 